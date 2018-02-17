<div class="dev-box">
    <div class="box-title">
        <h3><?php _e( "EVENT LOGS", wp_defender()->domain ) ?></h3>
    </div>
    <div class="box-content tc">
        <img src="<?php echo wp_defender()->getPluginUrl() ?>assets/img/def-stand.svg" class="mline">
        <div class="line">
			<?php _e( "Track and log each and every event when changes are made to your website and get
			detailed reports on what’s going on behind the scenes, including any hacking attempts on
			your site.", wp_defender()->domain ) ?>
        </div>
        <form method="post" class="audit-frm active-audit">
            <input type="hidden" name="action" value="activeAudit"/>
			<?php wp_nonce_field( 'activeAudit' ) ?>
            <button type="submit" class="button"><?php _e( "Activate", wp_defender()->domain ) ?></button>
        </form>
    </div>
</div>