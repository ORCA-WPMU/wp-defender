<div class="wrap">
    <div id="wp-defender" class="wp-defender">
        <div class="wdf-scanning">
            <h2 class="title">
				<?php _e( "File Scanning", wp_defender()->domain ) ?>
                <span>
                    <form id="start-a-scan" method="post" class="scan-frm">
						<?php
						wp_nonce_field( 'startAScan' );
						?>
                        <input type="hidden" name="action" value="startAScan"/>
                        <button type="submit"
                                class="button button-small"><?php _e( "New Scan", wp_defender()->domain ) ?></button>
                </form>
            </span>
            </h2>

            <div class="scan">
                <div class="dev-box summary-box">
                    <div class="box-content">
                        <div class="columns">
                            <div class="column is-7 issues-count">
                                <div>
                                    <h5 class="def-issues def-issues-top-left"><?php echo $countAll = $model->countAll( \WP_Defender\Module\Scan\Model\Result_Item::STATUS_ISSUE ) ?></h5>
                                    <?php if ( $countAll > 0 ) : ?>
                                    <span class="def-issues-top-left-icon" tooltip="<?php esc_attr_e( sprintf( __('You have %d suspicious file(s) needing attention.', wp_defender()->domain ), $countAll ) ); ?>">
                                    <?php else: ?>
                                    <span class="def-issues-top-left-icon" tooltip="<?php esc_attr_e( 'Your code is clean, the skies are clear.', wp_defender()->domain ); ?>">
                                    <?php endif; ?>
									<?php
									$icon = $countAll == 0 ? ' <i class="def-icon icon-tick" aria-hidden="true"></i>' : ' <i class="def-icon icon-warning fill-red" aria-hidden="true"></i>';
									echo $icon;
									?>
                                </span>
                                    <div class="clear"></div>
                                    <span class="sub"><?php _e( "File scanning issues need attention.", wp_defender()->domain ) ?></span>
                                    <div class="clear mline"></div>
                                    <strong><?php echo $lastScanDate ?></strong>
                                    <span class="sub"><?php _e( "Last scan", wp_defender()->domain ) ?></span>
                                </div>
                            </div>
                            <div class="column is-5">
                                <ul class="dev-list bold">
                                    <li>
                                        <div>
                                            <span class="list-label"><?php _e( "WordPress Core", wp_defender()->domain ) ?></span>
                                            <span class="list-detail def-issues-top-right-wp">
                                                <?php echo $model->getCount( 'core' ) == 0 ? ' <i class="def-icon icon-tick"></i>' : '<span class="def-tag tag-error">' . '<span class="def-issues">' . $model->getCount( 'core' ) . '</span></span>' ?>
                                            </span>
                                        </div>
                                    </li>
                                    <li>
                                        <div>
                                            <span class="list-label"><?php _e( "Plugins & Themes", wp_defender()->domain ) ?></span>
                                            <span class="list-detail def-issues-top-right-pt">
                                                <?php if ( \WP_Defender\Behavior\Utils::instance()->getAPIKey() ): ?>
	                                                <?php echo $model->getCount( 'vuln' ) == 0 ? ' <i class="def-icon icon-tick"></i>' : '<span class="def-tag tag-error">' . $model->getCount( 'vuln' ) . '</span>' ?>
                                                <?php else: ?>
                                                    <a href="<?php echo \WP_Defender\Behavior\Utils::instance()->campaignURL('defender_filescanning_summary_pro_tag') ?>" target="_blank"
                                                       class="button button-pre button-small"
                                                       tooltip="<?php esc_attr_e( "Try Defender Pro free today", wp_defender()->domain ) ?>">
                                                        <?php _e( "Pro Feature", wp_defender()->domain ) ?>
                                                    </a>
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                    </li>
                                    <li>
                                        <div>
                                            <span class="list-label"><?php _e( "Suspicious Code", wp_defender()->domain ) ?></span>
                                            <span class="list-detail def-issues-top-right-sc">
                                                <?php if ( \WP_Defender\Behavior\Utils::instance()->getAPIKey() ): ?>
	                                                <?php echo $model->getCount( 'content' ) == 0 ? ' <i class="def-icon icon-tick"></i>' : '<span class="def-tag tag-error">' . $model->getCount( 'content' ) . '</span>' ?>
                                                <?php else: ?>
                                                    <a href="<?php echo \WP_Defender\Behavior\Utils::instance()->campaignURL('defender_filescanning_summary_pro_tag') ?>" target="_blank"
                                                       class="button button-pre button-small"
                                                       tooltip="<?php esc_attr_e( "Try Defender Pro free today", wp_defender()->domain ) ?>" >
                                                        <?php _e( "Pro Feature", wp_defender()->domain ) ?>
                                                    </a>
                                                <?php endif; ?>
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
								<li class="issues-nav">
									<a class="<?php echo \Hammer\Helper\HTTP_Helper::retrieve_get( 'view', false ) == false ? 'active' : null ?>"
									href="<?php echo network_admin_url( 'admin.php?page=wdf-scan' ) ?>">
										<?php _e( "Issues", wp_defender()->domain ) ?>
										<?php
										$issues = $model->countAll( \WP_Defender\Module\Scan\Model\Result_Item::STATUS_ISSUE );
										$tooltip = '';
										if ( $issues > 0 ) :
											$tooltip = 'tooltip="' . esc_attr( sprintf( __("You have %d suspicious file(s) needing attention", wp_defender()->domain ), $countAll ) ) . '"';
										endif;
										echo $issues > 0 ? '<span class="def-tag tag-error def-issues-below" ' . $tooltip . '>' . $issues . '</span>' : '' ?>
									</a>
								</li>
								<!--                            <li>-->
								<!--                                <a class="-->
								<?php //echo $controller->isView( 'cleaned' ) ? 'active' : null ?><!--"-->
								<!--                                   href="-->
								<?php //echo network_admin_url( 'admin.php?page=wdf-scan&view=cleaned' ) ?><!--">--><?php //_e( "Cleaned", wp_defender()->domain ) ?>
								<!--                                    <span>-->
								<!--                                        --><?php
								//                                        $issues = $model->countAll( \WP_Defender\Module\Scan\Model\Result_Item::STATUS_FIXED );
								//                                        echo $issues > 0 ? $issues : '' ?>
								<!--                                    </span>-->
								<!--                                </a>-->
								<!--                            </li>-->
								<li>
									<a class="<?php echo $controller->isView( 'ignored' ) ? 'active' : null ?>"
									href="<?php echo network_admin_url( 'admin.php?page=wdf-scan&view=ignored' ) ?>">
										<?php _e( "Ignored", wp_defender()->domain ) ?>
										<span class="def-ignored">
											<?php
											$issues = $model->countAll( \WP_Defender\Module\Scan\Model\Result_Item::STATUS_IGNORED );
											echo $issues > 0 ? $issues : '' ?>
										</span>
									</a>
								</li>
								<li>
									<a class="<?php echo $controller->isView( 'settings' ) ? 'active' : null ?>"
									href="<?php echo network_admin_url( 'admin.php?page=wdf-scan&view=settings' ) ?>">
										<?php _e( "Settings", wp_defender()->domain ) ?></a>
								</li>
								<li>
									<a class="<?php echo $controller->isView( 'reporting' ) ? 'active' : null ?>"
									href="<?php echo network_admin_url( 'admin.php?page=wdf-scan&view=reporting' ) ?>">
										<?php _e( "Reporting", wp_defender()->domain ) ?></a>
								</li>
							</ul>
						</nav>
                        <div class="is-hidden-tablet mline">
							<nav role="navigation" aria-label="Filters">
								<select class="mobile-nav">
									<option <?php selected( '', \Hammer\Helper\HTTP_Helper::retrieve_get( 'view' ) ) ?>
											value="<?php echo network_admin_url( 'admin.php?page=wdf-scan' ) ?>"><?php _e( "Issues", wp_defender()->domain ) ?></option>
									<option <?php selected( 'ignored', \Hammer\Helper\HTTP_Helper::retrieve_get( 'view' ) ) ?>
											value="<?php echo network_admin_url( 'admin.php?page=wdf-scan&view=ignored' ) ?>"><?php _e( "Ignored", wp_defender()->domain ) ?></option>
									<option <?php selected( 'settings', \Hammer\Helper\HTTP_Helper::retrieve_get( 'view' ) ) ?>
											value="<?php echo network_admin_url( 'admin.php?page=wdf-scan&view=settings' ) ?>"><?php _e( "Settings", wp_defender()->domain ) ?></option>
									<option <?php selected( 'reporting', \Hammer\Helper\HTTP_Helper::retrieve_get( 'view' ) ) ?>
											value="<?php echo network_admin_url( 'admin.php?page=wdf-scan&view=reporting' ) ?>"><?php _e( "Reporting", wp_defender()->domain ) ?></option>
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
</div>
<?php if ( wp_defender()->isFree ) {
	$controller->renderPartial( 'pro-feature' );
} ?>