<?php
/**
 * Author: Paul Kevin
 */

namespace WP_Defender\Module\Hardener\Component\Servers;

use WP_Defender\Behavior\Utils;
use WP_Defender\Component\Error_Code;
use WP_Defender\Module\Hardener\IRule_Service;
use WP_Defender\Module\Hardener\Rule_Service;

class Apache_Service extends Rule_Service implements IRule_Service {

    /**
     * Exclude file paths
     *
     * @var array|bool|mixed
     */
    private $exclude_file_paths = array();

	/**
     * New htaccess file
     *
     * @var array|bool|mixed
     */
    private $new_htconfig = array();

    /**
	 * @return bool
	 */
	public function check() {
        return true;
    }

    /**
	 * @return bool|\WP_Error
	 */
    public function process() {
        $ret = $this->protectContentDir();
		if ( is_wp_error( $ret ) ) {
			return $ret;
		}

		$ret = $this->protectIncludesDir();
		if ( is_wp_error( $ret ) ) {
			return $ret;
		}

		$ret = $this->protectUploadsDir();
		if ( is_wp_error( $ret ) ) {
			return $ret;
		}

		return true;
    }

    /**
	 * @return bool|\WP_Error
	 */
	private function protectIncludesDir() {
		$htPath = ABSPATH . WPINC . '/' . '.htaccess';

		if ( ! is_file( $htPath ) ) {
			if ( ! file_put_contents( $htPath, '', LOCK_EX ) ) {
				return new \WP_Error( Error_Code::NOT_WRITEABLE,
					sprintf( __( "The file %s is not writeable", wp_defender()->domain ), $htPath ) );
			}
		} elseif ( ! is_writeable( $htPath ) ) {
			return new \WP_Error( Error_Code::NOT_WRITEABLE,
				sprintf( __( "The file %s is not writeable", wp_defender()->domain ), $htPath ) );
		}
		$htConfig 	= file( $htPath );
		$deny 		= $this->generateHtAccessRule( false );
		$allow 		= $this->generateHtAccessRule( true );
		$default  	= array(
			PHP_EOL . '## WP Defender - Protect PHP Executed ##' . PHP_EOL,
			'<Files *.php>' . PHP_EOL .
			$deny  .
			'</Files>' . PHP_EOL,
			'<Files wp-tinymce.php>' . PHP_EOL .
			$allow  .
			'</Files>' . PHP_EOL,
			'<Files ms-files.php>' . PHP_EOL .
			$allow  .
			'</Files>' . PHP_EOL,
			'## WP Defender - End ##' . PHP_EOL
		);
		/*$status   = wp_remote_head( network_site_url() . 'wp-includes', array( 'user-agent' => $_SERVER['HTTP_USER_AGENT'] ) );
		if ( 200 == wp_remote_retrieve_response_code( $status ) ) {
			$default[] = 'Options -Indexes' . PHP_EOL;
		}*/
		$containsSearch = array_diff( $default, $htConfig );
		if ( count( $containsSearch ) == 0 || ( count( $containsSearch ) == count( $default ) ) ) {
			//append this
			$htConfig = array_merge( $htConfig, array( implode( '', $default ) ) );
			file_put_contents( $htPath, implode( '', $htConfig ), LOCK_EX );
		}

		return true;
	}

	/**
	 * @return bool|\WP_Error
	 */
	private function protectContentDir() {
		$htPath = WP_CONTENT_DIR . '/' . '.htaccess';
		if ( ! file_exists( $htPath ) ) {
			if ( ! file_put_contents( $htPath, '', LOCK_EX ) ) {
				return new \WP_Error( Error_Code::NOT_WRITEABLE,
					sprintf( __( "The file %s is not writeable", wp_defender()->domain ), $htPath ) );
			}
		} elseif ( ! is_writeable( $htPath ) ) {
			return new \WP_Error( Error_Code::NOT_WRITEABLE,
				sprintf( __( "The file %s is not writeable", wp_defender()->domain ), $htPath ) );
		}
		$htConfig 	= file( $htPath );
		$deny 		= $this->generateHtAccessRule( false );
		$allow 		= $this->generateHtAccessRule( true );
		$default  	= array(
			PHP_EOL . '## WP Defender - Protect PHP Executed ##' . PHP_EOL,
			'<Files *.php>' . PHP_EOL .
			$deny .
			'</Files>' . PHP_EOL,
			'## WP Defender - End ##' . PHP_EOL
		);

		if ( ! empty( $this->exclude_file_paths ) ) {

			$custom_exclude = array();

			foreach ( $this->exclude_file_paths as $file_path ) {
				$file_path = trim( preg_replace('/\s\s+/', ' ', $file_path ) ); //remove trailing new lines
				if ( !empty( $file_path ) ) {
					$custom_exclude[] = '<Files ' . $file_path . '> '. PHP_EOL .
										$allow  .
										'</Files>' . PHP_EOL;
				}
			}

			if ( ! empty( $custom_exclude ) ) {
				array_splice( $default, 2, 0, $custom_exclude ); //Add the excludes before the ## WP Defender - End ##
				$this->new_htconfig = $default; //Set the new array structure for when we want to remove
			}
		}

		$containsSearch = array_diff( $default, $htConfig );
		if ( count( $containsSearch ) == 0 || ( count( $containsSearch ) == count( $default ) ) ) {
			//append this
			$htConfig = array_merge( $htConfig, array( implode( '', $default ) ) );
			file_put_contents( $htPath, implode( '', $htConfig ), LOCK_EX );
		}

		return true;
	}

