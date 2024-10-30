<?php
/*
Plugin Name: In Image Ads Manager
Plugin URI: http://www.smart-webentwicklung.de/wordpress-plugins/in-image-ads-manager/
Description: Displays clickable text ads as image overlay when hovering over an image.
Version: 1.1
Author: Stephan L.
Author URI: http://www.smart-webentwicklung.de
Text Domain: in-image-ads-manager
Domain Path: /languages
License: GPL3
*/

/*	Copyright 2012  Stephan L.  (email : kontakt@smart-webentwicklung.de)

	This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 3, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
 * In Image Ads Manager
 *
 * @since 1.0
 */
class In_Image_Ads_Manager
{
	/* meta information */
	const DB_TABLE_NAME = 'iiam_ads';
	const DB_VERSION = '1.0';
	const OPTION_NAME = 'iiam-options';
	const TEXT_DOMAIN = 'in-image-ads-manager';
	const VERSION = '1.1';

	/* overview table modes */
	const MODE_ACTIVE = 'active';
	const MODE_ALL = 'all';
	const MODE_TRASH = 'trash';

	/* page actions */
	const ACTION_ADD = 'add';
	const ACTION_EDIT = 'edit';
	const ACTION_SUCCESSFUL_ADD = 'add-success';
	const ACTION_DELETE = 'delete';
	const ACTION_TRASH = 'trash';
	const ACTION_UNTRASH = 'untrash';

	/* page ids */
	const PAGE_OVERVIEW = 0;
	const PAGE_ADD = 1;
	const PAGE_EDIT = 2;

	/* ads for the current requested page */
	private $current_ad_ids;

	/* options */
	private static $options;

	/* submenu pages */
	private $add_page;
	private $edit_page;

	/* page slugs */
	private $pages = array(
		self::PAGE_OVERVIEW => 'iiam-ads-overview',
		self::PAGE_ADD      => 'iiam-ads-add',
		self::PAGE_EDIT     => 'iiam-ads-edit'
	);

	/**
	 * Constructor that sets all needed add_action calls.
	 *
	 * @since 1.0
	 */
	public function __construct()
	{
		/* includes the needed in image ad class */
		include_once( 'in-image-ad.php' );

		/* sets all needed add_action calls */
		if ( is_admin() ) {
			add_action( 'init', array( &$this, 'register_plugin_textdomain' ) );
			add_action( 'admin_menu', array( &$this, 'register_admin_menu' ) );
			add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_admin_scripts' ) );
		} else {
			add_action( 'wp', array( &$this, 'get_ad_ids_from_post' ) );
			add_action( 'wp_enqueue_scripts', array( &$this, 'enqueue_scripts' ) );
			add_action( 'wp_head', array( &$this, 'include_inline_scripts_styles' ) );
		}
	}

	/**
	 * Registers the plugin textdomain.
	 *
	 * @since 1.0
	 */
	public function register_plugin_textdomain()
	{
		if ( function_exists( 'load_plugin_textdomain' ) ) {
			load_plugin_textdomain( self::TEXT_DOMAIN, false, basename( plugin_dir_path( __FILE__ ) ) . '/languages' );
		}
	}

	/**
	 * Invoked by plugin activation. Creates a in image ad table when not existing yet and loads all options.
	 *
	 * @since 1.0
	 * @static
	 */
	public static function install()
	{
		global $wpdb,
			   $charset_collate;

		/* loads options */
		self::load_options();

		/* gets table name with correct prefix */
		$table = self::get_table_name();

		/* creates new in image ad table when not already exists */
		if ( $wpdb->get_var( 'SHOW TABLES LIKE "' . $table . '"' ) !== $table ) {
			$query = '
				CREATE TABLE ' . $table . '
				(
					id INT(11) NOT NULL AUTO_INCREMENT,
					title TEXT NOT NULL,
					text TEXT NOT NULL,
					url TEXT NOT NULL,
					image_width SMALLINT(4) NOT NULL,
					image_height SMALLINT(4) NOT NULL,
					style TEXT NOT NULL,
					trash TINYINT(1) DEFAULT 0,
					PRIMARY KEY  (id)
				) ' . $charset_collate . ';
			';

			/* required for dbDelta function */
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

			/* execute query to create table (actually dbDelta also checks itself if table already exists) */
			dbDelta( $query );
		}

		/* stores version options - later may needed for table updates or sth. else */
		self::$options[ 'iiam_db_version' ] = self::DB_VERSION;
		self::$options[ 'iiam_version' ] = self::VERSION;
		self::save_options();
	}

	/**
	 * Invoked by plugin uninstall. Removes the in image ad table when existing and too removes all options.
	 *
	 * @since 1.0
	 * @static
	 */
	public static function uninstall()
	{
		global $wpdb;

		/* deletes all options */
		self::delete_options();

		/* gets table name with correct prefix */
		$table = self::get_table_name();

		/* deletes in image ad table when exists */
		if ( $wpdb->get_var( 'SHOW TABLES LIKE "' . $table . '"' ) === $table ) {
			$wpdb->query( 'DROP TABLE IF EXISTS ' . $table );
		}
	}

	/**
	 * Returns the in image ad table name with the specified WordPress table prefix.
	 *
	 * @since 1.0
	 * @static
	 *
	 * @return string in image ad table name
	 */
	private static function get_table_name()
	{
		global $wpdb;

		return strtolower( $wpdb->prefix ) . self::DB_TABLE_NAME;
	}

	/**
	 * Loads all plugin related options.
	 *
	 * @since 1.0
	 * @static
	 */
	private static function load_options()
	{
		/* loads all options */
		self::$options = get_option( self::OPTION_NAME );
	}

