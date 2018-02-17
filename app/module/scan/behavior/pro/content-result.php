<?php
/**
 * Author: Hoang Ngo
 */

namespace WP_Defender\Module\Scan\Behavior\Pro;

use Hammer\Helper\Log_Helper;
use WP_Defender\Behavior\Utils;
use WP_Defender\Component\Error_Code;
use WP_Defender\Module\Scan\Model\Result_Item;

class Content_Result extends \Hammer\Base\Behavior {
	/**
	 * @return string
	 */
	public function getTitle() {
		$raw = $this->getRaw();

		return pathinfo( $raw['file'], PATHINFO_BASENAME );
	}

	/**
	 * @return mixed
	 */
	public function getSubtitle() {
		$raw = $this->getRaw();

		return $raw['file'];
	}

	/**
	 * Get this slug, will require for checking ignore status while scan
	 * @return string
	 */
	public function getSlug() {
		$raw = $this->getRaw();

		return $raw['file'];
	}

	/**
	 * @return string|void
	 */
	public function getIssueDetail() {
		return __( "Suspicious function found", wp_defender()->domain );
	}

	/**
	 * @return string
	 */
	public function renderDialog() {
		$raw = $this->getRaw();
		ob_start()
		?>
        <dialog class="scan-item-dialog" title="<?php esc_attr_e( "Issue Details", wp_defender()->domain ) ?>"
                id="dia_<?php echo $this->getOwner()->id ?>">
            <div class="wpmud">
                <div class="wp-defender">
                    <div class="scan-dialog">
                        <div class="well mline">
                            <ul class="dev-list item-detail">
                                <li>
                                    <div>
                                        <span class="list-label"><?php _e( "Location", wp_defender()->domain ) ?></span>
                                        <span class="list-detail">
                                            <?php echo $this->getSubTitle() ?>
                                        </span>
                                    </div>
                                </li>
                                <li>
                                    <div>
                                        <span class="list-label"><?php _e( "Date Added", wp_defender()->domain ) ?></span>
                                        <span class="list-detail">
                                           <?php
                                           $filemtime = filemtime( $this->getSubtitle() );
                                           if ( $filemtime ) {
	                                           echo $this->getOwner()->formatDateTime( $filemtime );
                                           } else {
	                                           echo 'N/A';
                                           }
                                           ?>
                                        </span>
                                    </div>
                                </li>
                            </ul>
                        </div>
                        <div class="mline"><?php printf( __( " There’s some suspicious looking code in the file %s. If you know the code is harmless you can ignore this warning. Otherwise, you can choose to delete this file. Before deleting any files from your site directory, we recommend backing up your website.", wp_defender()->domain ), $this->getSubtitle() ) ?>
                        </div>
                        <div class="mline source-code">
                            <img src="<?php echo wp_defender()->getPluginUrl() ?>assets/img/loading.gif" width="18"
                                 height="18"/>
							<?php _e( "Pulling source file...", wp_defender()->domain ) ?>
                            <form method="post" class="float-l pull-src scan-frm">
                                <input type="hidden" name="action" value="pullSrcFile">
								<?php wp_nonce_field( 'pullSrcFile' ) ?>
                                <input type="hidden" name="id" value="<?php echo $this->getOwner()->id ?>"/>
                            </form>
                        </div>
                        <div class="well well-small">
                            <form method="post" class="float-l ignore-item scan-frm">
                                <input type="hidden" name="action" value="ignoreItem">
								<?php wp_nonce_field( 'ignoreItem' ) ?>
                                <input type="hidden" name="id" value="<?php echo $this->getOwner()->id ?>"/>
                                <button type="submit" class="button button-secondary button-small">
									<?php _e( "Ignore", wp_defender()->domain ) ?></button>
                            </form>
							<?php
							$file     = $this->getSubtitle();
							$tooltips = '';
							if ( strpos( $file, WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'plugins' ) === 0 ) {
								$loc      = 'plugin';
								$tooltips = ( __( "This will permanent delete the whole plugin containing this file, do you want to do this?", wp_defender()->domain ) );
							} elseif ( strpos( $file, WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'themes' ) === 0 ) {
								$loc      = 'theme';
								$tooltips = ( __( "This will permanent delete the whole theme containing this file, do you want to do this?", wp_defender()->domain ) );
							} else {
								$loc      = 'standalone';
								$tooltips = ( __( "This will permanent delete this file, do you want to do this?", wp_defender()->domain ) );
							}
							?>
                            <form method="post" class="scan-frm float-r delete-item">
                                <input type="hidden" name="id" value="<?php echo $this->getOwner()->id ?>"/>
                                <input type="hidden" name="action" value="deleteItem"/>
								<?php wp_nonce_field( 'deleteItem' ) ?>
                                <button type="button" class="button button-small delete-mitem button-grey">
									<?php _e( "Delete", wp_defender()->domain ) ?></button>
                                <div class="confirm-box wd-hide">
									<?php echo $tooltips; ?>
                                    &nbsp;
                                    <button type="submit" class="button button-small button-grey">
										<?php _e( "Yes", wp_defender()->domain ) ?>
                                    </button>
                                    <button type="button" class="button button-small button-secondary">
										<?php _e( "No", wp_defender()->domain ) ?>
                                    </button>
                                </div>
                            </form>
                            <div class="clear"></div>
                        </div>
                    </div>
                </div>
            </div>
        </dialog>
		<?php
		return ob_get_clean();
	}

