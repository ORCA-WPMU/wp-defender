<?php

/**
 * Author: Hoang Ngo
 */

namespace WP_Defender\Module\IP_Lockout\Model;

use Hammer\WP\Model;
use WP_Defender\Behavior\Utils;

/**
 * Class IP_Model
 * @package WP_Defender\Module\IP_Lockout\Model
 * @deprecated 1.7
 */
class IP_Model_Legacy extends Model {
	const STATUS_BLOCKED = 'blocked', STATUS_NORMAL = 'normal';

	static $post_type = 'wd_ip_lockout';

	public $id;
	public $ip;
	public $status;
	public $lockout_message;
	public $release_time;
	public $lock_time;
	public $lock_time_404;
	public $attempt;
	public $attempt_404;

	protected static function maps() {
		return array(
			'id'              => array(
				'type' => 'wp',
				'map'  => 'ID'
			),
			'ip'              => array(
				'type' => 'meta',
				'map'  => 'ip'
			),
			'status'          => array(
				'type' => 'meta',
				'map'  => 'status'
			),
			'lockout_message' => array(
				'type' => 'meta',
				'map'  => 'lockout_message'
			),
			'release_time'    => array(
				'type' => 'meta',
				'map'  => 'release_time'
			),
			'lock_time'       => array(
				'type' => 'meta',
				'map'  => 'lock_time'
			),
			'lock_time_404'   => array(
				'type' => 'meta',
				'map'  => 'lock_time_404'
			),
			'attempt'         => array(
				'type' => 'meta',
				'map'  => 'attempt'
			),
		);
	}


	/**
	 * @return bool
	 */
	public function is_locked() {
		if ( $this->status == self::STATUS_BLOCKED ) {
			if ( $this->release_time < time() ) {
				//unlock it
				$this->attempt = 0;
				$this->status  = self::STATUS_NORMAL;
				$this->save();

				return false;
			} else {
				return true;
			}
		}

		return false;
	}


	/**
	 * @return array
	 */
	public function events() {
		$that = $this;

		//becase we store all the logs in main blog, so in multisite we need to force to main when saving
		return array(
			self::EVENT_BEFORE_INSERT  => array(
				array(
					function () use ( $that ) {
						if ( Utils::instance()->isActivatedSingle() == false ) {
							wp_defender()->global['oldBlog'] = get_current_blog_id();
							switch_to_blog( 1 );
						}
					}
				)
			),
			self::EVENT_BEFORE_UPDATE  => array(
				array(
					function () use ( $that ) {
						if ( Utils::instance()->isActivatedSingle() == false ) {
							wp_defender()->global['oldBlog'] = get_current_blog_id();
							switch_to_blog( 1 );
						}
					}
				)
			),
			self::EVENT_AFTER_INSERT   => array(
				array(
					function () use ( $that ) {
						if ( Utils::instance()->isActivatedSingle() == false ) {
							if ( isset( wp_defender()->global['oldBlog'] ) ) {
								switch_to_blog( wp_defender()->global['oldBlog'] );
								unset( wp_defender()->global['oldBlog'] );
							}
						}
					}
				)
			),
			self::EVENT_AFTER_UPDATE   => array(
				array(
					function () use ( $that ) {
						if ( Utils::instance()->isActivatedSingle() == false ) {
							if ( isset( wp_defender()->global['oldBlog'] ) ) {
								switch_to_blog( wp_defender()->global['oldBlog'] );
								unset( wp_defender()->global['oldBlog'] );
							}
						}
					}
				)
			),
			self::EVENT_BEFORE_DELELTE => array(
				array(
					function () use ( $that ) {
						if ( Utils::instance()->isActivatedSingle() == false ) {
							wp_defender()->global['oldBlog'] = get_current_blog_id();
							switch_to_blog( 1 );
						}
					}
				)
			),
			self::EVENT_AFTER_DELETE   => array(
				array(
					function () use ( $that ) {
						if ( Utils::instance()->isActivatedSingle() == false ) {
							if ( isset( wp_defender()->global['oldBlog'] ) ) {
								switch_to_blog( wp_defender()->global['oldBlog'] );
								unset( wp_defender()->global['oldBlog'] );
							}
						}
					}
				)
			),
		);
	}
}