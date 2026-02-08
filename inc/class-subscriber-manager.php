<?php
/**
 * Subscriber Manager - Handles Mailster API interactions.
 *
 * @package WP_Ultimo_Mailster
 * @since 1.0.0
 */

namespace WP_Ultimo\Mailster;

// Exit if accessed directly.
if (! defined('ABSPATH')) {
	exit;
}

/**
 * Subscriber Manager class.
 *
 * Wrapper for Mailster API calls with error handling.
 */
class Subscriber_Manager {

	/**
	 * Single instance of the class.
	 *
	 * @var Subscriber_Manager
	 */
	protected static $instance = null;

	/**
	 * Main instance.
	 *
	 * @return Subscriber_Manager
	 */
	public static function get_instance() {

		if (null === self::$instance) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Add a subscriber to Mailster.
	 *
	 * @param array $data Subscriber data (email, firstname, lastname, etc.).
	 * @param array $lists Array of list IDs to assign subscriber to.
	 * @param bool  $double_optin Whether to require email confirmation.
	 * @return int|\WP_Error Subscriber ID on success, WP_Error on failure.
	 */
	public function add_subscriber(array $data, array $lists, bool $double_optin) {

		// Switch to main site where Mailster is active
		$main_site_id = get_main_site_id();
		$switched     = false;

		if (get_current_blog_id() !== $main_site_id) {
			switch_to_blog($main_site_id);
			$switched = true;
		}

		if (! function_exists('mailster')) {
			if ($switched) {
				restore_current_blog();
			}

			wu_log_add('mailster', 'Mailster plugin is not active on main site');

			return new \WP_Error('mailster_inactive', __('Mailster plugin is not active on the main site.', 'ultimate-multisite-mailster'));
		}

		// Validate email
		if (empty($data['email'])) {
			wu_log_add('mailster', 'Cannot add subscriber: email is empty');

			return new \WP_Error('mailster_empty_email', __('Email address is required.', 'ultimate-multisite-mailster'));
		}

		// Check if subscriber exists
		$existing = mailster('subscribers')->get_by_mail($data['email']);

		if ($existing && ! wu_get_setting('mailster_update_existing', true)) {
			wu_log_add('mailster', sprintf('Subscriber %s already exists, skipping', $data['email']));

			// Still assign to new lists
			$this->assign_to_lists($existing->ID, $lists, $double_optin);

			return $existing->ID;
		}

		// Set subscriber status (1 = subscribed, 0 = pending double opt-in)
		$data['status'] = $double_optin ? 0 : 1;

		try {
			if ($existing) {

				// Update existing subscriber
				$result = mailster('subscribers')->update($existing->ID, $data, true);

				if (is_wp_error($result)) {
					wu_log_add(
						'mailster',
						sprintf(
							'Failed to update subscriber %s: %s',
							$data['email'],
							$result->get_error_message()
						)
					);

					return $result;
				}

				$subscriber_id = $existing->ID;

				wu_log_add('mailster', sprintf('Updated subscriber %s (ID: %d)', $data['email'], $subscriber_id));
			} else {

				// Add new subscriber
				$subscriber_id = mailster('subscribers')->add($data, true);

				if (is_wp_error($subscriber_id)) {
					wu_log_add(
						'mailster',
						sprintf(
							'Failed to add subscriber %s: %s',
							$data['email'],
							$subscriber_id->get_error_message()
						)
					);

					return $subscriber_id;
				}

				wu_log_add('mailster', sprintf('Added subscriber %s (ID: %d)', $data['email'], $subscriber_id));
			}

			// Assign to lists
			if (! empty($lists)) {
				$list_result = $this->assign_to_lists($subscriber_id, $lists, $double_optin);

				if (is_wp_error($list_result)) {
					wu_log_add(
						'mailster',
						sprintf(
							'Failed to assign subscriber %d to lists: %s',
							$subscriber_id,
							$list_result->get_error_message()
						)
					);
				}
			}

			return $subscriber_id;
		} catch (\Exception $e) {
			wu_log_add(
				'mailster',
				sprintf(
					'Exception adding subscriber %s: %s',
					$data['email'],
					$e->getMessage()
				)
			);

			return new \WP_Error('mailster_exception', $e->getMessage());
		} finally {
			// Always restore blog if we switched
			if ($switched) {
				restore_current_blog();
			}
		}
	}

	/**
	 * Assign subscriber to lists.
	 *
	 * @param int   $subscriber_id Subscriber ID.
	 * @param array $lists Array of list IDs.
	 * @param bool  $double_optin Whether double opt-in is enabled. When false, list assignments
	 *                            are immediately confirmed. When true, assignments are pending
	 *                            until the subscriber confirms via email.
	 * @return bool|\WP_Error True on success, WP_Error on failure.
	 */
	public function assign_to_lists(int $subscriber_id, array $lists, bool $double_optin = false) {

		// Switch to main site where Mailster is active
		$main_site_id = get_main_site_id();
		$switched     = false;

		if (get_current_blog_id() !== $main_site_id) {
			switch_to_blog($main_site_id);
			$switched = true;
		}

		if (! function_exists('mailster')) {
			if ($switched) {
				restore_current_blog();
			}

			return new \WP_Error('mailster_inactive', __('Mailster plugin is not active on the main site.', 'ultimate-multisite-mailster'));
		}

		if (empty($lists)) {
			return true;
		}

		// Validate list IDs
		$valid_lists = $this->validate_lists($lists);

		if (empty($valid_lists)) {
			wu_log_add('mailster', sprintf('No valid lists found in: %s', implode(', ', $lists)));

			return true;
		}

		try {
			// When double opt-in is disabled, pass $added=true to immediately confirm
			// the list assignment. Otherwise pass $added=false so Mailster sends a
			// confirmation email. This prevents Mailster's list_based_opt_in from
			// overriding our addon's double opt-in setting.
			$added  = ! $double_optin;
			$result = mailster('subscribers')->assign_lists($subscriber_id, $valid_lists, false, $added);

			if (is_wp_error($result)) {
				wu_log_add(
					'mailster',
					sprintf(
						'Failed to assign subscriber %d to lists %s: %s',
						$subscriber_id,
						implode(', ', $valid_lists),
						$result->get_error_message()
					)
				);

				return $result;
			}

			wu_log_add(
				'mailster',
				sprintf(
					'Assigned subscriber %d to lists: %s',
					$subscriber_id,
					implode(', ', $valid_lists)
				)
			);

			return true;
		} catch (\Exception $e) {
			wu_log_add(
				'mailster',
				sprintf(
					'Exception assigning subscriber %d to lists: %s',
					$subscriber_id,
					$e->getMessage()
				)
			);

			return new \WP_Error('mailster_exception', $e->getMessage());
		} finally {
			// Always restore blog if we switched
			if ($switched) {
				restore_current_blog();
			}
		}
	}

	/**
	 * Get available Mailster lists.
	 *
	 * @return array Array of lists with ID and name.
	 */
	public function get_available_lists(): array {

		// Switch to main site where Mailster is active
		$main_site_id = get_main_site_id();
		$switched     = false;

		if (get_current_blog_id() !== $main_site_id) {
			switch_to_blog($main_site_id);
			$switched = true;
		}

		if (! function_exists('mailster')) {
			if ($switched) {
				restore_current_blog();
			}

			return [];
		}

		try {
			$lists = mailster('lists')->get();

			if (empty($lists) || is_wp_error($lists)) {
				if ($switched) {
					restore_current_blog();
				}

				return [];
			}

			$formatted = [];

			foreach ($lists as $list) {
				$formatted[ $list->ID ] = $list->name;
			}

			return $formatted;
		} catch (\Exception $e) {
			wu_log_add('mailster', sprintf('Exception getting lists: %s', $e->getMessage()));

			return [];
		} finally {
			// Always restore blog if we switched
			if ($switched) {
				restore_current_blog();
			}
		}
	}

	/**
	 * Map customer fields to Mailster subscriber data.
	 *
	 * @param \WP_Ultimo\Models\Customer $customer Customer object.
	 * @return array Mapped subscriber data.
	 */
	public function map_customer_fields(\WP_Ultimo\Models\Customer $customer): array {

		$data = [
			'email' => $customer->get_email_address(),
		];

		// Only map fields if setting is enabled
		if (! wu_get_setting('mailster_map_fields', true)) {
			return $data;
		}
		$user = $customer->get_user();

		// Map name fields
		if ($user->first_name) {
			$data['firstname'] = $user->first_name;
		}

		if ($user->last_name) {
			$data['lastname'] = $user->last_name;
		}

		// Map billing address if available
		$billing_address = $customer->get_billing_address();

		if ($billing_address) {
			if ($billing_address->billing_country) {
				$data['country'] = $billing_address->billing_country;
			}

			if ($billing_address->billing_state) {
				$data['state'] = $billing_address->billing_state;
			}

			if ($billing_address->billing_city) {
				$data['city'] = $billing_address->billing_city;
			}

			if ($billing_address->billing_zip_code) {
				$data['zip'] = $billing_address->billing_zip_code;
			}
		}

		// Add signup date
		if ($customer->get_date_created()) {
			$data['signup_date'] = $customer->get_date_created();
		}

		return $data;
	}

	/**
	 * Validate list IDs against available lists.
	 *
	 * @param array $list_ids Array of list IDs to validate.
	 * @return array Array of valid list IDs.
	 */
	private function validate_lists(array $list_ids): array {

		$available_lists = $this->get_available_lists();

		if (empty($available_lists)) {
			return [];
		}

		$available_ids = array_keys($available_lists);

		return array_filter(
			$list_ids,
			function ($id) use ($available_ids) {

				return in_array((int) $id, $available_ids, true);
			}
		);
	}
}
