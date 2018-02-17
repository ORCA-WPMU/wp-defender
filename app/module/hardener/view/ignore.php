<div class="dev-box">
    <div class="box-title">
        <h3><?php _e( "IGNORED", wp_defender()->domain ) ?>
			<?php if ( $controller->getCount( 'ignore' ) ): ?>
                <span class="def-tag tag-generic count-ignored">
                <?php echo $controller->getCount( 'ignore' ) ?>
            </span>
			<?php endif; ?>
        </h3>
    </div>
    <div class="box-content">
        <div class="box-content">
			<?php if ( count( \WP_Defender\Module\Hardener\Model\Settings::instance()->ignore ) > 0 ): ?>
                <div class="line">
					<?php _e( "You have chosen to ignore these fixes. You can restore and action them at any time.", wp_defender()->domain ) ?>
                </div>
                <div class="rules ignored">
					<?php foreach ( \WP_Defender\Module\Hardener\Model\Settings::instance()->getIgnore() as $rule ): ?>
						<?php
						$rule->showRestoreForm();
						?>
					<?php endforeach; ?>
                </div>
			<?php else: ?>
                <div class="well well-blue with-cap">
                    <i class="def-icon icon-warning" aria-hidden="true"></i>
					<?php _e( "You haven't ignored any issues yet. You can ignore any security tweaks you don't want to be warned about by clicking 'Ignore' inside the issue description.", wp_defender()->domain ) ?>
                </div>
			<?php endif; ?>
        </div>
    </div>
</div>