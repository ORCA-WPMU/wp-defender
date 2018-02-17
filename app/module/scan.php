<?php
/**
 * Author: Hoang Ngo
 */

namespace WP_Defender\Module;

use Hammer\Base\Module;
use WP_Defender\Module\Scan\Controller\Main;

class Scan extends Module {
	public function __construct() {
		$this->_registerPostTpe();
		$main = new Main();
	}

	private function _registerPostTpe() {
		register_post_type( 'wdf_scan', array(
			'labels'              => array(
				'name'          => __( "Scans", wp_defender()->domain ),
				'singular_name' => __( "Scan", wp_defender()->domain )
			),
			'capability_type'     => array( 'wdf_scan', 'wdf_scans' ),
			'supports'            => array( '' ),
			'hierarchical'        => false,
			'public'              => false,
			'show_ui'             => false,
			'show_in_menu'        => false,
			'show_in_admin_bar'   => false,
			'show_in_nav_menus'   => false,
			'can_export'          => false,
			'has_archive'         => true,
			'exclude_from_search' => false,
			'publicly_queryable'  => false,
			'rewrite'             => false,
		) );
		register_post_type( 'wdf_scan_item', array(
			'labels'              => array(
				'name'          => __( "Scan Items", wp_defender()->domain ),
				'singular_name' => __( "Scan Item", wp_defender()->domain )
			),
			'capability_type'     => array( 'wdf_scan_item', 'wdf_scan_items' ),
			'supports'            => array( '' ),
			'hierarchical'        => false,
			'public'              => false,
			'show_ui'             => false,
			'show_in_menu'        => false,
			'show_in_admin_bar'   => false,
			'show_in_nav_menus'   => false,
			'can_export'          => false,
			'has_archive'         => false,
			'exclude_from_search' => false,
			'publicly_queryable'  => false,
			'rewrite'             => false,
		) );
	}
}