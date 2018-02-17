<?php
/**
 * Author: Hoang Ngo
 */

namespace WP_Defender\Module\Scan\Component;

use WP_Defender\Module\Scan\Model\Result_Item;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Result_Table extends \WP_List_Table {
	public $type = Result_Item::STATUS_ISSUE;

	public function __construct( $args = array() ) {
		parent::__construct( array_merge( array(
			'plural'     => '',
			'autoescape' => false,
			'screen'     => ''
		), $args ) );
	}

	/**
	 * @return array
	 */
	function get_columns() {
		switch ( $this->type ) {
			case Result_Item::STATUS_ISSUE:
			default:
				$columns = array(
					'col_bulk'   => '<input id="apply-all" type="checkbox"/>',
					'col_file'   => esc_html__( 'Suspicious File', wp_defender()->domain ),
					'col_issue'  => esc_html__( 'Issue', wp_defender()->domain ),
					'col_action' => '',
				);
				break;
			case Result_Item::STATUS_IGNORED:
				$columns = array(
					'col_bulk'           => '<input id="apply-all" type="checkbox"/>',
					'col_file'           => esc_html__( 'File Name', wp_defender()->domain ),
					'col_ignore_date'    => esc_html__( 'Date Ignored', wp_defender()->domain ),
					'col_ignored_action' => '',
				);
				break;
			case Result_Item::STATUS_FIXED:
				$columns = array(
					'col_file'       => esc_html__( 'File Name', wp_defender()->domain ),
					'col_fixed_date' => esc_html__( 'Date Cleaned', wp_defender()->domain ),
				);
				break;
		}

		return $columns;
	}

	/**
	 * @param Result_Item $item
	 *
	 * @return mixed
	 */
	public function column_col_ignore_date( Result_Item $item ) {
		//$time = get_date_from_gmt( $item->dateIgnored, 'Y-m-d H:i:s' );
		return $item->formatDateTime( $item->dateIgnored );
	}

	/**
	 * @param Result_Item $item
	 *
	 * @return mixed
	 */
	public function column_col_fixed_date( Result_Item $item ) {
		//convert to local
		//$time = get_date_from_gmt( $item->dateFixed, 'Y-m-d H:i:s' );

		return $item->formatDateTime( $item->dateFixed );
	}

	/**
	 * @param Result_Item $item
	 *
	 * @return string
	 */
	public function column_col_ignored_action( Result_Item $item ) {
		ob_start();
		?>
        <form method="post" class="ignore-restore scan-frm">
            <input type="hidden" name="action" value="unIgnoreItem"/>
            <input type="hidden" name="id" value="<?php echo $item->id ?>"/>
			<?php wp_nonce_field( 'unIgnoreItem' ) ?>
            <button type="submit" tooltip="<?php esc_attr_e( "Restore File", wp_defender()->domain ) ?>"
                    class="button button-small">
                <i class="wdv-icon wdv-icon-fw wdv-icon-refresh" aria-hidden="true"></i>
            </button>
        </form>
		<?php
		return ob_get_clean();
	}

	/**
	 * prepare logs data
	 */
	function prepare_items() {
		$model        = Scan_Api::getLastScan();
		$itemsPerPage = 20;
		$totalItems   = $model->countAll( $this->type );

		$this->set_pagination_args( array(
			'total_items' => $totalItems,
			'total_pages' => ceil( $totalItems / $itemsPerPage ),
			'per_page'    => $itemsPerPage
		) );
		$offset                = ( $this->get_pagenum() - 1 ) * $itemsPerPage;
		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );
		$this->items           = $model->getItems( $offset . ',' . $itemsPerPage, $this->type );
	}

	/**
	 * @param $item
	 *
	 * @return string
	 */
	public function column_col_bulk( $item ) {
		return '<input value="' . $item->id . '" type="checkbox" class="scan-chk">';
	}

	/**
	 * @param Result_Item $item
	 *
	 * @return string
	 */
	public function column_col_file( Result_Item $item ) {
		return $item->getTitle() . ' <span class="sub">' . $item->getSubtitle() . '</span>';
	}

	/**
	 * @param Result_Item $item
	 *
	 * @return mixed
	 */
	public function column_col_issue( Result_Item $item ) {
		return $item->getIssueDetail();
	}

	/**
	 * @param Result_Item $item
	 *
	 * @return string
	 */
	public function column_col_action( Result_Item $item ) {
		$content = $item->renderDialog();

		$content .= '<a href="#dia_' . $item->id . '" rel="dialog" role="button" tooltip="' . esc_attr__( "Fix Issue", wp_defender()->domain ) . '" class="fix">
                        <img src="' . wp_defender()->getPluginUrl() . 'assets/img/icon-fix.svg">
                    </a>';

		return $content;
	}

	/**
	 * Display the table
	 *
	 * @since 3.1.0
	 * @access public
	 */
	public function display() {
		$singular = $this->_args['singular'];

		$this->display_tablenav( 'top' );
		?>
        <table class="<?php echo( $this->type == Result_Item::STATUS_FIXED ? 'resolved-table' : null ) ?>">
            <thead>
            <tr>
				<?php $this->print_column_headers(); ?>
            </tr>
            </thead>

            <tbody>
			<?php $this->display_rows_or_placeholder(); ?>
            </tbody>
        </table>
		<?php
		$this->display_tablenav( 'bottom' );
	}

	public function display_tablenav( $pos ) {
		if ( $this->type == Result_Item::STATUS_FIXED ) {
			return null;
		}
		?>
        <div class="bulk-nav">
            <div class="bulk-action">
                <form method="post" class="scan-bulk-frm">
                    <input type="hidden" name="action" value="scanBulkAction"/>
					<?php wp_nonce_field( 'scanBulkAction' ) ?>
                    <select name="bulk" class="bulk-action">
						<?php if ( $this->type != Result_Item::STATUS_IGNORED ): ?>
                            <option value="ignore"><?php _e( "Ignore", wp_defender()->domain ) ?></option>
<!--                            <option value="resolve">--><?php //_e( "Resolve", wp_defender()->domain ) ?><!--</option>-->
                            <!--                            <option value="delete">--><?php //_e( "Delete", wp_defender()->domain ) ?><!--</option>-->
						<?php endif; ?>
						<?php if ( $this->type == Result_Item::STATUS_IGNORED ): ?>
                            <option value="unignore"><?php _e( "Restore", wp_defender()->domain ) ?></option>
						<?php endif; ?>
                    </select>
                    <button class="button button-grey"><?php _e( "Apply", wp_defender()->domain ) ?></button>
                </form>
            </div>
            <div class="nav">
                <span><?php printf( __( "%s Results", wp_defender()->domain ), $this->_pagination_args['total_items'] ) ?></span>
                <div class="button-group is-hidden-mobile">
					<?php $this->pagination( 'top' ) ?>
                </div>
                <div class="button-group is-hidden-tablet">
					<?php $this->pagination( 'top' ) ?>
                </div>
            </div>
            <div class="clear"></div>
        </div>
		<?php
	}

	/**
	 * Generates content for a single row of the table
	 *
	 * @since 3.1.0
	 * @access public
	 *
	 * @param object $item The current item
	 */
	public function single_row( $item ) {
		echo '<tr id="mid-' . $item->id . '">';
		$this->single_row_columns( $item );
		echo '</tr>';
	}

	/**
	 * @param string $which
	 */
	protected function pagination( $which ) {
		if ( empty( $this->_pagination_args ) ) {
			return;
		}

		$total_items = $this->_pagination_args['total_items'];
		$total_pages = $this->_pagination_args['total_pages'];

		if ( $total_items == 0 ) {
			return;
		}

		if ( $total_pages < 2 ) {
			return;
		}

		$links        = array();
		$current_page = $this->get_pagenum();
		/**
		 * if pages less than 7, display all
		 * if larger than 7 we will get 3 previous page of current, current, and .., and, and previous, next, first, last links
		 */
		$current_url = set_url_scheme( 'http://' . parse_url( get_site_url(), PHP_URL_HOST ) . $_SERVER['REQUEST_URI'] );
		$current_url = remove_query_arg( array( 'hotkeys_highlight_last', 'hotkeys_highlight_first' ), $current_url );
		$current_url = esc_url( $current_url );

		$radius = 3;
		if ( $current_page > 1 && $total_pages > $radius ) {
			$links['first'] = sprintf( '<a class="button button-light" href="%s">%s</a>',
				add_query_arg( 'paged', 1, $current_url ), '&laquo;' );
			$links['prev']  = sprintf( '<a class="button button-light" href="%s">%s</a>',
				add_query_arg( 'paged', $current_page - 1, $current_url ), '&lsaquo;' );
		}

		for ( $i = 1; $i <= $total_pages; $i ++ ) {
			if ( ( $i >= 1 && $i <= $radius ) || ( $i > $current_page - 2 && $i < $current_page + 2 ) || ( $i <= $total_pages && $i > $total_pages - $radius ) ) {
				if ( $i == $current_page ) {
					$links[ $i ] = sprintf( '<a href="#" class="button audit-nav button-light" disabled="">%s</a>', $i );
				} else {
					$links[ $i ] = sprintf( '<a class="button audit-nav button-light" href="%s">%s</a>',
						add_query_arg( 'paged', $i, $current_url ), $i );
				}
			} elseif ( $i == $current_page - $radius || $i == $current_page + $radius ) {
				$links[ $i ] = '<a href="#" class="button audit-nav button-light" disabled="">...</a>';
			}
		}

		if ( $current_page < $total_pages && $total_pages > $radius ) {
			$links['next'] = sprintf( '<a class="button audit-nav button-light" href="%s">%s</a>',
				add_query_arg( 'paged', $current_page + 1, $current_url ), '&rsaquo;' );
			$links['last'] = sprintf( '<a class="button audit-nav button-light" href="%s">%s</a>',
				add_query_arg( 'paged', $total_pages, $current_url ), '&raquo;' );
		}
		$output            = join( "\n", $links );
		$this->_pagination = $output;

		echo $this->_pagination;
	}

}