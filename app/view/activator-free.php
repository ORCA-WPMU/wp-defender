<dialog id="activator">
    <div class="activate-picker">
        <div class="line end">
	        <?php _e( "Welcome to Defender! Let's quickly set up the most important security features, then you can fine tune each setting later. Our recommendations are turned on by default.", wp_defender()->domain ) ?>
        </div>
        <form method="post">
            <input type="hidden" value="activateModule" name="action"/>
			<?php wp_nonce_field( 'activateModule' ) ?>
            <div class="columns">
                <div class="column is-10">
                    <strong><?php
						if ( wp_defender()->isFree ) {
							_e( "File Scanning", wp_defender()->domain );
						} else {
							_e( "Automatic File Scans & Reporting", wp_defender()->domain );
						} ?></strong>
                    <p class="sub">
						<?php _e( "Scan your website for file changes, vulnerabilities and injected code and get notified about anything suspicious.", wp_defender()->domain ) ?>
                    </p>
                </div>
                <div class="column is-2">
               <span class="toggle float-r">
                    <input type="checkbox"
                           name="activator[]" checked
                           class="toggle-checkbox" id="active_scan"
                           value="activate_scan"/>
                    <label class="toggle-label" for="active_scan"></label>
                </span>
                </div>
            </div>
            <div class="columns">
                <div class="column is-10">
                    <strong><?php _e( "IP Lockouts", wp_defender()->domain ) ?></strong>
                    <p class="sub">
	                    <?php _e( "Protect your login area and give Defender the nod to automatically lockout any suspicious behavior.", wp_defender()->domain ) ?>
                    </p>
                </div>
                <div class="column is-2">
               <span class="toggle float-r">
                    <input type="checkbox" checked
                           name="activator[]" class="toggle-checkbox" id="activate_lockout" value="activate_lockout"/>
                    <label class="toggle-label" for="activate_lockout"></label>
                </span>
                </div>
            </div>
            <div class="columns last">
                <div class="column is-9">
                    <p class="sub">
						<?php _e( "These services will be configured with recommended settings. You can change these at any time.", wp_defender()->domain ) ?>
                    </p>
                </div>
                <div class="column is-3 tr">
                    <button type="submit" class="button"><?php _e( "Get Started", wp_defender()->domain ) ?></button>
                </div>
            </div>
        </form>
    </div>
    <div class="activate-progress wd-hide">
        <div class="line">
			<?php _e( "Just a moment while Defender activates those services for you...", wp_defender()->domain ) ?>
        </div>
        <div class="well mline">
            <div class="scan-progress">
                <div class="scan-progress-text">
                    <img src="<?php echo wp_defender()->getPluginUrl() ?>assets/img/loading.gif" width="18"
                         height="18"/>
                    <span>0%</span>
                </div>
                <div class="scan-progress-bar">
                    <span style="width: 0%"></span>
                </div>
            </div>
        </div>
        <p class="tc sub status-text"></p>
    </div>
</dialog>
<script type="text/javascript">
    jQuery(function ($) {
        //hack to fix the dialog toggle
        setTimeout(function () {
            $('.wd-activator label').each(function () {
                var parent = $(this).closest('div');
                var input = parent.find('#' + $(this).attr('for'));
                $(this).on('click', function () {
                    if (input.prop('checked') == false) {
                        input.prop('checked', true);
                    } else {
                        input.prop('checked', false);
                    }
                })
            })
        }, 500)
    })
</script>