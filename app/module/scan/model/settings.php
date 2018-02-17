<?php
/**
 * Author: Hoang Ngo
 */

namespace WP_Defender\Module\Scan\Model;

use Hammer\Helper\Log_Helper;
use Hammer\Helper\WP_Helper;
use Hammer\Queue\Queue;
use WP_Defender\Module\Scan\Behavior\Core_Scan;
use WP_Defender\Module\Scan\Behavior\Pro\Content_Scan;
use WP_Defender\Module\Scan\Behavior\Pro\Vuln_Scan;
use WP_Defender\Module\Scan\Component\Scan_Api;

class Settings extends \Hammer\WP\Settings {

	private static $_instance;
	/**
	 * Scan WP core files
	 *
	 * @var bool
	 */
	public $scan_core = true;
	/**
	 * Verify plugins/themes with our db to see if any known bugs
	 * @var bool
	 */
	public $scan_vuln = true;

	/**
	 * @var bool
	 */
	public $scan_content = true;

	/**
	 * Receipts to sending notification
	 * @var array
	 */
	public $receipts = array();

	/**
	 * Toggle notification on or off
	 * @var bool
	 */
	public $notification = false;

	/**
	 * Toggle only sending error email or all email
	 *
	 * @var bool
	 */
	public $always_send = false;

	/**
	 * Maximum filesize to scan, only apply for content scan
	 * @var int
	 */
	public $max_filesize = 1;

	/**
	 * @var string
	 */
	public $email_subject = '';
	/**
	 * @var string|void
	 */
	public $email_has_issue = '';
	/**
	 * @var string|void
	 */
	public $email_all_ok = '';

	/**
	 * @var string
	 */
	public $frequency = '7';
	/**
	 * @var string
	 */
	public $day = 'sunday';
	/**
	 * @var string
	 */
	public $time = '0:00';

	public $lastReportSent;

	/**
	 * @return array
	 */
	public function behaviors() {
		$behaviors = array(
			'utils' => '\WP_Defender\Behavior\Utils'
		);

		if ( wp_defender()->isFree == false ) {
			$behaviors['pro'] = '\WP_Defender\Module\Scan\Behavior\Pro\Model';
		}

		return $behaviors;
	}

	public function __construct( $id, $is_multi ) {
		$this->email_subject   = __( 'Scan of {SITE_URL} complete. {ISSUES_COUNT} issues found.', wp_defender()->domain );
		$this->email_has_issue = __( 'Hi {USER_NAME},

WP Defender here, reporting back from the front.

I\'ve finished scanning {SITE_URL} for vulnerabilities and I found {ISSUES_COUNT} issues that you should take a closer look at!
{ISSUES_LIST}

Stay Safe,
WP Defender
Official WPMU DEV Superhero', wp_defender()->domain );
		$this->email_all_ok    = __( 'Hi {USER_NAME},

WP Defender here, reporting back from the front.

I\'ve finished scanning {SITE_URL} for vulnerabilities and I found nothing. Well done for running such a tight ship!

Keep up the good work! With regular security scans and a well-hardened installation you\'ll be just fine.

Stay safe,
WP Defender
Official WPMU DEV Superhero', wp_defender()->domain );
		//call parent to load stored
		if ( is_admin() || is_network_admin() && current_user_can( 'manage_options' ) ) {
			$this->receipts[] = get_current_user_id();
		}
		parent::__construct( $id, $is_multi );
	}

	/**
	 * Act like a factory, return available scans based on pro or not
	 * @return array
	 */
	public function getScansAvailable() {
		$scans = array();
		if ( $this->scan_core ) {
			$scans[] = 'core';
		}

		if ( $this->scan_vuln && wp_defender()->isFree != true ) {
			$scans[] = 'vuln';
		}

		if ( $this->scan_content && wp_defender()->isFree != true ) {
			$scans[] = 'content';
		}

		return $scans;
	}

	/**
	 * @return Settings
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			$class           = new Settings( 'wd_scan_settings', WP_Helper::is_network_activate( wp_defender()->plugin_slug ) );
			self::$_instance = $class;
		}

		return self::$_instance;
	}

	/**
	 * @param $slug
	 * @param array $args
	 *
	 * @return Queue|null
	 */
	public static function queueFactory( $slug, $args = array() ) {
		switch ( $slug ) {
			case 'core':
				$queue                = new Queue(
					Scan_Api::getCoreFiles(),
					'core',
					true
				);
				$queue->args          = $args;
				$queue->args['owner'] = $queue;
				$queue->attachBehavior( 'core', new Core_Scan() );

				return $queue;
			case 'vuln':
				if ( ! class_exists( '\WP_Defender\Module\Scan\Behavior\Pro\Vuln_Scan' ) ) {
					return null;
				}

				$queue = new Queue( array(
					'dummy'
				), 'vuln', true );

				$queue->args          = $args;
				$queue->args['owner'] = $queue;
				$queue->attachBehavior( 'vuln', new Vuln_Scan() );

				return $queue;
				break;
			case 'content':
				if ( ! class_exists( '\WP_Defender\Module\Scan\Behavior\Pro\Content_Scan' ) ) {
					return null;
				}
				//dont use composer autoload preventing bloating
				$queue                   = new Queue( Scan_Api::getContentFiles(), 'content', true );
				$queue->args             = $args;
				$queue->args['owner']    = $queue;
				$patterns                = Scan_Api::getPatterns();
				$queue->args['patterns'] = $patterns;
				$queue->attachBehavior( 'content', new Content_Scan() );

				return $queue;
				break;
		}
	}
}