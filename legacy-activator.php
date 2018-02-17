<?php

/**
 * Author: Hoang Ngo
 */
class WD_Legacy_Activator {
	public $wp_defender;

	public function __construct( WP_Defender $wp_defender ) {
		$this->wp_defender = $wp_defender;
		include_once $this->wp_defender->getPluginPath() . 'app/controller/requirement.php';
		new WD_Requirement();
	}
}