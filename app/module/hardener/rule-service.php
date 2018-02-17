<?php
/**
 * Author: Hoang Ngo
 */

namespace WP_Defender\Module\Hardener;

use Hammer\Base\Component;
use Hammer\Base\Container;
use WP_Defender\Module\Hardener;

class Rule_Service extends Component {
	/**
	 * Attach Utils to all component rules
	 *
	 * @return array
	 */
	public function behaviors() {
		return array(
			'utils' => '\WP_Defender\Behavior\Utils'
		);
	}

	/**
	 * @param $curr_status
	 * @param $slug
	 */
	protected function store( $curr_status, $slug ) {
		$settings = Hardener\Model\Settings::instance();
		switch ( $curr_status ) {
			case 'fixed':
				$settings->addToResolved( $slug );
				break;
			case 'ignore':
				$settings->addToIgnore( $slug );
				break;
			case 'issue':
				$settings->addToIssues( $slug );
				break;
		}
	}

	/**
	 * @param $slug
	 */
	public function ignore( $slug ) {
		self::store( 'ignore', $slug );
	}

	/**
	 * A helper function for child class
	 * @return string
	 */
	public function retrieveWPConfigPath() {
		if ( file_exists( ABSPATH . 'wp-config.php' ) ) {
			return ( ABSPATH . 'wp-config.php' );
		} elseif ( @file_exists( dirname( ABSPATH ) . '/wp-config.php' ) && ! @file_exists( dirname( ABSPATH ) . '/wp-settings.php' ) ) {
			return ( dirname( ABSPATH ) . '/wp-config.php' );
		} elseif ( defined( 'WD_TEST' ) && constant( 'WD_TEST' ) == true ) {
			//case tests
			return '/tmp/wordpress-tests-lib/wp-tests-config.php';
		}
	}

	/**
	 * @param $config
	 *
	 * @return bool|int|string
	 */
	protected function findDefaultHookLine( $config ) {
		global $wpdb;
		$pattern = '/^\$table_prefix\s*=\s*[\'|\"]' . $wpdb->prefix . '[\'|\"]/';
		foreach ( $config as $k => $line ) {
			if ( preg_match( $pattern, $line ) ) {
				return $k;
			}
		}

		return false;
	}
}