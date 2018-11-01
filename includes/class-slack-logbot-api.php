<?php
/**
 * Slack_Logbot_API class
 *
 * @package WordPress
 * @subpackage WP Slack Logbot
 * @since 1.0.0
 * @version 1.0.0
 */

namespace wp_slack_logbot;

/**
 * Class Slack_Logbot_API
 *
 * @package wp_slack_logbot
 */
class Slack_Logbot_API {
	/**
	 * Slack_Logbot_API constructor.
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
	}

	/**
	 * API Endpoint of event API request.
	 */
	public function challenge() {
		$content_type = explode( ';', trim( strtolower( $_SERVER['CONTENT_TYPE'] ) ) );
		$media_type   = $content_type[0];

		if ( 'POST' == $_SERVER['REQUEST_METHOD'] && 'application/json' == $media_type ) {
			$slack_logbot = new Slack_Logbot();
			$request      = json_decode( file_get_contents( 'php://input' ), true );
			$data         = $slack_logbot->prepare_data( $request );
			// Save slack log to log table.
			$slack_logbot->save( $data );

			// Save slack log to wp_post table.
			$slack_logbot->upsert_post( $data );
		}
		return array();
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
}
