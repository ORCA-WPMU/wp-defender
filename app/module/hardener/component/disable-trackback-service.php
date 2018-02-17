<?php
/**
 * Author: Hoang Ngo
 */

namespace WP_Defender\Module\Hardener\Component;

use Hammer\Base\Container;
use Hammer\Helper\WP_Helper;
use WP_Defender\Module\Hardener\IRule_Service;
use WP_Defender\Module\Hardener\Model\Settings;
use WP_Defender\Module\Hardener\Rule_Service;

class Disable_Trackback_Service extends Rule_Service implements IRule_Service {
	const CACHE_KEY = 'disable_trackback';
	const PROCESS_POSTS_KEYS = 'disable_trackback_post_keys';
	public $process_posts;

	/**
	 * @return bool
	 */
	public function process() {
		//first need to cache the status
		Settings::instance()->setDValues( self::CACHE_KEY, 1 );
		Settings::instance()->setDValues( self::PROCESS_POSTS_KEYS, $this->process_posts );
		$this->handle_change( false, ( $this->process_posts === 'yes' ) );
		return true;
	}

	/**
	 * @return bool
	 */
	public function revert() {
		Settings::instance()->setDValues( self::CACHE_KEY, 0 );
		$process_posts = Settings::instance()->getDValues( self::PROCESS_POSTS_KEYS );
		Settings::instance()->setDValues( self::PROCESS_POSTS_KEYS, 'no' );
		//Only check posts if it was previously true
		$process = false;
		if ( !empty( $process_posts ) && $process_posts === 'yes' ) {
			$process = true;
		}
		$this->handle_change( true, $process );
		return true;
	}

	/**
	 * @return mixed
	 */
	public function check() {
		$key = Settings::instance()->getDValues( self::CACHE_KEY );

		return $key == 1;
	}

	/**
	 * Handle WordPress Settings Changes
	 *
	 * @param Boolean $revert - set to true to revert changes
	 * @param Boolean $handle_posts - set to true to also update posts
	 */
	private function handle_change( $revert = false, $handle_posts = false ) {
		global $wpdb;
		if ( is_multisite() ) {

			$blogs = $wpdb->get_results( "SELECT blog_id FROM {$wpdb->blogs} WHERE
						site_id = '{$wpdb->siteid}'
        				AND spam = '0'
        				AND deleted = '0'
        				AND archived = '0'
        				AND mature = '0'
        				AND public = '1'
    				" );
			foreach ( $blogs as $blog ) {
				update_blog_option( $blog->blog_id, 'default_pingback_flag', ( $revert ) ? 1 : 0 );
				update_blog_option( $blog->blog_id, 'default_ping_status', ( $revert ) ? 'open' : 'closed' );
				if ( $handle_posts ) {
					$this->posts_ping_status( $wpdb, $revert, $blog->blog_id );
				}
			}

		} else {
			update_option( 'default_pingback_flag', ( $revert ) ? 1 : 0 );
			update_option( 'default_ping_status', ( $revert ) ? 'open' : 'closed' );
			if ( $handle_posts ) {
				$this->posts_ping_status( $wpdb, $revert, 0 );
			}
		}
	}

	/**
	 * Update the post ping status
	 *
	 * @param Boolean $revert - set to true to revert changes
	 * @param Int $blog_id - the blog id if multisite
	 *
	 */
	private function posts_ping_status( $wpdb, $revert = false, $blog_id = 0 ) {
		$defender_post_types = array( 'wd_ip_lockout', 'wd_iplockout_log' );
		$defender_post_types = "'" . implode( "','", $defender_post_types ) . "'";
		$ping_status = ( $revert ) ? 'open' : 'closed';
		if ( $blog_id > 0 ) {
			$wpdb->set_blog_id( $blog_id );
			$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->posts} SET `ping_status` = %s WHERE `post_status` != %s AND `post_type` NOT IN(%s)", $ping_status, 'inherit', $defender_post_types ) );
		} else {

			$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->posts} SET `ping_status` = %s WHERE `post_status` != %s AND `post_type` NOT IN(%s)", $ping_status, 'inherit', $defender_post_types ) );	 		 	 	 	 		  		 			
		}
	}
}