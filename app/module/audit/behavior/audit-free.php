<?php
/**
 * Author: Hoang Ngo
 */

namespace WP_Defender\Module\Audit\Behavior;

use Hammer\Base\Behavior;
use WP_Defender\Behavior\Utils;
use WP_Defender\Module\Audit\Model\Settings;

class Audit_Free extends Behavior {
	public function renderAuditWidget() {
		?>
        <div class="dev-box reporting-sale audit-widget">
            <div class="box-title">
                <span class="span-icon icon-blacklist"></span>
                <h3><?php _e( "AUDIT LOGGING", wp_defender()->domain ) ?></h3>
                <a href="<?php echo Utils::instance()->campaignURL('defender_dash_auditlogging_pro_tag') ?>" target="_blank" class="button button-small button-pre"
				 tooltip="<?php esc_attr_e( "Try Defender Pro free today", wp_defender()->domain ) ?>"><?php _e( "PRO FEATURE", wp_defender()->domain ) ?></a>
            </div>
            <div class="box-content">
                <div class="line">
					<?php
					esc_html_e( "Track and log events when changes are made to your website, giving you full visibility over what's going on behind the scenes.", wp_defender()->domain )
					?>
                </div>
                <div class="presale-text">
                    <div>
						<?php printf( __( "Audit logging is a pro feature included in a WPMU DEV membership along with 100+ plugins &
                        themes, 24/7 support and lots of handy site management tools – <a target='_blank' href=\"%s\">Try it all absolutely FREE</a>", wp_defender()->domain ), Utils::instance()->campaignURL('defender_dash_auditlogging_upsell_link') ) ?>
                        </a>
                    </div>
                </div>
                <div class="clear"></div>
            </div>
        </div>
		<?php
	}
}