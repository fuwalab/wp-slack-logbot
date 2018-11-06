<?php
/**
 * Class Slack_LogbotTest
 *
 * @package Wp_Slack_Logbot
 */

use wp_slack_logbot\Slack_Logbot;
use wp_slack_logbot\WP_Slack_Logbot;
use wp_slack_logbot\Slack_Logbot_Admin;

/**
 * Class Slack_Logbot_Test
 */
class Slack_Logbot_Test extends WP_UnitTestCase {
	/**
	 * Instance of lack logbot.
	 *
	 * @var Slack_Logbot $slack_logbot
	 */
	protected $slack_logbot;

	/**
	 * Preparation of Slack_Logbot_Test
	 *
	 * @since 1.0.0
	 */
	public function setUp() {
		parent::setUp();
		$this->slack_logbot = new Slack_Logbot();
		$this->create_table();
	}

	/**
	 * Test if it's created table.
	 *
	 * @since 1.0.0
	 */
	function test_if_created_table() {
		global $wpdb;

		$expected = '0';
		$actual   = $wpdb->get_var( "select count(*) from {$wpdb->prefix}slack_logbot" );
		$this->assertSame( $expected, $actual );
	}

	/**
	 * Test if it can insert and select data.
	 *
	 * @since 1.0.0
	 */
	function test_save() {
		global $wpdb;

		$slack_logbot = $this->slack_logbot;
		$data         = $this->get_slack_post();
		$slack_logbot->save( $data );

		$result = $wpdb->get_row( "select * from {$wpdb->prefix}slack_logbot limit 1" );

		$this->assertSame( $result->id, '1' );
		$this->assertSame( $data['team_id'], $result->team_id );
		$this->assertSame( $data['type'], $result->type );
		$this->assertSame( $data['api_app_id'], $result->api_app_id );
		$this->assertSame( $data['event_id'], $result->event_id );
		$this->assertSame( $data['event_user'], $result->event_user );
		$this->assertSame( $data['event_client_msg_id'], $result->event_client_msg_id );
		$this->assertSame( $data['event_type'], $result->event_type );
		$this->assertSame( $data['event_text'], $result->event_text );
		$this->assertSame( $data['event_channel'], $result->event_channel );
		$this->assertSame( $data['event_channel_type'], $result->event_channel_type );
		$this->assertSame( $data['event_time'], (int) $result->event_time );
		$this->assertSame( $data['event_datetime'], $result->event_datetime );
		$this->assertSame( $data['create_date'], $result->create_date );
	}

	/**
	 * Test saving wp_post.
	 *
	 * @throws ReflectionException Reflection exception.
	 * @since 1.0.0
	 */
	function test_save_post() {
		global $wpdb;

		$reflection = new \ReflectionClass( $this->slack_logbot );

		// Make category first.
		$method = $reflection->getMethod( 'get_category' );
		$method->setAccessible( true );
		$parent_category    = $method->invoke( $this->slack_logbot, 'Sample Team', 'team_domain' );
		$parent_category_id = wp_insert_category( $parent_category );
		$category           = $method->invoke( $this->slack_logbot, 'sample channel', 'team_domain', 0, $parent_category_id );
		$category_id        = wp_insert_category( $category );

		$this->assertTrue( $parent_category_id > 0 );
		$this->assertTrue( $category_id > 0 );

		// Prepare wp post.
		$wp_user_id = get_current_user_id() > 0 ? get_current_user() : 1;
		$post       = array(
			'ID'            => 0,
			'post_title'    => '[Slack Log] sample channel( 2018-10-15 )',
			'post_content'  => 'sample post message.',
			'post_status'   => 'publish',
			'post_author'   => $wp_user_id,
			'post_category' => array( $parent_category_id, $category_id ),
		);

		// Save wp post.
		$method = $reflection->getMethod( 'save_wp_post' );
		$method->setAccessible( true );
		$method->invoke( $this->slack_logbot, $post );

		$result = $wpdb->get_results( "select * from $wpdb->posts", ARRAY_A );

		$this->assertSame( count( $result ), 1 );
		$this->assertSame( $result[0]['post_title'], '[Slack Log] sample channel( 2018-10-15 )' );
		$this->assertSame( $result[0]['post_content'], 'sample post message.' );
	}

