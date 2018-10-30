<?php
/**
 * Slack_Logbot_Exception class
 *
 * @package WordPress
 * @subpackage WP Slack Logbot
 * @since 1.0.0
 * @version 1.0.0
 */

namespace wp_slack_logbot;
use Throwable;

/**
 * Class Slack_Logbot_Exception
 *
 * @package wp_slack_logbot
 */
class Slack_Logbot_Exception extends \Exception {
	/**
	 * Slack_Logbot_Exception constructor.
	 *
	 * @param string         $message error message.
	 * @param int            $code status code.
	 * @param Throwable|null $previous throwable.
	 */
	function __construct( $message = '', $code = 0, Throwable $previous = null ) {
		parent::__construct( $message, $code, $previous );
	}
}
