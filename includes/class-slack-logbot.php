<?php
/**
 * Slack_Logbot class
 *
 * @package WordPress
 * @subpackage WP Slack Logbot
 * @since 1.0.0
 * @version 1.0.0
 */

namespace wp_slack_logbot;

/**
 * Class Slack_Logbot
 */
class Slack_Logbot {
	const TABLE_NAME = 'slack_logbot';

	/**
	 * Slack Logbot version.
	 *
	 * @var string $slack_logbot_version
	 */
	var $slack_logbot_version = '1.0';

	/**
	 * Slack team info.
	 *
	 * @var $team_id Slack_Logbot Slack team info object.
	 */
	public static $team_info;

	/**
	 * Base URL of slack API.
	 */
	const SLACK_API_BASE_URL = 'https://slack.com/api/';

	/**
	 * Path to team info.
	 */
	const SLACK_API_PATH_TEAM_INFO = 'team.info';

	/**
	 * Path to channel info.
	 */
	const SLACK_API_PATH_CHANNEL_INFO = 'channels.info';

	/**
	 * Path to user info.
	 */
	const SLACK_API_PATH_USER_INFO = 'users.info';

	/**
	 * Slack_Logbot constructor.
	 */
	function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_api_routes' ) );
		self::set_slack_team_info();
	}

	/**
	 * Regsiter API routes.
	 */
	public function register_api_routes() {
		// event API request.
		register_rest_route(
			'wp-slack-logbot',
			'/challenge',
			array(
				'methods'  => 'POST',
				'callback' => array( $this, 'challenge' ),
			)
		);

		// URL configration and verification.
		register_rest_route(
			'wp-slack-logbot',
			'/enable_events',
			array(
				'methods'  => 'POST',
				'callback' => array( $this, 'enable_events' ),
			)
		);

		// Show channel list.
		register_rest_route(
			'wp-slack-logbot',
			'/channel_list',
			array(
				'methods'  => 'GET',
				'callback' => array( $this, 'channel_list' ),
			)
		);
	}

	/**
	 * API Endpoint of event API request.
	 */
	public function challenge() {
		$content_type = explode( ';', trim( strtolower( $_SERVER['CONTENT_TYPE'] ) ) );
		$media_type   = $content_type[0];

		if ( 'POST' == $_SERVER['REQUEST_METHOD'] && 'application/json' == $media_type ) {
			$request = json_decode( file_get_contents( 'php://input' ), true );
			$data    = $this->prepare_data( $request );
			// Save slack log to log table.
			$this->save( $data );

			// Save slack log to wp_post table.
			$this->upsert_post( $data );
		}
		return array();
	}

	/**
	 * Update or Insert log data into wp_posts.
	 *
	 * @param array $data slack log data.
	 */
	private function upsert_post( $data ) {
		global $wpdb;
		$team = self::$team_info;

		$args               = array( 'hide_empty' => 0 );
		$terms              = get_terms( 'category', $args );
		$parent_category_id = 0;
		$category_id        = 0;
		// FIXME: プライベートチャンネルやDMのときは別のAPIになる
		$channel_name       = $this->get_slack_channel_name( $data['event_channel'] );

		foreach ( $terms as $term ) {
			if ( $team['name'] == $term->slug ) {
				$parent_category_id = $term->term_id;
			}
			if ( $channel_name == $term->slug ) {
				$category_id = $term->term_id;
			}
		}

		$parent_category = array(
			'cat_ID'   => $parent_category_id,
			'cat_name' => $team['name'],
			'taxonomy' => 'category',
		);

		$category = array(
			'cat_ID'          => $category_id,
			'cat_name'        => $channel_name,
			'taxonomy'        => 'category',
			'category_parent' => $parent_category_id,
		);

		if ( file_exists( ABSPATH . '/wp-admin/includes/taxonomy.php' ) ) {
			require_once( ABSPATH . '/wp-admin/includes/taxonomy.php' );
		}

		$parent_category_id = wp_insert_category( $parent_category );
		$category_id        = wp_insert_category( $category );

		$user_name    = $this->get_slack_user_name( $data['event_user'] );
		$table_name   = $wpdb->prefix . 'posts';
		$wp_user_id   = get_current_user_id() > 0 ? get_current_user() : 1;
		$post_id      = 0;
		$post_title   = '[Slack Log] ' . $channel_name . '( ' . date_i18n( 'Y-m-d', $data['event_time'], false ) . ' )';
		$post_content = '<h2>' . $channel_name . '</h2>';
		$current_date = date_i18n( 'Y-m-d' );

		$query  = "SELECT * FROM $table_name WHERE post_date > %s AND post_title = %s ORDER BY ID DESC LIMIT 1";
		$result = $wpdb->get_results( $wpdb->prepare( $query, array( $current_date, $post_title ) ), ARRAY_A );

		if ( count( $result ) > 0 ) {
			$post_id      = $result[0]['ID'];
			$post_content = $result[0]['post_content'];
			$post_content = str_replace( '</ul>', '', $post_content );
		} else {
			$post_content .= '<ul>';
		}

		$post_content .= '<li>';
		$post_content .= $data['event_datetime'] . ' ';
		$post_content .= $data['event_text'] . ' ';
		$post_content .= '@' . $user_name . '</li></ul>';

		$post = array(
			'ID'            => $post_id,
			'post_title'    => $post_title,
			'post_content'  => $post_content,
			'post_status'   => 'publish',
			'post_author'   => $wp_user_id,
			'meta_input'    => array(
				'test_meta_key' => 'value of test_meta_key',
			),
			'post_category' => array( $parent_category_id, $category_id ),
		);

		remove_action( 'post_updated', 'wp_save_post_revision' );
		$post_id > 0 ? wp_update_post( $post ) : wp_insert_post( $post );
	}

	/**
	 * API Endpoint of URL configuration and verification.
	 *
	 * @return array|string $response
	 */
	public function enable_events() {
		$content_type = explode( ';', trim( strtolower( $_SERVER['CONTENT_TYPE'] ) ) );
		$media_type   = $content_type[0];

		if ( 'POST' == $_SERVER['REQUEST_METHOD'] && 'application/json' == $media_type ) {
			$request = json_decode( file_get_contents( 'php://input' ), true );
			if ( isset( $request['challenge'] ) ) {
				$response = array( 'challenge' => $request['challenge'] );
			} else {
				$response = 'error';
			}
		} else {
			$response = 'error';
		}

		return $response;
	}

	/**
	 * Set slack team info via slack api.
	 */
	public static function set_slack_team_info() {
		$params = array(
			'headers' => array(
				'content-type' => 'application/x-www-form-urlencoded',
			),
		);

		$slack_access_token = get_option( 'wp-slack-logbot-bot-user-oauth-access-token' );

		if ( isset( $slack_access_token ) || '' != $slack_access_token ) {
			$request_url = self::SLACK_API_BASE_URL . self::SLACK_API_PATH_TEAM_INFO . '?token=' . $slack_access_token;
			$response    = wp_remote_get( $request_url, $params );

			if ( 200 == $response['response']['code'] ) {
				$team_info_body  = json_decode( $response['body'], true );
				self::$team_info = $team_info_body['team'];
			}
		}
	}

	/**
	 * Get slack channel name from channelID.
	 *
	 * @param string $channel_id slack channel ID.
	 * @return mixed channel name.
	 */
	private function get_slack_channel_name( $channel_id ) {
		$channel_name = '';
		$params       = array(
			'headers' => array(
				'content-type' => 'application/x-www-form-urlencoded',
			),
		);

		$slack_access_token = get_option( 'wp-slack-logbot-bot-user-oauth-access-token' );

		if ( isset( $slack_access_token ) || '' != $slack_access_token ) {
			$request_url = self::SLACK_API_BASE_URL . self::SLACK_API_PATH_CHANNEL_INFO . '?token=' . $slack_access_token . '&channel=' . $channel_id;
			$response    = wp_remote_get( $request_url, $params );

			if ( 200 == $response['response']['code'] ) {
				$channel_info_body = json_decode( $response['body'], true );
				$channel_name      = $channel_info_body['channel']['name'];
			}
		}

		return '' != $channel_name ? $channel_name : $channel_id;
	}

	/**
	 * Get slack user name from userID.
	 *
	 * @param string $user_id Slack user ID.
	 * @return mixed user name.
	 */
	private function get_slack_user_name( $user_id ) {
		$user_name = $user_id;
		$params    = array(
			'headers' => array(
				'content-type' => 'application/x-www-form-urlencoded',
			),
		);

		$slack_access_token = get_option( 'wp-slack-logbot-bot-user-oauth-access-token' );

		if ( isset( $slack_access_token ) || '' != $slack_access_token ) {
			$request_url = self::SLACK_API_BASE_URL . self::SLACK_API_PATH_USER_INFO . '?token=' . $slack_access_token . '&user=' . $user_id;
			$response    = wp_remote_get( $request_url, $params );

			if ( 200 == $response['response']['code'] ) {
				$user_info_body = json_decode( $response['body'], true );
				$user_name      = $user_info_body['user']['profile']['display_name'];
			}
		}

		return $user_name;
	}

	/**
	 * Prepare data will be saved.
	 *
	 * @param array $data Post data.
	 * @return array $values
	 */
	private function prepare_data( $data ) {
		$values = array(
			'team_id'             => $data['team_id'],
			'type'                => $data['type'],
			'api_app_id'          => $data['api_app_id'],
			'event_id'            => $data['event_id'],
			'event_user'          => $data['event']['user'],
			'event_client_msg_id' => $data['event']['client_msg_id'],
			'event_type'          => $data['event']['type'],
			'event_text'          => isset( $data['event']['text'] ) ? $data['event']['text'] : '',
			'event_channel'       => $data['event']['channel'],
			'event_channel_type'  => $data['event']['channel_type'],
			'event_time'          => $data['event_time'],
			'event_datetime'      => date( 'Y-m-d H:i:s', $data['event_time'] ),
			'create_date'         => date( 'Y-m-d', $data['event_time'] ),
		);

		return $values;
	}

	/**
	 * Save post data into database.
	 *
	 * @param array $data Post data.
	 */
	private function save( $data ) {
		global $wpdb;
		$table_name = $wpdb->prefix . self::TABLE_NAME;

		$wpdb->insert(
			$table_name,
			$data
		);
	}

	/**
	 * Show channel list.
	 */
	public function channel_list() {
		return array();
	}
}
