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
	 * Update or Insert log data into wp_posts.
	 *
	 * @throws Slack_Logbot_Exception If provided unexpected response from slack api.
	 * @param array $data slack log data.
	 */
	public function upsert_post( $data ) {
		global $wpdb;

		$slack_api    = new Slack_API();
		$team         = $slack_api::$team_info;
		$channel_name = $slack_api::request( $slack_api::SLACK_API_PATH_CONVERSATION_INFO, array( 'channel_id' => $data['event_channel'] ) );

		list( $parent_category_id, $category_id ) = $this->get_category_ids( $team['domain'], $channel_name );

		$parent_category = $this->get_category( $team['domain'], null, $parent_category_id );
		$category        = $this->get_category( $channel_name, $team['domain'], $category_id, $parent_category['cat_ID'] );

		if ( file_exists( ABSPATH . '/wp-admin/includes/taxonomy.php' ) ) {
			require_once( ABSPATH . '/wp-admin/includes/taxonomy.php' );
		}

		$parent_category_id = wp_insert_category( $parent_category );
		$category_id        = wp_insert_category( $category );

		$user_name    = $slack_api::request( $slack_api::SLACK_API_PATH_USER_INFO, array( 'user_id' => $data['event_user'] ) );
		$wp_user_id   = get_current_user_id() > 0 ? get_current_user() : 1;
		$post_title   = $this->generate_post_title( $data, $channel_name );
		$current_date = get_date_from_gmt( date( 'Y-m-d H:i:s' ), 'Y-m-d' );

		$result = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $wpdb->posts WHERE post_date > %s AND post_title = %s ORDER BY ID ASC LIMIT 1",
				array( $current_date, $post_title )
			),
			ARRAY_A
		);

		$post_id      = count( $result ) > 0 ? $result[0]['ID'] : 0;
		$post_content = $this->generate_post_content_html( $data, $channel_name, $user_name, $result );

		$post = array(
			'ID'            => $post_id,
			'post_title'    => $post_title,
			'post_content'  => $post_content,
			'post_status'   => 'publish',
			'post_author'   => $wp_user_id,
			'post_category' => array( $parent_category_id, $category_id ),
		);
		$this->save_wp_post( $post );
	}

	/**
	 * Get category id and parent_category_id.
	 *
	 * @param string $team_domain slack team domain.
	 * @param string $channel_name slack channel name.
	 * @return array array of category ids.
	 */
	private function get_category_ids( $team_domain, $channel_name ) {
		$parent_category_id = 0;
		$category_id        = 0;

		$slug  = $channel_name . '_' . $team_domain;
		$args  = array( 'hide_empty' => 0 );
		$terms = get_terms( 'category', $args );
		foreach ( $terms as $term ) {
			if ( $team_domain == $term->slug ) {
				$parent_category_id = $term->term_id;
			}
			if ( $slug == $term->slug ) {
				$category_id = $term->term_id;
			}
		}

		return array( $parent_category_id, $category_id );
	}

	/**
	 * Get category content.
	 *
	 * @param string      $cat_name category name.
	 * @param string|null $team_domain slack team domain.
	 * @param int         $category_id category id.
	 * @param int         $parent_category_id parent category id.
	 * @return array category content.
	 */
	private function get_category( $cat_name, $team_domain = null, $category_id = 0, $parent_category_id = 0 ) {
		$slug = $team_domain ? $cat_name . '_' . $team_domain : $cat_name;
		return array(
			'cat_ID'            => $category_id,
			'cat_name'          => $cat_name,
			'category_nicename' => $slug,
			'taxonomy'          => 'category',
			'category_parent'   => $parent_category_id,
		);
	}

	/**
	 * Insert or update wp_posts.
	 *
	 * @param array $post post data to save wp_posts.
	 */
	private function save_wp_post( $post ) {
		remove_action( 'post_updated', 'wp_save_post_revision' );
		$post['ID'] > 0 ? wp_update_post( $post ) : wp_insert_post( $post );
		add_action( 'post_updated', 'wp_save_post_revision' );
	}

	/**
	 * Generate post title.
	 *
	 * @param array  $data slack log data.
	 * @param string $channel_name slack channel name.
	 * @return string title of blog post.
	 */
	private function generate_post_title( $data, $channel_name ) {
		$post_title  = '[Slack Log] ' . $channel_name . '( ';
		$post_title .= get_date_from_gmt( date( 'Y-m-d H:i:s', $data['event_time'] ), get_option( 'date_format' ) );
		$post_title .= ' )';

		return $post_title;
	}

	/**
	 * Generate post content HTML.
	 *
	 * @param array  $data slack log data.
	 * @param string $channel_name slack channel name.
	 * @param string $user_name slack user name.
	 * @param array  $result wp post data.
	 * @return mixed|string post content HTML.
	 */
	private function generate_post_content_html( $data, $channel_name, $user_name, $result ) {
		$post_content = '<h2>' . $channel_name . '</h2>';

		if ( count( $result ) > 0 ) {
			$post_content = $result[0]['post_content'];
			$post_content = str_replace( '</ul>', '', $post_content );
		} else {
			$post_content .= '<ul>';
		}

		$msg_id = $data['event_client_msg_id'];

		// System message will be null.
		if ( $msg_id ) {
			$post_content .= '<li id="' . $msg_id . '">';
		} else {
			$post_content .= '<li>';
		}
		$post_content .= get_date_from_gmt( $data['event_datetime'], get_option( 'time_format' ) ) . ' ';
		$post_content .= '@' . $user_name . ' ';
		$post_content .= esc_attr( $data['event_text'] );
		$post_content .= '</li></ul>';

		return $post_content;
	}

	/**
	 * Prepare data will be saved.
	 *
	 * @param array $data Post data.
	 * @return array $values
	 */
	public function prepare_data( $data ) {
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
	 * @return mixed 1 or false.
	 */
	public function save( $data ) {
		global $wpdb;
		$table_name = $wpdb->prefix . self::TABLE_NAME;

		$result = $wpdb->insert(
			$table_name,
			$data
		);

		return $result;
	}
}
