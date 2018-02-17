<?php
/**
 * Author: Hoang Ngo
 */

namespace WP_Defender\Module\Audit\Behavior;

use Hammer\Base\Behavior;
use WP_Defender\Behavior\Utils;
use WP_Defender\Module\Audit\Model\Settings;

class Audit extends Behavior {
	public function renderAuditWidget() {
		$this->_renderAuditSample();
	}

	private function _renderAuditSample() {
		?>
        <div class="dev-box">
            <div class="box-title">
                <span class="span-icon icon-audit"></span>
                <h3><?php _e( "AUDIT LOGGING", wp_defender()->domain ) ?></h3>
            </div>
            <div class="box-content auditing">
				<?php if ( Settings::instance()->enabled ): ?>
                    <form method="post" class="audit-frm audit-widget">
                        <input type="hidden" name="action" value="dashboardSummary"/>
						<?php wp_nonce_field( 'dashboardSummary' ) ?>
                    </form>
                    <div class="">
						<?php __( "Please hold on, Defender will update Audit information soon...", wp_defender()->domain ) ?>
                    </div>
                    <div class="wd-overlay">
                        <i class="wdv-icon wdv-icon-fw wdv-icon-refresh spin"></i>
                    </div>
				<?php else: ?>
                    <div class="line">
						<?php _e( "Track and log events when changes are made to your website, giving you full visibility over what's going on behind the scenes.", wp_defender()->domain ) ?>
                    </div>
                    <form method="post" class="audit-frm active-audit">
                        <input type="hidden" name="action" value="activeAudit"/>
						<?php wp_nonce_field( 'activeAudit' ) ?>
                        <button type="submit" class="button button-small"><?php _e( "Activate", wp_defender()->domain ) ?></button>
                    </form>
				<?php endif; ?>
            </div>
        </div>
		<?php
	}
}