<?php
/**
 * Author: Hoang Ngo
 */

namespace WP_Defender\Behavior;

use Hammer\Base\Behavior;
use WP_Defender\Component\Error_Code;

class Blacklist extends Behavior {
	private $end_point = "https://premium.wpmudev.org/api/defender/v1/blacklist-monitoring";

	public function renderBlacklistWidget() {
		if ( wp_defender()->isFree == false ) {
			$this->_renderPlaceholder();
		} else {
			$this->_renderFree();
		}
	}

	private function _renderPlaceholder() {
		?>
        <div class="dev-box">
            <div class="wd-overlay">
                <i class="wdv-icon wdv-icon-fw wdv-icon-refresh spin"></i>
            </div>
            <div class="box-title">
                <span class="span-icon icon-blacklist"></span>
                <h3><?php _e( "Blacklist Monitor", wp_defender()->domain ) ?></h3>
            </div>
            <div class="box-content">
                <div class="line">
					<?php _e( "Automatically check if you’re on Google’s blacklist every 6 hours. If something’s
                    wrong, we’ll let you know via email.", wp_defender()->domain ) ?>
                </div>
                <div class="well well-blue with-cap mline">
                    <i class="def-icon icon-warning fill-blue"></i> <?php _e( "We are currently requesting
                    your domain status from Google. This can take anywhere
                    from a few minutes up to 12 hours.", wp_defender()->domain ) ?>
                </div>
                <p class="sub tc"><?php printf( __( "Want to know more about blacklisting? <a href=\"%s\">Read this article.</a>", wp_defender()->domain ), "https://premium.wpmudev.org/blog/get-off-googles-blacklist/" ) ?>
                </p>
            </div>
            <form method="post" class="blacklist-widget">
                <input type="hidden" name="action" value="blacklistWidgetStatus"/>
				<?php wp_nonce_field( 'blacklistWidgetStatus' ) ?>
            </form>
        </div>
		<?php
	}

	private function _renderFree() {
		?>
        <div class="dev-box">
            <div class="box-title">
                <span class="span-icon icon-blacklist"></span>
                <h3><?php _e( "BLACKLIST MONITOR", wp_defender()->domain ) ?></h3>
                <a href="#pro-feature" rel="dialog"
                   class="button button-small button-pre"
				   tooltip="<?php esc_attr_e( "Try Defender Pro free today", wp_defender()->domain ) ?>">
				   <?php _e( "PRO FEATURE", wp_defender()->domain ) ?></a>
            </div>
            <div class="box-content">
                <div class="line">
					<?php _e( "Automatically check if you’re on Google’s blacklist every 6 hours. If something’s
                    wrong, we’ll let you know via email.", wp_defender()->domain ) ?>
                </div>
                <a href="#pro-feature" rel="dialog"
                   class="button button-green button-small"><?php esc_html_e( "Upgrade to Pro", wp_defender()->domain ) ?></a>
            </div>
        </div>
		<?php
	}


	public function toggleStatus( $status = null, $format = true ) {
		$api = Utils::instance()->getAPIKey();
		if ( ! $api ) {
			wp_send_json_error( array(
				'message' => __( "A WPMU DEV subscription is required for blacklist monitoring", wp_defender()->domain )
			) );
		}
		if ( is_null( $status ) ) {
			$status = $this->_pullStatus();
		}
		if ( $status === - 1 ) {
			$result = Utils::instance()->devCall( $this->end_point, array(), array(
				'method' => 'POST'
			), true );
		} else {
			$result = Utils::instance()->devCall( $this->end_point, array(), array(
				'method' => 'DELETE'
			), true );
		}

		if ( $format == false ) {
			return;
		}

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array(
				'message' => __( "Whoops, it looks like something went wrong. Details: ", wp_defender()->domain ) . $result->get_error_message()
			) );
		}

		$this->pullBlackListStatus();
	}

	private function _renderDisabled() {
		ob_start();
		?>
        <div class="dev-box">
            <div class="box-title">
                <span class="span-icon icon-blacklist"></span>
                <h3><?php _e( "BLACKLIST MONITOR", wp_defender()->domain ) ?></h3>
            </div>
            <div class="box-content">
                <div class="line">
					<?php _e( " Automatically check if you’re on Google’s blacklist every 6 hours. If something’s
                    wrong, we’ll let you know via email.", wp_defender()->domain ) ?>
                </div>
                <form method="post" class="toggle-blacklist-widget">
                    <input type="hidden" name="action" value="toggleBlacklistWidget"/>
					<?php wp_nonce_field( 'toggleBlacklistWidget' ) ?>
                    <button type="submit"
                            class="button button-small"><?php _e( "ACTIVATE", wp_defender()->domain ) ?></button>
                </form>
            </div>
        </div>
		<?php
		return ob_get_clean();
	}

	private function _renderError( $error ) {
		ob_start();
		?>
        <div class="dev-box">
            <div class="box-title">
                <span class="span-icon icon-blacklist"></span>
                <h3><?php _e( "BLACKLIST MONITOR", wp_defender()->domain ) ?></h3>
            </div>
            <div class="box-content">
                <div class="line">
					<?php _e( " Automatically check if you’re on Google’s blacklist every 6 hours. If something’s
                    wrong, we’ll let you know via email.", wp_defender()->domain ) ?>
                </div>
                <div class="well well-error">
                    <p>
                        <i class="def-icon icon-cross"></i> <?php echo $error->get_error_message() ?>
                    </p>
                    <a href="<?php echo network_admin_url( "admin.php?page=wp-defender" ) ?>"
                       class="button button-small button-grey"><?php _e( "Try Again", wp_defender()->domain ) ?></a>
                </div>
            </div>
        </div>
		<?php
		return ob_get_clean();
	}

	private function _renderResult( $status ) {
		ob_start();
		?>
        <div class="dev-box">
            <div class="box-title">
                <span class="span-icon icon-blacklist"></span>
                <h3>
					<?php _e( "BLACKLIST MONITOR", wp_defender()->domain ) ?>
					<?php if ( $status === 0 ): ?>
                        <span class="def-tag tag-error">1</span>
					<?php endif; ?>
                </h3>
                <span class="toggle float-r">
                        <input type="checkbox" checked="checked" name="enabled" value="1" class="toggle-checkbox"
                               id="toggle_blacklist">
                        <label class="toggle-label" for="toggle_blacklist"></label>
                    </span>
                <form method="post" class="toggle-blacklist-widget">
                    <input type="hidden" name="action" value="toggleBlacklistWidget"/>
					<?php wp_nonce_field( 'toggleBlacklistWidget' ) ?>
                </form>
            </div>
            <div class="box-content">
                <div class="line">
					<?php _e( " Automatically check if you’re on Google’s blacklist every 6 hours. If something’s
                    wrong, we’ll let you know via email.", wp_defender()->domain ) ?>
                </div>
				<?php if ( $status === 0 ): ?>
                    <div class="well well-error with-cap mline">
                        <i class="def-icon icon-warning"></i> <?php _e( "Your domain is currently on Google’s blacklist.", wp_defender()->domain ) ?>
                    </div>
				<?php else: ?>
                    <div class="well well-green with-cap mline">
                        <i class="def-icon icon-tick"></i>
						<?php _e( 'Your domain is currently clean.', wp_defender()->domain ) ?>
                    </div>
				<?php endif; ?>
                <p class="sub tc"><?php printf( __( "Want to know more about blacklisting? <a href=\"%s\">Read this article.</a>", wp_defender()->domain ), "https://premium.wpmudev.org/blog/get-off-googles-blacklist/" ) ?>
                </p>
            </div>
        </div>
		<?php
		return ob_get_clean();
	}

	/**
	 * @param bool $format
	 *
	 * @return int|\WP_Error
	 */
	public function pullBlackListStatus( $format = true ) {
		$currStatus = $this->_pullStatus();
		if ( $format == false ) {
			return $currStatus;
		}
		if ( is_wp_error( $currStatus ) ) {
			$html = $this->_renderError( $currStatus );
		} elseif ( $currStatus === - 1 ) {
			$html = $this->_renderDisabled();
		} else {
			$html = $this->_renderResult( $currStatus );
		}

		wp_send_json_success( array(
			'html' => $html
		) );
	}

	/**
	 * @return int|\WP_Error
	 */
	private function _pullStatus() {
		$endpoint = $this->end_point . '?domain=' . network_site_url();
		$result   = Utils::instance()->devCall( $endpoint, array(), array(
			'method'  => 'GET',
			'timeout' => 5
		), true );
		if ( is_wp_error( $result ) ) {
			//this mean error when firing to API
			return new \WP_Error( Error_Code::API_ERROR, $result->get_error_message() );
		}
		$response_code = wp_remote_retrieve_response_code( $result );
		$body          = wp_remote_retrieve_body( $result );
		$body          = json_decode( $body, true );

		if ( $response_code == 412 ) {
			//this mean disable
			return - 1;
		} elseif ( isset( $body['services'] ) && is_array( $body['services'] ) ) {
			$status = 1;
			foreach ( $body['services'] as $service ) {
				if ( $service['blacklisted'] == true && $service['last_checked'] != false ) {
					$status = 0;
					break;
				}
			}

			return $status;
		} else {
			//fallbacl error
			return new \WP_Error( Error_Code::INVALID, esc_html__( "Something wrong happened, please try again.", wp_defender()->domain ) );
		}
	}
}