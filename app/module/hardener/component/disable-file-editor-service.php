<?php
/**
 * Author: Hoang Ngo
 */

namespace WP_Defender\Module\Hardener\Component;

use Hammer\Helper\Array_Helper;
use WP_Defender\Component\Error_Code;
use WP_Defender\Module\Hardener\IRule_Service;
use WP_Defender\Module\Hardener\Rule_Service;

class Disable_File_Editor_Service extends Rule_Service implements IRule_Service {
	/**
	 * @return bool
	 */
	public function check() {
		if ( defined( 'DISALLOW_FILE_EDIT' ) && constant( 'DISALLOW_FILE_EDIT' ) === true ) {
			return true;
		}

		return false;
	}

	/**
	 * @return bool|int|\WP_Error
	 */
	public function process() {
		$config_path = $this->retrieveWPConfigPath();
		if ( ! is_writeable( $config_path ) ) {
			return new \WP_Error( Error_Code::NOT_WRITEABLE,
				sprintf( __( "The file %s is not writeable", wp_defender()->domain ), $config_path ) );
		}
		$config = file( $config_path );
		$line   = $this->findLine( $config );
		if ( $line == false ) {
			//no defined, we just need to inject
			$hook_line = $this->findDefaultHookLine( $config );
			if ( $hook_line === false ) {
				return new \WP_Error( Error_Code::UNKNOWN_WPCONFIG, __( "Defender can't recognize your wp-config.php, please revert it to original state for further process.", wp_defender()->domain ) );
			}
			$config = Array_Helper::injectLine( $config, $hook_line + 1, PHP_EOL . "define( 'DISALLOW_FILE_EDIT', true );" . PHP_EOL );

			return file_put_contents( $config_path, implode( null, $config ), LOCK_EX );
		} else {
			list( $value, $line ) = $line;
			if ( $value == true ) {
				//already disable it, if we sitll here mean st not work, do nothing
			} else {
				$config[ $line ] = "define( 'DISALLOW_FILE_EDIT', true );" . PHP_EOL;

				return file_put_contents( $config_path, implode( null, $config ), LOCK_EX );
			}
		}

		return false;
	}

	/**
	 * @return int|\WP_Error
	 */
	public function revert() {
		$config_path = $this->retrieveWPConfigPath();
		if ( ! is_writeable( $config_path ) ) {
			return new \WP_Error( Error_Code::NOT_WRITEABLE,
				sprintf( __( "The file %s is not writeable", wp_defender()->domain ), $config_path ) );
		}
		$config = file( $config_path );
		$line   = $this->findLine( $config );
		if ( $line === false ) {
			//perhaps this already removed manually, do nothing
		} else {
			$value = $line[0];
			$line  = $line[1];
			if ( $value == "true" ) {
				//value is true, we will remove this
				unset( $config[ $line ] );
				//save it
				return file_put_contents( $config_path, implode( null, $config ), LOCK_EX );
			}
		}
	}

	/**
	 * @param $config
	 *
	 * @return array|bool
	 */
	private function findLine( $config ) {
		$pattern = "/^define\(\s*['|\"]DISALLOW_FILE_EDIT['|\"],(.*)\)/";
		foreach ( $config as $k => $line ) {
			if ( preg_match( $pattern, $line, $matches ) ) {
				return array( trim( $matches[1] ), $k );
			}
		}

		return false;
	}
}