<div class="rule closed" id="php_version">
    <div class="rule-title">
		<?php if ( $controller->check() == false ): ?>
            <i class="def-icon icon-warning" aria-hidden="true"></i>
		<?php else: ?>
            <i class="def-icon icon-tick" aria-hidden="true"></i>
		<?php endif; ?>
		<?php _e( "Update PHP to latest version", wp_defender()->domain ) ?>
    </div>
    <div class="rule-content">
        <h3><?php _e( "Overview", wp_defender()->domain ) ?></h3>
        <div class="line">
            <p>
				<?php _e( "PHP versions older than 5.6 are no longer supported. For security and stability we strongly recommend you upgrade your PHP version to version 5.6 or newer as soon as possible.", wp_defender()->domain ) ?>
            </p>
            <p>
				<?php printf( esc_html__( "More information: %s", wp_defender()->domain ), '<a target="_blank" href="http://php.net/supported-versions.php">http://php.net/supported-versions.php</a>' ) ?>
            </p>
        </div>
        <div class="columns version-col">
            <div class="column">
                <strong><?php _e( "Current version", wp_defender()->domain ) ?></strong>
				<?php $class = $controller->check() ? 'def-tag tag-success' : 'def-tag tag-error' ?>
                <span class="<?php echo $class ?>">
                    <?php echo \WP_Defender\Behavior\Utils::instance()->getPHPVersion() ?>
                </span>
            </div>
            <div class="column">
                <strong><?php _e( "Recommend Version", wp_defender()->domain ) ?></strong>
                <span><?php echo '5.6' ?></span>
            </div>
        </div>
        <h3>
			<?php _e( "How to fix", wp_defender()->domain ) ?>
        </h3>
        <div class="well mline">
			<?php if ( $controller->check() ): ?>
				<?php _e( "Your PHP version is okay.", wp_defender()->domain ) ?>
			<?php else: ?>
				<?php _e( "Your PHP version can be upgraded by your hosting provider or System Administrator. Please contact them for assistance.", wp_defender()->domain ) ?>
			<?php endif; ?>
            <div class="clear"></div>
        </div>
	    <?php $controller->showIgnoreForm() ?>
        <div class="clear"></div>
    </div>
</div>