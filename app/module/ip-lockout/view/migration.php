<div class="wrap">
    <div id="wp-defender" class="wp-defender">
        <div class="iplockout">
            <div class="advanced-tools">
                <h2 class="title">
			        <?php _e( "Migration", wp_defender()->domain ) ?>
                </h2>
            </div>
        </div>
    </div>
</div>

<dialog id="defLockoutUpgrade">
    <div class="line">
		<?php _e( "Please hold on, we are updating your data, please don't close this tab...", wp_defender()->domain ) ?>
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
    <form method="post" id="moving-data" class="ip-frm">
        <input type="hidden" name="action" value="migrateData"/>
		<?php
		wp_nonce_field( 'processScan' );
		?>
    </form>
</dialog>