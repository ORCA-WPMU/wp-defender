<?php
/**
 * Author: Hoang Ngo
 */

namespace WP_Defender\Module\Scan\Behavior\Pro;

use WP_Defender\Module\Scan\Component\Plugin_Upgrader_Skin;
use WP_Defender\Module\Scan\Component\Theme_Upgrader_Skin;

class Vuln_Result extends \Hammer\Base\Behavior {
	private $hasFix;

	/**
	 * @return string
	 */
	public function getTitle() {
		$raw = $this->getRaw();
		switch ( $raw['type'] ) {
			case 'plugin':
				$plugin = $this->getPluginBySlug( $raw['slug'] );
				if ( isset( $plugin['Name'] ) ) {
					return $plugin['Name'];
				}
				break;
			case 'theme':
				$theme = $this->getThemeBySlug( $raw['slug'] );
				if ( is_object( $theme ) ) {
					return $theme->get( 'Name' );
				}
				break;
			default:
				return esc_html__( "WordPress Vulnerability", wp_defender()->domain );
				break;
		}
	}

	/**
	 * @return mixed|null
	 */
	public function getSlug() {
		$raw = $this->getRaw();
		if ( $raw['type'] == 'wordpress' ) {
			return null;
		}

		return $raw['slug'];
	}

	/**
	 * @return false|string
	 */
	public function getSubtitle() {
		$raw  = $this->getRaw();
		$text = __( "Version:", wp_defender()->domain );
		switch ( $raw['type'] ) {
			case 'plugin':
				$plugin = $this->getPluginBySlug( $raw['slug'] );
				if ( isset( $plugin['Name'] ) ) {
					return $text . ' ' . $plugin['Version'];
				}
				break;
			case 'theme':
				$theme = $this->getThemeBySlug( $raw['slug'] );
				if ( is_object( $theme ) ) {
					return $text . ' ' . $theme->get( 'Version' );
				}
				break;
			default:
				global $wp_version;

				return $text . ' ' . $wp_version;
				break;
		}
	}

	/**
	 * @return string
	 */
	public function getIssueDetail() {
		$raw      = $this->getRaw();
		$texts    = array();
		$hasFixed = false;
		foreach ( $raw['bugs'] as $bug ) {
			if ( ! empty( $bug['fixed_in'] ) ) {
				$hasFixed = true;
			}
			$text    = '<div class="vuln-list">';
			$text    .= '<p>' . $bug['title'] . '</p>';
			$text    .= '<ul>';
			$text    .= '<li>' . __( "Vulnerability type:", wp_defender()->domain ) . ' ' . $bug['vuln_type'] . '</li>';
			$text    .= '<li>' . __( "This bug has been fixed in version:", wp_defender()->domain ) . ' ' . $bug['fixed_in'] . '</li>';
			$text    .= '</ul>';
			$text    .= '</div>';
			$texts[] = $text;
		}
		$this->hasFix = $hasFixed;

		return implode( '', $texts );
	}

