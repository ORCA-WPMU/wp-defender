<div class="dev-box">
	<div class="box-title">
		<h3><?php esc_html_e( "Login Protection", wp_defender()->domain ) ?></h3>
	</div>
	<div class="box-content tc">
		<img
			src="<?php echo wp_defender()->getPluginUrl() ?>assets/img/lockout-man.svg"
			class="line"/>
		<p class="line max-600">
			<?php esc_html_e( "Watch and protect your login area for attackers trying to randomly guess login
				details for your site. Defender will lock them out after a set number of failed attempts.", wp_defender()->domain ) ?>
		</p>
		<form method="post" id="settings-frm" class="ip-frm">
			<?php wp_nonce_field( 'saveLockoutSettings' ) ?>
            <input type="hidden" name="action" value="saveLockoutSettings"/>
			<input type="hidden" name="login_protection" value="1"/>
			<button type="submit" class="button button-primary">
				<?php esc_html_e( "Enable", wp_defender()->domain ) ?>
			</button>
		</form>
	</div>
</div>