<div class="rule closed" id="wp-version">
    <div class="rule-title">
		<?php if ( $controller->check() == false ): ?>
            <i class="def-icon icon-warning" aria-hidden="true"></i>
		<?php else: ?>
            <i class="def-icon icon-tick" aria-hidden="true"></i>
		<?php endif; ?>
		<?php _e( "Update WordPress to latest version", wp_defender()->domain ) ?>
    </div>
    <div class="rule-content">
        <h3><?php _e( "Overview", wp_defender()->domain ) ?></h3>
        <div class="line">
			<?php _e( "WordPress is an extremely popular platform, and with that popularity comes hackers that increasingly want to exploit WordPress based websites. Leaving your WordPress installation out of date is an almost guaranteed way to get hacked!", wp_defender()->domain ) ?>
        </div>
        <div class="columns version-col">
            <div class="column">
                <strong><?php _e( "Current version", wp_defender()->domain ) ?></strong>
			    <?php $class = $controller->check() ? 'def-tag tag-success' : 'def-tag tag-error' ?>
                <span class="<?php echo $class ?>">
                    <?php echo \WP_Defender\Behavior\Utils::instance()->getWPVersion() ?>
                </span>
            </div>
            <div class="column">
                <strong><?php _e( "Recommend Version", wp_defender()->domain ) ?></strong>
                <span><?php echo $controller->getService()->getLatestVersion() ?></span>
            </div>
        </div>
        <h3>
			<?php _e( "How to fix", wp_defender()->domain ) ?>
        </h3>
        <div class="well">
			<?php if ( $controller->check() ): ?>
				<?php _e( "You have the latest WordPress version installed.", wp_defender()->domain ) ?>
			<?php else: ?>
                <form method="post" class="hardener-frm">
					<?php $controller->createNonceField(); ?>
                    <input type="hidden" name="action" value="processHardener"/>
                    <input type="hidden" name="slug" value="<?php echo $controller::$slug ?>"/>
                    <a href="<?php echo network_admin_url('update-core.php') ?>" class="button float-r">
						<?php esc_html_e( "Update WordPress", wp_defender()->domain ) ?>
                    </a>
                </form>
				<?php $controller->showIgnoreForm() ?>
                <div class="clear"></div>
			<?php endif; ?>
        </div>
        <div class="clear"></div>
    </div>
</div>