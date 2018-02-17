<?php if ( ! is_wp_error( $data ) ): ?>
    <div class="bulk-nav">
        <div class="bulk-action">

        </div>
        <div class="nav">
            <span><?php printf( __( "%d Results", wp_defender()->domain ), $data['total_items'] ) ?></span>
            <div class="button-group is-hidden-mobile">
				<?php echo $pagination ?>
            </div>
        </div>
        <div class="clear"></div>
    </div>
    <div class="clear"></div>
	<?php if ( count( $data['data'] ) ): ?>
        <div id="audit-table">
            <table>
                <thead>
                <tr>
                    <th><?php _e( "Summary", wp_defender()->domain ) ?></th>
                    <th><?php _e( "Time", wp_defender()->domain ) ?></th>
                    <th class="is-hidden-touch"><?php _e( "IP Address", wp_defender()->domain ) ?></th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
				<?php foreach ( $data['data'] as $row ): ?>
					<?php
					$timestamp = is_array( $row['timestamp'] ) ? $row['timestamp'][1] : $row['timestamp'];
					?>
                    <tr class="critical show-info" data-target="#<?php echo $timestamp ?>">
                        <td><?php echo wp_trim_words( $row['msg'], 10 ) ?></td>
                        <td><?php
							echo \WP_Defender\Module\Audit\Component\Audit_API::time_since( $timestamp ) . esc_html__( " ago", wp_defender()->domain ); ?>
                        </td>
                        <td class="is-hidden-touch">
							<?php echo $row['ip'] ?>
                        </td>
                        <td>
                            <a href="#<?php echo $timestamp ?>">
                                <i class="dev-icon dev-icon-caret_down"></i>
                            </a>
                        </td>
                    </tr>
                    <tr class="table-info wd-hide" id="<?php echo $timestamp ?>">
                        <td colspan="4">
                            <div class="dev-box">
                                <div class="box-content">
                                    <strong><?php _e( "Description", wp_defender()->domain ) ?></strong>
                                    <p class="mline"><?php echo $row['msg'] ?></p>
                                    <table class="log-detail is-hidden-touch">
                                        <thead>
                                        <tr>
                                            <th><?php _e( "Context", wp_defender()->domain ) ?></th>
                                            <th><?php _e( "Type", wp_defender()->domain ) ?></th>
                                            <th><?php _e( "IP address", wp_defender()->domain ) ?></th>
                                            <th><?php _e( "User", wp_defender()->domain ) ?></th>
                                            <th><?php _e( "Date / Time", wp_defender()->domain ) ?></th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <tr>
                                            <td>
                                                <a class="afilter"
                                                   href="<?php echo $controller->buildFilterUrl( 'context', $row['context'] ) ?>">
													<?php echo ucwords( \WP_Defender\Module\Audit\Component\Audit_API::get_action_text( $row['context'] ) ) ?>
                                                </a>
                                            </td>
                                            <td>
                                                <a class="afilter"
                                                   href="<?php echo $controller->buildFilterUrl( 'event_type[]', $row['event_type'] ) ?>">
													<?php echo ucwords( \WP_Defender\Module\Audit\Component\Audit_API::get_action_text( $row['event_type'] ) ) ?>
                                                </a>
                                            </td>
                                            <td>
                                                <a class="afilter"
                                                   href="<?php echo $controller->buildFilterUrl( 'ip', $row['ip'] ) ?>">
													<?php echo $row['ip'] ?>
                                                </a>
                                            </td>
                                            <td>
                                                <a class="afilter"
                                                   href="<?php echo $controller->buildFilterUrl( 'term', $row['user_id'] ) ?>">
													<?php
													if ( $row['user_id'] == 0 ) {
														_e( "Guest", wp_defender()->domain );
													} else {
														echo \WP_Defender\Behavior\Utils::instance()->getDisplayName( $row['user_id'] );
													}
													?>
                                                </a>
                                            </td>
                                            <td>
												<?php
												echo $controller->formatDateTime( date( 'Y-m-d H:i:s', $timestamp ) );
												?>
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                    <ul class="dev-list is-hidden-desktop">
                                        <li>
                                            <div class="list-label">
                                                <strong><?php _e( "Context", wp_defender()->domain ) ?></strong>
                                            </div>
                                            <div class="list-detail">
                                                <a class="afilter"
                                                   href="<?php echo $controller->buildFilterUrl( 'context', $row['context'] ) ?>">
													<?php echo ucwords( \WP_Defender\Module\Audit\Component\Audit_API::get_action_text( $row['context'] ) ) ?>
                                                </a>
                                            </div>
                                        </li>
                                        <li>
                                            <div class="list-label">
                                                <strong><?php _e( "Type", wp_defender()->domain ) ?></strong>
                                            </div>
                                            <div class="list-detail">
                                                <a class="afilter"
                                                   href="<?php echo $controller->buildFilterUrl( 'event_type[]', $row['event_type'] ) ?>">
													<?php echo ucwords( \WP_Defender\Module\Audit\Component\Audit_API::get_action_text( $row['event_type'] ) ) ?>
                                                </a>
                                            </div>
                                        </li>
                                        <li>
                                            <div class="list-label">
                                                <strong><?php _e( "IP address", wp_defender()->domain ) ?></strong>
                                            </div>
                                            <div class="list-detail">
                                                <a class="afilter"
                                                   href="<?php echo $controller->buildFilterUrl( 'ip', $row['ip'] ) ?>">
													<?php echo $row['ip'] ?>
                                                </a>
                                            </div>
                                        </li>
                                        <li>
                                            <div class="list-label">
                                                <strong><?php _e( "User", wp_defender()->domain ) ?></strong>
                                            </div>
                                            <div class="list-detail">
                                                <a class="afilter"
                                                   href="<?php echo $controller->buildFilterUrl( 'term', $row['user_id'] ) ?>">
													<?php
													if ( $row['user_id'] == 0 ) {
														_e( "Guest", wp_defender()->domain );
													} else {
														echo \WP_Defender\Behavior\Utils::instance()->getDisplayName( $row['user_id'] );
													}
													?>
                                                </a>
                                            </div>
                                        </li>
                                        <li>
                                            <div class="list-label">
                                                <strong><?php _e( "Date / Time", wp_defender()->domain ) ?></strong>
                                            </div>
                                            <div class="list-detail">
	                                            <?php
	                                            echo $controller->formatDateTime( date( 'Y-m-d H:i:s', $timestamp ) );
	                                            ?>
                                            </div>
                                        </li>
                                    </ul>
									<?php if ( ! \WP_Defender\Module\IP_Lockout\Model\Settings::instance()->isBlacklist( $row['ip'] ) ): ?>
                                        <div class="clear mline"></div>
                                        <div class="well">
                                            <div class="columns">
                                                <div class="column is-10">
                                                    <p><?php _e( "You can ban this IP address from being able to access your site, just be sure it’s not a legitimate operation of a plugin or service that needs access.", wp_defender()->domain ) ?></p>
                                                </div>
                                                <div class="column is-2 tc">
                                                    <form method="post" class="audit-frm banIP">
                                                        <input type="hidden" name="action" value="lockoutIPAction"/>
                                                        <input type="hidden" name="type" value="blacklist">
                                                        <input type="hidden" name="ip"
                                                               value="<?php echo $row['ip'] ?>"/>
														<?php wp_nonce_field( 'lockoutIPAction', 'nonce' ) ?>
                                                        <button type="submit" class="button">
															<?php _e( "Ban Ip", wp_defender()->domain ) ?></button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
									<?php endif; ?>
                                </div>
                            </div>
                        </td>
                    </tr>
				<?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="clear"></div>
        <div class="bulk-nav">
            <div class="bulk-action">

            </div>
            <div class="nav">
                <span><?php printf( __( "%d Results", wp_defender()->domain ), $data['total_items'] ) ?></span>
                <div class="button-group is-hidden-mobile">
					<?php echo $pagination ?>
                </div>
            </div>
            <div class="clear"></div>
        </div>
	<?php else: ?>
        <div class="well with-cap well-blue">
            <i class="def-icon icon-info fill-blue"></i>
			<?php _e( "There have been no events logged in the selected time period.", wp_defender()->domain ) ?>
        </div>
	<?php endif; ?>
<?php else: ?>
    <div class="well well-error with-cap">
        <i class="def-icon icon-warning"></i>
		<?php echo $data->get_error_message() ?>
    </div>
<?php endif; ?>