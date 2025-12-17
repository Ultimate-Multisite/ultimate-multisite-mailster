<?php
/**
 * Sample test case for Mailster Integration addon.
 *
 * @package MAILSTER
 */

/**
 * Test the main ultimate-multisite-addon-template functionality.
 */
class() Mailster_Test extends Mailster_Base {

	/**
	 * Test that the addon is loaded properly.
	 */
	public function test_addon_loaded() {
		// Test that the main addon class exists
		$this->assertTrue(class_exists('WP_Ultimo_Mailster'), 'Main addon class should exist');

		// Test that the addon instance is available
		$instance = WP_Ultimo_Mailster::get_instance();
		$this->assertInstanceOf('WP_Ultimo_Mailster', $instance, 'Addon instance should be of correct type');
	}

	/**
	 * Test addon initialization.
	 */
	public function test_addon_initialization() {
		// Test that addon hooks are registered
		$this->assertGreaterThan(0, has_action('plugins_loaded'), 'plugins_loaded action should be registered');

		// Test that textdomain is loaded
		$this->assertTrue(has_action('init', 'WP_Ultimo_Mailster::get_instance()->load_textdomain'), 'Textdomain should be loaded on init');
	}

	/**
	 * Test addon integration with main plugin.
	 */
	public function test_main_plugin_integration() {
		// Test that the addon integrates properly with WP Ultimo
		$this->assertTrue(function_exists('wu_get_version'), 'Main plugin functions should be available');

		// Test that WP_Ultimo class exists
		$this->assertTrue(class_exists('WP_Ultimo\WP_Ultimo'), 'Main WP_Ultimo class should be loaded');
	}

	/**
	 * Test addon constants are defined.
	 */
	public function test_addon_constants() {
		// Test that addon constants are defined
		$this->assertTrue(defined('MAILSTER_VERSION'), 'Version constant should be defined');
		$this->assertTrue(defined('MAILSTER_PLUGIN_FILE'), 'Plugin file constant should be defined');
		$this->assertTrue(defined('MAILSTER_PLUGIN_DIR'), 'Plugin dir constant should be defined');
		$this->assertTrue(defined('MAILSTER_PLUGIN_URL'), 'Plugin URL constant should be defined');
	}

	/**
	 * Test dependency check functionality.
	 */
	public function test_dependency_check() {
		// Test that the addon properly checks for WP Ultimo dependency
		$addon = WP_Ultimo_Mailster::get_instance();

		// If WP Ultimo is loaded, admin notice should not be triggered
		$this->assertFalse(has_action('admin_notices', [$addon, 'wp_ultimo_missing_notice']), 'Admin notice should not be shown when WP Ultimo is active');
	}

	/**
	 * Test addon version.
	 */
	public function test_addon_version() {
		$addon = WP_Ultimo_Mailster::get_instance();
		$this->assertEquals('1.0.0', $addon->version, 'Addon version should match expected value');
		$this->assertEquals('1.0.0', ULTIMATE_MULTISITE_MAILSTER_VERSION, 'Version constant should match expected value');
	}
}
