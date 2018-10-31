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
 *
 * @package wp_slack_logbot
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

		$slack_api          = new Slack_API();
		$team               = $slack_api::$team_info;
		$args               = array( 'hide_empty' => 0 );
		$terms              = get_terms( 'category', $args );
		$parent_category_id = 0;
		$category_id        = 0;
		$channel_name       = $slack_api::request( $slack_api::SLACK_API_PATH_CHANNEL_INFO, array( 'channel_id' => $data['event_channel'] ) );

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

		$user_name    = $slack_api::request( $slack_api::SLACK_API_PATH_USER_INFO, array( 'user_id' => $data['event_user'] ) );
		$table_name   = $wpdb->prefix . 'posts';
		$wp_user_id   = get_current_user_id() > 0 ? get_current_user() : 1;
		$post_id      = 0;
		$post_title   = '[Slack Log] ' . $channel_name . '( ';
		$post_title  .= get_date_from_gmt( date( 'Y-m-d H:i:s', $data['event_time'] ), get_option( 'date_format' ) );
		$post_title  .= ' )';
		$post_content = '<h2>' . $channel_name . '</h2>';
		$current_date = get_date_from_gmt( date( 'Y-m-d H:i:s' ), 'Y-m-d' );

		$query  = "SELECT * FROM $table_name WHERE post_date > %s AND post_title = %s ORDER BY ID ASC LIMIT 1";
		$result = $wpdb->get_results( $wpdb->prepare( $query, array( $current_date, $post_title ) ), ARRAY_A );

		if ( count( $result ) > 0 ) {
			$post_id      = $result[0]['ID'];
			$post_content = $result[0]['post_content'];
			$post_content = str_replace( '</ul>', '', $post_content );
		} else {
			$post_content .= '<ul>';
		}

		$post_content .= '<li>';
		$post_content .= get_date_from_gmt( $data['event_datetime'], get_option( 'time_format' ) ) . ' ';
		$post_content .= esc_attr( $data['event_text'] ) . ' ';
		$post_content .= '@' . $user_name . '</li></ul>';

		$post = array(
			'ID'            => $post_id,
			'post_title'    => $post_title,
			'post_content'  => $post_content,
			'post_status'   => 'publish',
			'post_author'   => $wp_user_id,
			'post_category' => array( $parent_category_id, $category_id ),
		);

		remove_action( 'post_updated', 'wp_save_post_revision' );
		$post_id > 0 ? wp_update_post( $post ) : wp_insert_post( $post );
		add_action( 'post_updated', 'wp_save_post_revision' );
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
