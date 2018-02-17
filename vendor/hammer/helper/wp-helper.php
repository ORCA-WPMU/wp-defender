<?php
/**
 * Author: Hoang Ngo
 */

namespace Hammer\Helper;

use Hammer\Base\Container;
use Hammer\Caching\Array_Cache;
use Hammer\Caching\DB_Cache;
use Hammer\Caching\Memcached_Cache;

class WP_Helper {
	/**
	 * Check if this plugin activate for network wide
	 *
	 * @param $slug
	 *
	 * @return bool
	 */
	public static function is_network_activate( $slug ) {
		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		}

		return is_plugin_active_for_network( $slug );
	}

	/**
	 * @return mixed
	 */
	public static function getUploadDir() {
		$uploadDir = wp_upload_dir();

		return $uploadDir['basedir'];
	}

	/**
	 * @return mixed
	 */
	public static function getUploadUrl() {
		$uploadDir = wp_upload_dir();

		return $uploadDir['baseurl'];
	}

	/**
	 * @return bool|Array_Cache
	 */
	public static function getArrayCache() {
		return Container::instance()->get( 'cache_alt' );
	}

	/**
	 * @return bool|DB_Cache|Memcached_Cache
	 */
	public static function getCache() {
		return Container::instance()->get( 'cache' );
	}
}