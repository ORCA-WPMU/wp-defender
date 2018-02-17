<div class="rule closed" id="security_key">
    <div class="rule-title">
		<?php if ( $controller->check() == false ): ?>
            <i class="def-icon icon-warning" aria-hidden="true"></i>
		<?php else: ?>
            <i class="def-icon icon-tick" aria-hidden="true"></i>
		<?php endif; ?>
		<?php _e( "Update old security keys", wp_defender()->domain ) ?>
    </div>
    <div class="rule-content">
        <h3><?php _e( "Overview", wp_defender()->domain ) ?></h3>
        <div class="line end">
            <p><?php _e( "We recommend changing your security keys every 60 days", wp_defender()->domain ) ?></p>
            <div class="security-reminder">
				<?php esc_html_e( "Remind me to change my security keys every", wp_defender()->domain ) ?>
                <form method="post" class="hardener-frm" id="reminder-date">
                    <select name="remind_date">
                        <option
                                value="30 days" <?php selected( '30 days', $interval ) ?>><?php esc_html_e( '30 Days', wp_defender()->domain ) ?></option>
                        <option
                                value="60 days" <?php selected( '60 days', $interval ) ?>><?php esc_html_e( '60 Days', wp_defender()->domain ) ?></option>
                        <option
                                value="90 days" <?php selected( '90 days', $interval ) ?>><?php esc_html_e( '90 Days', wp_defender()->domain ) ?></option>
                        <option
                                value="6 months" <?php selected( '6 months', $interval ) ?>><?php esc_html_e( '6 Months', wp_defender()->domain ) ?></option>
                        <option
                                value="1 year" <?php selected( '1 year', $interval ) ?>><?php esc_html_e( '1 Year', wp_defender()->domain ) ?></option>
                    </select>
                    <input type="hidden" name="action" value="updateSecurityReminder"/>
                    <button type="submit" class="button">
						<?php _e( "Update", wp_defender()->domain ) ?></button>
                </form>
            </div>
        </div>
        <h3>
			<?php _e( "How to fix", wp_defender()->domain ) ?>
        </h3>
        <div class="well">
			<?php if ( $controller->check() ): ?>
				<?php printf( esc_html__( "Your salt keys are %d days old. You are fine for now.", wp_defender()->domain ), $daysAgo ) ?>
			<?php else: ?>
                <div class="line">
                    <p><?php _e( "We can regenerate your key salts instantly for you and they will be good for another <span class=\"expiry-days\">60 days</span>. Note that this will log all users out of your site.", wp_defender()->domain ) ?></p>
                </div>
                <form method="post" class="hardener-frm rule-process">
					<?php $controller->createNonceField(); ?>
                    <input type="hidden" name="action" value="processHardener"/>
                    <input type="hidden" name="slug" value="<?php echo $controller::$slug ?>"/>
                    <button class="button float-r"
                            type="submit"><?php _e( "Regenerate Security Keys", wp_defender()->domain ) ?></button>
                </form>
				<?php $controller->showIgnoreForm() ?>
			<?php endif; ?>
        </div>
        <div class="clear"></div>
    </div>
</div>