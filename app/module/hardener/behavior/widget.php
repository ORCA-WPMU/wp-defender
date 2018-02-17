<?php
/**
 * Author: Hoang Ngo
 */

namespace WP_Defender\Module\Hardener\Behavior;

use Hammer\Base\Behavior;
use WP_Defender\Module\Hardener\Model\Settings;

class Widget extends Behavior {
	public function renderHardenerWidget() {
		$issues = Settings::instance()->getIssues();
		$issues = array_slice( $issues, 0, 3 );
		?>
        <div class="dev-box hardener-widget">
            <div class="box-title">
                <span class="span-icon hardener-icon" aria-hidden="true"></span>
                <h3><?php _e( "Security Tweaks", wp_defender()->domain ) ?>
					<?php
                    $hardener_issues = count( Settings::instance()->issues );
                    if ( $hardener_issues ): ?>
                        <span class="def-tag tag-yellow"
                        tooltip="<?php esc_attr_e( sprintf( __('You have %d security tweak(s) needing attention.', wp_defender()->domain ), $hardener_issues ) ); ?>">
                        <?php
                        echo $hardener_issues ?>
                    </span>
					<?php endif; ?>
                </h3>
            </div>
            <div class="box-content">
				<?php $count = count( $issues ); ?>
                <div class="line <?php echo $count ? 'end' : null ?>">
					<?php _e( "Defender checks for security tweaks you can make to enhance your website’s
                    defense against hackers and bots.", wp_defender()->domain ) ?>
                </div>
				<?php if ( $count ): ?>
                    <ul class="dev-list end">
						<?php
						foreach ( $issues as $issue ):
							?>
                            <li>
                                <div>
                                    <a target="_blank"
                                       href="<?php echo network_admin_url( 'admin.php?page=wdf-hardener#' . $issue::$slug ) ?>">
                                        <span class="list-label"><i
                                                    class="def-icon icon-h-warning"></i><?php echo $issue->getTitle(); ?></span>
                                    </a>
                                </div>
                            </li>
						<?php endforeach;
						?>
                    </ul>
				<?php else: ?>
                    <div class="well well-green with-cap mline">
                        <i class="def-icon icon-tick"></i>
						<?php _e( "You have actioned all available security tweaks. Great work!", wp_defender()->domain ) ?>
                    </div>
				<?php endif; ?>
                <div class="row">
                    <div class="col-third tl">
                        <a href="<?php echo network_admin_url( 'admin.php?page=wdf-hardener' ) ?>"
                           class="button button-small button-secondary"><?php _e( "VIEW ALL", wp_defender()->domain ) ?></a>
                    </div>
                </div>
            </div>
        </div>
		<?php
	}

	private function _renderNew() {

	}

	private function _render() {

	}
}