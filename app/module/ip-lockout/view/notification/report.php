<div class="dev-box">
    <div class="box-title">
        <h3><?php esc_html_e( "Reporting", wp_defender()->domain ) ?></h3>
    </div>
    <div class="box-content">
        <form method="post" id="settings-frm" class="ip-frm">
            <div class="columns">
                <div class="column is-one-third">
                    <label>
						<?php esc_html_e( "Lockouts report", wp_defender()->domain ) ?>
                    </label>
                    <span class="sub">
                        <?php esc_html_e( "Configure Defender to automatically email you a lockout report for this website. ", wp_defender()->domain ) ?>
					</span>
                </div>
                <div class="column">
                    <span
                            tooltip="<?php echo esc_attr( __( "Send regular email report", wp_defender()->domain ) ) ?>"
                            class="toggle float-l">
	                                        <input type="hidden" name="report" value="0"/>
                                <input type="checkbox"
                                       name="report" <?php checked( 1, $settings->report ) ?>
                                       value="1" class="toggle-checkbox"
                                       id="toggle_report"/>
                                <label class="toggle-label" for="toggle_report"></label>
                                </span>
                    <label>
						<?php esc_html_e( "Send regular email report", wp_defender()->domain ) ?>
                    </label>
                    <div class="clear mline"></div>
                    <div class="well well-white schedule-box">
                        <strong><?php esc_html_e( "SCHEDULE", wp_defender()->domain ) ?></strong>
                        <label><?php esc_html_e( "Frequency", wp_defender()->domain ) ?></label>
                        <select name="report_frequency">
                            <option <?php selected( '1', $settings->report_frequency ) ?>
                                    value="1"><?php esc_html_e( "Daily", wp_defender()->domain ) ?></option>
                            <option <?php selected( '7', $settings->report_frequency ) ?>
                                    value="7"><?php esc_html_e( "Weekly", wp_defender()->domain ) ?></option>
                            <option <?php selected( '30', $settings->report_frequency ) ?>
                                    value="30"><?php esc_html_e( "Monthly", wp_defender()->domain ) ?></option>
                        </select>
                        <div class="days-container">
                            <label><?php esc_html_e( "Day of the week", wp_defender()->domain ) ?></label>
                            <select name="report_day">
								<?php foreach ( \WP_Defender\Behavior\Utils::instance()->getDaysOfWeek() as $day ): ?>
                                    <option <?php selected( $settings->report_day, strtolower( $day ) ) ?>
                                            value="<?php echo strtolower( $day ) ?>"><?php echo $day ?></option>
								<?php endforeach;; ?>
                            </select>
                        </div>
                        <label><?php esc_html_e( "Time of day", wp_defender()->domain ) ?></label>
                        <select name="report_time">
							<?php foreach ( \WP_Defender\Behavior\Utils::instance()->getTimes() as $timestamp => $time ): ?>
                                <option <?php selected( $settings->report_time, $timestamp ) ?>
                                        value="<?php echo $timestamp ?>"><?php echo strftime( '%I:%M %p', strtotime( $time ) ) ?></option>
							<?php endforeach; ?>
                        </select>
                        <!--						<span>-->
						<?php //printf( esc_html__( "You will receive a lockout report email %s.", wp_defender()->domain ), date_i18n( WD_Utils::get_date_time_format(), \WP_Defender\IP_Lockout\Component\Login_Protection_Api::get_report_sending_time() ) ) ?><!--</span>-->
                    </div>
                </div>
            </div>

            <div class="columns">
                <div class="column is-one-third">
                    <label>
						<?php esc_html_e( "Email recipients", wp_defender()->domain ) ?>
                    </label>
                    <span class="sub">
						<?php esc_html_e( "Choose which of your website’s users will receive the lockout report.", wp_defender()->domain ) ?>
					</span>
                </div>
                <div class="column">
					<?php $email_search->renderInput() ?>
                </div>
            </div>
            <div class="clear line"></div>
			<?php wp_nonce_field( 'saveLockoutSettings' ) ?>
            <input type="hidden" name="action" value="saveLockoutSettings"/>
            <button type="submit" class="button button-primary float-r">
				<?php esc_html_e( "UPDATE SETTINGS", wp_defender()->domain ) ?>
            </button>
            <div class="clear"></div>
        </form>
    </div>
</div>