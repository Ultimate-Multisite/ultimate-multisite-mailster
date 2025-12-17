<?php
/**
 * Settings Manager - Handles addon settings registration.
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
 * Settings Manager class.
 *
 * Registers and manages addon settings.
 */
class Settings_Manager {

	/**
	 * Single instance of the class.
	 *
	 * @var Settings_Manager
	 */
	protected static $instance = null;

	/**
	 * Main instance.
	 *
	 * @return Settings_Manager
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

		add_action('init', [$this, 'register_settings']);
		add_filter('wu_pre_save_settings', [$this, 'save_mailster_lists'], 10, 3);
		add_filter('wu_get_setting', [$this, 'filter_mailster_lists_value'], 10, 4);
	}

	/**
	 * Register settings section and fields.
	 */
	public function register_settings(): void {

		// Register settings section
		wu_register_settings_section(
			'mailster',
			[
				'title' => __('Mailster Integration', 'ultimate-multisite-mailster'),
				'desc'  => __('Configure Mailster email marketing integration.', 'ultimate-multisite-mailster'),
				'icon'  => 'dashicons-wu-email',
				'order' => 999,
				'addon' => true,
			]
		);

		// General Settings Header
		wu_register_settings_field(
			'mailster',
			'mailster_header_general',
			[
				'type'  => 'header',
				'title' => __('General Settings', 'ultimate-multisite-mailster'),
				'desc'  => __('Configure Mailster email marketing integration. The addon is active once Mailster is installed on the main site.', 'ultimate-multisite-mailster'),
			]
		);

		// Subscription Timing
		wu_register_settings_field(
			'mailster',
			'mailster_subscription_timing',
			[
				'type'    => 'select',
				'title'   => __('Subscription Timing', 'ultimate-multisite-mailster'),
				'desc'    => __('When to add customers to Mailster lists.', 'ultimate-multisite-mailster'),
				'tooltip' => __('Choose "Order Creation" to subscribe immediately when the customer signs up. Choose "Payment Complete" to wait until payment is confirmed (recommended for paid plans).', 'ultimate-multisite-mailster'),
				'options' => [
					'order_creation'   => __('On Order Creation (Immediate)', 'ultimate-multisite-mailster'),
					'payment_complete' => __('On Payment Complete', 'ultimate-multisite-mailster'),
				],
				'default' => 'order_creation',
			]
		);

		// Opt-in Mode
		wu_register_settings_field(
			'mailster',
			'mailster_optin_mode',
			[
				'type'    => 'select',
				'title'   => __('Opt-in Mode', 'ultimate-multisite-mailster'),
				'desc'    => __('How customers consent to email marketing.', 'ultimate-multisite-mailster'),
				'tooltip' => __('Choose "Automatic" to subscribe all customers, or "Checkbox" to require explicit consent via a checkout field. Checkbox mode is recommended for GDPR compliance.', 'ultimate-multisite-mailster'),
				'options' => [
					'automatic' => __('Automatic (No Checkbox)', 'ultimate-multisite-mailster'),
					'checkbox'  => __('Requires Checkbox Confirmation', 'ultimate-multisite-mailster'),
				],
				'default' => 'automatic',
			]
		);

		// Double Opt-in
		wu_register_settings_field(
			'mailster',
			'mailster_double_optin',
			[
				'type'    => 'toggle',
				'title'   => __('Double Opt-in', 'ultimate-multisite-mailster'),
				'desc'    => __('Require email confirmation before subscribing. Subscribers will receive a confirmation email.', 'ultimate-multisite-mailster'),
				'default' => false,
			]
		);

		// Default Lists Header
		wu_register_settings_field(
			'mailster',
			'mailster_header_lists',
			[
				'type'  => 'header',
				'title' => __('List Settings', 'ultimate-multisite-mailster'),
				'desc'  => __('Configure default Mailster lists for new subscribers.', 'ultimate-multisite-mailster'),
			]
		);

		// Default Lists (Multi-select using Mailster's native selector)
		wu_register_settings_field(
			'mailster',
			'mailster_default_lists',
			[
				'type'    => 'html',
				'title'   => __('Default Lists', 'ultimate-multisite-mailster'),
				'desc'    => __('Global default lists to add customers to when no product-specific lists are set.', 'ultimate-multisite-mailster'),
				'tooltip' => __('Select multiple lists by checking the boxes. Leave all unchecked to disable default subscriptions.', 'ultimate-multisite-mailster'),
				'content' => $this->render_mailster_lists_selector(),
			]
		);

		// Advanced Settings Header
		wu_register_settings_field(
			'mailster',
			'mailster_header_advanced',
			[
				'type'  => 'header',
				'title' => __('Advanced Settings', 'ultimate-multisite-mailster'),
				'desc'  => __('Advanced configuration options.', 'ultimate-multisite-mailster'),
			]
		);

		// Update Existing Subscribers
		wu_register_settings_field(
			'mailster',
			'mailster_update_existing',
			[
				'type'    => 'toggle',
				'title'   => __('Update Existing Subscribers', 'ultimate-multisite-mailster'),
				'desc'    => __('Update subscriber data if the email already exists in Mailster. New lists will be added to existing subscriptions.', 'ultimate-multisite-mailster'),
				'default' => true,
			]
		);

		// Map Fields
		wu_register_settings_field(
			'mailster',
			'mailster_map_fields',
			[
				'type'    => 'toggle',
				'title'   => __('Map Customer Fields', 'ultimate-multisite-mailster'),
				'desc'    => __('Automatically map customer fields (first name, last name, billing address) to Mailster subscriber fields.', 'ultimate-multisite-mailster'),
				'default' => true,
			]
		);
	}

