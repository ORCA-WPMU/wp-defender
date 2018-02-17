<?php
/**
 * @author: Hoang Ngo
 */
// If uninstall is not called from WordPress, exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

$phpVersion = phpversion();
if ( version_compare( $phpVersion, '5.3', '<' ) ) {
	//php 5.2 does not need uninstall
	return;
}

$path = dirname( __FILE__ );
include_once $path . DIRECTORY_SEPARATOR . 'wp-defender.php';

$tweakFixed = \WP_Defender\Module\Hardener\Model\Settings::instance()->getFixed();

foreach ( $tweakFixed as $rule ) {
	$rule->getService()->revert();
}

$scan = \WP_Defender\Module\Scan\Model\Scan::findAll();
foreach ( $scan as $model ) {
	$model->delete();
}

\WP_Defender\Module\Scan\Component\Scan_Api::flushCache();

$cache = \Hammer\Helper\WP_Helper::getCache();
$cache->delete( 'isActivated' );
$cache->delete( 'wdfchecksum' );
$cache->delete( 'cleanchecksum' );

\WP_Defender\Module\Scan\Model\Settings::instance()->delete();
\WP_Defender\Module\Audit\Model\Settings::instance()->delete();
\WP_Defender\Module\Hardener\Model\Settings::instance()->delete();
\WP_Defender\Module\IP_Lockout\Model\Settings::instance()->delete();
\WP_Defender\Module\Advanced_Tools\Model\Auth_Settings::instance()->delete();
//clear old stuff
delete_site_option( 'wp_defender' );
delete_option( 'wp_defender' );
delete_option( 'wd_db_version' );
delete_site_option( 'wd_db_version' );