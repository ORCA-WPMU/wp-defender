<div class="dev-box">
    <div class="box-title">
        <h3><?php _e( "Settings", wp_defender()->domain ) ?></h3>
    </div>
    <div class="box-content">
        <form method="post" class="scan-frm scan-settings">
            <div class="columns <?php echo wp_defender()->isFree ? 'has-presale' : null ?>">
                <div class="column is-one-third">
                    <strong><?php _e( "Scan Types", wp_defender()->domain ) ?></strong>
                    <span class="sub">
                        <?php _e( "Choose the scan types you would like to include in your default scan. It's recommended you enable all types.", wp_defender()->domain ) ?>
                    </span>
                </div>
                <div class="column">
                    <span class="toggle" aria-hidden="true" role="presentation">
                        <input type="hidden" name="scan_core" value="0"/>
                        <input role="presentation" type="checkbox" name="scan_core" class="toggle-checkbox" id="core-scan" value="1"
	                        <?php checked( true, $setting->scan_core ) ?>/>
                        <label aria-hidden="true"  class="toggle-label" for="core-scan"></label>
                    </span>
                    <label for="core-scan"><?php _e( "WordPress Core", wp_defender()->domain ) ?></label>
                    <span class="sub inpos">
                        <?php _e( "Defender checks for any modifications or additions to WordPress core files.", wp_defender()->domain ) ?>
                    </span>
                    <div class="clear mline"></div>
                    <span class="toggle" aria-hidden="true" role="presentation">
                        <input type="hidden" name="scan_vuln" value="0"/>
                        <input role="presentation" type="checkbox" class="toggle-checkbox" name="scan_vuln" value="1"
                               id="scan-vuln" <?php checked( true, $setting->scan_vuln ) ?>/>
                        <label aria-hidden="true" class="toggle-label" for="scan-vuln"></label>
                    </span>
                    <label for="scan-vuln"><?php _e( "Plugins & Themes", wp_defender()->domain ) ?></label>
                    <span class="sub inpos">
                        <?php _e( "Defender looks for publicly reported vulnerabilities in your installed plugins and themes.", wp_defender()->domain ) ?>
                    </span>
                    <div class="clear mline"></div>
                    <span class="toggle" aria-hidden="true" role="presentation">
                        <input type="hidden" name="scan_content" value="0"/>
                        <input role="presentation" type="checkbox" class="toggle-checkbox" name="scan_content" value="1"
                               id="scan-content" <?php checked( true, $setting->scan_content ) ?>/>
                        <label aria-hidden="true" class="toggle-label" for="scan-content"></label>
                    </span>
                    <label for="scan-content"><?php _e( "Suspicious Code", wp_defender()->domain ) ?></label>
                    <span class="sub inpos">
                        <?php _e( "Defender looks inside all of your files for suspicious and potentially harmful code.", wp_defender()->domain ) ?>
                    </span>
                </div>
            </div>
            <div class="columns">
                <div class="column is-one-third">
                    <strong><?php _e( "Maximum included file size", wp_defender()->domain ) ?></strong>
                    <span class="sub">
                        <?php _e( "Defender will skip any files larger than this size. The smaller the number, the faster Defender will scan your website.", wp_defender()->domain ) ?>
                    </span>
                </div>
                <div class="column">
                    <input type="text" size="4" value="<?php echo esc_attr( $setting->max_filesize ) ?>"
                           name="max_filesize"> <?php _e( "MB", wp_defender()->domain ) ?>
                </div>
            </div>
            <div class="columns">
                <div class="column is-one-third">
                    <strong><?php _e( "Optional emails", wp_defender()->domain ) ?></strong>
                    <span class="sub">
                        <?php _e( "By default, you'll only get email reports when your site runs into trouble. Turn this option on to get reports even when your site is running smoothly.", wp_defender()->domain ) ?>
                    </span>
                </div>
                <div class="column">
                    <span class="toggle">
                        <input type="hidden" name="always_send" value="0"/>
                        <input type="checkbox" name="always_send" class="toggle-checkbox" value="1"
                               id="always_send" <?php checked( true, $setting->always_send ) ?>/>
                        <label class="toggle-label" for="always_send"></label>
                    </span>
                    <label><?php _e( "Send all scan report emails", wp_defender()->domain ) ?></label>
                </div>
            </div>
            <div class="columns">
                <div class="column is-one-third">
                    <strong><?php _e( "Email subject", wp_defender()->domain ) ?></strong>
                </div>
                <div class="column">
                    <input type="text" name="email_subject" value="<?php echo esc_attr( $setting->email_subject ) ?>"/>
                </div>
            </div>
            <div class="columns">
                <div class="column is-one-third">
                    <strong><?php _e( "Email templates", wp_defender()->domain ) ?></strong>
                    <span class="sub">
                         <?php _e( "When Defender scans your website, a report will be generated with any issues that have been found. You can choose to have reports emailed to you.", wp_defender()->domain ) ?>
                    </span>
                </div>
                <div class="column">
                    <ul class="dev-list">
                        <li>
                            <div>
                                <span class="list-label"><?php _e( "When an issue is found", wp_defender()->domain ) ?></span>
                                <span class="list-detail tr">
                                    <a href="#issue-found" rel="dialog" role="button"><?php _e( "Edit", wp_defender()->domain ) ?></a></span>
                            </div>
                        </li>
                        <li>
                            <div>
                                <span class="list-label"><?php _e( "When no issues are found", wp_defender()->domain ) ?></span>
                                <span class="list-detail tr">
                                    <a href="#all-ok"
                                       rel="dialog" role="button"><?php _e( "Edit", wp_defender()->domain ) ?></a></span>
                            </div>
                        </li>
                    </ul>
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
<dialog id="issue-found" title="<?php esc_attr_e( "Issues found", wp_defender()->domain ) ?>">
    <div class="wp-defender">
        <form method="post" class="scan-frm scan-settings">
            <textarea rows="12" name="email_has_issue"><?php echo $setting->email_has_issue ?></textarea>
            <strong class="small">
				<?php _e( "Available variables", wp_defender()->domain ) ?>
            </strong>
            <input type="hidden" name="action" value="saveScanSettings"/>
            <div class="clearfix"></div>
            <span class="def-tag tag-generic">{USER_NAME}</span>
            <span class="def-tag tag-generic">{SITE_URL}</span>
            <span class="def-tag tag-generic">{ISSUES_COUNT}</span>
            <span class="def-tag tag-generic">{ISSUES_LIST}</span>
			<?php wp_nonce_field( 'saveScanSettings' ) ?>
            <div class="clearfix mline"></div>
            <hr class="mline"/>
            <button type="button"
                    class="button button-light close"><?php _e( "Cancel", wp_defender()->domain ) ?></button>
            <button class="button float-r"><?php _e( "Save Template", wp_defender()->domain ) ?></button>
        </form>
    </div>
</dialog>
<dialog id="all-ok" title="<?php esc_attr_e( 'All OK', wp_defender()->domain ) ?>">
    <div class="wp-defender">
        <form method="post" class="scan-frm scan-settings">
            <input type="hidden" name="action" value="saveScanSettings"/>
			<?php wp_nonce_field( 'saveScanSettings' ) ?>
            <textarea rows="12" name="email_all_ok"><?php echo $setting->email_all_ok ?></textarea>
            <strong class="small">
		        <?php _e( "Available variables", wp_defender()->domain ) ?>
            </strong>
            <div class="clearfix"></div>
            <span class="def-tag tag-generic">{USER_NAME}</span>
            <span class="def-tag tag-generic">{SITE_URL}</span>
            <div class="clearfix mline"></div>
            <hr class="mline"/>
            <button type="button"
                    class="button button-light close"><?php _e( "Cancel", wp_defender()->domain ) ?></button>
            <button class="button float-r"><?php _e( "Save Template", wp_defender()->domain ) ?></button>
        </form>
    </div>
</dialog>