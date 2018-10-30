<?php
/**
 * Slack_Api class
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
	 * Path to auth test.
	 */
	const SLACK_API_PATH_AUTH_TEST = 'auth.test';

	/**
	 * Slack_API constructor.
	 */
	function __construct() {
		self::set_slack_team_info();
	}

	/**
	 * Test if access token is valid.
	 *
	 * @return array|mixed response from slack test api.
	 */
	public function auth_test() {
		$params = array(
			'headers' => array(
				'content-type' => 'application/x-www-form-urlencoded',
			),
		);

		$slack_access_token = get_option( 'wp-slack-logbot-bot-user-oauth-access-token' );

		if ( isset( $slack_access_token ) || '' != $slack_access_token ) {
			$request_url = self::SLACK_API_BASE_URL . self::SLACK_API_PATH_AUTH_TEST . '?token=' . $slack_access_token;
			$response    = wp_remote_get( $request_url, $params );

			if ( 200 == $response['response']['code'] ) {
				$body = json_decode( $response['body'], true );
				if ( false == $body['ok'] ) {
					return $body;
				}
			}
		}

		return array( 'error', 'something went wrong.' );
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
				$team_info_body = json_decode( $response['body'], true );
				if ( isset( $team_info_body['team'] ) ) {
					self::$team_info = $team_info_body['team'];
				}
			}
		}
	}

	/**
	 * Get slack channel name from channelID.
	 *
	 * @param string $channel_id slack channel ID.
	 * @throws Slack_Logbot_Exception If provided missing channel name from slack api response.
	 * @return mixed channel name.
	 */
	public function get_slack_channel_name( $channel_id ) {
		$channel_name = '';
		$params       = array(
			'headers' => array(
				'content-type' => 'application/x-www-form-urlencoded',
			),
		);

		$slack_access_token = get_option( 'wp-slack-logbot-bot-user-oauth-access-token' );

		try {
			if ( isset( $slack_access_token ) || '' != $slack_access_token ) {
				$request_url = self::SLACK_API_BASE_URL . self::SLACK_API_PATH_CHANNEL_INFO . '?token=' . $slack_access_token . '&channel=' . $channel_id;
				$response    = wp_remote_get( $request_url, $params );

				if ( 200 == $response['response']['code'] ) {
					$channel_info_body = json_decode( $response['body'], true );
					if ( ! isset( $channel_info_body['error'] ) ) {
						$channel_name = $channel_info_body['channel']['name'];
					}
				}
			}
			if ( '' == $channel_name ) {
				throw new Slack_Logbot_Exception( __( 'Failed to fetching channel name via Slack API.' ), 500 );
			}
		} catch ( Slack_Logbot_Exception $e ) {
			die( $e->getMessage() . '(' . $e->getCode() . ')' );
		}

		return $channel_name;
	}

	/**
	 * Get slack user name from userID.
	 *
	 * @param string $user_id Slack user ID.
	 * @throws Slack_Logbot_Exception If provided missing user name from slack api response.
	 * @return mixed user name.
	 */
	public function get_slack_user_name( $user_id ) {
		$user_name = '';
		$params    = array(
			'headers' => array(
				'content-type' => 'application/x-www-form-urlencoded',
			),
		);

		$slack_access_token = get_option( 'wp-slack-logbot-bot-user-oauth-access-token' );

		try {
			if ( isset( $slack_access_token ) || '' != $slack_access_token ) {
				$request_url = self::SLACK_API_BASE_URL . self::SLACK_API_PATH_USER_INFO . '?token=' . $slack_access_token . '&user=' . $user_id;
				$response    = wp_remote_get( $request_url, $params );

				if ( 200 == $response['response']['code'] ) {
					$user_info_body = json_decode( $response['body'], true );
					if ( ! isset( $user_info_body['error'] ) ) {
						$user_name = $user_info_body['user']['profile']['display_name'];
					}
				}
			}
			if ( '' == $user_name ) {
				throw new Slack_Logbot_Exception( __( 'Failed to fetching user name via Slack API.' ), 500 );
			}
		} catch ( Slack_Logbot_Exception $e ) {
			die( $e->getMessage() . '(' . $e->getCode() . ')' );
		}

		return $user_name;
	}
}
