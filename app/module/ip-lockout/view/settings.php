<div class="dev-box">
    <div class="box-title">
        <h3><?php _e( "Settings", wp_defender()->domain ) ?></h3>
    </div>
    <div class="box-content">
        <form method="post" id="settings-frm" class="ip-frm">
            <div class="columns">
                <div class="column is-one-third">
                    <label for="login_protection_login_attempt">
						<?php esc_html_e( "Storage", wp_defender()->domain ) ?>
                    </label>
                    <span class="sub">
                        <?php esc_html_e( "Event logs are cached on your local server to speed up load times. You can choose how many days to keep logs for before they are removed.", wp_defender()->domain ) ?>
						</span>
                </div>
                <div class="column">
                    <input size="8" value="<?php echo $settings->storage_days ?>" type="text"
                           class="inline" id="storage_days"
                           name="storage_days"/>
                    <span><?php esc_html_e( "days", wp_defender()->domain ) ?></span>&nbsp;
                    <span class="form-help"><?php _e( "Choose how many days of event logs you’d like to store locally.", wp_defender()->domain ) ?></span>
                </div>
            </div>
            <div class="columns">
                <div class="column is-one-third">
                    <label for="login_protection_login_attempt">
						<?php esc_html_e( "Delete logs", wp_defender()->domain ) ?>
                    </label>
                    <span class="sub">
                        <?php esc_html_e( "If you wish to delete your current logs simply hit delete and this will wipe your logs clean.", wp_defender()->domain ) ?>
						</span>
                </div>
                <div class="column">
                    <button type="button" data-nonce="<?php echo esc_attr( wp_create_nonce( 'lockoutEmptyLogs' ) ) ?>"
                            class="button button-secondary empty-logs"><?php _e( "Delete Logs", wp_defender()->domain ) ?></button>
                    <span class="delete-status"></span>
                    <span class="form-help"><?php _e( "Note: Defender will instantly remove all past event logs, you will not be able to get them back.", wp_defender()->domain ) ?></span>
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