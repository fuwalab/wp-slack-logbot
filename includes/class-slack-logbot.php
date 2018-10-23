<?php
/**
 * Slack_Logbot class
 *
 * @package WordPress
 * @subpackage WP Slack Logbot
 * @since 1.0.0
 * @version 1.0.0
 */

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
	 * Slack_Logbot constructor.
	 */
	function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_api_routes' ) );
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
			$this->save( $data );
		}
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
			'create_date'         => date( 'Y-m-d' ),
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
		global $wpdb;
		$table_name = $wpdb->prefix . self::TABLE_NAME;

		// Ignore result of phpcs. It says "Use placeholders and $wpdb->prepare(); found $query (WordPress.DB.PreparedSQL.NotPrepared)".
		// Actually, it doesn't have to use prepare statement.
		$query = "SELECT MAX(create_date) AS create_date, event_channel FROM $table_name where id > %d GROUP BY event_channel";
		$rows  = $wpdb->get_results( $wpdb->prepare( $query, 0 ) );

		return $rows;
	}
}
