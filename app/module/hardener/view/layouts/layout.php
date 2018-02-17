<div class="wrap">
    <div id="wp-defender" class="wp-defender">
        <h2 class="title"><?php _e( "Security Tweaks", wp_defender()->domain ) ?></h2>
        <div class="hardener">
            <div class="dev-box summary-box">
                <div class="box-content">
                    <div class="columns">
                        <div class="column is-7 issues-count">
                            <div>
                                <h5 class="">
                                    <span class="issues-actioned"><?php echo( $controller->getCount( 'fixed' ) + $controller->getCount( 'ignore' ) ) ?></span>
                                        /<?php echo count( \WP_Defender\Module\Hardener\Model\Settings::instance()->getDefinedRules( false ) ) ?>

                                </h5>
                                <?php if ( $controller->getCount( 'issues' ) > 0 ) :
                                    $hardener_issues = ( $controller->getCount( 'fixed' ) + $controller->getCount( 'ignore' ) ) . '/' . count( \WP_Defender\Module\Hardener\Model\Settings::instance()->getDefinedRules( false ) );
                                ?>
                                    <span class="" tooltip="<?php esc_attr_e( sprintf( __('You have actioned %s security tweaks.', wp_defender()->domain ), $hardener_issues ) ); ?>">
                                <?php else : ?>
                                    <span class="" tooltip="<?php esc_attr_e( 'You have no outstanding security issues.', wp_defender()->domain ); ?>">
                                <?php endif; ?>
									<?php
									$icon = $controller->getCount( 'issues' ) == 0 ? ' <i class="def-icon icon-tick icon-active" aria-hidden="true"></i>' : ' <i class="def-icon icon-warning" aria-hidden="true"></i>';
									echo $icon;
									?>
                                </span>
                                <div class="clear"></div>
                                <span class="sub"><?php _e( "Security tweaks actioned", wp_defender()->domain ) ?></span>
                            </div>
                        </div>
                        <div class="column is-5">
                            <ul class="dev-list bold">
                                <li>
                                    <div>
                                        <span class="list-label"><?php _e( "PHP Version", wp_defender()->domain ) ?></span>
                                        <span class="list-detail"><?php echo $controller->getPHPVersion() ?></span>
                                    </div>
                                </li>
                                <li>
                                    <div>
                                        <span class="list-label"><?php _e( "WordPress Version", wp_defender()->domain ) ?></span>
                                        <span class="list-detail">
                                                <?php
                                                echo \WP_Defender\Behavior\Utils::instance()->getWPVersion();
                                                ?>
                                                </span>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-third">
					<nav role="navigation" aria-label="Filters">
						<ul class="inner-nav is-hidden-mobile">
							<li>
								<a class="<?php echo \Hammer\Helper\HTTP_Helper::retrieve_get( 'view', false ) == false ? 'active' : null ?>"
								href="<?php echo network_admin_url( 'admin.php?page=wdf-hardener' ) ?>">
									<?php _e( "Issues", wp_defender()->domain ) ?>
									<?php
										$tooltip = '';
										if ( $controller->getCount( 'issues' ) > 0 ) :
											$tooltip = 'tooltip="'.esc_attr( sprintf( __('You have %d security tweak(s) needing attention.', wp_defender()->domain ), $controller->getCount( 'issues' ) ) ).'"';
										endif;
									?>
									<span class="def-tag count-issues tag-yellow <?php echo $controller->getCount( 'issues' ) == 0 ? 'wd-hide' : null ?>" <?php echo $tooltip; ?>><?php echo $controller->getCount( 'issues' ) ?></span>
								</a>
							</li>
							<li>
								<a class="<?php echo $controller->isView( 'resolved' ) ? 'active' : null ?>"
								href="<?php echo network_admin_url( 'admin.php?page=wdf-hardener&view=resolved' ) ?>">
									<?php _e( "Resolved", wp_defender()->domain ) ?>
									<span class="count-resolved <?php echo $controller->getCount( 'fixed' ) == 0 ? 'wd-hide' : null ?>"><?php echo $controller->getCount( 'fixed' ) ?></span>
								</a>
							</li>
							<li>
								<a class="<?php echo $controller->isView( 'ignored' ) ? 'active' : null ?>"
								href="<?php echo network_admin_url( 'admin.php?page=wdf-hardener&view=ignored' ) ?>">
									<?php _e( "Ignored", wp_defender()->domain ) ?>
									<span class="count-ignored <?php echo $controller->getCount( 'ignore' ) == 0 ? 'wd-hide' : null ?>"><?php echo $controller->getCount( 'ignore' ) ?></span>
								</a>
							</li>
							<!--                        <li>-->
							<!--                            <a class="-->
							<?php //echo $controller->isView( 'notification' ) ? 'active' : null ?><!--"-->
							<!--                               href="-->
							<?php //echo network_admin_url( 'admin.php?page=wdf-hardener&view=notification' ) ?><!--">-->
							<?php //_e( "Notifications", wp_defender()->domain ) ?><!--</a>-->
							<!--                        </li>-->
						</ul>
					</nav>
                    <div class="is-hidden-tablet mline">
						<nav role="navigation" aria-label="Filters">
							<select class="mobile-nav">
								<option <?php selected( '', \Hammer\Helper\HTTP_Helper::retrieve_get( 'view' ) ) ?>
										value="<?php echo network_admin_url( 'admin.php?page=wdf-hardener' ) ?>"><?php _e( "Issues", wp_defender()->domain ) ?></option>
								<option <?php selected( 'resolved', \Hammer\Helper\HTTP_Helper::retrieve_get( 'view' ) ) ?>
										value="<?php echo network_admin_url( 'admin.php?page=wdf-hardener&view=resolved' ) ?>"><?php _e( "Resolved", wp_defender()->domain ) ?></option>
								<option <?php selected( 'ignored', \Hammer\Helper\HTTP_Helper::retrieve_get( 'view' ) ) ?>
										value="<?php echo network_admin_url( 'admin.php?page=wdf-hardener&view=ignored' ) ?>"><?php _e( "Ignored", wp_defender()->domain ) ?></option>
							</select>
						</nav>
                    </div>
                </div>
                <div class="col-two-third">
					<?php echo $contents ?>
                </div>
            </div>
        </div>
    </div>
</div>