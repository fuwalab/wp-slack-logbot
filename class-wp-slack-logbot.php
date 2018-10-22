<?php
/**
 * Plugin Name:     Wp Slack Logbot
 * Plugin URI:      PLUGIN SITE HERE
 * Description:     PLUGIN DESCRIPTION HERE
 * Author:          YOUR NAME HERE
 * Author URI:      YOUR SITE HERE
 * Text Domain:     wp-slack-logbot
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Wp_Slack_Logbot
 */

/**
 * Require file
 */
require_once 'includes/class-slack-logbot.php';
require_once 'admin/class-slack-logbot-admin.php';

class WP_Slack_Logbot {
	const TABLE_NAME = 'slack_logbot';
	var $slack_logbot_version = '1.0';
	var $admin;

	function __construct()
	{
		new SlackLogbot();
		$this->register_activation_hook();

		// enable admin
		$this->admin = new SlackLogbotAdmin();
	}

	public function register_activation_hook()
	{
		register_activation_hook( __FILE__, array( $this, 'install') );
	}

	public function install()
	{
		global $wpdb;
		$table_name = $wpdb->prefix . self::TABLE_NAME;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
				id INT(11) NOT NULL AUTO_INCREMENT,
				team_id VARCHAR(255),
				type VARCHAR(255),
				api_app_id VARCHAR(255),
				event_id VARCHAR(255),
				event_user VARCHAR(255),
				event_client_msg_id VARCHAR(255),
				event_type VARCHAR(255),
				event_text TEXT NOT NULL,
				event_channel VARCHAR(255),
				event_channel_type VARCHAR(255),
				event_time INT(11),
				event_datetime DATETIME NOT NULL,
				create_date DATE NOT NULL,
				PRIMARY KEY id (id),
				KEY channel (team_id, event_channel),
				KEY create_date (create_date),
				KEY event_channel_type (event_channel_type)
  				) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );

		add_option( 'slack_logbot_version', $this->slack_logbot_version );
	}
}

$wp_slack_logbot = new WP_Slack_Logbot();
