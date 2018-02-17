<div class="wrap">
    <div id="wp-defender" class="wp-defender">
        <div class="auditing">
            <h2 class="title">
				<?php _e( "AUDIT LOGGING", wp_defender()->domain ) ?>
            </h2>
            <div class="dev-box summary-box">
                <div class="box-content">
                    <div class="columns">
                        <div class="column is-7 issues-count">
                            <div>
                                <h5 class="">
                                    <form method="post" class="audit-frm count-7-days">
                                        <input type="hidden" name="action" value="dashboardSummary"/>
                                        <input type="hidden" name="weekly" value="1"/>
										<?php wp_nonce_field( 'dashboardSummary' ) ?>
                                    </form>
                                    -
                                </h5>
                                <span class="sub"><?php _e( "Events logged in the past 7 days", wp_defender()->domain ) ?></span>
                            </div>
                        </div>
                        <div class="column is-5">
                            <div class="dev-list-container">
                                <ul class="dev-list bold">
                                    <li>
                                        <div>
                                            <span class="list-label"><?php _e( "Reports", wp_defender()->domain ) ?></span>
                                            <span class="list-detail">
                                            <?php
                                            $settings = \WP_Defender\Module\Audit\Model\Settings::instance();
                                            if ( $settings->notification == true ) {
	                                            ?> <span
                                                        class="defender-audit-frequency"><?php echo ucfirst( \WP_Defender\Behavior\Utils::instance()->frequencyToText( $settings->frequency ) );
		                                            ?></span>
                                                <p class="sub defender-audit-schedule">
                                                    <?php
                                                    if ( $settings->frequency == 1 ) {
	                                                    printf( __( "at %s", wp_defender()->domain ),
		                                                    strftime( '%I:%M %p', strtotime( $settings->time ) ) );
                                                    } else {
	                                                    printf( __( "%s at %s", wp_defender()->domain ),
		                                                    ucfirst( $settings->day ),
		                                                    strftime( '%I:%M %p', strtotime( $settings->time ) ) );
                                                    } ?>
                                                </p>
	                                            <?php
                                            } else {
	                                            echo '-';
                                            }
                                            ?>
                                        </span>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                            <div class="clear"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-third">
                    <ul class="inner-nav is-hidden-mobile">
                        <li>
                            <a class="<?php echo \Hammer\Helper\HTTP_Helper::retrieve_get( 'view', false ) == false ? 'active' : null ?>"
                               href="<?php echo network_admin_url( 'admin.php?page=wdf-logging' ) ?>"><?php _e( "Event Logs", wp_defender()->domain ) ?></a>
                        </li>
                        <li>
                            <a class="<?php echo $controller->isView( 'settings' ) ? 'active' : null ?>"
                               href="<?php echo network_admin_url( 'admin.php?page=wdf-logging&view=settings' ) ?>"><?php _e( "Settings", wp_defender()->domain ) ?></a>
                        </li>
                        <li>
                            <a class="<?php echo $controller->isView( 'report' ) ? 'active' : null ?>"
                               href="<?php echo network_admin_url( 'admin.php?page=wdf-logging&view=report' ) ?>"><?php _e( "Reports", wp_defender()->domain ) ?></a>
                        </li>
                    </ul>
                    <div class="is-hidden-tablet mline">
                        <select class="mobile-nav">
                            <option <?php selected( '', \Hammer\Helper\HTTP_Helper::retrieve_get( 'view' ) ) ?>
                                    value="<?php echo network_admin_url( 'admin.php?page=wdf-logging' ) ?>"><?php _e( "Event Logs", wp_defender()->domain ) ?></option>
                            <option <?php selected( 'settings', \Hammer\Helper\HTTP_Helper::retrieve_get( 'view' ) ) ?>
                                    value="<?php echo network_admin_url( 'admin.php?page=wdf-logging&view=settings' ) ?>"><?php _e( "Settings", wp_defender()->domain ) ?></option>
                            <option <?php selected( 'report', \Hammer\Helper\HTTP_Helper::retrieve_get( 'view' ) ) ?>
                                    value="<?php echo network_admin_url( 'admin.php?page=wdf-logging&view=report' ) ?>"><?php _e( "Reports", wp_defender()->domain ) ?></option>
                        </select>
                    </div>
                </div>
                <div class="col-two-third">
					<?php echo $contents ?>
                </div>
            </div>
        </div>
    </div>
</div>