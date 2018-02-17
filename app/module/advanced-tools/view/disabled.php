<div class="dev-box">
    <div class="box-title">
        <h3 class="def-issues-title">
			<?php _e( "Two Factor Authentication", wp_defender()->domain ) ?>
        </h3>
    </div>
    <div class="box-content issues-box-content tc">
        <img src="<?php echo wp_defender()->getPluginUrl() . 'assets/img/2factor-disabled.svg' ?>"/>
        <p>
			<?php _e( "Beef up your website’s security with two-factor authentication. Add an extra step in the login process so that users are required to enter a password and an app-generated passcode using their phone – the best protection against brute force attacks.", wp_defender()->domain ) ?>
        </p>
        <form method="post" id="advanced-settings-frm" class="advanced-settings-frm">

            <div class="clear line"></div>
            <input type="hidden" name="action" value="saveAdvancedSettings"/>
			<?php wp_nonce_field( 'saveAdvancedSettings' ) ?>
            <input type="hidden" name="enabled" value="1"/>
            <button type="submit" class="button button-primary">
				<?php _e( "Activate", wp_defender()->domain ) ?>
            </button>
            <div class="clear"></div>
        </form>
    </div>
</div>