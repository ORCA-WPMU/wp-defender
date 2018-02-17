<?php
/**
 * Author: Hoang Ngo
 */

namespace WP_Defender\Module\Hardener\Controller;

use Hammer\Base\Container;
use Hammer\Helper\HTTP_Helper;
use Hammer\Helper\Log_Helper;
use WP_Defender\Controller;
use WP_Defender\Module\Hardener;

class Main extends Controller {
	protected $slug = 'wdf-hardener';
	public $layout = 'layout';

	/**
	 * @return array
	 */
	public function behaviors() {
		return array(
			'utils' => '\WP_Defender\Behavior\Utils'
		);
	}

	/**
	 * Main constructor.
	 */
	public function __construct() {
		if ( $this->is_network_activate( wp_defender()->plugin_slug ) ) {
			$this->add_action( 'network_admin_menu', 'adminMenu' );
		} else {
			$this->add_action( 'admin_menu', 'adminMenu' );
		}

		if ( $this->isInPage() ) {
			$this->add_action( 'defender_enqueue_assets', 'scripts', 11 );
		}

		$this->add_ajax_action( 'processHardener', 'processHardener' );
		$this->add_ajax_action( 'processRevert', 'processRevert' );
		$this->add_ajax_action( 'ignoreHardener', 'ignoreHardener' );
		$this->add_ajax_action( 'restoreHardener', 'restoreHardener' );
		$this->add_ajax_action( 'updateHardener', 'updateHardener' );
	}

	public function restoreHardener() {
		if ( ! $this->checkPermission() ) {
			return;
		}

		$slug = HTTP_Helper::retrieve_post( 'slug' );
		$rule = Hardener\Model\Settings::instance()->getRuleBySlug( $slug );
		if ( is_object( $rule ) ) {
			$rule->restore();
			wp_send_json_success( array(
				'message' => __( "Security tweak successfully restored.", wp_defender()->domain ),
				'issues'  => $this->getCount( 'issues' ),
				'fixed'   => $this->getCount( 'fixed' ),
				'ignore'  => $this->getCount( 'ignore' )
			) );
		}
	}

	public function ignoreHardener() {
		if ( ! $this->checkPermission() ) {
			return;
		}

		$slug = HTTP_Helper::retrieve_post( 'slug' );
		$rule = Hardener\Model\Settings::instance()->getRuleBySlug( $slug );
		if ( is_object( $rule ) ) {
			$rule->ignore();
			wp_send_json_success( array(
				'message' => __( "Security tweak successfully ignored.", wp_defender()->domain ),
				'issues'  => $this->getCount( 'issues' ),
				'fixed'   => $this->getCount( 'fixed' ),
				'ignore'  => $this->getCount( 'ignore' )
			) );
		}
	}

	public function processRevert() {
		if ( ! $this->checkPermission() ) {
			return;
		}
		$slug = HTTP_Helper::retrieve_post( 'slug' );
		do_action( "processRevert" . $slug );
		//fall back
		wp_send_json_success( array(
			'message' => __( "Security tweak successfully reverted.", wp_defender()->domain ),
			'issues'  => $this->getCount( 'issues' ),
			'fixed'   => $this->getCount( 'fixed' ),
			'ignore'  => $this->getCount( 'ignore' )
		) );
	}

	/**
	 * Ajax to process or ignore a rule
	 */
	public function processHardener() {
		if ( ! $this->checkPermission() ) {
			return;
		}

		$slug 		= HTTP_Helper::retrieve_post( 'slug' );

		do_action( "processingHardener" . $slug );
		//fall back
		wp_send_json_success( array(
			'message' => __( "Security tweak successfully resolved.", wp_defender()->domain ),
			'issues'  => $this->getCount( 'issues' ),
			'fixed'   => $this->getCount( 'fixed' ),
			'ignore'  => $this->getCount( 'ignore' )
		) );
	}

	/**
	 * Update Hardener
	 * Update existing rules
	 */
	public function updateHardener() {
		if ( ! $this->checkPermission() ) {
			return;
		}

		$slug = HTTP_Helper::retrieve_post( 'slug' );

		do_action( "processUpdate" . $slug );
		//fall back
		wp_send_json_success( array(
			'message' => __( "Security tweak successfully updated.", wp_defender()->domain ),
			'issues'  => $this->getCount( 'issues' ),
			'fixed'   => $this->getCount( 'fixed' ),
			'ignore'  => $this->getCount( 'ignore' ),
			'update'  => false
		) );
	}

	/**
	 * Add submit admin page
	 */
	public function adminMenu() {
		$cap = is_multisite() ? 'manage_network_options' : 'manage_options';
		add_submenu_page( 'wp-defender', esc_html__( "Security Tweaks", wp_defender()->domain ), esc_html__( "Security Tweaks", wp_defender()->domain ), $cap, $this->slug, array(
			&$this,
			'actionIndex'
		) );
	}

	/**
	 *
	 */
	public function actionIndex() {
		switch ( HTTP_Helper::retrieve_get( 'view' ) ) {
			case 'issues':
			default:
				$this->_renderIssues();
				break;
			case 'resolved':
				$this->_renderResolved();
				break;
			case 'notification':
				break;
			case 'ignored':
				$this->_renderIgnored();
				break;
		}
	}

	private function _renderIssues() {
		$this->render( 'issues' );
	}

	private function _renderResolved() {
		$this->render( 'resolved' );
	}

	private function _renderIgnored() {
		$this->render( 'ignore' );
	}

	private function _renderNotification() {

	}

	/**
	 * Enqueue scripts & styles
	 */
	public function scripts() {
		\WDEV_Plugin_Ui::load( wp_defender()->getPluginUrl() . 'shared-ui/' );
		wp_enqueue_script( 'defender' );
		wp_enqueue_script( 'hardener', wp_defender()->getPluginUrl() . 'app/module/hardener/js/scripts.js', array(
			'jquery-effects-core'
		) );
		wp_enqueue_style( 'defender' );
	}

	/**
	 *
	 * @param $type
	 *
	 * @return int
	 */
	public function getCount( $type ) {
		$settings = Hardener\Model\Settings::instance();
		switch ( $type ) {
			case 'issues':
				return count( $settings->issues );
				break;
			case 'fixed':
				return count( $settings->fixed );
				break;
			case 'ignore':
				return count( $settings->ignore );
				break;
		}
	}
}