	/**
	 * Saves all plugin related options.
	 *
	 * @since 1.0
	 * @static
	 */
	private static function save_options()
	{
		/* adds options when not existing yet */
		if ( get_option( self::OPTION_NAME ) === false ) {
			/* do not need the options to load on every page load so turned autoload off */
			add_option( self::OPTION_NAME, self::$options, '', 'no' );
		} else {
			/* updates options */
			update_option( self::OPTION_NAME, self::$options );
		}
	}

	/**
	 * Removes all plugin related options.
	 *
	 * @since 1.0
	 * @static
	 */
	private static function delete_options()
	{
		/* deletes all options */
		delete_option( self::OPTION_NAME );
	}

	/**
	 * Registers admin menu and submenus for the plugin.
	 *
	 * @since 1.0
	 */
	public function register_admin_menu()
	{
		/* adds the main menu page */
		add_menu_page(
			__( 'In Image Ads Manager', self::TEXT_DOMAIN ),
			__( 'In Image Ads', self::TEXT_DOMAIN ),
			'manage_options',
			'iiam-ads-overview',
			array( &$this, 'overview_page' )
		);

		/* adds the overview submenu page */
		add_submenu_page(
			'iiam-ads-overview',
			__( 'Overview - List of all In Image Ads', self::TEXT_DOMAIN ),
			__( 'Overview', self::TEXT_DOMAIN ),
			'manage_options',
			$this->pages[ self::PAGE_OVERVIEW ],
			array( &$this, 'overview_page' )
		);

		/* adds the add submenu page */
		$this->add_page = add_submenu_page(
			'iiam-ads-overview',
			__( 'Add In Image Ad', self::TEXT_DOMAIN ),
			__( 'Add Ad', self::TEXT_DOMAIN ),
			'manage_options',
			$this->pages[ self::PAGE_ADD ],
			array( &$this, 'add_page' )
		);

		/* adds the edit submenu page */
		$this->edit_page = add_submenu_page(
			'iiam-ads-overview',
			__( 'Edit In Image Ad', self::TEXT_DOMAIN ),
			__( 'Edit Ad', self::TEXT_DOMAIN ),
			'manage_options',
			$this->pages[ self::PAGE_EDIT ],
			array( &$this, 'edit_page' )
		);

		/* add_action calls for enqueueing css styles */
		add_action( 'admin_print_styles-' . $this->add_page, array( &$this, 'enqueue_admin_styles' ) );
		add_action( 'admin_print_styles-' . $this->edit_page, array( &$this, 'enqueue_admin_styles' ) );
	}

	/**
	 * Enqueues needed admin styles.
	 *
	 * @since 1.0
	 */
	public function enqueue_admin_styles()
	{
		//wp_enqueue_style( 'thickbox' );
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_style(
			'iiam-admin',
			plugins_url( 'css/iiam-admin.css', __FILE__ )
		);
	}

	/**
	 * Enqueues needed admin scripts when the current page is the add or edit page.
	 *
	 * @since 1.0
	 *
	 * @param string @page current requested page
	 */
	public function enqueue_admin_scripts( $page )
	{
		if ( $page === $this->add_page || $page === $this->edit_page ) {
			wp_enqueue_script( 'wp-color-picker' );
			wp_enqueue_script(
				'iiam-admin',
				plugins_url( 'js/iiam-admin.js', __FILE__ ),
				array( 'jquery', 'jquery-ui-draggable', 'jquery-ui-slider', 'wp-color-picker' ),
				'1.1',
				true
			);
		}
	}

