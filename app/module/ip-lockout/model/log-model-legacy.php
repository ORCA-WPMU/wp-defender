<?php

/**
 * Author: Hoang Ngo
 */

namespace WP_Defender\Module\IP_Lockout\Model;

use Hammer\Helper\WP_Helper;
use Hammer\WP\Model;
use WP_Defender\Behavior\Utils;
use WP_Defender\Module\IP_Lockout\Component\Login_Protection_Api;
use WP_Defender\Module\IP_Lockout\Component\Logs_Table;

/**
 * Class Log_Model_Legacy
 * @package WP_Defender\Module\IP_Lockout\Model
 * @deprecated 1.7
 */
class Log_Model_Legacy extends Model {
	const AUTH_FAIL = 'auth_fail', AUTH_LOCK = 'auth_lock', ERROR_404 = '404_error', LOCKOUT_404 = '404_lockout', ERROR_404_IGNORE = '404_error_ignore';
	static $post_type = 'wd_iplockout_log';

	public $id;
	public $log;
	public $ip;
	public $date;
	public $user_agent;
	public $type;
	public $blog_id;

	protected static function maps() {
		return array(
			'id'         => array(
				'type' => 'wp',
				'map'  => 'ID'
			),
			'log'        => array(
				'type' => 'meta',
				'map'  => 'log'
			),
			'ip'         => array(
				'type' => 'meta',
				'map'  => 'ip'
			),
			'date'       => array(
				'type' => 'meta',
				'map'  => 'date'
			),
			'type'       => array(
				'type' => 'meta',
				'map'  => 'type'
			),
			'user_agent' => array(
				'type' => 'meta',
				'map'  => 'user_agent'
			),
			'blog_id'    => array(
				'type' => 'meta',
				'map'  => 'blog_id'
			),
		);
	}

	/**
	 * @return string
	 */
	public function get_ip() {
		return esc_html( $this->ip );
	}

	/**
	 * @return string
	 */
	public function get_log_text( $format = false ) {
		if ( ! $format ) {
			return esc_html( $this->log );
		} else {
			$text = sprintf( __( "Request for file <span class='log-text-table' tooltip='%s'>%s</span> which doesn't exist", wp_defender()->domain ), esc_attr( $this->log ), pathinfo( $this->log, PATHINFO_BASENAME ) );

			return $text;
		}
	}

	public function before_update() {
		$this->blog_id = get_current_blog_id();
		if ( Utils::instance()->isActivatedSingle() == false ) {
			switch_to_blog( 1 );
		}
	}

	public function before_insert() {
		$this->blog_id = get_current_blog_id();
		if ( Utils::instance()->isActivatedSingle() == false ) {
			switch_to_blog( 1 );
		}
	}

	/**
	 * @return string
	 */
	public function get_date() {
		return Utils::instance()->formatDateTime( date( 'Y-m-d H:i:s', $this->date ) );
	}

	/**
	 * @return mixed|null
	 */
	public function get_type() {
		$types = array(
			'auth_fail'        => __( "Failed login attempts", wp_defender()->domain ),
			'auth_lock'        => __( "Login lockout", wp_defender()->domain ),
			'404_error'        => __( "404 error", wp_defender()->domain ),
			'404_error_ignore' => __( "404 error", wp_defender()->domain ),
			'404_lockout'      => __( "404 lockout", wp_defender()->domain )
		);

		if ( isset( $types[ $this->type ] ) ) {
			return $types[ $this->type ];
		}

		return null;
	}

	/**
	 * @return array
	 */
	public function events() {
		$that = $this;

		return array(
			self::EVENT_BEFORE_INSERT => array(
				array(
					function () use ( $that ) {
						$that->before_insert();
					}
				)
			),
			self::EVENT_BEFORE_UPDATE => array(
				array(
					function () use ( $that ) {
						$that->before_update();
					}
				)
			),
			self::EVENT_AFTER_INSERT  => array(
				array(
					function () use ( $that ) {
						if ( Utils::instance()->isActivatedSingle() == false ) {
							switch_to_blog( $that->blog_id );
						}
					}
				)
			),
			self::EVENT_AFTER_UPDATE  => array(
				array(
					function () use ( $that ) {
						if ( Utils::instance()->isActivatedSingle() == false ) {
							switch_to_blog( $that->blog_id );
						}
					}
				)
			)
		);
	}
}