<div class="dev-box">
    <div class="box-title">
        <h3 class="def-issues-title">
			<?php _e( "Issues", wp_defender()->domain ) ?>
			<?php $issues = $model->countAll( \WP_Defender\Module\Scan\Model\Result_Item::STATUS_ISSUE );
			if ( $issues ) {
				?>
                <span class="def-tag tag-error def-issues def-issues-summary"><?php echo $issues ?></span>
				<?php
			}
			?>
        </h3>
        <!--        <div>-->
        <!--            <span>--><?php //_e( "Type", wp_defender()->domain ) ?><!--</span>-->
        <!--            <select>-->
        <!--                <option value="all">--><?php //_e( "All", wp_defender()->domain ) ?><!--</option>-->
        <!--                <option value="core">--><?php //_e( "Core", wp_defender()->domain ) ?><!--</option>-->
        <!--                <option value="plugins">-->
		<?php //_e( "Plugins & Themes", wp_defender()->domain ) ?><!--</option>-->
        <!--                <option value="suspicious">-->
		<?php //_e( "Suspicious", wp_defender()->domain ) ?><!--</option>-->
        <!--            </select>-->
        <!--        </div>-->
    </div>
    <div class="box-content issues-box-content">
		<?php $table = new \WP_Defender\Module\Scan\Component\Result_Table();
		$table->prepare_items();
		if ( $table->get_pagination_arg( 'total_items' ) > 0 ) {
			?>
            <p class="line"><?php _e( "Defender has found potentially harmful files on your website. In many cases, the security scan will pick up harmless files, but in some cases you may wish to remove the files listed below that look suspicious.", wp_defender()->domain ) ?></p>
			<?php
			$table->display();
		} else {
			?>
            <div class="well well-green with-cap">
                <i class="def-icon icon-tick" aria-hidden="true"></i>
				<?php _e( "Your code is currently clean! There were no issues found during the last scan, though you can always perform a new scan anytime.", wp_defender()->domain ) ?>
            </div>
			<?php
		}
		?>
    </div>
</div>