	/**
	 * @return string
	 */
	public function renderDialog() {
		$raw = $this->getRaw();
		ob_start()
		?>
        <dialog title="<?php esc_attr_e( "Issue Details", wp_defender()->domain ) ?>"
                id="dia_<?php echo $this->getOwner()->id ?>">
            <div class="wpmud">
                <div class="wp-defender">
                    <div class="scan-dialog">
                        <div class="well mline">
                            <ul class="dev-list item-detail">
                                <li>
                                    <div>
                                        <span class="list-label"><?php
	                                        if ( $raw['type'] == 'plugin' ) {
		                                        _e( "Plugin Name", wp_defender()->domain );
	                                        } elseif ( $raw['type'] == 'theme' ) {
		                                        _e( "Theme Name", wp_defender()->domain );
	                                        } elseif ( $raw['type'] == 'wordpress' ) {
		                                        _e( "WordPress", wp_defender()->domain );
	                                        }
	                                        ?></span>
                                        <span class="list-detail"><?php echo $this->getTitle() ?></span>
                                    </div>
                                </li>
                                <li>
                                    <div>
                                        <span class="list-label"><?php _e( "Version", wp_defender()->domain ) ?></span>
                                        <span class="list-detail">
                                            <?php echo $this->getSubTitle() ?>
                                        </span>
                                    </div>
                                </li>
                            </ul>
                        </div>
                        <div class="mline">
							<?php _e( "Vulnerability found in this version:", wp_defender()->domain ) ?>
							<?php echo $this->getIssueDetail() ?>
                        </div>
						<?php if ( $this->hasFix ): ?>
                            <div class="mline">
								<?php _e( "There’s a newer version available that fixes this issue. We recommend updating to the latest release.", wp_defender()->domain ) ?>
                            </div>
                            <div class="clear"></div>
                            <div class="well">
								<?php if ( $raw['type'] != 'wordpress' ): ?>
                                    <form method="post" class="float-l ignore-item scan-frm">
                                        <input type="hidden" name="action" value="ignoreItem">
										<?php wp_nonce_field( 'ignoreItem' ) ?>
                                        <input type="hidden" name="id" value="<?php echo $this->getOwner()->id ?>"/>
                                        <button type="submit" class="button button-secondary button-small">
											<?php _e( "Ignore", wp_defender()->domain ) ?></button>
                                    </form>
                                    <form method="post" class="scan-frm float-r resolve-item">
                                        <input type="hidden" name="id" value="<?php echo $this->getOwner()->id ?>"/>
                                        <input type="hidden" name="action" value="resolveItem"/>
										<?php wp_nonce_field( 'resolveItem' ) ?>
                                        <button class="button button-small"><?php _e( "Update", wp_defender()->domain ) ?></button>
                                    </form>
								<?php else: ?>
                                    <a class="button button-small float-r"
                                       href="<?php echo network_admin_url( 'update-core.php' ) ?>"><?php _e( "Update", wp_defender()->domain ) ?></a>
								<?php endif; ?>
                                <div class="clear"></div>
                            </div>
						<?php else: ?>
							<?php
							if ( $raw['type'] == 'wordpress' ) {
								_e( "This is a known issue identified by WordPress. When a security release is available we recommend you update your WordPress core to the latest version to make sure protected from this vulnerability.", wp_defender()->domain );
							}
							?>
						<?php endif; ?>
                    </div>
                </div>
            </div>
        </dialog>
		<?php
		return ob_get_clean();
	}

	/**
	 * @return bool|string|\WP_Error
	 */
	public function resolve() {
		$raw = $this->getRaw();
		if ( $raw['type'] == 'wordpress' ) {
			//redirect to upgrade page
			return network_admin_url( 'wp-admin/update-core.php' );
		} elseif ( $raw['type'] == 'theme' ) {
			if ( ! class_exists( 'Theme_Upgrader' ) ) {
				require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
			}
			$skin     = new Theme_Upgrader_Skin( $raw['slug'] );
			$upgrader = new \Theme_Upgrader( $skin );
			$upgrader->upgrade( $raw['slug'] );
			if ( is_wp_error( $skin->result ) ) {
				return $skin->result;
			}
			$this->getOwner()->markAsResolved();

			return true;
		} elseif ( $raw['type'] == 'plugin' ) {
			if ( ! class_exists( 'Plugin_Upgrader' ) ) {
				require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
			}
			list( $rslug, $plugin ) = $this->getPluginBySlug( $raw['slug'], true );
			$skin     = new Plugin_Upgrader_Skin( $rslug );
			$upgrader = new \Plugin_Upgrader( $skin );
			$upgrader->upgrade( $rslug );
			if ( is_wp_error( $skin->result ) ) {
				return $skin->result;
			}
			$this->getOwner()->markAsResolved();

			return true;
		}
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

	/**
	 * @param $slug
	 * @param $withRealSlug
	 *
	 * @return null
	 */
	protected function getPluginBySlug( $slug, $withRealSlug = false ) {
		$plugins = get_plugins();
		foreach ( $plugins as $sl => $plugin ) {
			if ( strpos( $sl, $slug ) === 0 ) {
				if ( $withRealSlug == false ) {
					return $plugin;
				} else {
					return array( $sl, $plugin );
				}
			}
		}

		return null;
	}

	/**
	 * @param $slug
	 *
	 * @return \WP_Theme
	 */
	protected function getThemeBySlug( $slug ) {
		$theme = wp_get_theme( $slug );
		if ( is_object( $theme ) ) {
			return $theme;
		}
	}
}