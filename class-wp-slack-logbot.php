<?php
/**
 * Plugin Name:     WP Slack Logbot
 * Plugin URI:      https://github.com/fuwalab/wp-slack-logbot
 * Description:     Stores all messages of particular channels on slack. And able to see them.
 * Author:          ryotsun
 * Author URI:      https://4to.pics/
 * Text Domain:     wp-slack-logbot
 * Domain Path:     /languages
 * Version:         1.1
 *
 * @package         Wp_Slack_Logbot
 */

namespace wp_slack_logbot;

/**
 * Require file
 */
require_once 'includes/class-slack-logbot.php';
require_once 'includes/class-slack-api.php';
require_once 'includes/class-slack-logbot-api.php';
require_once 'includes/class-slack-logbot-exception.php';
require_once 'admin/class-slack-logbot-admin.php';

/**
 * Class WP_Slack_Logbot
 *
 * @package wp_slack_logbot
 */
class WP_Slack_Logbot {
	const TABLE_NAME = 'slack_logbot';

	/**
	 * Plugin name.
	 *
	 * @var string $plugin_name plugin name.
	 */
	public static $plugin_name = 'WP Slack Logbot';

	/**
	 * Logbot version
	 *
	 * @var string $slack_logbot_version
	 */
	var $slack_logbot_version = '1.1';

	/**
	 * WP_Slack_Logbot constructor.
	 */
	function __construct() {
		$this->register();

		new Slack_Logbot();
		new Slack_Logbot_API();
		new Slack_Logbot_Admin();
	}

	/**
	 * Register activation hook.
	 */
	public function register() {
		// activation hook.
		register_activation_hook( __FILE__, array( $this, 'install' ) );
		// load translation file.
		load_plugin_textdomain( dirname( plugin_basename( __FILE__ ) ), false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
		// uninstall hook.
		register_uninstall_hook( __FILE__, 'self::uninstall' );
	}

	/**
	 * Create table for this plugin.
	 */
	public function install() {
		global $wpdb;
		$table_name      = $wpdb->prefix . self::TABLE_NAME;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
				id INT(11) NOT NULL AUTO_INCREMENT,
				team_id VARCHAR(50),
				type VARCHAR(255),
				api_app_id VARCHAR(255),
				event_id VARCHAR(20),
				event_user VARCHAR(20),
				event_client_msg_id VARCHAR(50),
				event_type VARCHAR(255),
				event_text TEXT NOT NULL,
				event_channel VARCHAR(20),
				event_channel_type VARCHAR(50),
				event_time INT(11),
				event_datetime DATETIME NOT NULL,
				create_date DATE NOT NULL,
				PRIMARY KEY id (id),
				KEY channel (team_id, event_channel),
				UNIQUE KEY message (event_id, event_client_msg_id),
				KEY create_date (create_date),
				KEY event_channel_type (event_channel_type)
  				) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );

		add_option( 'slack_logbot_version', $this->slack_logbot_version );
	}

	/**
	 * Uninstall plugin.
	 */
	public static function uninstall() {
		global $wpdb;
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}slack_logbot" );

		delete_option( 'slack_logbot_version' );
		delete_option( 'wp-slack-logbot-bot-user-oauth-access-token' );
	}
}

new WP_Slack_Logbot();
