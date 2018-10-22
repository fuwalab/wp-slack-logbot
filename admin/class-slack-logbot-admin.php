<?php

class SlackLogbotAdmin {

	function __construct()
	{
		add_action('admin_menu', array( $this, 'create_admin_menu' ));
	}

	public function create_admin_menu()
	{
		add_menu_page('WP Slack Logbot Settings', 'WP Slack Logbot', 'administrator',
			__FILE__, array( $this, 'show_page' ), plugins_url('/images/icon.png', __FILE__ ));
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	public function register_settings()
	{
		register_setting( 'wp-slack-logbot-settings-group', 'wp-slack-logbot-bot-user-oauth-access-token' );
	}

	public function show_page()
	{
		?>
		<div class="wrap">
			<h2>WP Slack Logbot</h2>
			<form method="post" action="options.php">
				<table class="form-table">
					<tr valign="top">
						<th scope="row">Bot User OAuth Access Token</th>
						<td><input type="text" name="wp-slack-logbot-bot-user-oauth-access-token" value="<?php echo esc_attr( get_option('wp-slack-logbot-bot-user-oauth-access-token') ); ?>"></td>
					</tr>
				</table>
				<?php settings_fields( 'wp-slack-logbot-settings-group' ); ?>
				<?php do_settings_sections( 'wp-slack-logbot-settings-group' ); ?>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
