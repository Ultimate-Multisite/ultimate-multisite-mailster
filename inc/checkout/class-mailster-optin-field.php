<?php
/**
 * Mailster Opt-in Checkout Field.
 *
 * @package WP_Ultimo_Mailster
 * @since 1.0.0
 */

namespace WP_Ultimo\Mailster\Checkout;

use WP_Ultimo\Checkout\Signup_Fields\Base_Signup_Field;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Mailster Opt-in Field class.
 *
 * Adds an opt-in checkbox to the checkout form for Mailster subscriptions.
 */
class Mailster_Optin_Field extends Base_Signup_Field {

	/**
	 * Returns the type of the field.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_type() {

		return 'mailster_optin';
	}

	/**
	 * Returns if this field should be present on the checkout flow or not.
	 *
	 * @since 1.0.0
	 * @return boolean
	 */
	public function is_required() {

		return false;
	}

	/**
	 * Is this a user-related field?
	 *
	 * If this is set to true, this field will be hidden
	 * when the user is already logged in.
	 *
	 * @since 1.0.0
	 * @return boolean
	 */
	public function is_user_field() {

		return false;
	}

	/**
	 * Requires the title of the field/element type.
	 *
	 * This is used on the Field/Element selection screen.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_title() {

		return __('Mailster Opt-in Checkbox', 'ultimate-multisite-mailster');
	}

	/**
	 * Returns the description of the field/element.
	 *
	 * This is used as the title attribute of the selector.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_description() {

		return __('Adds a checkbox for customers to opt-in to Mailster email lists.', 'ultimate-multisite-mailster');
	}

	/**
	 * Returns the tooltip of the field/element.
	 *
	 * This is used as the tooltip attribute of the selector.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_tooltip() {

		return __('Allows customers to opt-in to your Mailster email lists during checkout. Only shown when opt-in mode is set to "Requires Checkbox Confirmation" in settings.', 'ultimate-multisite-mailster');
	}

	/**
	 * Returns the icon to be used on the selector.
	 *
	 * Can be either a dashicon class or a wu-dashicon class.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_icon() {

		return 'dashicons-wu-email';
	}

	/**
	 * Returns the default values for the field-elements.
	 *
	 * This is passed through a wp_parse_args before we send the values
	 * to the method that returns the actual fields for the checkout form.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function defaults() {

		return [
			'checkbox_text'   => __('Yes, I want to receive email updates', 'ultimate-multisite-mailster'),
			'default_checked' => true,
		];
	}

	/**
	 * List of keys of the default fields we want to display on the builder.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function default_fields() {

		return [
			'name',
			'tooltip',
		];
	}
	/**
	 * If you want to force a particular attribute to a value, declare it here.
	 *
	 * @return array
	 */
	public function force_attributes(): array {

		return [
			'id' => 'mailster_optin',
		];
	}

	/**
	 * Returns the list of additional fields specific to this type.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_fields() {

		return [
			'checkbox_text'   => [
				'type'        => 'text',
				'title'       => __('Checkbox Label', 'ultimate-multisite-mailster'),
				'desc'        => __('The text shown next to the checkbox.', 'ultimate-multisite-mailster'),
				'placeholder' => __('Yes, I want to receive email updates', 'ultimate-multisite-mailster'),
				'value'       => '',
				'order'       => 10,
			],
			'default_checked' => [
				'type'  => 'toggle',
				'title' => __('Checked by Default', 'ultimate-multisite-mailster'),
				'desc'  => __('Whether the checkbox should be checked by default.', 'ultimate-multisite-mailster'),
				'value' => 1,
				'order' => 11,
			],
		];
	}

	/**
	 * Returns the field/element actual field array to be used on the checkout form.
	 *
	 * @since 1.0.0
	 *
	 * @param array $attributes Attributes saved on the editor form.
	 * @return array An array of fields, not the field itself.
	 */
	public function to_fields_array($attributes) {

		// Only show if optin_mode is 'checkbox'
		$optin_mode = wu_get_setting('mailster_optin_mode', 'automatic');

		if ('checkbox' !== $optin_mode) {
			return [];
		}

		$checkout_fields = [];

		// Get checkbox text from attributes or use default
		$checkbox_text = ! empty($attributes['checkbox_text'])
			? $attributes['checkbox_text']
			: __('Yes, I want to receive email updates', 'ultimate-multisite-mailster');

		$checkout_fields[ $attributes['id'] ] = [
			'type'            => 'checkbox',
			'id'              => $attributes['id'],
			'name'            => $checkbox_text,
			'tooltip'         => $attributes['tooltip'],
			'wrapper_classes' => $attributes['element_classes'],
		];

		// Set default checked state
		if (! empty($attributes['default_checked'])) {
			$checkout_fields[ $attributes['id'] ]['html_attr']['checked'] = 'checked';
		}

		// Check if value was already set (returning customer)
		$value = $this->get_value();

		if ('' !== $value && true === (bool) $value) {
			$checkout_fields[ $attributes['id'] ]['html_attr']['checked'] = 'checked';
		}

		return $checkout_fields;
	}
}