	/**
	 * Protect uploads directory
	 * Sometimes a user will set a custom upload directory
	 *
	 * @return bool|\WP_Error
	 */
	public function protectUploadsDir() {
		if ( defined( 'UPLOADS' ) ) {
			$htPath = ABSPATH . UPLOADS . '/' . '.htaccess';
			if ( ! file_exists( $htPath ) ) {
				if ( ! file_put_contents( $htPath, '', LOCK_EX ) ) {
					return new \WP_Error( Error_Code::NOT_WRITEABLE,
						sprintf( __( "The file %s is not writeable", wp_defender()->domain ), $htPath ) );
				}
			} elseif ( ! is_writeable( $htPath ) ) {
				return new \WP_Error( Error_Code::NOT_WRITEABLE,
					sprintf( __( "The file %s is not writeable", wp_defender()->domain ), $htPath ) );
			}
			$htConfig 	= file( $htPath );
			$deny 		= $this->generateHtAccessRule( false );
			$allow 		= $this->generateHtAccessRule( true );
			$default  	= array(
				PHP_EOL . '## WP Defender - Protect PHP Executed ##' . PHP_EOL,
				'<Files *.php>' . PHP_EOL .
				$deny .
				'</Files>' . PHP_EOL,
				'## WP Defender - End ##' . PHP_EOL
			);

			if ( ! empty( $this->exclude_file_paths ) ) {

				$custom_exclude = array();

				foreach ( $this->exclude_file_paths as $file_path ) {
					$file_path = trim( preg_replace('/\s\s+/', ' ', $file_path ) ); //remove trailing new lines
					if ( !empty( $file_path ) ) {
						$custom_exclude[] = '<Files ' . $file_path . '> '. PHP_EOL .
											$allow  .
											'</Files>' . PHP_EOL;
					}
				}

				if ( ! empty( $custom_exclude ) ) {
					array_splice( $default, 2, 0, $custom_exclude ); //Add the excludes before the ## WP Defender - End ##
					$this->new_htconfig = $default; //Set the new array structure for when we want to remove
				}
			}

			$containsSearch = array_diff( $default, $htConfig );
			if ( count( $containsSearch ) == 0 || ( count( $containsSearch ) == count( $default ) ) ) {
				//append this
				$htConfig = array_merge( $htConfig, array( implode( '', $default ) ) );
				file_put_contents( $htPath, implode( '', $htConfig ), LOCK_EX );
			}
		}
		return true;
	}

	public function unProtectContentDir() {
		$htPath = WP_CONTENT_DIR . '/' . '.htaccess';
		if ( ! is_writeable( $htPath ) ) {
			return new \WP_Error( Error_Code::NOT_WRITEABLE,
				sprintf( __( "The file %s is not writeable", wp_defender()->domain ), $htPath ) );
		}
		$htConfig 	= file_get_contents( $htPath );
		$deny 		= $this->generateHtAccessRule( false );
		$default  	= array(
			PHP_EOL .'## WP Defender - Protect PHP Executed ##' . PHP_EOL,
			'<Files *.php>' . PHP_EOL .
			$deny  .
			'</Files>' . PHP_EOL,
			'## WP Defender - End ##' . PHP_EOL
		);

		if ( ! empty( $this->new_htconfig ) ) {
			$default = $this->new_htconfig;
		}

		//Introduced regex
		preg_match_all('/## WP Defender(.*?)## WP Defender - End ##/s', $htConfig, $matches);
		if ( is_array( $matches ) && count( $matches ) > 0 ) {
			$htConfig = str_replace( implode( '', $matches[0] ), '', $htConfig );
		} else {
			$htConfig = str_replace( implode( '', $default ), '', $htConfig );
		}
		$htConfig = trim( $htConfig );
		file_put_contents( $htPath, $htConfig, LOCK_EX );
	}

