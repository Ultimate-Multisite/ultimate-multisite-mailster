<?php
/**
 * Base test case for Mailster Integration addon.
 *
 * @package MAILSTER
 */

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * Base test class for ultimate-multisite-addon-template addon tests.
 */
class() Mailster_Base extends TestCase {

	/**
	 * Set up test fixtures before each test.
	 */
	public function set_up() {
		parent::set_up();

		// Ensure the main plugin is loaded
		$this->assertTrue(function_exists('WP_Ultimo'), 'Main WP Ultimo plugin should be loaded');

		// Add any addon-specific setup here
	}

	/**
	 * Clean up after each test.
	 */
	public function tear_down() {
		// Add any cleanup logic here

		parent::tear_down();
	}

	/**
	 * Helper method to create a test user.
	 *
	 * @param string $role User role.
	 * @return int User ID.
	 */
	protected function create_test_user($role = 'subscriber') {
		return $this->factory->user->create(
			[
				'role' => $role,
			]
		);
	}

	/**
	 * Helper method to create a test site.
	 *
	 * @return int Site ID.
	 */
	protected function create_test_site() {
		return $this->factory->blog->create(
			[
				'domain' => 'test.example.com',
				'path'   => '/',
			]
		);
	}

	/**
	 * Helper method to create a test customer.
	 *
	 * @return \WP_Ultimo\Models\Customer|false
	 */
	protected function create_test_customer() {
		if (function_exists('wu_create_customer')) {
			return wu_create_customer(
				[
					'user_id'      => $this->create_test_user(),
					'email'        => 'test@example.com',
					'username'     => 'testuser',
					'display_name' => 'Test User',
				]
			);
		}
		return false;
	}

	/**
	 * Helper method to create a test membership.
	 *
	 * @return \WP_Ultimo\Models\Membership|false
	 */
	protected function create_test_membership() {
		if (function_exists('wu_create_membership')) {
			$customer = $this->create_test_customer();
			if ($customer) {
				return wu_create_membership(
					[
						'customer_id' => $customer->get_id(),
						'plan_id'     => 1, // Adjust based on your test data
						'status'      => 'active',
					]
				);
			}
		}
		return false;
	}
}