	/**
	 * Test if json decoded array from slack returns correct array data.
	 *
	 * @since 1.0.0
	 */
	function test_prepare_data() {
		$slack_logbot = $this->slack_logbot;

		$json_string = '{"authed_users": ["XXX1234", "MMEOAD345"], "token": "XXXabckekejlasdgKSH", "team_id": "X22345O", "type": "event_callback", "event": {"ts": "1539570604.000100", "client_msg_id": "06e8b044-e420-4879-bf44-3f762dc8eecf", "user": "UADJETKV9", "event_ts": "1539570604.000100", "type": "message", "channel": "CDFBSHT46", "text": "dummy message.", "channel_type": "channel"}, "event_time": 1539570604, "api_app_id": "ACXXXJEA1", "event_id": "EvDD486j8"}';
		$data_array  = json_decode( $json_string, true );
		$actual_data = $slack_logbot->prepare_data( $data_array );

		$expected_data = $this->get_slack_post();

		$this->assertEquals( $expected_data, $actual_data );
	}

	/**
	 * Test if wp post title is correct.
	 *
	 * @throws ReflectionException Reflection exception.
	 * @since 1.0.0
	 */
	function test_generate_post_title() {
		$data = $this->get_slack_post();

		$reflection = new \ReflectionClass( $this->slack_logbot );
		$method     = $reflection->getMethod( 'generate_post_title' );
		$method->setAccessible( true );
		$title    = $method->invoke( $this->slack_logbot, $data, 'sample channel' );
		$expected = '[Slack Log] sample channel( October 15, 2018 )';

		$this->assertSame( $title, $expected );
	}

	/**
	 * Test if wp post content is correct.
	 *
	 * @throws ReflectionException Reflection exception.
	 * @since 1.0.0
	 */
	function test_generate_post_content_html() {
		$data = $this->get_slack_post();

		// In case there is no post.
		$result   = array();
		$expected = '<h2>sample channel</h2><ul><li id="06e8b044-e420-4879-bf44-3f762dc8eecf">2:30 am dummy message. @test user</li></ul>';

		$reflection = new \ReflectionClass( $this->slack_logbot );
		$method     = $reflection->getMethod( 'generate_post_content_html' );
		$method->setAccessible( true );
		$content = $method->invoke( $this->slack_logbot, $data, 'sample channel', 'test user', $result );

		$this->assertSame( $content, $expected );

		// In case post is already existing.
		$result             = array(
			array(
				'post_content' => $content,
			),
		);
		$expected           = '<h2>sample channel</h2><ul><li id="06e8b044-e420-4879-bf44-3f762dc8eecf">2:30 am dummy message. @test user</li><li id="06e8b044-e420-4879-bf44-3f762dc8eecf">2:30 am connecting continuous message. @another user</li></ul>';
		$data['event_text'] = 'connecting continuous message.';
		$content            = $method->invoke( $this->slack_logbot, $data, 'sample channel', 'another user', $result );

		$this->assertSame( $content, $expected );
	}

	/**
	 * Test if error message is correct.
	 */
	function test_error_massage_in_admin() {
		// In case of access token is missing.
		ob_start();
		$admin = new Slack_Logbot_Admin();
		$admin->show_error_message();
		$message_empty  = ob_get_contents();
		$expected_empty = 'Please set Access Token of your Slack bot.';
		ob_end_clean();

		// In case of access token is wrong.
		ob_start();
		// Set token.
		add_option( 'wp-slack-logbot-bot-user-oauth-access-token', 'dummy token' );
		$admin = new Slack_Logbot_Admin();
		$admin->show_error_message();
		$message_wrong  = ob_get_contents();
		$expected_wrong = 'invalid_auth';
		ob_end_clean();

		$this->assertContains( $expected_empty, $message_empty );
		$this->assertContains( $expected_wrong, $message_wrong );
	}

	/**
	 * Test if reset options.
	 */
	function test_uninstall() {
		WP_Slack_Logbot::uninstall();

		$version = get_option( 'slack_logbot_version' );
		$token   = get_option( 'wp-slack-logbot-bot-user-oauth-access-token' );
		$this->assertFalse( $version );
		$this->assertFalse( $token );
	}

	/**
	 * Create required table.
	 *
	 * @since 1.0.0
	 */
	private function create_table() {
		$wp_slack_logbot = new WP_Slack_Logbot();
		$wp_slack_logbot->install();
	}

	/**
	 * Get sample slack post.
	 *
	 * @since 1.0.0
	 * @return array slack post
	 */
	private function get_slack_post() {
		return array(
			'team_id'             => 'X22345O',
			'type'                => 'event_callback',
			'api_app_id'          => 'ACXXXJEA1',
			'event_id'            => 'EvDD486j8',
			'event_user'          => 'UADJETKV9',
			'event_client_msg_id' => '06e8b044-e420-4879-bf44-3f762dc8eecf',
			'event_type'          => 'message',
			'event_text'          => 'dummy message.',
			'event_channel'       => 'CDFBSHT46',
			'event_channel_type'  => 'channel',
			'event_time'          => 1539570604,
			'event_datetime'      => '2018-10-15 02:30:04',
			'create_date'         => '2018-10-15',
		);
	}
}
