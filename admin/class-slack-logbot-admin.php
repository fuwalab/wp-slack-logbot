<?php
/**
 * Slack_Logbot_Admin class
 *
 * @package WordPress
 * @subpackage WP Slack Logbot
 * @since 1.0.0
 * @version 1.0.0
 */

namespace wp_slack_logbot;

/**
 * Class Slack_Logbot_Admin
 *
 * @package wp_slack_logbot
 */
class Slack_Logbot_Admin {
	/**
	 * Slack Bot access token.
	 *
	 * @var $slack_access_token Slack_Logbot_Admin Slack access token.
	 */
	public $slack_access_token;

	/**
	 * Slack_Logbot_Admin constructor.
	 */
	function __construct() {
		add_action( 'admin_menu', array( $this, 'create_admin_menu' ) );
		add_action( 'admin_notices', array( $this, 'show_error_message' ) );

		// Set access token.
		$this->set_slack_token();
	}

	/**
	 * Create admin menu.
	 */
	public function create_admin_menu() {
		add_menu_page( 'WP Slack Logbot Settings', 'WP Slack Logbot', 'administrator', __FILE__, array( $this, 'show_page' ), plugins_url( '/images/icon.png', __FILE__ ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Register settings.
	 */
	public function register_settings() {
		register_setting( 'wp-slack-logbot-settings-group', 'wp-slack-logbot-bot-user-oauth-access-token' );

		// set slack team info.
		Slack_API::set_slack_team_info();
	}

	/**
	 * Set access token of Slack Logbot.
	 */
	public function set_slack_token() {
		$this->slack_access_token = get_option( 'wp-slack-logbot-bot-user-oauth-access-token' );
	}

	/**
	 * Display error message.
	 */
	public function show_error_message() {
		$error_message = '';

		$slack_api = new Slack_API();
		$error     = $slack_api->auth_test();

		if ( ! isset( $this->slack_access_token ) || '' == $this->slack_access_token ) {
			$error_message .= 'Please set Access Token of your Slack bot.';
		} elseif ( isset( $error['error'] ) ) {
			$error_message .= $error['error'];
		}
		$error_message_html = '';
		$plugin_name        = WP_Slack_Logbot::$plugin_name;

		if ( '' != $error_message ) {
			$error_message_html .= "<div class=\"message error\"><h2>$plugin_name</h2><p>$error_message</p></div>";
		}

		echo $error_message_html;
	}

	/**
	 * Show setting page.
	 */
	public function show_page() {
		?>
		<div class="wrap">
			<h2><?php echo WP_Slack_Logbot::$plugin_name; ?></h2>
			<?php settings_errors(); ?>
			<form method="post" action="options.php">
				<table class="form-table">
					<tr valign="top">
						<th scope="row">Bot User OAuth Access Token</th>
						<td><input type="text" name="wp-slack-logbot-bot-user-oauth-access-token" value="<?php echo esc_attr( get_option( 'wp-slack-logbot-bot-user-oauth-access-token' ) ); ?>"></td>
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
