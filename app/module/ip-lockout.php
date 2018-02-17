<?php
/**
 * Author: Hoang Ngo
 */

namespace WP_Defender\Module;

use Hammer\Base\Module;
use WP_Defender\Module\IP_Lockout\Controller\Main;

class IP_Lockout extends Module {
	public function __construct() {
		$this->register_post_type();
		new Main();
	}

	public function register_post_type() {
		register_post_type( 'wd_iplockout_log', array(
			'labels'          => array(
				'name'          => __( "Lockout Logs", wp_defender()->domain ),
				'singular_name' => __( "Lockout Log", wp_defender()->domain )
			),
			'public'          => false,
			'show_ui'         => false,
			'show_in_menu'    => false,
			'capability_type' => array( 'wd_iplockout_log', 'wd_iplockout_logs' ),
			'map_meta_cap'    => true,
			'hierarchical'    => false,
			'rewrite'         => false,
			'query_var'       => false,
			'supports'        => array( '' ),
		) );
		register_post_type( 'wd_ip_lockout', array(
			'labels'          => array(
				'name'          => __( "IP Lockouts", wp_defender()->domain ),
				'singular_name' => __( "IP Lockout", wp_defender()->domain )
			),
			'public'          => false,
			'show_ui'         => false,
			'show_in_menu'    => false,
			'capability_type' => array( 'wd_ip_lockout', 'wd_ip_lockouts' ),
			'map_meta_cap'    => true,
			'hierarchical'    => false,
			'rewrite'         => false,
			'query_var'       => false,
			'supports'        => array( '' ),
		) );
	}
}