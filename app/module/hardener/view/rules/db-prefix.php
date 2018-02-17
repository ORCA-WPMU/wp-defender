<div class="rule closed" id="db_prefix">
    <div class="rule-title">
		<?php if ( $controller->check() == false ): ?>
            <i class="def-icon icon-warning" aria-hidden="true"></i>
		<?php else: ?>
            <i class="def-icon icon-tick" aria-hidden="true"></i>
		<?php endif; ?>
		<?php _e( "Change default database prefix", wp_defender()->domain ) ?>
    </div>
    <div class="rule-content">
        <h3><?php _e( "Overview", wp_defender()->domain ) ?></h3>
        <div class="line end">
			<?php _e( "When you first install WordPress on a new database, the default settings start with wp_ as the prefix to anything that gets stored in the tables. This makes it easier for hackers to perform SQL injection attacks if they find a code vulnerability. It’s good practice to come up with a unique prefix to protect yourself from this. Please backup your database before changing the prefix.", wp_defender()->domain ) ?>
        </div>
        <h3>
			<?php _e( "How to fix", wp_defender()->domain ) ?>
        </h3>
        <div class="well has-input">
			<?php if ( $controller->check() ): ?>
				<?php
				global $wpdb;
				printf( __( "Your prefix is <strong>%s</strong> and is unique.", wp_defender()->domain ), $wpdb->prefix ) ?>
			<?php else: ?>
                <div class="line">
                    <p>
						<?php esc_html_e( "We recommend using a different prefix to protect your database. Ensure you backup your database before changing the prefix.", wp_defender()->domain ) ?>
                    </p>
                </div>
                <form method="post" class="hardener-frm rule-process">
					<?php $controller->createNonceField(); ?>
                    <input type="hidden" name="action" value="processHardener"/>
                    <input type="text"
                           placeholder="<?php esc_attr_e( "Enter new database prefix", wp_defender()->domain ) ?>"
                           name="dbprefix" class="block">
                    <input type="hidden" name="slug" value="<?php echo $controller::$slug ?>"/>
                    <button class="button float-r"
                            type="submit"><?php _e( "Update", wp_defender()->domain ) ?></button>
                </form>
				<?php $controller->showIgnoreForm() ?>
                <div class="clear"></div>
			<?php endif; ?>
        </div>
        <div class="clear"></div>
    </div>
</div>