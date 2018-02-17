<?php

/**
 * Author: Hoang Ngo
 */
class WD_Requirement {
	protected $slug = 'wp-defender';

	public function __construct() {
		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		}

		$networkActivate = is_plugin_active_for_network( 'wp-defender/wp-defender.php' );

		if ( $networkActivate ) {
			add_action( 'network_admin_menu', array( &$this, 'admin_menu' ) );
		} else {
			add_action( 'admin_menu', array( &$this, 'admin_menu' ) );
		}

		if ( isset( $_GET['page'] ) && $_GET['page'] == $this->slug ) {
			add_action( 'defender_enqueue_assets', array( &$this, 'scripts' ), 11 );
		}
	}

	public function actionIndex() {
		?>
        <div class="wrap">
            <div class="wp-defender">
                <div class="wdf-requirement">
                    <h2 class="title">

                    </h2>
                </div>
            </div>
            <dialog id="requirement" title="<?php esc_attr_e( "Required Modules", wp_defender()->domain ) ?>">
                <div class="line">
					<?php _e( "It looks like some required PHP modules are missing or outdated. We recommend you get in touch with your web hosting service to update the modules listed below.", wp_defender()->domain ) ?>
                </div>
                <table class="table">
                    <thead>
                    <tr>
                        <th><?php _e( "Module", wp_defender()->domain ) ?></th>
                        <th><?php _e( "Version", wp_defender()->domain ) ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td><?php _e( "PHP version", wp_defender()->domain ) ?></td>
                        <td>
                            <span class="def-tag tag-yellow"><?php echo phpversion() ?></span>&nbsp;&nbsp;<?php _e( "Please upgrade to 5.3 or later.", wp_defender()->domain ) ?>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </dialog>
        </div>
		<?php
	}

	/**
	 *
	 */
	public function admin_menu() {
		$cap        = is_multisite() ? 'manage_network_options' : 'manage_options';
		$menu_title = esc_html__( "Defender%s", wp_defender()->domain );
		$menu_title = sprintf( $menu_title, ' <span class="update-plugins wd-issue-indicator-sidebar"></span>' );
		add_menu_page( esc_html__( "Defender", wp_defender()->domain ), $menu_title, $cap, 'wp-defender', array(
			&$this,
			'actionIndex'
		), $this->get_menu_icon() );
	}

	/**
	 * Return svg image
	 * @return string
	 */
	private function get_menu_icon() {
		ob_start();
		?>
        <svg width="17px" height="18px" viewBox="10 397 17 18" version="1.1" xmlns="http://www.w3.org/2000/svg"
             xmlns:xlink="http://www.w3.org/1999/xlink">
            <!-- Generator: Sketch 3.8.3 (29802) - http://www.bohemiancoding.com/sketch -->
            <desc>Created with Sketch.</desc>
            <defs></defs>
            <path
                    d="M24.8009393,403.7962 L23.7971393,410.1724 C23.7395393,410.5372 23.5313393,410.8528 23.2229393,411.0532 L18.4001393,413.6428 L13.5767393,411.0532 C13.2683393,410.8528 13.0601393,410.5372 13.0019393,410.1724 L11.9993393,403.7962 L11.6153393,401.3566 C12.5321393,402.9514 14.4893393,405.5518 18.4001393,408.082 C22.3115393,405.5518 24.2675393,402.9514 25.1855393,401.3566 L24.8009393,403.7962 Z M26.5985393,398.0644 C25.7435393,397.87 22.6919393,397.2106 19.9571393,397 L19.9571393,403.4374 L18.4037393,404.5558 L16.8431393,403.4374 L16.8431393,397 C14.1077393,397.2106 11.0561393,397.87 10.2011393,398.0644 C10.0685393,398.0938 9.98213933,398.221 10.0031393,398.3536 L10.8875393,403.969 L11.8913393,410.3446 C12.0071393,411.0796 12.4559393,411.7192 13.1105393,412.0798 L16.8431393,414.1402 L18.4001393,415 L19.9571393,414.1402 L23.6891393,412.0798 C24.3431393,411.7192 24.7925393,411.0796 24.9083393,410.3446 L25.9121393,403.969 L26.7965393,398.3536 C26.8175393,398.221 26.7311393,398.0938 26.5985393,398.0644 L26.5985393,398.0644 Z"
                    id="Defender-Icon" stroke="none" fill="#FFFFFF" fill-rule="evenodd"></path>
        </svg>
		<?php
		$svg = ob_get_clean();

		return 'data:image/svg+xml;base64,' . base64_encode( $svg );
	}

	public function scripts() {
		WDEV_Plugin_Ui::load( wp_defender()->getPluginUrl() . 'shared-ui/' );
		wp_enqueue_script( 'defender' );
		wp_enqueue_style( 'defender' );
	}
}