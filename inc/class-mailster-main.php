<?php
/**
 * Main functionality class for Mailster Integration addon.
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
 * Main functionality class for Mailster Integration.
 */
class Mailster_Main {

	/**
	 * Single instance of the class.
	 *
	 * @var Mailster_Main
	 */
	protected static $instance = null;

	/**
	 * Main instance.
	 *
	 * @return Mailster_Main
	 */
	public static function get_instance() {

		if (null === self::$instance) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {

		// Check dependencies before initialization
		if (! $this->check_dependencies()) {
			return;
		}

		$this->init_components();
		$this->init_hooks();
	}

	/**
	 * Check if required dependencies are active.
	 *
	 * @return bool True if dependencies are met.
	 */
	private function check_dependencies(): bool {

		$dependencies_met = true;

		// Check Mailster (only needs to be active on main site, not network-wide)
		$mailster_active = false;

		// Switch to main site to check if Mailster is active
		$main_site_id = get_main_site_id();
		switch_to_blog($main_site_id);

		if (function_exists('mailster')) {
			$mailster_active = true;
		}

		restore_current_blog();

		if (! $mailster_active) {
			add_action(
				'network_admin_notices',
				function () {

					echo '<div class="notice notice-error"><p>';
					printf(
						/* translators: %s: Main site URL */
						esc_html__('Ultimate Multisite: Mailster Integration requires Mailster to be installed and active on the main site (%s).', 'ultimate-multisite-mailster'),
						'<a href="' . esc_url(get_admin_url(get_main_site_id(), 'plugins.php')) . '">' . esc_html__('Manage Plugins', 'ultimate-multisite-mailster') . '</a>'
					);
					echo '</p></div>';
				}
			);

			$dependencies_met = false;
		}

		return $dependencies_met;
	}

	/**
	 * Initialize addon components.
	 */
	private function init_components(): void {

		// Initialize Settings Manager
		Settings_Manager::get_instance();

		// Initialize Product Integration
		Product_Integration::get_instance();

		// Subscriber Manager is initialized on-demand
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks(): void {

		// Register checkout field
		add_filter('wu_checkout_field_types', [$this, 'register_checkout_field']);

		// Customer creation hook (for immediate timing)
		// Note: wu_customer_post_save fires on both create and update, with $new parameter indicating if new
		add_action('wu_customer_post_save', [$this, 'handle_customer_creation'], 10, 3);

		// Payment status change hook (for payment complete timing)
		// Note: wu_transition_payment_status fires when payment status changes
		add_action('wu_transition_payment_status', [$this, 'handle_payment_status_change'], 10, 3);
	}

	/**
	 * Register Mailster opt-in checkout field.
	 *
	 * @param array $fields Existing checkout field types.
	 * @return array Modified field types.
	 */
	public function register_checkout_field(array $fields): array {

		$fields['mailster_optin'] = \WP_Ultimo\Mailster\Checkout\Mailster_Optin_Field::class;

		return $fields;
	}

	/**
	 * Handle customer creation event.
	 *
	 * @param array                      $data     Customer data array.
	 * @param \WP_Ultimo\Models\Customer $customer Customer object.
	 * @param bool                       $is_new   True if customer is new, false if being updated.
	 */
	public function handle_customer_creation(array $data, $customer, bool $is_new): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter

		// Only process for new customers (not updates)
		if (! $is_new) {
			return;
		}

		// Only process if timing is set to order_creation
		if (wu_get_setting('mailster_subscription_timing', 'order_creation') !== 'order_creation') {
			return;
		}

		$this->subscribe_customer($customer);
	}

	/**
	 * Handle payment status change event.
	 *
	 * @param string $old_status Previous payment status.
	 * @param string $new_status New payment status.
	 * @param int    $payment_id Payment ID.
	 */
	public function handle_payment_status_change(string $old_status, string $new_status, int $payment_id): void {

		// Only process if timing is set to payment_complete
		if (wu_get_setting('mailster_subscription_timing', 'order_creation') !== 'payment_complete') {
			return;
		}

		// Only process when payment becomes completed
		if ('completed' !== $new_status) {
			return;
		}

		// Get payment object
		$payment = wu_get_payment($payment_id);

		if (! $payment) {
			wu_log_add('mailster', sprintf('Payment %d not found', $payment_id));

			return;
		}

		// Get customer from payment
		$customer = $payment->get_customer();

		if (! $customer) {
			wu_log_add('mailster', sprintf('Payment %d has no associated customer', $payment_id));

			return;
		}

		$this->subscribe_customer($customer, $payment);
	}

	/**
	 * Subscribe customer to Mailster lists.
	 *
	 * @param \WP_Ultimo\Models\Customer     $customer Customer object.
	 * @param \WP_Ultimo\Models\Payment|null $payment Optional payment object.
	 */
	private function subscribe_customer($customer, $payment = null): void {

		// Check opt-in requirements
		if (! $this->customer_opted_in($customer)) {
			wu_log_add(
				'mailster',
				sprintf(
					'Customer %d (%s) has not opted in to Mailster',
					$customer->get_id(),
					$customer->get_email_address()
				)
			);

			return;
		}

		// Get lists to subscribe to
		$lists = $this->get_lists_for_customer($customer, $payment);

		if (empty($lists)) {
			wu_log_add(
				'mailster',
				sprintf(
					'No lists configured for customer %d (%s)',
					$customer->get_id(),
					$customer->get_email_address()
				)
			);

			return;
		}

		// Get subscriber manager
		$subscriber_manager = Subscriber_Manager::get_instance();

		// Map customer fields
		$subscriber_data = $subscriber_manager->map_customer_fields($customer);

		// Get double opt-in setting
		$double_optin = wu_get_setting('mailster_double_optin', false);

		// Add subscriber
		$result = $subscriber_manager->add_subscriber($subscriber_data, $lists, $double_optin);

		if (is_wp_error($result)) {
			wu_log_add(
				'mailster',
				sprintf(
					'Failed to subscribe customer %d (%s): %s',
					$customer->get_id(),
					$customer->get_email_address(),
					$result->get_error_message()
				)
			);
		} else {
			wu_log_add(
				'mailster',
				sprintf(
					'Successfully subscribed customer %d (%s) to lists: %s',
					$customer->get_id(),
					$customer->get_email_address(),
					implode(', ', $lists)
				)
			);
		}
	}

	/**
	 * Check if customer has opted in to Mailster.
	 *
	 * @param \WP_Ultimo\Models\Customer $customer Customer object.
	 * @return bool True if customer opted in or opt-in not required.
	 */
	private function customer_opted_in($customer): bool {

		$optin_mode = wu_get_setting('mailster_optin_mode', 'automatic');

		// If automatic mode, always return true
		if ('automatic' === $optin_mode) {
			return true;
		}

		// Check customer meta for opt-in
		return (bool) $customer->get_meta('mailster_opted_in', false);
	}

	/**
	 * Get lists for customer based on product and global settings.
	 *
	 * @param \WP_Ultimo\Models\Customer     $customer Customer object.
	 * @param \WP_Ultimo\Models\Payment|null $payment Optional payment object.
	 * @return array Array of list IDs.
	 */
	private function get_lists_for_customer($customer, $payment = null): array {

		$lists = [];

		// Get product from payment or membership
		$product = $this->get_product_from_customer($customer, $payment);

		if ($product) {
			$product_integration = Product_Integration::get_instance();
			$product_lists       = $product_integration->get_product_lists($product->get_id());

			// Check if product has Mailster enabled and lists configured
			if (! empty($product_lists)) {
				$lists = $product_lists;

				wu_log_add(
					'mailster',
					sprintf(
						'Using product-specific lists for customer %d: %s',
						$customer->get_id(),
						implode(', ', $lists)
					)
				);
			}
		}

		// Fall back to global default lists if no product lists
		if (empty($lists)) {
			$default_lists = wu_get_setting('mailster_default_lists', []);

			if (! empty($default_lists) && is_array($default_lists)) {
				$lists = $default_lists;

				wu_log_add(
					'mailster',
					sprintf(
						'Using default lists for customer %d: %s',
						$customer->get_id(),
						implode(', ', $lists)
					)
				);
			}
		}

		return array_filter(array_map('intval', $lists));
	}

	/**
	 * Get product from customer's membership or payment.
	 *
	 * @param \WP_Ultimo\Models\Customer     $customer Customer object.
	 * @param \WP_Ultimo\Models\Payment|null $payment Optional payment object.
	 * @return \WP_Ultimo\Models\Product|null Product object or null.
	 */
	private function get_product_from_customer($customer, $payment = null) {

		// Try to get product from payment first
		if ($payment) {
			$membership = $payment->get_membership();

			if ($membership) {
				return $membership->get_plan();
			}
		}

		// Try to get from customer's memberships
		$memberships = $customer->get_memberships();

		if (! empty($memberships)) {

			// Get the first active membership
			foreach ($memberships as $membership) {
				if ($membership->is_active()) {
					return $membership->get_plan();
				}
			}

			// If no active membership, use the first one
			return reset($memberships)->get_plan();
		}

		return null;
	}
}