	public function unProtectIncludeDir() {
		$htPath = ABSPATH . WPINC . '/' . '.htaccess';
		if ( ! is_writeable( $htPath ) ) {
			return new \WP_Error( Error_Code::NOT_WRITEABLE,
				sprintf( __( "The file %s is not writeable", wp_defender()->domain ), $htPath ) );
		}
		$htConfig 	= file_get_contents( $htPath );
		$deny 		= $this->generateHtAccessRule( false );
		$allow 		= $this->generateHtAccessRule( true );
		$default  	= array(
			PHP_EOL . '## WP Defender - Protect PHP Executed ##' . PHP_EOL,
			'<Files *.php>' . PHP_EOL .
			$deny  .
			'</Files>' . PHP_EOL,
			'<Files wp-tinymce.php>' . PHP_EOL .
			$allow  .
			'</Files>' . PHP_EOL,
			'<Files ms-files.php>' . PHP_EOL .
			$allow  .
			'</Files>' . PHP_EOL,
			'## WP Defender - End ##' . PHP_EOL
		);

		preg_match_all('/## WP Defender(.*?)## WP Defender - End ##/s', $htConfig, $matches);
		if ( is_array( $matches ) && count( $matches ) > 0 ) {
			$htConfig = str_replace( implode( '', $matches[0] ), '', $htConfig );
		} else {
			$htConfig = str_replace( implode( '', $default ), '', $htConfig );
		}
		$htConfig = trim( $htConfig );
		file_put_contents( $htPath, $htConfig, LOCK_EX );
	}

	public function unProtectUploadDir() {
		if ( defined( 'UPLOADS' ) ) {
			$htPath = ABSPATH . UPLOADS . '/' . '.htaccess';
			if ( ! is_writeable( $htPath ) ) {
				return new \WP_Error( Error_Code::NOT_WRITEABLE,
					sprintf( __( "The file %s is not writeable", wp_defender()->domain ), $htPath ) );
			}
			$htConfig 	= file_get_contents( $htPath );
			$deny 		= $this->generateHtAccessRule( false );
			$default  	= array(
				PHP_EOL .'## WP Defender - Protect PHP Executed ##' . PHP_EOL,
				'<Files *.php>' . PHP_EOL .
				$deny  .
				'</Files>' . PHP_EOL,
				'## WP Defender - End ##' . PHP_EOL
			);

			if ( ! empty( $this->new_htconfig ) ) {
				$default = $this->new_htconfig;
			}

			//Introduced regex
			preg_match_all('/## WP Defender(.*?)## WP Defender - End ##/s', $htConfig, $matches);
			if ( is_array( $matches ) && count( $matches ) > 0 ) {
				$htConfig = str_replace( implode( '', $matches[0] ), '', $htConfig );
			} else {
				$htConfig = str_replace( implode( '', $default ), '', $htConfig );
			}
			$htConfig = trim( $htConfig );
			file_put_contents( $htPath, $htConfig, LOCK_EX );
		}
	}

    /**
	 * @return bool|\WP_Error
	 */
	public function revert() {
        $ret = $this->unProtectContentDir();
        if ( is_wp_error( $ret ) ) {
            return $ret;
        }

        $ret = $this->unProtectIncludeDir();
        if ( is_wp_error( $ret ) ) {
            return $ret;
		}
		$ret = $this->unProtectUploadDir();
		if ( is_wp_error( $ret ) ) {
            return $ret;
		}

        return true;
    }

    /**
	 * Set the exclude file paths
	 *
	 * @param String $paths
	 */
	public function setExcludeFilePaths( $paths ) {
		if ( ! empty( $paths ) ) {
			$this->exclude_file_paths = explode( "\n", $paths );
		}
	}


	/**
	 * Get the exclude file paths
	 *
	 * @return Array - $exclude_file_paths
	 */
	public function getExcludedFilePaths() {
		return $this->exclude_file_paths;
	}

	/**
	 * Set the exclude file paths
	 *
	 * @param String $paths
	 */
	public function setHtConfig( $config = array() ) {
		if ( ! empty( $config ) ) {
			$this->new_htconfig = $config;
		}
	}


	/**
	 * Get the new HT config
	 *
	 * @return Array - $new_htconfig
	 */
	public function getNewHtConfig() {
		return $this->new_htconfig;
	}

	/**
	 * Return the correct apache rules for allow/deny
	 *
	 * @return String
	 */
	protected function generateHtAccessRule( $allow = true ) {
		$version = Utils::instance()->determineApacheVersion();
		if ( floatval( $version ) >= 2.4 ) {
			if ( $allow ) {
				return 'Require all granted' . PHP_EOL;
			} else {
				return 'Require all denied' . PHP_EOL;
			}
		} else {
			if ( $allow ) {
				return 'Allow from all' . PHP_EOL;
			} else {
				return 'Order allow,deny' . PHP_EOL .
				'Deny from all' . PHP_EOL;
			}
		}
	}
}
?>