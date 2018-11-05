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
		// Event API.
		register_rest_route(
			'wp-slack-logbot',
			'/events',
			array(
				'methods'  => 'POST',
				'callback' => array( $this, 'events' ),
			)
		);
	}

	/**
	 * API Endpoint of URL event API request.
	 *
	 * @return array|null|string $response
	 * @throws Slack_Logbot_Exception Logbot exception.
	 */
	public function events() {
		$content_type = explode( ';', trim( strtolower( $_SERVER['CONTENT_TYPE'] ) ) );
		$media_type   = $content_type[0];
		$response     = null;

		if ( 'POST' == $_SERVER['REQUEST_METHOD'] && 'application/json' == $media_type ) {
			$slack_logbot = new Slack_Logbot();
			$request      = json_decode( file_get_contents( 'php://input' ), true );

			if ( isset( $request['challenge'] ) ) {
				// For verification.
				$response = array( 'challenge' => $request['challenge'] );
			} else {
				$data = $slack_logbot->prepare_data( $request );
				// Save slack log to log table.
				$result = $slack_logbot->save( $data );

				if ( $result ) {
					// Save slack log to wp_post table.
					$slack_logbot->upsert_post( $data );
				}
			}
		} else {
			$response = 'error';
		}

		return $response;
	}
}
