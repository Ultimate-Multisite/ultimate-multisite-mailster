<?php
/**
 * Updates add-ons
 *
 * @subpackage Updater
 * @since 2.0.0
 */

namespace WP_Ultimo;

/**
 * Updates add-ons from the main site.
 *
 * @since 2.0.0
 */
class Multisite_Ultimate_Updater {

	/**
	 * Plugin slug.
	 *
	 * @var string
	 */
	private string $plugin_slug;

	/**
	 * Plugin file path.
	 *
	 * @var string
	 */
	private string $plugin_file;

	/**
	 * @param string $plugin_slug Slug of the plugin.
	 * @param string $plugin_file Main file of the plugin.
	 */
	public function __construct(string $plugin_slug, string $plugin_file) {
		$this->plugin_slug = $plugin_slug;
		$this->plugin_file = $plugin_file;
	}
	/**
	 * Add the main hooks.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function init() {
		add_action('init', [$this, 'enable_auto_updates']);
	}

	/**
	 * Adds the auto-update hooks
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function enable_auto_updates() {
		// Check if the main plugin is loaded and has the update URL constant
		if ( ! defined('MULTISITE_ULTIMATE_UPDATE_URL')) {
			define('MULTISITE_ULTIMATE_UPDATE_URL', 'https://ultimatemultisite.com/');
		}

		$url = add_query_arg(
			[
				'update_slug'   => $this->plugin_slug,
				'update_action' => 'get_metadata',
			],
			MULTISITE_ULTIMATE_UPDATE_URL
		);

		if (class_exists('\\YahnisElsts\\PluginUpdateChecker\\v5\\PucFactory')) {
			\YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
				$url,
				$this->plugin_file,
				$this->plugin_slug
			);
		}
	}
}
