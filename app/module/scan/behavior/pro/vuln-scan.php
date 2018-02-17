<?php
/**
 * Author: Hoang Ngo
 */

namespace WP_Defender\Module\Scan\Behavior\Pro;

use Hammer\Base\Behavior;
use Hammer\Helper\Log_Helper;
use WP_Defender\Module\Scan\Component\Scan_Api;
use WP_Defender\Module\Scan\Model\Result_Item;

class Vuln_Scan extends Behavior {
	protected $endPoint = "https://premium.wpmudev.org/api/defender/v1/vulnerabilities";
	protected $model;

	public function processItemInternal( $args, $current ) {
		$model       = $args['model'];
		$this->model = $model;
		$this->scan();

		return true;
	}

	public function scan( $wp_version = null, $plugins = array(), $themes = array() ) {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		if ( is_null( $wp_version ) ) {
			global $wp_version;
		}

		if ( empty( $plugins ) ) {
			//get all the plugins, even activate or not, as in network
			foreach ( get_plugins() as $slug => $plugin ) {
				$base_slug             = explode( '/', $slug ); //DIRECTORY_SEPARATOR wont work on windows
				$base_slug             = array_shift( $base_slug );
				$plugins[ $base_slug ] = $plugin['Version'];
			}
		}

		if ( empty( $themes ) ) {
			foreach ( wp_get_themes() as $theme ) {
				$themes[ $theme->get_template() ] = $theme->Version;
			}
		}

		$response = $this->devCall( $this->endPoint, array(
			'themes'    => json_encode( $themes ),
			'plugins'   => json_encode( $plugins ),
			'wordpress' => $wp_version
		), array(
			'method'  => 'POST',
			'timeout' => 15
		) );

		if ( is_array( $response ) ) {
			$this->processWordPressVuln( $response['wordpress'] );
			$this->processPluginsVuln( $response['plugins'] );
			$this->processThemesVuln( $response['themes'] );
		}

		return true;
	}


	/**
	 * @param $issues
	 */
	private function processWordPressVuln( $issues ) {
		if ( empty( $issues ) ) {
			return;
		}
		$model           = new Result_Item();
		$model->type     = 'vuln';
		$model->parentId = $this->model->id;
		$model->status   = Result_Item::STATUS_ISSUE;
		$model->raw      = array(
			'type' => 'wordpress',
			'slug' => 'wordpress',
			'bugs' => array()
		);
		foreach ( $issues as $issue ) {
			$model->raw['bugs'][] = array(
				'vuln_type' => $issue['vuln_type'],
				'title'     => $issue['title'],
				'ref'       => $issue['references'],
				'fixed_in'  => $issue['fixed_in']
			);
		}

		$model->save();
	}

	/**
	 * @param $issues
	 */
	private function processThemesVuln( $issues ) {
		if ( empty( $issues ) ) {
			return;
		}

		foreach ( $issues as $slug => $bugs ) {
			if ( ( $id = Scan_Api::isIgnored( $slug ) ) ) {
				$status = Result_Item::STATUS_IGNORED;
				$model  = Result_Item::findByID( $id );
			} else {
				$status = Result_Item::STATUS_ISSUE;
				$model  = new Result_Item();
			}
			$model->parentId = $this->model->id;
			$model->type     = 'vuln';
			$model->status   = $status;
			$model->raw      = array(
				'type' => 'theme',
				'slug' => $slug,
				'bugs' => array()
			);
			if ( is_array( $bugs['confirmed'] ) ) {
				foreach ( $bugs['confirmed'] as $bug ) {
					$model->raw['bugs'][] = array(
						'vuln_type' => $bug['vuln_type'],
						'title'     => $bug['title'],
						'ref'       => $bug['references'],
						'fixed_in'  => $bug['fixed_in'],
					);
				}
			}
			if ( count( $model->raw['bugs'] ) ) {
				$model->save();
			}
		}
	}

	/**
	 * @param $issues
	 */
	private function processPluginsVuln( $issues ) {
		if ( empty( $issues ) ) {
			return;
		}
		foreach ( $issues as $slug => $bugs ) {
			if ( ( $id = Scan_Api::isIgnored( $slug ) ) ) {
				$status = Result_Item::STATUS_IGNORED;
				$model  = Result_Item::findByID( $id );
			} else {
				$status = Result_Item::STATUS_ISSUE;
				$model  = new Result_Item();
			}
			$model->parentId = $this->model->id;
			$model->type     = 'vuln';
			$model->status   = $status;
			$model->raw      = array(
				'type' => 'plugin',
				'slug' => $slug,
				'bugs' => array()
			);
			if ( is_array( $bugs['confirmed'] ) ) {
				foreach ( $bugs['confirmed'] as $bug ) {
					$model->raw['bugs'][] = array(
						'vuln_type' => $bug['vuln_type'],
						'title'     => $bug['title'],
						'ref'       => $bug['references'],
						'fixed_in'  => $bug['fixed_in'],
					);
				}
			}
			$model->save();
		}
	}

	/**
	 * @return array
	 */
	public function behaviors() {
		return array(
			'utils' => 'WP_Defender\Behavior\Utils'
		);
	}
}