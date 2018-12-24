<?php
/**
 * Slack_API class
 *
 * @package WordPress
 * @subpackage WP Slack Logbot
 * @since 1.0.0
 * @version 1.0.0
 */

namespace wp_slack_logbot;

/**
 * Class Slack_API
 *
 * @package wp_slack_logbot
 */
class Slack_API {
	/**
	 * Slack team info.
	 *
	 * @var $team_id Slack_API Slack team info object.
	 */
	public static $team_info;

	/**
	 * Slack access token.
	 *
	 * @var string $access_token access token.
	 */
	private static $access_token;

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
	const SLACK_API_PATH_CONVERSATION_INFO = 'conversations.info';

	/**
	 * Path to user info.
	 */
	const SLACK_API_PATH_USER_INFO = 'users.info';

	/**
	 * Path to auth test.
	 */
	const SLACK_API_PATH_AUTH_TEST = 'auth.test';

	/**
	 * Slack_API constructor.
	 *
	 * @param string $access_token Access token.
	 * @throws Slack_Logbot_Exception Slack api exception.
	 */
	function __construct( $access_token = '' ) {
		if ( empty( $access_token ) ) {
			$access_token = get_option( 'wp-slack-logbot-bot-user-oauth-access-token' );
		}
		$this->set_access_token( $access_token );
		$this->request( self::SLACK_API_PATH_TEAM_INFO );
	}

	/**
	 * Set Slack access token.
	 *
	 * @param string $token access token.
	 */
	private function set_access_token( $token ) {
		self::$access_token = $token;
	}

	/**
	 * Make request to slack APIs.
	 *
	 * @param string $path API name.
	 * @param null   $options array of options.
	 * @param null   $wp_params array of wp_params.
	 * @throws Slack_Logbot_Exception If provided unexpected response from slack api.
	 * @return array|mixed|string mixed response.
	 */
	public static function request( $path, $options = null, $wp_params = null ) {
		$response = '';

		if ( is_null( $wp_params ) ) {
			$wp_params = array(
				'headers' => array(
					'content-type' => 'application/x-www-form-urlencoded',
				),
			);
		}
		try {
			if ( empty( $path ) ) {
				throw new Slack_Logbot_Exception( __( 'API path should not be null.', 'wp-slack-logbot' ), 500 );
			}
			switch ( $path ) {
				case self::SLACK_API_PATH_AUTH_TEST:
					$response = self::auth_test( $path, $wp_params );
					break;
				case self::SLACK_API_PATH_CONVERSATION_INFO:
					$response = self::get_slack_channel_name( $path, $wp_params, $options );
					break;
				case self::SLACK_API_PATH_TEAM_INFO:
					self::set_slack_team_info( $path, $wp_params );
					break;
				case self::SLACK_API_PATH_USER_INFO:
					$response = self::get_slack_user_name( $path, $wp_params, $options );
					break;
				default:
					throw new Slack_Logbot_Exception( __( 'Unknown API path.', 'wp-slack-logbot' ), 500 );
					break;
			}
		} catch ( Slack_Logbot_Exception $e ) {
			die( $e->getMessage() . '(' . $e->getCode() . ')' );
		}
		return $response;
	}

	/**
	 * Test if access token is valid.
	 *
	 * @param string $path api path.
	 * @param array  $wp_params wp_params.
	 * @return array|mixed response from slack test api.
	 */
	private static function auth_test( $path, $wp_params ) {
		$request_url = self::SLACK_API_BASE_URL . $path . '?token=' . self::$access_token;
		$response    = wp_remote_get( $request_url, $wp_params );

		if ( 200 == $response['response']['code'] ) {
			$body = json_decode( $response['body'], true );
			if ( false == $body['ok'] ) {
				return $body;
			}
		}

		return array( 'error', 'something went wrong.' );
	}

	/**
	 * Set slack team info via slack api.
	 *
	 * @param string $path api path.
	 * @param array  $wp_params wp_params.
	 */
	private static function set_slack_team_info( $path, $wp_params ) {
		$request_url = self::SLACK_API_BASE_URL . $path . '?token=' . self::$access_token;
		$response    = wp_remote_get( $request_url, $wp_params );

		if ( 200 == $response['response']['code'] ) {
			$team_info_body = json_decode( $response['body'], true );
			if ( isset( $team_info_body['team'] ) ) {
				self::$team_info = $team_info_body['team'];
			}
		}
	}

	/**
	 * Get slack channel name from channelID.
	 *
	 * @param string $path api path.
	 * @param array  $wp_params wp_params.
	 * @param array  $options array of options.
	 * @throws Slack_Logbot_Exception If provided missing channel name from slack api response.
	 * @return string channel name.
	 */
	private static function get_slack_channel_name( $path, $wp_params, $options ) {
		$channel_name = '';

		try {
			if ( ! isset( $options['channel_id'] ) ) {
				throw new Slack_Logbot_Exception( __( 'channel ID should be set.', 'wp-slack-logbot' ), 500 );
			}
			$request_url = self::SLACK_API_BASE_URL . $path . '?token=' . self::$access_token . '&channel=' . $options['channel_id'];
			$response    = wp_remote_get( $request_url, $wp_params );

			if ( 200 == $response['response']['code'] ) {
				$channel_info_body = json_decode( $response['body'], true );
				if ( ! isset( $channel_info_body['error'] ) ) {
					$channel_name = $channel_info_body['channel']['name'];
				}
			}
			if ( '' == $channel_name ) {
				throw new Slack_Logbot_Exception( __( 'Failed to fetching channel name via Slack API.', 'wp-slack-logbot' ), 500 );
			}
		} catch ( Slack_Logbot_Exception $e ) {
			die( $e->getMessage() . '(' . $e->getCode() . ')' );
		}

		return $channel_name;
	}

	/**
	 * Get slack user name from userID.
	 *
	 * @param string $path api path.
	 * @param array  $wp_params wp_params.
	 * @param array  $options options.
	 * @throws Slack_Logbot_Exception If provided missing user name from slack api response.
	 * @return string user name.
	 */
	private static function get_slack_user_name( $path, $wp_params, $options ) {
		$user_name = '';

		try {
			if ( ! isset( $options['user_id'] ) ) {
				throw new Slack_Logbot_Exception( __( 'user ID should be set.', 'wp-slack-logbot' ), 500 );
			}
			$request_url = self::SLACK_API_BASE_URL . $path . '?token=' . self::$access_token . '&user=' . $options['user_id'];
			$response    = wp_remote_get( $request_url, $wp_params );

			if ( 200 == $response['response']['code'] ) {
				$user_info_body = json_decode( $response['body'], true );
				if ( ! isset( $user_info_body['error'] ) ) {
					$user_name = $user_info_body['user']['profile']['display_name'];

					if ( '' == $user_name ) {
						$user_name = $user_info_body['user']['profile']['real_name'];
					}
				}
			}
			if ( '' == $user_name ) {
				throw new Slack_Logbot_Exception( __( 'Failed to fetching user name via Slack API.', 'wp-slack-logbot' ), 500 );
			}
		} catch ( Slack_Logbot_Exception $e ) {
			error_log( $e->getMessage() . '(' . $e->getCode() . ')' );
		}

		return $user_name;
	}
}
