<?php
/**
 * Author: Hoang Ngo
 */

namespace WP_Defender\Module\Hardener\Component;

use WP_Defender\Module\Hardener\Model\Settings;
use WP_Defender\Module\Hardener\Rule;

class Disable_File_Editor extends Rule {
	static $slug = 'disable_file_editor';
	static $service;

	function getDescription() {
		$this->renderPartial( 'rules/disable-file-editor' );
	}

	function check() {
		return $this->getService()->check();
	}

	public function getTitle() {
		return __( "Disable the file editor", wp_defender()->domain );
	}

	function addHooks() {
		$this->add_action( 'processingHardener' . self::$slug, 'process' );
		$this->add_action( 'processRevert' . self::$slug, 'revert' );
		//Extra hardener actions incase setup is messed
		if ( $this->check() ) {
			$this->add_action( 'current_screen', 'current_screen' );
			if ( is_network_admin() ) {
				$this->add_action( 'network_admin_menu', 'editor_admin_menu', 999 );
			} elseif ( is_user_admin() ) {
				$this->add_action( 'user_admin_menu', 'editor_admin_menu', 999 );
			} else {
				$this->add_action( 'admin_menu', 'editor_admin_menu', 999 );
			}
			$this->add_filter( 'plugin_action_links', 'action_links', 10, 4 );
		}
	}

	function revert() {
		if ( ! $this->verifyNonce() ) {
			return;
		}
		$ret = $this->getService()->revert();
		if ( ! is_wp_error( $ret ) ) {
			Settings::instance()->addToIssues( self::$slug );
		} else {
			wp_send_json_error( array(
				'message' => $ret->get_error_message()
			) );
		}
	}

	function process() {
		if ( ! $this->verifyNonce() ) {
			return;
		}

		$ret = $this->getService()->process();
		if ( ! is_wp_error( $ret ) ) {
			Settings::instance()->addToResolved( self::$slug );
		} else {
			wp_send_json_error( array(
				'message' => $ret->get_error_message()
			) );
		}
	}

	/**
	 * @return Disable_File_Editor_Service
	 */
	function getService() {
		if ( self::$service == null ) {
			self::$service = new Disable_File_Editor_Service();
		}

		return self::$service;
	}

	/**
	 * Sometimes the roles are messed in the installation
	 * So we manually check if the pages are accessed and disable access to the,
	 */
	function current_screen() {
		$current_screen = get_current_screen();
		if( $current_screen->id == 'theme-editor-network' || $current_screen->id == 'theme-editor' ){
			wp_die('<p>'.__('Sorry, you are not allowed to edit templates for this site.').'</p>');
		}
		if( $current_screen->id == 'plugin-editor-network' || $current_screen->id == 'plugin-editor' ){
			wp_die('<p>'.__('Sorry, you are not allowed to edit plugins for this site.').'</p>');
		}
	}

	/**
	 * Remove the edit in the admin menu
	 */
	function editor_admin_menu() {
		remove_submenu_page( 'themes.php','theme-editor.php' );
		remove_submenu_page( 'plugins.php','plugin-editor.php' );
	}

	/**
	 * Remove any edit links from the plugin list
	 *
	 */
	function action_links( $actions, $plugin_file, $plugin_data, $context) {
		if ( isset( $actions['edit'] ) )
			unset( $actions['edit'] );
		return $actions;
	}
}