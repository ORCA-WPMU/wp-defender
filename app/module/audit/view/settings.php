<div class="dev-box">
    <div class="box-title">
        <h3><?php _e( "Settings", wp_defender()->domain ) ?></h3>
    </div>
    <div class="box-content">
        <form method="post" id="settings-frm" class="audit-frm">
            <div class="columns">
                <div class="column is-one-third">
                    <label for="login_protection_login_attempt">
						<?php esc_html_e( "Deactivate", wp_defender()->domain ) ?>
                    </label>
                    <span class="sub">
					<?php esc_html_e( "If you no longer want to use this feature you can turn it off at any time.", wp_defender()->domain ) ?>
				</span>
                </div>
                <div class="column">
				<span tooltip="<?php esc_attr_e( "Deactivate Audit Logging", wp_defender()->domain ) ?>"
                      class="toggle">
                        <input type="hidden" name="enabled" value="0"/>
                        <input type="checkbox" checked="checked" name="enabled" value="1"
                               class="toggle-checkbox" id="toggle_audit_logging"/>
                        <label class="toggle-label" for="toggle_audit_logging"></label>
                    </span>
                </div>
            </div>
            <div class="clear line"></div>
			<?php wp_nonce_field( 'saveAuditSettings' ) ?>
            <input type="hidden" name="action" value="saveAuditSettings"/>
            <button type="submit" class="button button-primary float-r">
				<?php esc_html_e( "UPDATE SETTINGS", wp_defender()->domain ) ?>
            </button>
            <div class="clear"></div>
        </form>
    </div>
</div>