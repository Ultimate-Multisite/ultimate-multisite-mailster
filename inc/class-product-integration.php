<?php
/**
 * Product Integration - Adds Mailster options to product pages.
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
 * Product Integration class.
 *
 * Adds Mailster section to product edit pages.
 */
class Product_Integration {

	/**
	 * Single instance of the class.
	 *
	 * @var Product_Integration
	 */
	protected static $instance = null;

	/**
	 * Main instance.
	 *
	 * @return Product_Integration
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

		add_filter('wu_product_options_sections', [$this, 'add_product_section'], 10, 2);
		add_action('wu_save_product', [$this, 'save_product_meta']);
	}

	/**
	 * Add Mailster section to product edit page.
	 *
	 * @param array                     $sections Existing sections.
	 * @param \WP_Ultimo\Models\Product $product Product object.
	 * @return array Modified sections.
	 */
	public function add_product_section(array $sections, $product): array {

		$sections['mailster'] = [
			'title'  => __('Mailster', 'ultimate-multisite-mailster'),
			'desc'   => __('Configure Mailster email list subscriptions for this product. By default, customers will be added to the global default lists. Override to use product-specific lists or disable for this product.', 'ultimate-multisite-mailster'),
			'icon'   => 'dashicons-wu-email',
			'state'  => [
				'mailster_override_global' => $product->get_meta('mailster_override_global', false),
			],
			'fields' => $this->get_product_fields($product),
		];

		return $sections;
	}

	/**
	 * Get product fields for Mailster section.
	 *
	 * @param \WP_Ultimo\Models\Product $product Product object.
	 * @return array Field definitions.
	 */
	public function get_product_fields($product): array {

		$fields = [
			'mailster_override_global' => [
				'type'      => 'toggle',
				'title'     => __('Override Global Lists', 'ultimate-multisite-mailster'),
				'desc'      => __('Use product-specific lists instead of global defaults. Enable this to customize lists for this product or to disable Mailster by unselecting all lists.', 'ultimate-multisite-mailster'),
				'value'     => $product->get_meta('mailster_override_global', false),
				'html_attr' => [
					'v-model' => 'mailster_override_global',
				],
			],
		];

		// Use Mailster's native list selector - only show when overriding
		$fields['mailster_lists'] = [
			'type'              => 'html',
			'title'             => __('Mailster Lists', 'ultimate-multisite-mailster'),
			'desc'              => __('Select which Mailster lists customers should be added to when they purchase this product. Leave all unchecked to disable Mailster for this product.', 'ultimate-multisite-mailster'),
			'content'           => $this->render_mailster_lists_selector($product),
			'wrapper_html_attr' => [
				'v-cloak' => '1',
				'v-show'  => 'require("mailster_override_global", true)',
			],
		];

		return $fields;
	}

	/**
	 * Render Mailster lists selector using Mailster's native function.
	 *
	 * @param \WP_Ultimo\Models\Product $product Product object.
	 * @return string HTML for list selector.
	 */
	private function render_mailster_lists_selector($product): string {

		// Get selected lists from product meta
		$selected_lists = $product->get_meta('mailster_lists', []);

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
			mailster('lists')->print_it(null, null, 'mailster_lists', false, $selected_lists);
			?>
			<p class="description">
				<?php esc_html_e('Customers who purchase this product will be added to these lists.', 'ultimate-multisite-mailster'); ?>
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
	 * Save product meta when product is saved.
	 *
	 * @param object $admin_page The admin page object.
	 */
	public function save_product_meta($admin_page): void {

		// Get the product object from the admin page
		$product = $admin_page->get_object();

		if (! $product) {
			return;
		}

		// Save mailster_override_global toggle
		$mailster_override = wu_request('mailster_override_global', false);
		$product->update_meta('mailster_override_global', (bool) $mailster_override);

		// Save mailster_lists (array of list IDs from checkboxes)
		$mailster_lists = wu_request('mailster_lists', []);

		// Ensure it's an array and convert to integers
		if (! is_array($mailster_lists)) {
			$mailster_lists = [];
		}

		// Filter out empty values and convert to integers
		$mailster_lists = array_filter(array_map('intval', $mailster_lists));

		$product->update_meta('mailster_lists', $mailster_lists);
	}

	/**
	 * Get product lists for subscription.
	 *
	 * Returns product-specific lists only if override is enabled.
	 * Returns empty array otherwise (will fallback to global defaults).
	 *
	 * @param int $product_id Product ID.
	 * @return array Array of list IDs, or empty array to use global defaults.
	 */
	public function get_product_lists(int $product_id): array {

		$product = wu_get_product($product_id);

		if (! $product) {
			return [];
		}

		// Check if product is overriding global lists
		if (! $product->get_meta('mailster_override_global', false)) {
			// Not overriding - return empty to use global defaults
			return [];
		}

		// Get product-specific lists
		$lists = $product->get_meta('mailster_lists', []);

		// Ensure it's an array
		if (! is_array($lists)) {
			return [];
		}

		// Return the lists (could be empty if user wants to disable for this product)
		return array_filter(array_map('intval', $lists));
	}
}
