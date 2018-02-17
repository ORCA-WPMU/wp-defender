<div class="dev-box">
    <div class="box-title">
        <h3><?php _e( "Reporting", wp_defender()->domain ) ?></h3>
    </div>
    <div class="box-content">
        <form method="post" class="scan-frm scan-settings">
            <div class="columns">
                <div class="column is-one-third">
                    <strong><?php _e( "Schedule scans", wp_defender()->domain ) ?></strong>
                    <span class="sub">
                        <?php _e( "Configure Defender to automatically and regularly scan your website and email you reports.", wp_defender()->domain ) ?>
                    </span>
                </div>
                <div class="column">
                    <span class="toggle">
                        <input type="hidden" name="notification" value="0"/>
                        <input type="checkbox" class="toggle-checkbox" name="notification" value="1"
                               id="chk1" <?php checked( 1, $setting->notification ) ?>/>
                        <label class="toggle-label" for="chk1"></label>
                    </span>
                    <label><?php _e( "Run regular scans & reports", wp_defender()->domain ) ?></label>
                    <div class="clear mline"></div>
                    <div class="well well-white schedule-box">
                        <strong><?php _e( "Schedule", wp_defender()->domain ) ?></strong>
                        <label><?php _e( "Frequency", wp_defender()->domain ) ?></label>
                        <select name="frequency">
                            <option <?php selected( 1, $setting->frequency ) ?>
                                    value="1"><?php _e( "Daily", wp_defender()->domain ) ?></option>
                            <option <?php selected( 7, $setting->frequency ) ?>
                                    value="7"><?php _e( "Weekly", wp_defender()->domain ) ?></option>
                            <option <?php selected( 30, $setting->frequency ) ?>
                                    value="30"><?php _e( "Monthly", wp_defender()->domain ) ?></option>
                        </select>
                        <div class="days-container">
                            <label><?php _e( "Day of the week", wp_defender()->domain ) ?></label>
                            <select name="day">
								<?php foreach ( \WP_Defender\Behavior\Utils::instance()->getDaysOfWeek() as $day ): ?>
                                    <option <?php selected( $day, $setting->day ) ?>
                                            value="<?php echo $day ?>"><?php echo ucfirst( $day ) ?></option>
								<?php endforeach; ?>
                            </select>
                        </div>
                        <label><?php _e( "Time of day", wp_defender()->domain ) ?></label>
                        <select name="time">
							<?php foreach ( \WP_Defender\Behavior\Utils::instance()->getTimes() as $time ): ?>
                                <option <?php selected( $time, $setting->time ) ?>
                                        value="<?php echo $time ?>"><?php echo strftime( '%I:%M %p', strtotime( $time ) ) ?></option>
							<?php endforeach;; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="columns">
                <div class="column is-one-third">
                    <strong><?php _e( "Email recipients", wp_defender()->domain ) ?></strong>
                    <span class="sub">
                        <?php _e( "Choose which of your website’s users will receive scan report results to their email inboxes.", wp_defender()->domain ) ?>
                    </span>
                </div>
                <div class="column">
					<?php $email->renderInput() ?>
                </div>
            </div>
            <div class="clear line"></div>
            <input type="hidden" name="action" value="saveScanSettings"/>
			<?php wp_nonce_field( 'saveScanSettings' ) ?>
            <button class="button float-r"><?php _e( "Update Settings", wp_defender()->domain ) ?></button>
            <div class="clear"></div>
        </form>
    </div>
</div>