	/**
	 * Render Mailster lists selector using Mailster's native function.
	 *
	 * @return string HTML for list selector.
	 */
	private function render_mailster_lists_selector(): string {

		// Get currently selected lists from settings
		$selected_lists = wu_get_setting('mailster_default_lists', []);

		// Ensure it's an array
		if (! is_array($selected_lists)) {
			$selected_lists = [];
		}

		// Switch to main site where Mailster is active
		$main_site_id = get_main_site_id();
		$switched     = false;

		if (get_current_blog_id() !== $main_site_id) {
			switch_to_blog($main_site_id);
			$switched = true;
		}

		// Start output buffering
		ob_start();

		if (function_exists('mailster')) {
			// Use Mailster's native list selector
			mailster('lists')->print_it(null, null, 'mailster_default_lists', false, $selected_lists);
			?>
			<p class="description">
				<?php esc_html_e('Customers will be added to these lists by default (unless a product overrides with its own lists).', 'ultimate-multisite-mailster'); ?>
			</p>
			<?php
		} else {
			?>
			<p class="description" style="color: #d63638;">
				<?php esc_html_e('Mailster plugin is not active on the main site. Please activate Mailster to select lists.', 'ultimate-multisite-mailster'); ?>
			</p>
			<?php
		}

		$output = ob_get_clean();

		// Restore blog if we switched
		if ($switched) {
			restore_current_blog();
		}

		return $output;
	}

	/**
	 * Save mailster_default_lists field from POST data.
	 *
	 * This filter intercepts the settings save process to manually handle
	 * the mailster_default_lists field since it uses type => 'html'.
	 *
	 * @param array $settings         Settings being saved.
	 * @param array $settings_to_save Raw POST data.
	 * @param array $saved_settings   Currently saved settings.
	 * @return array Modified settings array.
	 */
	public function save_mailster_lists(array $settings, array $settings_to_save, array $saved_settings): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter

		// Only process if we're on the mailster settings tab
		if (wu_request('tab') !== 'mailster') {
			return $settings;
		}

		// Get the mailster_default_lists from POST (submitted by Mailster's checkbox selector)
		$mailster_lists = wu_request('mailster_default_lists', []);

		// Ensure it's an array and sanitize
		if (! is_array($mailster_lists)) {
			$mailster_lists = [];
		}

		// Filter out empty values and convert to integers
		$mailster_lists = array_filter(array_map('intval', $mailster_lists));

		// Add to settings array
		$settings['mailster_default_lists'] = $mailster_lists;

		return $settings;
	}

	/**
	 * Filter the mailster_default_lists value when retrieved.
	 *
	 * Ensures the value is always an array, even if it wasn't saved properly.
	 *
	 * @param mixed  $setting_value  Current setting value.
	 * @param string $setting        Setting name.
	 * @param mixed  $default_value  Default value.
	 * @param array  $settings       All settings.
	 * @return mixed Filtered setting value.
	 */
	public function filter_mailster_lists_value($setting_value, string $setting, $default_value, array $settings) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter

		// Only filter the mailster_default_lists setting
		if ('mailster_default_lists' !== $setting) {
			return $setting_value;
		}

		// Ensure it's always an array
		if (! is_array($setting_value)) {
			return [];
		}

		return $setting_value;
	}
}