	/**
	 * @return string
	 */
	public function getSrcCode() {
		$raw     = $this->getRaw();
		$content = file_get_contents( $raw['file'] );
		$content = explode( PHP_EOL, $content );
		foreach ( $raw['meta'] as $meta ) {
			$line = $meta['lineFrom'];
			if ( ! isset( $content[ $line - 1 ] ) ) {
				continue;
			}
			$colFrom = $meta['columnFrom'];
			$colTo   = $meta['columnTo'];

			$content[ $line - 1 ]           = substr_replace( $content[ $line - 1 ], '[[del]]', $colFrom - 1, 0 );
			$content[ $meta['lineTo'] - 1 ] = substr_replace( $content[ $meta['lineTo'] - 1 ], '[[/del]]', $colTo + 1, 0 );
		}
		$content = implode( PHP_EOL, $content );

		if ( function_exists( 'mb_convert_encoding' ) ) {
			$content = mb_convert_encoding( $content, 'UTF-8', 'ASCII' );
		}

		$entities = htmlentities( $content, null, 'UTF-8', false );
		$entities = str_replace( '[[del]]', '<del>', $entities );
		$entities = str_replace( '[[/del]]', '</del>', $entities );

		return '<pre><code class="html">' . $entities . '</code></pre>';
	}

	public function purge() {
		//remove the file first
		$raw  = $this->getRaw();
		$file = $raw['file'];
		if ( strpos( $file, WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'plugins' ) === 0 ) {
			//find the plugin
			$revPath = str_replace( WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR, '', $file );
			$pools   = explode( '/', $revPath );
			//the path should be first item in pools
			$path = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . $pools[0];
			$res  = $this->deleteFolder( $path );
			if ( is_wp_error( $res ) ) {
				return $res;
			}
			$this->getOwner()->delete();
		} elseif ( strpos( $file, WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'themes' ) === 0 ) {
			//find the theme
			$revPath = str_replace( WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR, '', $file );
			$pools   = explode( '/', $revPath );
			//the path should be first item in pools
			$path = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . $pools[0];
		} else {
			if ( $file == ABSPATH . 'wp-config.php' ) {
				return new \WP_Error( Error_Code::NOT_WRITEABLE, __( "wp-config.php can't be removed. Please remove the suspicious code manually.", wp_defender()->domain ) );
			}
			$res = unlink( $raw['file'] );
			if ( $res ) {
				$this->getOwner()->delete();
			} else {
				return new \WP_Error( Error_Code::NOT_WRITEABLE, __( "Defender doesn't have enough permission to remove this file", wp_defender()->domain ) );
			}
		}

		return true;
	}

	private function deleteFolder( $dir ) {
		if ( ! is_dir( $dir ) ) {
			return;
		}
		$it    = new \RecursiveDirectoryIterator( $dir, \RecursiveDirectoryIterator::SKIP_DOTS );
		$files = new \RecursiveIteratorIterator( $it,
			\RecursiveIteratorIterator::CHILD_FIRST );
		foreach ( $files as $file ) {
			if ( $file->isDir() ) {
				$res = @rmdir( $file->getRealPath() );
			} else {
				$res = @unlink( $file->getRealPath() );
			}
			if ( $res == false ) {
				return new \WP_Error( Error_Code::NOT_WRITEABLE, __( "Defender doesn't have enough permission to remove this file", wp_defender()->domain ) );
			}
		}
		$res = @rmdir( $dir );
		if ( $res == false ) {
			return new \WP_Error( Error_Code::NOT_WRITEABLE, __( "Defender doesn't have enough permission to remove this file", wp_defender()->domain ) );
		}

		return true;
	}

	/**
	 * @return Result_Item;
	 */
	protected function getOwner() {
		return $this->owner;
	}

	/**
	 * @return array
	 */
	protected function getRaw() {
		return $this->getOwner()->raw;
	}
}