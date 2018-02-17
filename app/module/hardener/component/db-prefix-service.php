<?php
/**
 * Author: Hoang Ngo
 */

namespace WP_Defender\Module\Hardener\Component;

use Hammer\Helper\Log_Helper;
use Hammer\Helper\WP_Helper;
use WP_Defender\Behavior\Utils;
use WP_Defender\Component\Error_Code;
use WP_Defender\Module\Hardener\IRule_Service;
use WP_Defender\Module\Hardener\Rule_Service;

class DB_Prefix_Service extends Rule_Service implements IRule_Service {
	public $new_prefix;
	protected $old_prefix;

	/**
	 * @return bool
	 */
	public function check() {
		global $wpdb;

		return $wpdb->prefix != 'wp_';
	}

	public function process() {
		$config_path = $this->retrieveWPConfigPath();
		if ( ! is_writeable( $config_path ) ) {
			return new \WP_Error( Error_Code::NOT_WRITEABLE,
				sprintf( __( "The file %s is not writeable", wp_defender()->domain ), $config_path ) );
		}

		$hook_line = $this->findDefaultHookLine( file( $config_path ) );
		if ( $hook_line === false ) {
			return new \WP_Error( Error_Code::UNKNOWN_WPCONFIG, __( "Your wp-config.php was modified by a 3rd party, this will cause conflict with Defender. Please revert it to original for updating your database prefix", wp_defender()->domain ) );
		}

		if ( ! Utils::instance()->isActivatedSingle() ) {
			//validate if this network is too big, then we will prevent it
			$sites = get_sites( array(
				'count' => true
			) );

			if ( $sites >= 100 ) {
				return new \WP_Error( Error_Code::VALIDATE, __( "Unfortunately it's not safe to do this via a plugin for larger WordPress Multisite installs. You can ignore this step, or follow a tutorial online on how to use a scalable tool like WP-CLI.", wp_defender()->domain ) );
			}
		}
		if ( is_wp_error( $is_valid = $this->validatePrefix() ) ) {
			return $is_valid;
		}

		$prefix = $this->new_prefix;
		set_time_limit( - 1 );
		//add trailing underscore if not present
		if ( substr( $prefix, - 1 ) != '_' ) {
			$this->new_prefix .= '_';
		}

		global $wpdb;
		$wpdb->query( 'BEGIN' );
		//run a query to change db prefix
		if ( is_wp_error( ( $err = $this->changeDBPrefix() ) ) ) {
			return $err;
		}
		//update data
		if ( is_wp_error( ( $err = $this->updateData() ) ) ) {
			return $err;
		}
		//almost there, now just need to update wpconfig
		if ( is_wp_error( ( $err = $this->writeToWpConfig() ) ) ) {
			$wpdb->query( 'ROLLBACK' );

			return $err;
		}
		//all good
		$wpdb->query( 'COMMIT' );

		return true;
	}

	/**
	 * @return bool|\WP_Error
	 */
	private function writeToWpConfig() {
		$config_path          = $this->retrieveWpConfigPath();
		$config               = file( $config_path );
		$hook_line            = $this->findDefaultHookLine( $config );
		$new_prefix           = "\$table_prefix = '" . $this->new_prefix . "';" . PHP_EOL;
		$config[ $hook_line ] = $new_prefix;
		if ( ! file_put_contents( $config_path, implode( null, $config ), LOCK_EX ) ) {
			//should not happen
			return new \WP_Error( Error_Code::NOT_WRITEABLE,
				sprintf( __( "The file %s is not writeable", wp_defender()->domain ), $config_path ) );
		}

		return true;
	}

