<div class="dev-box">
    <div class="box-title">
        <h3><?php _e( "Issues", wp_defender()->domain ) ?>
			<?php if ( $controller->getCount( 'issues' ) ): ?>
            <span class="def-tag tag-yellow count-issues"><?php echo $controller->getCount( 'issues' ) ?></span>
			<?php endif; ?>
        </h3>
    </div>
    <div class="box-content">
        <div class="box-content">
            <div class="line">
				<?php _e( "There are a number of security tweaks you can make to your website to strengthen it against harmful hackers and bots that try to break in. We recommend you action as many tweaks as possible.", wp_defender()->domain ) ?>
            </div>
            <div class="rules">
				<?php
				$setting = \WP_Defender\Module\Hardener\Model\Settings::instance();
				$issues  = $setting->getIssues();
				if ( count( $issues ) == 0 ) {
					?>
                    <div class="well well-green with-cap">
                        <i class="def-icon icon-tick" aria-hidden="true"></i>
						<?php _e( "You have actioned all available security tweaks. Great work!", wp_defender()->domain ) ?>
                    </div>
					<?php
				} else {
					foreach ( $setting->getIssues() as $rule ): ?>
						<?php
						$rule->getDescription();
						?>
					<?php endforeach;
				}
				?>
            </div>
        </div>
    </div>
</div>