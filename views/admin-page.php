<?php
/**
 * Admin page template for Mailster Integration.
 *
 * @package MAILSTER
 */

// Exit if accessed directly.
if (! defined('ABSPATH')) {
	exit;
}
?>

<div class="wrap">
	<h1><?php echo esc_html__('Mailster Integration', 'ultimate-multisite-addon-template'); ?></h1>
	
	<div class="wu-styling">
		<div class="wu-header">
			<h2><?php echo esc_html__('Mailster Integration Settings', 'ultimate-multisite-addon-template'); ?></h2>
			<p class="description">
				<?php echo esc_html__('Integrate with Mailster email marketing during checkout', 'ultimate-multisite-addon-template'); ?>
			</p>
		</div>

		<form method="post" action="options.php">
			<?php settings_fields('ultimate-multisite-addon-template_settings'); ?>
			<?php do_settings_sections('ultimate-multisite-addon-template_settings'); ?>
			
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="ultimate-multisite-addon-template_enabled">
							<?php echo esc_html__('Enable Mailster Integration', 'ultimate-multisite-addon-template'); ?>
						</label>
					</th>
					<td>
						<input type="checkbox" 
								id="ultimate-multisite-addon-template_enabled" 
								name="ultimate-multisite-addon-template_enabled" 
								value="1" 
								<?php checked(get_option('ultimate-multisite-addon-template_enabled', false)); ?> />
						<p class="description">
							<?php echo esc_html__('Check this to enable Mailster Integration functionality.', 'ultimate-multisite-addon-template'); ?>
						</p>
					</td>
				</tr>
				
				<!-- Add more settings fields as needed -->
			</table>
			
			<?php submit_button(); ?>
		</form>
	</div>
</div>