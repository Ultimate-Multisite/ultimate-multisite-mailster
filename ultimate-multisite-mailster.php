<?php
/**
 * Plugin Name: Ultimate Multisite: Mailster Integration
 * Description: Integrate with Mailster email marketing during checkout
 * Plugin URI: https://multisiteultimate.com
 * Text Domain: ultimate-multisite-mailster
 * Version: 1.0.2
 * Author: David Stone - Multisite Ultimate
 * Author URI: https://multisiteultimate.com
 * Copyright: David Stone, Multisite Ultimate
 * Network: true
 * Requires Plugins: ultimate-multisite
 * Requires at least: 5.3
 * Tested up to: 6.9
 * Requires PHP: 7.4
 */

// Exit if accessed directly.
if (! defined('ABSPATH')) {
	exit;
}

// Define addon constants.
const ULTIMATE_MULTISITE_MAILSTER_VERSION     = '1.0.1';
const ULTIMATE_MULTISITE_MAILSTER_PLUGIN_FILE = __FILE__;
define('ULTIMATE_MULTISITE_MAILSTER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ULTIMATE_MULTISITE_MAILSTER_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Main addon class.
 */
class WP_Ultimo_Mailster {

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	public $version = '1.0.1';

	/**
	 * Single instance of the class.
	 *
	 * @var WP_Ultimo_Mailster
	 */
	protected static $instance = null;

	/**
	 * Main instance.
	 *
	 * @return WP_Ultimo_Mailster
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

		add_action('plugins_loaded', [$this, 'init'], 11);
	}

	/**
	 * Initialize the addon.
	 */
	public function init() {

		// Check if Ultimate Multisite is active.
		if (! class_exists('WP_Ultimo') && ! function_exists('WP_Ultimo')) {
			add_action('network_admin_notices', [$this, 'ultimate_multisite_missing_notice']);

			return;
		}

		// Load plugin files.
		$this->load_dependencies();

		// Initialize hooks.
		$this->init_hooks();

		// Initialize updater.
		$this->init_updater();

		// Initialize main functionality.
		\WP_Ultimo\Mailster\Mailster_Main::get_instance();
	}

	/**
	 * Load required dependencies.
	 */
	private function load_dependencies() {

		// Load Jetpack autoloader - it handles all class loading automatically.
		if (file_exists(ULTIMATE_MULTISITE_MAILSTER_PLUGIN_DIR . 'vendor/autoload.php')) {
			require_once ULTIMATE_MULTISITE_MAILSTER_PLUGIN_DIR . 'vendor/autoload.php';
		}

		// All classes are now autoloaded via Jetpack autoloader.
		// No manual require_once needed - the autoloader handles everything!
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {

		add_action('plugins_loaded', [$this, 'register_translation_updates']);
	}

	/**
	 * Initialize the updater.
	 */
	private function init_updater() {

		$updater = new \WP_Ultimo\Multisite_Ultimate_Updater('ultimate-multisite-mailster', __FILE__);
		$updater->init();
	}

	/**
	 * Register with Traduttore for automatic translation updates.
	 */
	public function register_translation_updates() {
		\Required\Traduttore_Registry\add_project(
			'plugin',
			'ultimate-multisite-mailster',
			'https://translate.ultimatemultisite.com/api/translations/ultimatemultisite/ultimate-multisite-mailster/'
		);
	}

	/**
	 * Display notice when Ultimate Multisite is not active.
	 */
	public function ultimate_multisite_missing_notice() {

		?>
		<div class="notice notice-error is-dismissible">
			<p>
			<?php
			printf(
				/* translators: %1$s: Plugin name, %2$s: Required plugin */
				esc_html__('%1$s requires %2$s to be installed and active.', 'ultimate-multisite-mailster'),
				'<strong>' . esc_html__('Ultimate Multisite: Mailster Integration', 'ultimate-multisite-mailster') . '</strong>',
				'<strong>' . esc_html__('Ultimate Multisite', 'ultimate-multisite-mailster') . '</strong>'
			);
			?>
			</p>
		</div>
		<?php
	}
}

// Initialize the addon.
WP_Ultimo_Mailster::get_instance();
