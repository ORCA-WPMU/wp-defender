<div class="dev-box">
    <div class="box-title">
        <h3><?php esc_html_e( "LOCKOUT LOGS", wp_defender()->domain ) ?></h3>
        <button type="button" data-target=".lockout-logs-filter" rel="show-filter"
                class="button button-secondary button-small"><?php _e( "Filter", wp_defender()->domain ) ?></button>
    </div>
    <div class="box-content">
		<?php
		$table = new \WP_Defender\Module\IP_Lockout\Component\Logs_Table();
		$table->prepare_items();
		$table->display();
		?>
    </div>
</div>