	/**
	 * @return bool|\WP_Error
	 */
	private function updateData() {
		global $wpdb;
		$prefix     = $this->new_prefix;
		$old_prefix = $this->old_prefix;

		if ( is_multisite() ) {
			/**
			 * case multiste
			 * in multisite, blog options will have a option name $prefix_id_user_roles, we have to update this or
			 * we will have issue with roles
			 */
			$sql   = "SELECT blog_id FROM `{$prefix}blogs`";
			$blogs = $wpdb->get_col( $sql );
			if ( is_array( $blogs ) && count( $blogs ) ) {
				foreach ( $blogs as $blog_id ) {
					if ( $blog_id == 1 ) {
						continue;
					}
					$sql = "UPDATE `{$prefix}{$blog_id}_options` SET option_name=%s WHERE option_name=%s";
					$sql = $wpdb->prepare( $sql, $prefix . $blog_id . '_user_roles', $old_prefix . $blog_id . '_user_roles' );
					if ( $wpdb->query( $sql ) == false ) {
						return new \WP_Error( Error_Code::SQL_ERROR, $wpdb->last_error );
					}
				}
			}
		}
		//now update the main blog
		$sql = "UPDATE `{$prefix}options` SET option_name=%s WHERE option_name=%s";
		$sql = $wpdb->prepare( $sql, $prefix . 'user_roles', $old_prefix . 'user_roles' );
		$wpdb->query( $sql );
		//we will need to update the prefix inside user meta, or we will get issue with permission
		$sql  = "SELECT * FROM {$prefix}usermeta";
		$rows = $wpdb->get_results( $sql, ARRAY_A );
		foreach ( $rows as $row ) {
			if ( strpos( $row['meta_key'], $old_prefix ) === 0 ) {
				$clean_name = substr( $row['meta_key'], strlen( $old_prefix ), strlen( $row['meta_key'] ) );
				$new_name   = $prefix . $clean_name;
				$sql        = $wpdb->prepare( "UPDATE `{$prefix}usermeta` SET meta_key=%s WHERE meta_key=%s", $new_name, $row['meta_key'] );
				//run the updater
				if ( $wpdb->query( $sql ) === false ) {
					return new \WP_Error( Error_Code::SQL_ERROR, $wpdb->last_error );
				}
			}
		}

		return true;
	}

	/**
	 * @param null $old_prefix
	 *
	 * @return bool|\WP_Error
	 */
	private function changeDBPrefix( $old_prefix = null ) {
		global $wpdb;
		if ( is_null( $old_prefix ) ) {
			$old_prefix = $wpdb->prefix;
		}
		//cache it
		$this->old_prefix = $old_prefix;
		$tables           = $this->getTables();

		foreach ( $tables as $table ) {
			$new_table_name = substr_replace( $table, $this->new_prefix, 0, strlen( $this->old_prefix ) );
			$sql            = "RENAME TABLE `{$table}` TO `{$new_table_name}`";
			if ( $wpdb->query( $sql ) === false ) {
				return new \WP_Error( Error_Code::SQL_ERROR, $wpdb->last_error );
			}
		}

		return true;
	}

	/**
	 * Validate the current prefix
	 *
	 * @return bool|\WP_Error
	 */
	private function validatePrefix() {
		$new_prefix = trim( $this->new_prefix );

		global $wpdb;
		if ( $new_prefix == $wpdb->prefix ) {
			return new \WP_Error( Error_Code::VALIDATE, __( "You are currently using this prefix.", wp_defender()->domain ) );
		}

		if ( strlen( $new_prefix ) == 0 ) {
			return new \WP_Error( Error_Code::VALIDATE, __( "Your prefix can't be empty!", wp_defender()->domain ) );
		}

		if ( preg_match( '|[^a-z0-9_]|i', $new_prefix ) ) {
			return new \WP_Error( Error_Code::VALIDATE, __( "Table prefix can only contain numbers, letters, and underscores.", wp_defender()->domain ) );
		}

		if ( count( $tables = $this->getTables( $new_prefix ) ) ) {
			return new \WP_Error( Error_Code::VALIDATE, __( "This prefix is already in use. Please choose a different prefix.", wp_defender()->domain ) );
		}

		$this->new_prefix = $new_prefix;

		return true;
	}

	private function getTables( $prefix = null ) {
		global $wpdb;

		if ( ! $prefix ) {
			$prefix = $wpdb->base_prefix;
		}

		$results = $wpdb->get_col( $wpdb->prepare( "SHOW TABLES LIKE %s", $prefix . '%' ) );
		$results = array_unique( $results );

		return $results;
	}

	public function revert() {
		// TODO: Implement revert() method.
	}

	public function listen() {
		// TODO: Implement listen() method.
	}
}