	/**
	 * Invoked when overview page is requested. Renders the overview page.
	 *
	 * @since 1.0
	 */
	public function overview_page()
	{
		global $wpdb;

		/* gets table name with correct prefix */
		$table = $this->get_table_name();

		/* retrieves action and id */
		$action = isset( $_GET[ 'action' ] ) ? $_GET[ 'action' ] : '';
		$id = isset( $_GET[ 'id' ] ) ? (int)$_GET[ 'id' ] : null;

		/* if an action is requested... */
		if ( !empty( $action ) ) {
			/* checks nonce for the requested action and id */
			$this->check_nonce( $action, $id );
		}

		/* table mode - default mode is active */
		$mode = self::MODE_ACTIVE;

		/* retrieves table mode */
		if ( isset( $_GET[ 'mode' ] ) && ( $_GET[ 'mode' ] === self::MODE_ALL || $_GET[ 'mode' ] === self::MODE_TRASH ) ) {
			$mode = $_GET[ 'mode' ];
		}

		/* if id is given... */
		if ( !is_null( $id ) ) {
			/* if action is for trash or untrash... */
			if ( $action === self::ACTION_TRASH || $action === self::ACTION_UNTRASH ) {
				$is_trash = true;

				/* if action is untrash... */
				if ( $action === self::ACTION_UNTRASH ) {
					$is_trash = false;
				}

				/* updates the trash status of the ad with the given id */
				$wpdb->update(
					$table,
					array( 'trash' => $is_trash ),
					array( 'id' => $id ),
					array( '%d' ),
					array( '%d' )
				);
				/* if action is delete... */
			} elseif ( $action === self::ACTION_DELETE ) {
				/* deletes the ad with the given id */
				$wpdb->query( '
					DELETE FROM
						' . $table . '
					WHERE
						id=' . $id
				);
			}
		}

		/* gets number of active and trash ads */
		$num_active_ads = $this->get_number_of_ads();
		$num_trash_ads = $this->get_number_of_ads( true );

		/* ensures to have an integer zero when results were null */
		$num_active_ads = is_null( $num_active_ads ) ? 0 : $num_active_ads;
		$num_trash_ads = is_null( $num_trash_ads ) ? 0 : $num_trash_ads;

		/* calculates total number of ads */
		$num_all_ads = $num_active_ads + $num_trash_ads;

		/* ads per table page */
		$ads_per_page = 6;

		/* default page is 1 */
		$page = 1;

		/* retrieves the current table page */
		$page = isset( $_GET[ 'ppage' ] ) ? (int)$_GET[ 'ppage' ] : $page;

		/* number of visible ads on the table page */
		$num_visible_ads = $num_all_ads;

		/* adjusts the number of visible ads by checking table mode */
		if ( $mode === self::MODE_TRASH ) {
			$num_visible_ads = $num_trash_ads;
		} elseif ( $mode === self::MODE_ACTIVE ) {
			$num_visible_ads = $num_active_ads;
		}

		/* calculates offset */
		$offset = ( $page - 1 ) * $ads_per_page;

		$page_links = paginate_links( array(
			'base'      => add_query_arg( 'ppage', '%#%' ),
			'format'    => '',
			'prev_text' => __( '&laquo;' ),
			'next_text' => __( '&raquo;' ),
			'total'     => ceil( $num_visible_ads / $ads_per_page ),
			'current'   => $page
		) );

		/* where condition for querying ads - default empty */
		$where_condition = '';

		/* if table mode is all... */
		if ( $mode !== self::MODE_ALL ) {
			/* sets where condition - ads where status is trash or not */
			$where_condition = ' AND trash = ' . ( $mode === self::MODE_TRASH ? 'TRUE' : 'FALSE' );
		}

		/* query for retrieving ads */
		$query = '
    		SELECT
    			id,
    			title,
    			text,
    			image_width,
    			image_height,
    			trash
    		FROM
    			' . $table . '
			WHERE
				1 = 1' .
				$where_condition . '
			LIMIT '
				. $offset . ', ' . $ads_per_page;

		/* fires query */
		$ads = $wpdb->get_results( $query, ARRAY_A );
		?>
	<div class="wrap">
		<h2><?php _e( 'Overview', self::TEXT_DOMAIN ); ?></h2>
		<ul class="subsubsub">
			<li>
				<a <?php echo ( $mode === self::MODE_ALL ) ? 'class="current"' : ''; ?>
						href="<?php echo $this->get_overview_url( self::MODE_ALL ); ?>"
						title="<?php _e( 'Show all ads', self::TEXT_DOMAIN ); ?>">
					<?php _e( 'All', self::TEXT_DOMAIN ); ?>
				</a>
				(<?php echo $num_all_ads; ?>) |
			</li>
			<li>
				<a <?php echo ( $mode === self::MODE_ACTIVE ) ? 'class="current"' : ''; ?>
						href="<?php echo $this->get_overview_url( self::MODE_ACTIVE ); ?>"
						title="<?php _e( 'Show active ads', self::TEXT_DOMAIN ); ?>">
					<?php _e( 'Active', self::TEXT_DOMAIN ); ?>
				</a>
				(<?php echo $num_active_ads; ?>) |
			</li>
			<li>
				<a <?php echo ( $mode === self::MODE_TRASH ) ? 'class="current"' : ''; ?>
						href="<?php echo $this->get_overview_url( self::MODE_TRASH ); ?>"
						title="<?php _e( 'Show trash ads', self::TEXT_DOMAIN ); ?>">
					<?php _e( 'Trash', self::TEXT_DOMAIN ); ?>
				</a>
				(<?php echo $num_trash_ads; ?>)
			</li>
		</ul>
		<div class="tablenav">
			<div class="alignleft">
				<a class="button-secondary" href="<?php echo $this->get_add_url(); ?>"
				   title="<?php _e( 'Add a new in image ad', self::TEXT_DOMAIN ); ?>">
					<?php _e( 'Add Ad', self::TEXT_DOMAIN ); ?>
				</a>
			</div>
			<div class="tablenav-pages">
				<?php
				$pagination = sprintf(
					'<span class="displaying-num">' . __( 'Displaying %s&#8211;%s of %s', self::TEXT_DOMAIN ) . '</span>%s',
					number_format_i18n( $offset + 1 ),
					number_format_i18n( min( $page * $ads_per_page, $num_visible_ads ) ),
						'<span class="total-type-count">' . number_format_i18n( $num_visible_ads ) . '</span>',
					$page_links
				);
				echo $pagination;
				?>
			</div>
		</div>
		<table class="widefat">
			<thead>
				<tr>
					<th><?php _e( 'ID', self::TEXT_DOMAIN ); ?></th>
					<th><?php _e( 'Title', self::TEXT_DOMAIN ); ?></th>
					<th><?php _e( 'Text', self::TEXT_DOMAIN ); ?></th>
					<th><?php _e( 'Image Size', self::TEXT_DOMAIN ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				if ( empty( $ads ) || !is_array( $ads ) ) {
					?>
					<tr>
						<td colspan="4"><?php _e( 'No ads existing.', self::TEXT_DOMAIN ); ?></td>
					</tr>
					<?php
				} else {
					$i = 0;
					foreach ( $ads as $ad ) {
						?>
						<tr <?php echo ( ( $i & 1 ) ? 'class="alternate"' : '' ); ?>>
							<td>iiam-<?php echo $ad[ 'id' ]; ?></td>
							<td>
								<strong>
									<a href="<?php echo $this->get_edit_url( $ad[ 'id' ] ); ?>"
									   title="<?php _e( 'Edit this ad', self::TEXT_DOMAIN ); ?>">
										<?php echo $ad[ 'title' ] ?>
									</a>
								</strong>
								<br />
								<div class="row-actions">
                                        <span class="edit">
											<a href="<?php echo $this->get_edit_url( $ad[ 'id' ] ); ?>"
											   title="<?php _e( 'Edit this ad', self::TEXT_DOMAIN ); ?>">
												<?php _e( 'Edit', self::TEXT_DOMAIN ); ?>
											</a> |
										</span>
									<?php
									if ( (bool)$ad[ 'trash' ] === true ) {
										?>
										<span class="untrash">
											<?php
											$url = $this->get_overview_url(
												$mode,
												self::ACTION_UNTRASH,
												$ad[ 'id' ],
												true
											);
											?>
											<a href="<?php echo $url; ?>"
											   title="<?php _e( 'Restore this ad', self::TEXT_DOMAIN ); ?>">
												<?php _e( 'Restore', self::TEXT_DOMAIN ); ?>
											</a> |
										</span>
										<span class="delete">
											<?php
											$url = $this->get_overview_url(
												$mode,
												self::ACTION_DELETE,
												$ad[ 'id' ],
												true
											);
											?>
											<a href="<?php echo $url; ?>"
											   title="<?php _e( 'Delete this ad', self::TEXT_DOMAIN ); ?>">
												<?php _e( 'Delete', self::TEXT_DOMAIN ); ?>
											</a>
										</span>
										<?php
									} else {
										?>
										<span class="delete">
											<?php
											$url = $this->get_overview_url(
												$mode,
												self::ACTION_TRASH,
												$ad[ 'id' ],
												true
											);
											?>
											<a href="<?php echo $url; ?>"
											   title="<?php _e( 'Trash this ad', self::TEXT_DOMAIN ); ?>">
												<?php _e( 'Trash', self::TEXT_DOMAIN ); ?>
											</a>
										</span>
										<?php } ?>
								</div>
							</td>
							<td><?php echo $ad[ 'text' ] ?></td>
							<td><?php echo $ad[ 'image_width' ] ?>x<?php echo $ad[ 'image_height' ] ?></td>
						</tr>
						<?php
						$i++;
					}
				}
				?>
			</tbody>
			<tfoot>
				<tr>
					<th><?php _e( 'ID', self::TEXT_DOMAIN ); ?></th>
					<th><?php _e( 'Title', self::TEXT_DOMAIN ); ?></th>
					<th><?php _e( 'Text', self::TEXT_DOMAIN ); ?></th>
					<th><?php _e( 'Image Size', self::TEXT_DOMAIN ); ?></th>
				</tr>
			</tfoot>
		</table>
		<div class="tablenav">
			<div class="alignleft">
				<a class="button-secondary" href="<?php echo $this->get_add_url(); ?>"
				   title="<?php _e( 'Add a new in image ad', self::TEXT_DOMAIN ); ?>">
					<?php _e( 'Add Ad', self::TEXT_DOMAIN ); ?>
				</a>
			</div>
		</div>
	</div>
	<?php
	}

	/**
	 * Returns number of ads by trash status.
	 *
	 * @since 1.0
	 *
	 * @param bool $is_trash true if trash status is true otherwise false
	 *
	 * @return null|string number of ads where status is trash or not
	 */
	private function get_number_of_ads( $is_trash = false )
	{
		global $wpdb;

		return $wpdb->get_var(
			$wpdb->prepare( '
				SELECT
					COUNT(*)
				FROM
					' . $this->get_table_name() . '
				WHERE
					trash = %s',
				$is_trash
			)
		);
	}

	/**
	 * Returns the admin url for the requested admin page.
	 *
	 * @since 1.0
	 *
	 * @param string $page the requested admin page
	 *
	 * @return string admin url for the requested page
	 */
	private function get_admin_url( $page )
	{
		return add_query_arg( 'page', $page, admin_url( 'admin.php' ) );
	}

	/**
	 * Returns admin overview page url.
	 *
	 * @since 1.0
	 *
	 * @param bool|string $mode      table mode (all, active and trash)
	 * @param bool|string $action    the requested action
	 * @param bool|int    $id        id of an existing ad
	 * @param bool        $use_nonce whether to use nonce or not
	 *
	 * @return string admin overview page url
	 */
	private function get_overview_url( $mode = false, $action = false, $id = false, $use_nonce = false )
	{
		$url = add_query_arg( array(
				'mode'   => $mode,
				'action' => $action,
				'id'     => $id
			),
			$this->get_admin_url( $this->pages[ self::PAGE_OVERVIEW ] )
		);

		if ( $use_nonce ) {
			$url = wp_nonce_url( $url, self::OPTION_NAME . $action . $id );
		}

		return $url;
	}

	/**
	 * Returns admin add page url.
	 *
	 * @since 1.0
	 *
	 * @param bool $use_noheader whether to use no header parameter or not
	 *
	 * @return string admin add page url
	 */
	private function get_add_url( $use_noheader = false )
	{
		return add_query_arg( 'noheader', $use_noheader, $this->get_admin_url( $this->pages[ self::PAGE_ADD ] ) );
	}

	/**
	 * Returns admin edit page url.
	 *
	 * @since 1.0
	 *
	 * @param int         $id     id of an existing ad
	 * @param bool|string $action the requested action
	 *
	 * @return string admin edit page url
	 */
	private function get_edit_url( $id, $action = false )
	{
		$url = add_query_arg( array(
				'id'     => $id,
				'action' => $action,
			),
			$this->get_admin_url( $this->pages[ self::PAGE_EDIT ] )
		);

		return $url;
	}

	/**
	 * Checks security nonce.
	 *
	 * @since 1.0
	 *
	 * @param string   $action the requested page
	 * @param null|int $id     id of an existing ad
	 */
	private function check_nonce( $action, $id = null )
	{
		/* nonce for the given action */
		$nonce_action = self::OPTION_NAME . $action;

		/* if id is given than add it nonce */
		if ( !empty( $id ) ) {
			$nonce_action .= $id;
		}

		/* checks nonce */
		check_admin_referer( $nonce_action );
	}

	/**
	 * Invoked when add page is requested. Adds an new ad when all inputs are valid and
	 * than redirects to the edit page.
	 *
	 * @since 1.0
	 */
	public function add_page()
	{
		/* initialize a new ad */
		$ad = new In_Image_Ad();

		/* if post request and add is set... */
		if ( isset( $_POST[ 'add' ] ) ) {
			/* checks nonce */
			$this->check_nonce( self::ACTION_ADD );

			/* sets all inputs to the related ad properties */
			$ad->set_title( $_POST[ 'iiam-title' ] );
			$ad->set_text( $_POST[ 'iiam-text' ] );
			$ad->set_url( $_POST[ 'iiam-url' ] );
			$ad->set_image_width( $_POST[ 'iiam-image-width' ] );
			$ad->set_image_height( $_POST[ 'iiam-image-height' ] );
			$ad->set_colors( array(
				In_Image_Ad::COLOR_BACKGROUND => $_POST[ 'iiam-background-color' ],
				In_Image_Ad::COLOR_TITLE      => $_POST[ 'iiam-title-color' ],
				In_Image_Ad::COLOR_TEXT       => $_POST[ 'iiam-text-color' ]
			) );

			global $wpdb;

			/* if ad is valid... */
			if ( $ad->is_valid() ) {
				/* inserts the new ad into the database */
				$result = $wpdb->insert( $this->get_table_name(),
					array(
						'title'        => $ad->get_title(),
						'text'         => $ad->get_text(),
						'url'          => $ad->get_url(),
						'image_width'  => $ad->get_image_width(),
						'image_height' => $ad->get_image_height(),
						'style'        => serialize( $ad->get_colors() )
					)
				);

				/* if successfully inserted... */
				if ( $result ) {
					/* redirects to edit page of the newly inserted ad */
					wp_redirect( $this->get_edit_url( $wpdb->insert_id, self::ACTION_SUCCESSFUL_ADD ) );
					exit();
				}
			} else {
				/* if no header parameter exists... */
				if ( isset( $_GET[ 'noheader' ] ) ) {
					/* includes the admin header file */
					require_once( ABSPATH . 'wp-admin/admin-header.php' );
				}
			}
		}

		/* renders the add page */
		$this->render_add_edit_page( self::PAGE_ADD, $this->get_add_url( true ), $ad );
	}

	/**
	 * Invoked when edit page is requested. Updates an existing ad when all inputs are valid.
	 *
	 * @since 1.0
	 */
	public function edit_page()
	{
		/* retrieves id */
		$id = isset( $_GET[ 'id' ] ) ? (int)$_GET[ 'id' ] : null;

		/* if id is not given... */
		if ( empty( $id ) ) {
			/* renders edit error page */
			$this->render_edit_error_page();
			exit();
		}

		global $wpdb;

		/* gets table name with correct prefix */
		$table = $this->get_table_name();

		/* if post request and update is set... */
		if ( isset( $_POST[ 'update' ] ) ) {
			/* checks nonce */
			$this->check_nonce( self::ACTION_EDIT, $id );

			/* initialize a new ad with the given id */
			$ad = new In_Image_Ad( $id );

			/* sets all inputs to the related ad properties */
			$ad->set_title( $_POST[ 'iiam-title' ] );
			$ad->set_text( $_POST[ 'iiam-text' ] );
			$ad->set_url( $_POST[ 'iiam-url' ] );
			$ad->set_image_width( $_POST[ 'iiam-image-width' ] );
			$ad->set_image_height( $_POST[ 'iiam-image-height' ] );
			$ad->set_colors( array(
				In_Image_Ad::COLOR_BACKGROUND => $_POST[ 'iiam-background-color' ],
				In_Image_Ad::COLOR_TITLE      => $_POST[ 'iiam-title-color' ],
				In_Image_Ad::COLOR_TEXT       => $_POST[ 'iiam-text-color' ]
			) );

			/* if ad is valid... */
			if ( $ad->is_valid() ) {
				/* updates an existing ad into the database */
				$wpdb->update(
					$table,
					array(
						'title'        => $ad->get_title(),
						'text'         => $ad->get_text(),
						'url'          => $ad->get_url(),
						'image_width'  => $ad->get_image_width(),
						'image_height' => $ad->get_image_height(),
						'style'        => serialize( $ad->get_colors() )
					),
					array( 'id' => $ad->get_id() ),
					array(
						'%s',
						'%s',
						'%s',
						'%d',
						'%d',
						'%s'
					),
					array( '%d' )
				);
			}
		} else {
			/* query for retrieving ad that has the given id */
			$query = '
						SELECT
							*
						FROM
							' . $table . '
						WHERE
							id = ' . $id;

			/* fires query */
			$result = $wpdb->get_row( $query, ARRAY_A );

			/* if result is empty and so no ad with the given id exists... */
			if ( empty( $result ) ) {
				/* render edit error page */
				$this->render_edit_error_page();
				exit();
			}

			/* initialize a new ad with the given id */
			$ad = new In_Image_Ad( $result[ 'id' ] );

			/* sets all inputs to the related ad properties */
			$ad->set_title( $result[ 'title' ] );
			$ad->set_text( $result[ 'text' ] );
			$ad->set_url( $result[ 'url' ] );
			$ad->set_image_width( $result[ 'image_width' ] );
			$ad->set_image_height( $result[ 'image_height' ] );
			$ad->set_colors( unserialize( $result[ 'style' ] ) );
		}

		/* retrieves action */
		$action = isset( $_GET[ 'action' ] ) ? $_GET[ 'action' ] : '';

		/* default add success indicator is false */
		$is_add_success = false;

		/* if success add action is set... */
		if ( $action === self::ACTION_SUCCESSFUL_ADD ) {
			/* assigns add success indicator with true */
			$is_add_success = true;
		}

		/* renders edit page */
		$this->render_add_edit_page( self::PAGE_EDIT, $this->get_edit_url( $id ), $ad, $is_add_success );
	}

	/**
	 * Renders add or edit page.
	 *
	 * @since 1.0
	 *
	 * @param int       $page_id        id of the requested page
	 * @param string    $page           slug of the requested page
	 * @param InImageAd $ad             ad instance
	 * @param bool      $is_add_success true when add successfully added otherwise false
	 */
	private function render_add_edit_page( $page_id, $page, $ad, $is_add_success = false )
	{
		?>
	<div class="wrap">
		<?php
		if ( !$ad->is_valid() ) {
			echo '<div id="message" class="error"><p><strong>' . __( 'The following errors occurred', self::TEXT_DOMAIN ) . ':</strong></p><ul>';
			foreach ( $ad->get_errors() as $field => $error_text ) {
				echo '<li><strong>' . $field . '</strong>: ' . $error_text . '</li>';
			}
			echo '</ul></div>';
		} else {
			if ( $is_add_success || isset( $_POST[ 'update' ] ) ) {
				echo '<div id="message" class="updated"><p>';

				if ( $is_add_success ) {
					echo '<strong>' . __( 'Successfully added', self::TEXT_DOMAIN ) . ' (iiam-' . $ad->get_id() . ')</strong>';
				} else {
					echo '<strong>' . __( 'Successfully updated', self::TEXT_DOMAIN ) . '</strong>';
				}

				echo '</p></div>';
			}
		}
		?>
	<h2><?php echo $page_id === self::PAGE_ADD ? __( 'Add', self::TEXT_DOMAIN ) : __( 'Edit', self::TEXT_DOMAIN ); ?></h2>
	<form action="<?php echo $page; ?>" method="post">
		<div class="metabox-holder has-right-sidebar">
			<div class="inner-sidebar">
				<div class="postbox">
					<h3><?php _e( 'Status', self::TEXT_DOMAIN ); ?></h3>
					<div class="submitbox">
						<div id="minor-publishing">
							<div class="misc-pub-section">
								<?php _e( 'ID', self::TEXT_DOMAIN ); ?>:
								<span id="iiam-ad-id">
										<strong>
											<?php $id = $ad->get_id(); ?>
											<?php echo !empty( $id ) ? ' iiam-' . $id : ' ' . __( 'Undefined', self::TEXT_DOMAIN ); ?>
										</strong>
									</span>
							</div>
							<div class="clear"></div>
						</div>
						<div id="major-publishing-actions">
							<div id="delete-action">
								<a class="submitdelete deletion"
								   href="<?php echo $this->get_overview_url(); ?>"
								   title="<?php _e( 'Cancel', self::TEXT_DOMAIN ); ?>">
									<?php _e( 'Cancel', self::TEXT_DOMAIN ); ?>
								</a>
							</div>
							<div id="publishing-action">
								<?php
								if ( $page_id === self::PAGE_ADD ) {
									wp_nonce_field( self::OPTION_NAME . self::ACTION_ADD );
									?>
									<input type="submit" value="<?php _e( 'Save', self::TEXT_DOMAIN ); ?>" name="add"
										   class="button-primary" />
									<?php
								} else {
									wp_nonce_field( self::OPTION_NAME . self::ACTION_EDIT . $ad->get_id() );
									?>
									<input type="submit" value="<?php _e( 'Update', self::TEXT_DOMAIN ); ?>"
										   name="update" class="button-primary" />
									<?php
								}
								?>
							</div>
							<div class="clear"></div>
						</div>
					</div>
				</div>
			</div>
			<div id="post-body">
				<div id="post-body-content" class="has-sidebar-content">
					<div class="postbox">
						<h3><?php _e( 'Data', self::TEXT_DOMAIN ); ?></h3>
						<div class="inside">
							<table class="form-table">
								<tr>
									<th><?php _e( 'Title', self::TEXT_DOMAIN ); ?>:</th>
									<td>
										<input id="iiam-title" name="iiam-title" type="text"
											   value="<?php echo $ad->get_title(); ?>" />
										<br />
										<span class="description">
											<?php _e( 'Choose a title for your ad.', self::TEXT_DOMAIN ); ?>
										</span>
									</td>
								</tr>
								<tr>
									<th><?php _e( 'Text', self::TEXT_DOMAIN ); ?>:</th>
									<td>
										<textarea id="iiam-text" name="iiam-text"
												  rows="2"><?php echo $ad->get_text(); ?></textarea>
										<br />
										<span class="description">
											<?php _e( 'Choose a text for your ad.', self::TEXT_DOMAIN ); ?>
										</span>
									</td>
								</tr>
								<tr>
									<th><?php _e( 'URL', self::TEXT_DOMAIN ); ?>:</th>
									<td>
										<input id="iiam-url" name="iiam-url" type="text"
											   value="<?php echo $ad->get_url(); ?>" />
										<br />
										<span class="description">
											<?php _e( 'Choose a url for your ad. When a user clicks on your ad he or she will be redirected to this URL.', self::TEXT_DOMAIN ); ?>
										</span>
									</td>
								</tr>
							</table>
							<span class="description">
								<?php _e( 'Note: If the title or the text is too long and so does not fit into the ad box it will be clipped and filled with ellipsis.', self::TEXT_DOMAIN ); ?>
							</span>
						</div>
					</div>
					<div class="postbox">
						<h3><?php _e( 'Image Size', self::TEXT_DOMAIN ); ?></h3>
						<div class="inside">
							<table class="form-table">
								<tr>
									<th><?php _e( 'Image Width', self::TEXT_DOMAIN ); ?>:</th>
									<td>
										<input id="iiam-image-width" name="iiam-image-width" type="text"
											   value="<?php echo $ad->get_image_width(); ?>"
											   size="1" /> px
										<br />
										<span class="description">
											<?php _e( 'Specify the image width.', self::TEXT_DOMAIN ); ?>
										</span>
									</td>
								</tr>
								<tr>
									<th><?php _e( 'Image Height', self::TEXT_DOMAIN ); ?>:</th>
									<td>
										<input id="iiam-image-height" name="iiam-image-height" type="text"
											   value="<?php echo $ad->get_image_height(); ?>"
											   size="1" /> px
										<br />
										<span class="description">
											<?php _e( 'Specify the image height.', self::TEXT_DOMAIN ); ?>
										</span>
									</td>
								</tr>
							</table>
							<span class="description">
								<?php _e( 'Note: Meant is the size of the image by which the ad will be overlayed. But this is just used for preview and for the overview page as additional information. The width of the ad is responsive and so always fits into the related image.', self::TEXT_DOMAIN ); ?>
							</span>
						</div>
					</div>
					<?php $colors = $ad->get_colors(); ?>
					<div class="postbox">
						<h3><?php _e( 'Style', self::TEXT_DOMAIN ); ?></h3>
						<div class="inside">
							<table class="form-table">
								<tr>
									<th><?php _e( 'Background Color', self::TEXT_DOMAIN ); ?>:</th>
									<td>
										<input id="iiam-background-color" class="pickcolor"
											   name="iiam-background-color" type="text"
											   value="<?php echo $colors[ In_Image_Ad::COLOR_BACKGROUND ]; ?>" />
										<br />
										<span class="description">
											<?php _e( 'Choose a background color for your ad.', self::TEXT_DOMAIN ); ?>
										</span>
									</td>
								</tr>
								<tr>
									<th><?php _e( 'Title Color', self::TEXT_DOMAIN ); ?>:</th>
									<td>
										<input id="iiam-title-color" class="pickcolor" name="iiam-title-color"
											   type="text"
											   value="<?php echo $colors[ In_Image_Ad::COLOR_TITLE ]; ?>" />
										<br />
										<span class="description">
											<?php _e( 'Choose a title color for your ad.', self::TEXT_DOMAIN ); ?>
										</span>
									</td>
								</tr>
								<tr>
									<th><?php _e( 'Text Color', self::TEXT_DOMAIN ); ?>:</th>
									<td>
										<input id="iiam-text-color" class="pickcolor" name="iiam-text-color"
											   type="text"
											   value="<?php echo $colors[ In_Image_Ad::COLOR_TEXT ]; ?>" />
										<br />
										<span class="description">
											<?php _e( 'Choose a text color for your ad.', self::TEXT_DOMAIN ); ?>
										</span>
									</td>
								</tr>
							</table>
							<span class="description">
								<?php _e( 'Note: When choosing a color keep in mind that the background of the ad will have a slight opacity.', self::TEXT_DOMAIN ); ?>
							</span>
						</div>
					</div>
					<div class="postbox">
						<h3><?php _e( 'Preview', self::TEXT_DOMAIN ); ?></h3>
						<div class="inside">
							<div id="iiam-preview">
								<div id="iiam-preview-ad"></div>
							</div>
							<span class="description">
								<?php _e( 'Note: The height the of the ad is limited to 50px.', self::TEXT_DOMAIN ); ?>
							</span>
						</div>
					</div>
				</div>
			</div>
		</div>
	</form>
	</div>
	<?php
	}

	/**
	 * Renders edit error page.
	 *
	 * @since 1.0
	 */
	private function render_edit_error_page()
	{
		?>
	<div class="wrap">
		<h2><?php _e( 'Edit In Image Ad', self::TEXT_DOMAIN ); ?></h2>
		<div id="message" class="updated">
			<p>
				<?php _e( 'Choose an existing ad on the overview page to edit an ad.', self::TEXT_DOMAIN ); ?>
			</p>
		</div>
	</div>
	<?php
	}

	/**
	 * Retrieves all ad ids from the requested post.
	 *
	 * @since 1.0
	 */
	public function get_ad_ids_from_post()
	{
		global $wp_query;

		if ( isset( $wp_query->post ) ) {
			/* gets post content */
			$postContent = $wp_query->post->post_content;

			/* search all ad ids */
			preg_match_all( '/iiam-(\d+)/', $postContent, $matches );

			/* stores the matched ad ids */
			$this->current_ad_ids = $matches[ 1 ];
		}
	}

	/**
	 * Enqueues scripts for the frontend.
	 *
	 * @since 1.0
	 */
	public function enqueue_scripts()
	{
		/* if ad ids found for the requested page... */
		if ( !empty( $this->current_ad_ids ) ) {
			wp_enqueue_script(
				'iiam',
				plugins_url( 'js/iiam.js', __FILE__ ),
				array( 'jquery' ),
				'1.0',
				true
			);
		}
	}

	/**
	 * Includes inline styles and scripts for the frontend.
	 *
	 * @since 1.0
	 */
	public function include_inline_scripts_styles()
	{
		/* get ads for the requested post */
		$ads = $this->get_ads();

		/* if ads existing... */
		if ( !empty( $ads ) ) {
			/* gets css for the ads */
			$cssOutput = $this->get_css( $ads );

			/* gets javascript for the ad data */
			$jsOutput = $this->get_js_formatted_ad_data( $ads );
			?>
		<style type="text/css">
				<?php echo $cssOutput; ?>
		</style>
		<script type="text/javascript">
			var inImageAds = [<?php echo $jsOutput; ?>];
		</script>
		<?php
		}
	}

	/**
	 * Returns all ads for the ad ids found for the requested page that are not in trash.
	 *
	 * @since 1.0
	 *
	 * @return null|array all ads for the ad ids found for the requested page
	 */
	private function get_ads()
	{
		/* if ad ids found for the requested page... */
		if ( !empty( $this->current_ad_ids ) ) {
			global $wpdb;

			/* query for retrieving all ads with the given ids */
			$query = '
				SELECT
					id,
					title,
					text,
					url,
					image_width,
					image_height,
					style
				FROM
					' . $this->get_table_name() . '
				WHERE
					id IN(' . implode( ",", $this->current_ad_ids ) . ')
					AND trash = FALSE;
			';

			/* fires query and returns result */
			return $wpdb->get_results( $query, ARRAY_A );
		}
	}

	/**
	 * Returns the css for the frontend.
	 *
	 * @since 1.0
	 *
	 * @param array $ads all ads for the requested page
	 *
	 * @return string the css for the frontend
	 */
	private function get_css( $ads )
	{
		$cssOutput = '';

		/* css needed for all ads */
		$cssOutput .= '
			div.iiam-box{position:absolute;font-family:Arial;line-height:1.7;}
			div.iiam-box a{display:block;width:100%;height:100%;cursor:pointer;font-size:12px;overflow:hidden;}
			div.iiam-box a:hover{text-decoration:none;}
			div.iiam-box a span{display:block;padding:3px 8px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
			div.iiam-box a .title{display:block;padding:0;font-size:13px;font-weight:bold;}
		';

		/* loops through all ads */
		foreach ( $ads as $ad ) {
			/* unserializes the ad styles */
			$colors = unserialize( $ad[ 'style' ] );

			/* ad specific css */
			$cssOutput .= '
				div.iiam-' . $ad[ 'id' ] . '-box{background:' . $this->get_css_rgb_by_hex_color( $colors[ In_Image_Ad::COLOR_BACKGROUND ] ) . ';}
				div.iiam-' . $ad[ 'id' ] . '-box a{color:' . $colors[ In_Image_Ad::COLOR_TEXT ] . ';}
				div.iiam-' . $ad[ 'id' ] . '-box a .title{color:' . $colors[ In_Image_Ad::COLOR_TITLE ] . ';}
			';
		}

		return $this->remove_whitespaces( $cssOutput );
	}

	/**
	 * Returns the ad data as javascript formatted
	 *
	 * @since 1.0
	 *
	 * @param array $ads all ads for the requested page
	 *
	 * @return string ad data as javascript formatted
	 */
	private function get_js_formatted_ad_data( $ads )
	{
		$jsOutput = '';

		foreach ( $ads as $ad ) {
			$jsOutput .= '{"id":"iiam-' . $ad[ 'id' ] . '","title":"' . $ad[ 'title' ] . '","text":"' . $ad[ 'text' ] . '","url":"' . $ad[ 'url' ] . '"},';
		}

		return substr( $jsOutput, 0, -1 );
	}

	/**
	 * Returns a rgb color with opacity for use with background in css for a given color and opacity.
	 *
	 * @since 1.0
	 *
	 * @param string $color   a color in hex format
	 * @param float  $opacity opacity for the background of the ad box
	 *
	 * @return string rgb color with opacity for use with background in css for a given color and opacity
	 */
	private function get_css_rgb_by_hex_color( $color, $opacity = 0.9 )
	{
		/* removes the '#' */
		$color = substr( $color, 1 );

		/* assigns the three color parts */
		list( $r, $g, $b ) = array(
			$color[ 0 ] . $color[ 1 ],
			$color[ 2 ] . $color[ 3 ],
			$color[ 4 ] . $color[ 5 ]
		);

		/* hex into decimal */
		$r = hexdec( $r );
		$g = hexdec( $g );
		$b = hexdec( $b );

		/* return rgb color */
		return 'rgba(' . $r . ',' . $g . ',' . $b . ',' . $opacity . ')';
	}

	/**
	 * Returns a (not fully) minified string by replacing whitespaces with a single space.
	 *
	 * @since 1.0
	 *
	 * @param string $string any string that needs to be minified
	 *
	 * @return string minified string
	 */
	private function remove_whitespaces( $string )
	{
		/* replaces whitespaces with a single space */
		$string = preg_replace( '/\s+/', ' ', $string );
		return $string;
	}
}

/* start everything when plugins loaded and ready to start */
add_action( 'plugins_loaded', 'initialize_in_image_ad_manager' );

/* registers hook for activation */
register_activation_hook( __FILE__, array( 'In_Image_Ads_Manager', 'install' ) );

/* registers hook for uninstall */
register_uninstall_hook( __FILE__, array( 'In_Image_Ads_Manager', 'uninstall' ) );

/**
 * Initialize a new In_Image_Ads_Manager instance.
 *
 * @since 1.0
 */
function initialize_in_image_ad_manager()
{
	new In_Image_Ads_Manager();
}