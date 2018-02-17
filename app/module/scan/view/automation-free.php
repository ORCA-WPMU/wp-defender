<div class="dev-box report-sale">
    <div class="box-title">
        <h3><?php _e( "Reporting", wp_defender()->domain ) ?></h3>
        <a class="button button-green button-small"
           href="<?php echo \WP_Defender\Behavior\Utils::instance()->campaignURL('defender_filescanning_reports_upgrade_button') ?>" target="_blank"><?php _e( "Upgrade to Pro", wp_defender()->domain ) ?></a>
    </div>
    <div class="box-content">
        <form method="post" class="">
            <div class="sale-overlay">

            </div>
            <div class="columns">
                <div class="column is-one-third">
                    <strong><?php _e( "Schedule scans", wp_defender()->domain ) ?></strong>
                    <span class="sub">
                        <?php _e( "Configure Defender to automatically and regularly scan your website and email you reports.", wp_defender()->domain ) ?>
                    </span>
                </div>
                <div class="column">
                    <span class="toggle">
                        <input type="checkbox" class="toggle-checkbox" name="notification" value="1" id="chk1"/>
                        <label class="toggle-label" for="chk1"></label>
                    </span>
                    <label><?php _e( "Run regular scans & reports", wp_defender()->domain ) ?></label>
                    <div class="clear mline"></div>
                    <div class="well well-white schedule-box">
                        <strong><?php _e( "Schedule", wp_defender()->domain ) ?></strong>
                        <label><?php _e( "Frequency", wp_defender()->domain ) ?></label>
                        <select name="frequency">
                            <option value="1"><?php _e( "Daily", wp_defender()->domain ) ?></option>
                        </select>
                        <div class="days-container">
                            <label><?php _e( "Day of the week", wp_defender()->domain ) ?></label>
                            <select name="day">
								<?php foreach ( \WP_Defender\Behavior\Utils::instance()->getDaysOfWeek() as $day ): ?>
                                    <option value="<?php echo $day ?>"><?php echo ucfirst( $day ) ?></option>
								<?php endforeach; ?>
                            </select>
                        </div>
                        <label><?php _e( "Time of day", wp_defender()->domain ) ?></label>
                        <select name="time">
							<?php foreach ( \WP_Defender\Behavior\Utils::instance()->getTimes() as $time ): ?>
                                <option value="<?php echo $time ?>"><?php echo strftime( '%I:%M %p', strtotime( $time ) ) ?></option>
							<?php endforeach;; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="columns last">
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
        </form>
        <div class="presale-text">
            <div>
				<?php printf( __( "Schedule automated file scanning and email reporting for all your websites. This feature is included in a WPMU DEV membership along with 100+ plugins & themes, 24/7 support and lots of handy site management tools  – <a target='_blank' href=\"%s\">Try it all FREE today!</a>", wp_defender()->domain ), \WP_Defender\Behavior\Utils::instance()->campaignURL('defender_filescanning_reports_upsell_link') ) ?>
            </div>
        </div>
    </div>
</div>