<div class="dev-box">
    <div class="box-title">
        <h3><?php esc_html_e( "404 DETECTION", wp_defender()->domain ) ?></h3>
    </div>
    <div class="box-content tc">
        <img src="<?php echo wp_defender()->getPluginUrl() ?>assets/img/lockout-man.svg"
             class="intro line"/>
        <p class="intro max-600 line">
			<?php esc_html_e( "With 404 detection enabled, Defender will keep an eye out for IP addresses that repeatedly request pages on your website that don’t exist and then temporarily block them from accessing your site.", wp_defender()->domain ) ?>
        </p>
        <form method="post" id="settings-frm" class="ip-frm">
			<?php wp_nonce_field( 'saveLockoutSettings' ) ?>
            <input type="hidden" name="action" value="saveLockoutSettings"/>
            <input type="hidden" name="detect_404" value="1"/>
            <button type="submit" class="button button-primary">
				<?php esc_html_e( "Enable", wp_defender()->domain ) ?>
            </button>
        </form>
    </div>
</div>