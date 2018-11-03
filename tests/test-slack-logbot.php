<?php
/**
 * Class Slack_LogbotTest
 *
 * @package Wp_Slack_Logbot
 */

use wp_slack_logbot\Slack_Logbot;
use wp_slack_logbot\WP_Slack_Logbot;

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

		$slack_logbot = $this->slack_logbot;
		$table_name   = $wpdb->prefix . $slack_logbot::TABLE_NAME;

		$expected = '0';
		$actual   = $wpdb->get_var( "select count(*) from $table_name" );
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
		$table_name   = $wpdb->prefix . $slack_logbot::TABLE_NAME;

		$data = $this->get_slack_post();
		$slack_logbot->save( $data );

		$result = $wpdb->get_row( "select * from $table_name limit 1" );

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
		$parent_category    = $method->invoke( $this->slack_logbot, 'Sample Team' );
		$parent_category_id = wp_insert_category( $parent_category );
		$category           = $method->invoke( $this->slack_logbot, 'sample channel', 0, $parent_category_id );
		$category_id        = wp_insert_category( $category );

		$this->assertTrue( $parent_category_id > 0 );
		$this->assertTrue( $category_id > 0 );

		// Prepare wp post.
		$wp_user_id = get_current_user_id() > 0 ? get_current_user() : 1;
		$post       = array(
			'ID'            => 0,
			'post_title'    => '[Slack Logbot] sample channel(2018-10-15)',
			'post_content'  => 'sample post message.',
			'post_status'   => 'publish',
			'post_author'   => $wp_user_id,
			'post_category' => array( $parent_category_id, $category_id ),
		);

		// Save wp post.
		$method = $reflection->getMethod( 'save_wp_post' );
		$method->setAccessible( true );
		$method->invoke( $this->slack_logbot, $post );

		$table_name = $wpdb->prefix . 'posts';
		$result     = $wpdb->get_results( "select * from $table_name", ARRAY_A );

		$this->assertSame( count( $result ), 1 );
		$this->assertSame( $result[0]['post_title'], '[Slack Logbot] sample channel(2018-10-15)' );
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
