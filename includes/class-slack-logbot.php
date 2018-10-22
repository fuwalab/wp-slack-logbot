<?php
/**
 * Calendar class extended from Base_Calendar class in Combined Calendar
 *
 * @package WordPress
 * @subpackage Calendar Framework
 * @since 1.0.0
 * @version 1.0.0
 */

/**
 * Class SlackLogbot
 */
class SlackLogbot {
	const TABLE_NAME = 'slack_logbot';
	var $slack_logbot_version = '1.0';

	function __construct()
	{
		add_action('rest_api_init', array($this, 'register_api_route'));
	}

	public function register_api_route()
	{
		register_rest_route( 'wp-slack-logbot', '/challenge', array(
			'methods' => 'POST',
			'callback' => array($this, 'challenge'),
		));
		register_rest_route( 'wp-slack-logbot', '/enable_events', array(
			'methods' => 'POST',
			'callback' => array($this, 'enable_events'),
		));
	}

	public function challenge()
	{
		$content_type = explode(';', trim(strtolower($_SERVER['CONTENT_TYPE'])));
		$media_type = $content_type[0];

		if ($_SERVER['REQUEST_METHOD'] == 'POST' && $media_type == 'application/json') {
			$request = json_decode(file_get_contents('php://input'), true);
			$data = $this->prepare_data($request);
			$this->save($data);
		}
	}

	public function enable_events()
	{
		$content_type = explode(';', trim(strtolower($_SERVER['CONTENT_TYPE'])));
		$media_type = $content_type[0];

		if ($_SERVER['REQUEST_METHOD'] == 'POST' && $media_type == 'application/json') {
			$request = json_decode(file_get_contents('php://input'), true);
			if ( isset( $request["challenge"] )) {
				$response = array("challenge" => $request["challenge"] );
			} else {
				$response = "error";
			}
		} else {
			$response = "error";
		}

		return $response;
	}

	private function prepare_data($data)
	{
		$values = array(
			"team_id" => $data["team_id"],
			"type" => $data["type"],
			"api_app_id" => $data["api_app_id"],
			"event_id" => $data["event_id"],
			"event_user" => $data["event"]["user"],
			"event_client_msg_id" => $data["event"]["client_msg_id"],
			"event_type" => $data["event"]["type"],
			"event_text" => isset($data["event"]["text"]) ? $data["event"]["text"] : "",
			"event_channel" => $data["event"]["channel"],
			"event_channel_type" => $data["event"]["channel_type"],
			"event_time" => $data["event_time"],
			"event_datetime" => date("Y-m-d H:i:s", $data["event_time"]),
			"create_date" => date("Y-m-d")
		);

		return $values;
	}

	private function save($data)
	{
		global $wpdb;
		$table_name = $wpdb->prefix . self::TABLE_NAME;

		$wpdb->insert(
			$table_name,
			$data
		);
	}
}
