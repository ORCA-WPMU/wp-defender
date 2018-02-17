<div class="dev-box">
    <div class="box-title">
        <span class="span-icon icon-audit"></span>
        <h3><?php _e( "AUDIT LOGGING", wp_defender()->domain ) ?></h3>
    </div>
    <div class="box-content">
        <div class="line end">
			<?php printf( __( "There have been <strong>%d events</strong> logged in the past 24 hours.", wp_defender()->domain ), $eventDay ) ?>
        </div>
        <ul class="dev-list bold end">
            <li>
                <div>
                    <span class="list-label"><?php _e( "Last event logged", wp_defender()->domain ) ?></span>
                    <span class="list-detail"><?php echo $lastEvent ?></span>
                </div>
            </li>
            <li>
                <div>
                    <span class="list-label"><?php _e( "Events logged this month", wp_defender()->domain ) ?></span>
                    <span class="list-detail"><?php echo $eventMonth ?></span>
                </div>
            </li>
        </ul>
        <div class="row">
            <div class="col-third tl">
                <a href="<?php echo network_admin_url('admin.php?page=wdf-logging') ?>"
                   class="button button-small button-secondary"><?php _e( "VIEW LOGS", wp_defender()->domain ) ?></a>
            </div>
            <div class="col-two-third tr">
                <p class="status-text"><?php
					if ( \WP_Defender\Module\Audit\Model\Settings::instance()->notification ) {
						_e( "Audit log reports are enabled", wp_defender()->domain );
					} else {
						_e( "Audit log reports are disabled", wp_defender()->domain );
					}
					?></p>
            </div>
        </div>
    </div>
</div>