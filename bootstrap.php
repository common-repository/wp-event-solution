<?php

namespace Etn;

use Etn\Core\TemplateBuilder\Api_Template;
use Etn\Utils\Helper;
use Eventin\Eventin;
use Eventin\Base\Speaker_role;

defined( 'ABSPATH' ) || exit;

require_once ABSPATH . 'wp-admin/includes/plugin.php';

/**
 * Plugin final Class.
 * Handles dynamically loading classes only when needed. CheFck Elementor Plugin.
 *
 * @since 1.0.0
 */
final class Bootstrap {

	private static $instance;
	private $event;
	private $speaker;
	private $schedule;
	private $attendee;
	private $has_pro;

	/**
	 * __construct function
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// load autoload method.
		// Autoloader::run();

		Eventin::instance();		
	}

	/**
	 * Public function name.
	 * set for plugin name
	 *
	 * @since 1.0.0
	 */
	public function name() {
		return __( 'WP Event Solution', 'eventin' );
	}

	/**
	 * Public function init.
	 * call function for all
	 *
	 * @since 1.0.0
	 */
	public function init() {
		$this->create_table();

		include_once ABSPATH . 'wp-admin/includes/plugin.php';

		// handle woocommerce notice depending on settings.
		$this->handle_woo_dependency();

		$this->has_pro = defined( 'ETN_PRO_FILES_LOADED' );

		// handle buy-pro notice.
		$this->handle_buy_pro_module();

		// Do all migrations.
		Core\Migration\Migration::instance()->init();

		// check permission for manage user.
		add_action( 'after_setup_theme', array( $this, 'initialize_settings_dependent_cpt_modules' ), 11 );

		// Plugin to redirect when active the plugin
		add_action( 'after_setup_theme', [ $this, 'etn_activation_redirect' ], 99 );

		// register all styles and scripts.
		add_action( 'admin_enqueue_scripts', array( $this, 'js_css_admin' ) );
		add_action( 'elementor/frontend/before_enqueue_scripts', array( $this, 'elementor_js' ) );
		
		// archive search filter.
		add_filter( 'pre_get_posts', '\Etn\Utils\Helper::event_etn_search_filter', 999999 );
		add_action( 'wp_ajax_etn_event_ajax_get_data', '\Etn\Utils\Helper::etn_event_ajax_get_data' );
		add_action( 'wp_ajax_nopriv_etn_event_ajax_get_data', '\Etn\Utils\Helper::etn_event_ajax_get_data' );

		// archive pagination filter.
		add_filter( 'pre_get_posts', '\Etn\Utils\Helper::etn_event_archive_pagination_per_page' );


		// Initialize woocommerce module.
		Core\Woocommerce\Base::instance()->init();
		

		// initialize niche shortcode.
		Core\Shortcodes\Hooks::instance()->init();

		// initialize elementor widget.
		Widgets\Manifest::instance()->init();

		// call event-metabox hooks.
		if( class_exists( 'Wpeventin_Pro' )){ 
			if ( \Etn\Core\Addons\Helper::instance()->pro_version_checking( "3.3.32" ) ) {
				Core\Metaboxs\Pro_metabox::instance()->init();
			}
		} else {
			Core\Metaboxs\Pro_metabox::instance()->init();
		}

		// make admin menu open if custom taxonomy is selected.
		add_action( 'parent_file', array( $this, 'keep_taxonomy_menu_open' ) );

		// add mini-cart to header.
		add_action( 'wp_head', array( $this, 'etn_custom_inline_css' ) );

		// seat plan
		if ( \Etn\Core\Addons\Helper::instance()->check_active_module( "seat_map" ) ) {
			\Etn\Core\Modules\Seat_Plan\Seat_Plan::instance()->init();
		}

		// register gutenberg blocks.
		if ( file_exists( \Wpeventin::plugin_dir() . 'core/guten-block/inc/init.php' ) ) {
			include_once \Wpeventin::plugin_dir() . 'core/guten-block/inc/init.php';
		}

		// Recurring event functions.
		if ( file_exists( \Wpeventin::plugin_dir() . 'core/event/template-functions.php' ) ) {
			include_once \Wpeventin::plugin_dir() . 'core/event/template-functions.php';
		}

		$payment_gateway = Helper::retrieve_payment_gateway();
		if ( $payment_gateway == 'woocommerce' ) {
			if ( file_exists( \Wpeventin::plugin_dir() . 'core/woocommerce/etn-product-data-store-cpt.php' ) ) {
				include_once \Wpeventin::plugin_dir() . 'core/woocommerce/etn-product-data-store-cpt.php';
			}

			if ( file_exists( \Wpeventin::plugin_dir() . '/core/woocommerce/etn-order-item-product.php' ) ) {
				include_once \Wpeventin::plugin_dir() . '/core/woocommerce/etn-order-item-product.php';
			}
		}

		// register wpml functions.
		if ( class_exists( 'SitePress' ) && function_exists( 'icl_object_id' ) && file_exists( \Wpeventin::plugin_dir() . 'core/wpml/init.php' ) ) {
			include_once \Wpeventin::plugin_dir() . 'core/wpml/init.php';
		}

		// Bricks theme compatibility
		$theme = wp_get_theme(); // gets the current theme
		if ( ! empty( $theme ) && ( 'Bricks' == $theme->name || 'Bricks' == $theme->parent_theme ) ) {
			add_filter( 'language_attributes', [ $this, 'add_class_in_html_bricks' ], 10, 2 );

		}

		// Include functions file.
		// include_once \Wpeventin::plugin_dir() . 'utils/functions.php';

		new \Etn\Base\Enqueue\Register();
		
		// Instantiate Eventin AI module.
		\Etn\Core\Modules\Eventin_Ai\Eventin_AI::instance()->init();
		// Initialize Template Api.
		Api_Template::instance()->init();

		// Ticket style enqueue.
		// Todo: Need to register this one style file from base qnqueue class
		add_action( 'wp_enqueue_scripts', array( $this, 'register_ticket_markup_style' ) );

		if ( etn_is_request( 'admin' ) ) {
			new \Etn\Base\Enqueue\Admin();
		}

		if ( etn_is_request( 'frontend' ) ) {
			new \Etn\Base\Enqueue\Frontend();
		}

		Speaker_role::instance()->init();

		add_action( 'template_include', array( $this, 'single_author_page' ));

	}

	/**
	 * Checks if the current page is an author page and if the author has the 'etn-speaker' or 'etn-organizer' role.
	 * If so, it includes the template part for single author.
	 *
	 * @since 2.4.0
	 */
	public function single_author_page($template) {
		if (!is_author()) {
			return $template;
		}
	
		$author_id = get_queried_object_id();
		$author = get_userdata($author_id);
	
		if ($author && (in_array('etn-speaker', (array) $author->roles) || in_array('etn-organizer', (array) $author->roles))) {
			// Path to your custom author template in the plugin folder
			$custom_template = \Wpeventin::plugin_dir() . "templates/single-author.php";
			// If the file exists, use it
			if (file_exists($custom_template)) {
				return $custom_template;
			}
		}
	
		return $template;
	}
	

	public function add_class_in_html_bricks( $output, $doctype ) {
		if ( 'html' !== $doctype ) {
			return $output;
		}
		$output .= ' class="no-js no-svg bricks_parent"';

		return $output;
	}

	/**
	 * redirect to setup wizard when active pluginn
	 *
	 */
	public function etn_activation_redirect() {
		if ( ( ! get_option( 'etn_wizard' ) ) ) {
			update_option( 'etn_wizard', 'active' );
			wp_redirect( admin_url( 'admin.php?page=etn-wizard' ) );
			exit;
		}
	}	

	/**
	 * Add wizard submenu
	 *
	 */
	public function add_wizard_menu() {

		// Add settings menu if user has specific access
		if ( current_user_can( 'manage_etn_settings' ) && current_user_can( 'manage_options' ) ) {
			add_submenu_page(
				'',
				esc_html__( 'Wizard', 'eventin' ),
				esc_html__( 'Wizard', 'eventin' ),
				'manage_options',
				'etn-wizard',
				[ $this, 'etn_wizard_page' ],
				11
			);
		}

	}


	/**
	 * Settings Markup Page
	 *
	 * @return void
	 */
	public function etn_wizard_page() {
		?>
<div class="etn-wizard-wrapper" id="etn-wizard-wrapper"></div>
<?php
	}


	/**
	 * Add Shortcode Sub-menu
	 *
	 * @return void
	 * @since 2.6.1
	 *
	 */
	public function add_shortcode_menu() {

		// Add settings menu if user has specific access
		if ( current_user_can( 'manage_etn_settings' ) && current_user_can( 'manage_options' ) ) {

			add_submenu_page(
				'eventin',
				esc_html__( 'Shortcodes', 'eventin' ),
				esc_html__( 'Shortcodes', 'eventin' ),
				'manage_options',
				'etn-event-shortcode',
				[ $this, 'etn_shortcode_page' ],
				5
			);
		}
	}

	/**
	 * Settings Markup Page
	 *
	 * @return void
	 */
	public function etn_shortcode_page() {
		include_once( \Wpeventin::plugin_dir() . "templates/layout/header.php" );

		$shortcodeView = \Wpeventin::plugin_dir() . "core/shortcodes/views/shortcode-list-menu.php";
		if ( file_exists( $shortcodeView ) ) {
			include $shortcodeView;
		}
	}

	/**
	 * Add addons Sub-menu
	 *
	 * @return void
	 * @since 2.6.1
	 *
	 */

	public function add_addons_menu() {

		// Add settings menu if user has specific access
		if ( current_user_can( 'manage_etn_settings' ) && current_user_can( 'manage_options' ) ) {

			add_submenu_page(
				'eventin',
				esc_html__( 'Add-ons', 'eventin' ),
				esc_html__( 'Add-ons', 'eventin' ),
				'manage_options',
				'etn_addons',
				[ $this, 'etn_addons_page' ],
				6
			);
		}
	}

	/**
	 * Version 4 Page
	 *
	 * @return void
	 * @since 4.0.0
	 *
	 */
	public function version_four_page() {
		// Add settings menu if user has specific access
		add_submenu_page(
			'eventin',
			esc_html__( 'Events', 'eventin' ),
			esc_html__( 'Events', 'eventin' ),
			'manage_options',
			'eventin',
			[ $this, 'etn_version_four_page' ],
			0
		);
	}

	/**
	 * Version 4 page markup
	 *
	 * @return void
	 */
	public function etn_version_four_page() {
		\do_action('enqueue_block_assets');
 		wp_enqueue_script( 'etn-dashboard' );
		wp_enqueue_style( 'etn-dashboard' );
		$settings = etn_editor_settings();
        wp_add_inline_script( 'etn-dashboard', 'window.eventinEditorSettings = ' . wp_json_encode( $settings ) . ';' );
		wp_enqueue_script('wp-edit-post');
		$versionFourView = \Wpeventin::plugin_dir() . "core/admin-view/dashboard.php";
		include $versionFourView;
	}

	/**
	 * Settings Addons Page
	 *
	 * @return void
	 */
	public function etn_addons_page() {
		include_once( \Wpeventin::plugin_dir() . "templates/layout/header.php" );

		$addonsView = \Wpeventin::plugin_dir() . "core/Extensions/addons.php";
		if ( file_exists( $addonsView ) ) {
			include $addonsView;
		}
	}

	/**
	 * Add Settings Sub-menu
	 *
	 * @return void
	 * @since 1.0.1
	 *
	 */
	public function add_setting_menu() {

		// Add settings menu if user has specific access
		if ( current_user_can( 'manage_etn_settings' ) && current_user_can( 'manage_options' ) ) {

			add_submenu_page(
				'etn-events-manager',
				esc_html__( 'Settings', 'eventin' ),
				esc_html__( 'Settings', 'eventin' ),
				'manage_options',
				'etn-event-settings',
				[ $this, 'etn_settings_page' ],
				10
			);
		}
	}

	/**
	 * Settings Markup Page
	 *
	 * @return void
	 */
	public function etn_settings_page() {
		$settings_file = \Wpeventin::plugin_dir() . "core/settings/views/etn-settings.php";
		if ( file_exists( $settings_file ) ) {
			include $settings_file;
		}
	}


	/**
	 * Initialize some cpt modules like attendee, zoom, schedules, speakers
	 *
	 * @return void
	 */
	public function initialize_settings_dependent_cpt_modules() {
		add_action( 'admin_menu', [ $this, 'add_shortcode_menu' ], 100 );
		add_action( 'admin_menu', [ $this, 'add_wizard_menu' ], 101 );
		add_action( 'admin_menu', [ $this, 'add_addons_menu' ], 102 );
		
		// Initialize event module.
		Core\Event\Hooks::instance()->init();
		// recurring event.
		Core\Recurring_Event\Hooks::instance()->init();

		// initialize event ticket registration module.
		Core\Event\Registration::instance()->init();

		// Initialize schedule module.
		Core\Schedule\Hooks::instance()->init();

		// Initialize speaker module.
		Core\Speaker\Hooks::instance()->init();

		// Initialize Admin Hooks.
		Core\Admin\Hooks::instance()->init();

		// Core\Attendee\Hooks::instance()->init();
		Core\Attendee\Attendee_List::instance()->init();
		Core\Attendee\Hooks::instance()->init();
		Core\Attendee\InfoUpdate::instance()->init();
	}

	/**
	 * Handle woocommerce admin notice depending on settings
	 *
	 * @return void
	 */
	public function handle_woo_dependency() {

		$eventin_global_settings = \Etn\Utils\Helper::get_settings();
		$sell_tickets            = ! empty( $eventin_global_settings['sell_tickets'] ) ? true : false;

		if ( $sell_tickets && ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			add_action( 'admin_head', array( $this, 'admin_notice_wc_not_active' ) );

			return;
		}
	}

	/**
	 * Show buy-pro menu if pro plugin not active
	 *
	 * @return void
	 */
	public function handle_buy_pro_module() {

		/**
		 * Show banner (codename: jhanda)
		 */
		$filter_string = 'eventin,eventin-free-only';

		if ( $this->has_pro ) {

			$filter_string .= ',eventin-pro';
			$filter_string = str_replace( ',eventin-free-only', '', $filter_string );

		}
		\Wpmet\Libs\Banner::instance('eventin')
			// ->is_test(true)
			->set_filter(ltrim($filter_string, ','))
			->set_api_url('https://demo.themewinter.com/public/jhanda')
			->set_plugin_screens('edit-etn')
			->set_plugin_screens('eventin_page_etn_sales_report')
			->set_plugin_screens('edit-etn-attendee')
  			->set_plugin_screens('eventin_page_eventin_get_help')
 			->call();
		// show get-help and upgrade-to-premium menu.
		$this->handle_get_help_and_upgrade_menu();
	}

	/**
	 * Show menu for get-help
	 * Show menu for upgrade-te-premium if pro version not active
	 *
	 * @return void
	 */
	public function handle_get_help_and_upgrade_menu() {

		/**
		 * Show go Premium menu
		 */
		\Wpmet\Libs\Pro_Awareness::instance( 'eventin' )
			->set_parent_menu_slug( 'eventin' )
			->set_plugin_file( 'wp-event-solution/eventin.php' )
			// ->set_pro_link( $this->has_pro ? '' : 'https://themewinter.com/eventin/' )
			->set_default_grid_thumbnail( \Wpeventin::plugin_url() . '/utils/pro-awareness/assets/document.png' )
			->set_default_grid_link( 'https://support.themewinter.com/docs/plugins/docs-category/eventin/' )
			->set_default_grid_desc( esc_html__( 'Learn More', 'eventin' ) )
			->set_page_grid(
				array(
					'url'         => 'https://themewinter.com/support/',
					'title'       => esc_html__( 'Email Support', 'eventin' ),
					'thumbnail'   => \Wpeventin::plugin_url() . '/utils/pro-awareness/assets/envelope.png',
					'description' => esc_html__( 'Learn More', 'eventin' ),
				)
			)
			->set_page_grid(
				array(
					'url'         => 'https://themewinter.com/',
					'title'       => esc_html__( 'Live Chat', 'eventin' ),
					'thumbnail'   => \Wpeventin::plugin_url() . '/utils/pro-awareness/assets/chat.png',
					'description' => esc_html__( 'Learn More', 'eventin' ),
				)
			)
			->set_page_grid(
				array(
					'url'         => 'https://www.youtube.com/watch?v=FSC-jtN9xgg&list=PLW54c-mt4ObDwu0GWjJIoH0aP1hQHyKj7',
					'title'       => esc_html__( 'Video Tutorials', 'eventin' ),
					'thumbnail'   => \Wpeventin::plugin_url() . '/utils/pro-awareness/assets/video.png',
					'description' => esc_html__( 'Learn More', 'eventin' ),
				)
			)
			->set_plugin_row_meta( 'Documentation', 'https://support.themewinter.com/docs/plugins/docs-category/eventin/', array( 'target' => '_blank' ) )
			->set_plugin_row_meta( 'Facebook Community', 'https://www.facebook.com/groups/themewinter', array( 'target' => '_blank' ) )
			->set_plugin_action_link( 'Settings', admin_url() . 'admin.php?page=eventin#/settings/event-settings/event-details' )
			->set_plugin_action_link(
				( $this->has_pro ? '' : 'Go Premium' ),
				'https://themewinter.com/eventin/',
				array(
					'target' => '_blank',
					'style'  => 'color: #FCB214; font-weight: bold;',
				)
			)
			->set_plugin_row_meta( 'Rate the plugin ★★★★★', 'https://wordpress.org/support/plugin/wp-event-solution/reviews/#new-post', array( 'target' => '_blank' ) )
			->call();
	}

	/**
	 * Show notice if woocommerce not active
	 */
	public function admin_notice_pro_not_active() {

		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}
		$btn = array(
			'default_class' => 'button',
			'class'         => 'button-primary ',
		);
		if ( file_exists( WP_PLUGIN_DIR . '/eventin-pro/eventin-pro.php' ) ) {
			$btn['text'] = esc_html__( 'Activate Eventin Pro', 'eventin' );
			$btn['url']  = wp_nonce_url( 'plugins.php?action=activate&plugin=eventin-pro/eventin-pro.php&plugin_status=all&paged=1', 'activate-plugin_eventin-pro/eventin-pro.php' );
		} else {
			$btn['text'] = esc_html__( 'Buy Eventin Pro', 'eventin' );
			$btn['url']  = esc_url( $this->get_pro_link() );
		}

		\Oxaim\Libs\Notice::instance( 'eventin', 'buy-eventin-pro' )
		->set_class( 'error' )
		->set_dismiss( 'global', ( 3600 * 24 * 30 ) )
		->set_message( sprintf( esc_html__( 'Get Eventin Pro for more exciting features.', 'eventin' ) ) )
		->set_button( $btn )
		->call();
	}

	/**
	 * Open taxonomy inside eventin
	 *
	 * @param string $parent_file Parent File.
	 *
	 * @return string
	 */
	public function keep_taxonomy_menu_open( $parent_file ) {
		global $current_screen;
		$taxonomy            = $current_screen->taxonomy;
		$eligible_taxonomies = array( 'etn_category', 'etn_tags', 'etn_speaker_category', 'etn_location' );

		if ( in_array( $taxonomy, $eligible_taxonomies ) ) {
			$parent_file = 'etn-events-manager';
		}

		return $parent_file;
	}

	/**
	 * Show notice if woocommerce not active
	 */
	public function admin_notice_wc_not_active() {

		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}
		$btn = array(
			'default_class' => 'button',
			'class'         => 'button-primary ',
		);
		if ( file_exists( WP_PLUGIN_DIR . '/woocommerce/woocommerce.php' ) ) {
			$btn['text'] = esc_html__( 'Activate WooCommerce', 'eventin' );
			$btn['url']  = wp_nonce_url( 'plugins.php?action=activate&plugin=woocommerce/woocommerce.php&plugin_status=all&paged=1', 'activate-plugin_woocommerce/woocommerce.php' );
		} else {
			$btn['text'] = esc_html__( 'Install WooCommerce', 'eventin' );
			$btn['url']  = wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=woocommerce' ), 'install-plugin_woocommerce' );
		}

		\Oxaim\Libs\Notice::instance( 'eventin', 'unsupported-woocommerce-version' )
		->set_class( 'error' )
		->set_dismiss( 'global', ( 3600 * 24 * 30 ) )
		->set_message( sprintf( esc_html__( 'Eventin requires WooCommerce to get all features, which is currently NOT RUNNING.', 'eventin' ) ) )
		->set_button( $btn )
		->call();
	}

	/**
	 * Public function package_type.
	 * set for plugin package type
	 *
	 * @since 1.0.0
	 */
	public function package_type() {
		return 'free';
	}

	/**
	 * Plugin Text Domain
	 *
	 * @return string
	 */
	public function text_domain() {
		return 'eventin';
	}

	

	/**
	 * Enqueue Elementor Assets
	 *
	 * @return void
	 */
	public function elementor_js() {
		wp_enqueue_script( 'etn-elementor-inputs', \Wpeventin::assets_url() . 'js/elementor.js', array( 'elementor-frontend' ), \Wpeventin::version(), true );
		wp_enqueue_script( 'etn-app-index' );
	}

	/**
	 * Enqueue JS CSS
	 *
	 * @return void
	 */
	public function js_css_admin() {

		// get screen id.
		$screen    = get_current_screen();
		$screen_id = $screen->id;

		$allowed_screen_ids = array(
			'post',
			'page',
			'etn',
			'widgets',
			'edit-etn',
			'etn-attendee',
			'edit-etn-attendee',
			'edit-etn_category',
			'edit-etn_tags',
			'etn-schedule',
			'edit-etn-schedule',
			'edit-etn_speaker_category',
			'etn-speaker',
			'edit-etn-speaker',
			'etn-zoom-meeting',
			'edit-etn-zoom-meeting',
			'eventin_page_etn-event-settings',
			'eventin_page_etn_sales_report',
			'eventin_page_eventin_get_help',
			'eventin_page_etn-license',
			'eventin_page_etn-event-shortcode',
			'admin_page_etn-wizard',
			'edit-etn_location',
			'eventin_page_etn_stripe_orders_report',
			'eventin_page_etn_addons',
			'buddyboss_page_bp-integrations',
			'eventin_page_etn_rsvp_invitation',
			'eventin_page_etn_fb_import'
		);


		if ( in_array( $screen_id, array("woocommerce_page_wc-orders") ) ) {
			wp_enqueue_style( 'event-manager-admin' );
		}

		if ( in_array( $screen_id, $allowed_screen_ids ) ) {
			$localized_data = etn_get_locale_data();
			wp_localize_script( 'etn', 'form_data', $localized_data );
		}
		
		if($screen_id ==='admin_page_etn-wizard'){ 

			wp_enqueue_style( 'etn-onboard-index' );
			wp_enqueue_script( 'etn-onboard-index' );
			wp_set_script_translations( 'etn-onboard-index', 'eventin' );
			$localize_data = etn_get_locale_data();
			wp_localize_script( 'etn-onboard-index', 'localized_data_obj', $localize_data );
		}
	}

	/**
	 * Register Menu
	 *
	 * @return void
	 */
	public function register_admin_menu() {

		if ( current_user_can( 'manage_etn_event' ) || current_user_can( 'manage_etn_speaker' ) || current_user_can( 'manage_etn_schedule' ) || current_user_can( 'manage_etn_attendee' ) || current_user_can( 'manage_etn_zoom' ) || current_user_can( 'manage_etn_settings' ) ) {
			add_menu_page(
				esc_html__('Eventin', 'eventin'),
				'Eventin',
				'read',
				'etn-events-manager',
				'',
				"data:image/svg+xml;base64,PHN2ZyBzdHlsZT0icGFkZGluLXRvcDogNnB4IiB3aWR0aD0iMjAiIGhlaWdodD0iMjIiIHZpZXdCb3g9IjAgMCAyNiA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTI1LjExMyAxOS4yNjA0TDE3LjU2OTcgMjYuODAyMkwxMi43MDY3IDMxLjY2NTJMMTAuMzI0IDI5LjI4MjVMNy44ODU3MyAyNi44NDVDNi43NTkyNyAyNS43MTgzIDYuMDYyNzkgMjQuMjMyNyA1LjkxNzEyIDIyLjY0NjFDNS43NzE0NSAyMS4wNTk1IDYuMTg1NzcgMTkuNDcyIDcuMDg4MjEgMTguMTU5QzcuOTkwNjUgMTYuODQ1OSA5LjMyNDI3IDE1Ljg5MDIgMTAuODU3NyAxNS40NTc3QzEyLjM5MTEgMTUuMDI1MSAxNC4wMjc2IDE1LjE0MyAxNS40ODMyIDE1Ljc5MDlMMTIuNjgzNCAxOC41OTA3QzEyLjEzNjEgMTkuMTM3OSAxMS43MDIgMTkuNzg3NSAxMS40MDU4IDIwLjUwMjVDMTEuMTA5NiAyMS4yMTc1IDEwLjk1NzIgMjEuOTgzOCAxMC45NTcyIDIyLjc1NzdDMTAuOTU3MiAyMy41MzE2IDExLjEwOTYgMjQuMjk3OSAxMS40MDU4IDI1LjAxMjlDMTEuNzAyIDI1LjcyNzggMTIuMTM2MSAyNi4zNzc1IDEyLjY4MzQgMjYuOTI0N0wxOS4zMDY3IDIwLjMwMTRMMjMuODAwNiAxNS44MDY2QzIzLjIzMiAxNC43ODk0IDIyLjUyNSAxMy44NTYxIDIxLjY5OTkgMTMuMDMzMUMyMS4xMTk3IDEyLjQ1MjMgMjAuNDg0OSAxMS45Mjg4IDE5LjgwNDMgMTEuNDY5OEMxOC45Mzk0IDEwLjg4NTggMTguMDA1MiAxMC40MTE5IDE3LjAyMzIgMTAuMDU5QzE1LjgxMjIgMTEuMDY3MiAxNC4yODYyIDExLjYxOTMgMTIuNzEwNCAxMS42MTkzQzExLjEzNDYgMTEuNjE5MyA5LjYwODYxIDExLjA2NzIgOC4zOTc1OSAxMC4wNTlDNi42MzcyOCAxMC42OTMyIDUuMDM5IDExLjcwODggMy43MTcyMSAxMy4wMzMxQy0wLjY0ODIyNyAxNy4zOTg2IC0xLjE2ODM1IDI0LjE3NDUgMi4xNTUzNCAyOS4xMTc5QzIuNjEzNjMgMjkuNzk4MiAzLjEzNjcgMzAuNDMyNSAzLjcxNzIxIDMxLjAxMkw2LjE1NTQ5IDMzLjQ0NzNMMTIuNzA2NyA0MEwyMS42OTY4IDMxLjAxMkMyMy4yMDkgMjkuNDk4OCAyNC4zMTQ4IDI3LjYyODUgMjQuOTExOSAyNS41NzQzQzI1LjUwOTEgMjMuNTIwMSAyNS41NzgyIDIxLjM0ODQgMjUuMTEzIDE5LjI2MDRaIiBmaWxsPSJ3aGl0ZSIvPgo8cGF0aCBkPSJNMTIuNzA2IDkuNzI0NTNDMTUuMzkxNCA5LjcyNDUzIDE3LjU2ODMgNy41NDc2MiAxNy41NjgzIDQuODYyMjdDMTcuNTY4MyAyLjE3NjkxIDE1LjM5MTQgMCAxMi43MDYgMEMxMC4wMjA3IDAgNy44NDM3NSAyLjE3NjkxIDcuODQzNzUgNC44NjIyN0M3Ljg0Mzc1IDcuNTQ3NjIgMTAuMDIwNyA5LjcyNDUzIDEyLjcwNiA5LjcyNDUzWiIgZmlsbD0id2hpdGUiLz4KPC9zdmc+Cg== ",
				10
			);
		}
	}


	/**
	 * Singleton Instance
	 *
	 * @return Bootstrap
	 */
	public static function instance() {

		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Create plugin specific tables to store data
	 *
	 * @return void
	 */
	public function create_table() {
		global $wpdb;
		$table_name      = ETN_EVENT_PURCHASE_HISTORY_TABLE;
		$charset_collate = $wpdb->get_charset_collate();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// create table for events.
		if ( $table_name !== $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) ) {

			// create table to store event purchase history.
			// post_id is the event id.
			// form_id is the woo order id.
			// create events table.
			$wdp_sql = "CREATE TABLE IF NOT EXISTS `$table_name` (
			  `event_id` mediumint(9) NOT NULL AUTO_INCREMENT,
			  `post_id` bigint(20) NOT NULL COMMENT 'This id is teh event id',
			  `form_id` bigint(20) NOT NULL COMMENT 'This id From wp post table',
			  `invoice` varchar(150) NOT NULL,
			  `event_amount` double NOT NULL DEFAULT '0',
			  `user_id` mediumint(9) NOT NULL,
			  `email` varchar(200) NOT NULL,
			  `event_type` ENUM('ticket') DEFAULT 'ticket',
			  `payment_type` ENUM('woocommerce', 'stripe') DEFAULT 'woocommerce',
			  `pledge_id` varchar(20) NOT NULL DEFAULT '0',
			  `payment_gateway` ENUM('offline_payment', 'online_payment', 'bank_payment', 'check_payment', 'stripe_payment', 'other_payment') default 'online_payment',
			  `date_time` datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			  `status` ENUM('Active','Review', 'DeActive', 'Failed', 'Processing', 'Pending', 'Hold', 'Refunded', 'Delete', 'Completed', 'Cancelled') DEFAULT 'Pending',
			  PRIMARY KEY (`event_id`)
			) $charset_collate;";
			dbDelta( $wdp_sql );

			// create meta table.
			$table_name_meta = ETN_EVENT_PURCHASE_HISTORY_META_TABLE;

			$wdp_meta = "
				CREATE TABLE IF NOT EXISTS `$table_name_meta`(
					`meta_id` mediumint NOT NULL AUTO_INCREMENT,
					`event_id` mediumint NOT NULL,
					`meta_key` varchar(255),
					`meta_value` longtext,
					PRIMARY KEY(`meta_id`)
				) $charset_collate;
			";
			dbDelta( $wdp_meta );

			update_option( 'etn_version', \Wpeventin::version() );
		}

		// run table column migration for older version than 2.3.3.
		if ( version_compare( get_option( 'etn_version' ), '2.3.3', '<' ) ) {

			$migration_query = "ALTER TABLE `$table_name` MODIFY COLUMN `status` ENUM('Failed', 'Processing', 'Pending', 'Hold', 'Refunded', 'Completed', 'Cancelled') DEFAULT 'Pending';";

			$wpdb->query( $migration_query );
		}

	}

	/**
	 * Custom inline css
	 */
	public function etn_custom_inline_css() {
		$etn_custom_css  = '';
		$primary_color   = '#5D78FF';
		$secondary_color = '';

		// cart bg color.
		$etn_primary_color = etn_get_option( 'etn_primary_color' );
		if ( $etn_primary_color ) {
			$primary_color = $etn_primary_color;
		}

		// cart icon color.
		$etn_secondary_color = etn_get_option( 'etn_secondary_color' );
		if ( $etn_secondary_color ) {
			$secondary_color = $etn_secondary_color;
		}

		$etn_custom_css .= "
        .etn-event-single-content-wrap .etn-event-meta .etn-event-category span,
        .etn-event-item .etn-event-footer .etn-atend-btn .etn-btn-border,
        .etn-btn.etn-btn-border, .attr-btn-primary.etn-btn-border,
        .etn-attendee-form .etn-btn.etn-btn-border,
        .etn-ticket-widget .etn-btn.etn-btn-border,
        .etn-settings-dashboard .button-primary.etn-btn-border,
        .etn-single-speaker-item .etn-speaker-content a:hover,
        .etn-event-style2 .etn-event-date,
        .etn-event-style3 .etn-event-content .etn-title a:hover,
        .event-tab-wrapper ul li a.etn-tab-a,
        .etn-speaker-item.style-3:hover .etn-speaker-content .etn-title a,
		.etn-variable-ticket-widget .ticket-header,
		.events_calendar_list .calendar-event-details:hover .calendar-event-title,
        .etn-event-item:hover .etn-title a,
		.etn-recurring-widget .etn-date-text,
		
		.etn-event-header ul li i {
            color: {$primary_color};
        }
        .etn-event-item .etn-event-category span,
        .etn-btn, .attr-btn-primary,
        .etn-attendee-form .etn-btn,
        .etn-ticket-widget .etn-btn,
        .schedule-list-1 .schedule-header,
        .speaker-style4 .etn-speaker-content .etn-title a,
        .etn-speaker-details3 .speaker-title-info,
        .etn-event-slider .swiper-pagination-bullet, .etn-speaker-slider .swiper-pagination-bullet,
        .etn-event-slider .swiper-button-next, .etn-event-slider .swiper-button-prev,
        .etn-speaker-slider .swiper-button-next, .etn-speaker-slider .swiper-button-prev,
        .etn-single-speaker-item .etn-speaker-thumb .etn-speakers-social a,
        .etn-event-header .etn-event-countdown-wrap .etn-count-item,
        .schedule-tab-1 .etn-nav li a.etn-active,
        .schedule-list-wrapper .schedule-listing.multi-schedule-list .schedule-slot-time,
        .etn-speaker-item.style-3 .etn-speaker-content .etn-speakers-social a,
        .event-tab-wrapper ul li a.etn-tab-a.etn-active,
        .etn-btn, button.etn-btn.etn-btn-primary,
        .etn-schedule-style-3 ul li:before,
        .etn-zoom-btn,
        .cat-radio-btn-list [type=radio]:checked+label:after,
        .cat-radio-btn-list [type=radio]:not(:checked)+label:after,
        .etn-default-calendar-style .fc-button:hover,
        .etn-default-calendar-style .fc-state-highlight,
		.etn-calender-list a:hover,
        .events_calendar_standard .cat-dropdown-list select,
		.etn-event-banner-wrap,
		.events_calendar_list .calendar-event-details .calendar-event-content .calendar-event-category-wrap .etn-event-category,
		.etn-variable-ticket-widget .etn-add-to-cart-block,
		.etn-recurring-event-wrapper #seeMore,
		.more-event-tag,
		.etn-order-purchase-create-form .ant-input-outlined:hover,
		.etn-order-purchase-create-form .ant-input-outlined:focus-within,
        .etn-settings-dashboard .button-primary{
            background-color: {$primary_color};
        }

        .etn-event-item .etn-event-footer .etn-atend-btn .etn-btn-border,
        .etn-btn.etn-btn-border, .attr-btn-primary.etn-btn-border,
        .etn-attendee-form .etn-btn.etn-btn-border,
        .etn-ticket-widget .etn-btn.etn-btn-border,
        .event-tab-wrapper ul li a.etn-tab-a,
        .event-tab-wrapper ul li a.etn-tab-a.etn-active,
        .etn-schedule-style-3 ul li:after,
        .etn-default-calendar-style .fc-ltr .fc-basic-view .fc-day-top.fc-today .fc-day-number,
        .etn-default-calendar-style .fc-button:hover,
		.etn-variable-ticket-widget .etn-variable-total-price,
		.etn-order-purchase-create-form .ant-input-outlined:hover,
		.etn-order-purchase-create-form .ant-input-outlined:focus-within,
		.etn-order-purchase-create-form .ant-input-number-outlined:focus-within,
		.etn-order-purchase-create-form .ant-input-number-outlined:hover,
        .etn-settings-dashboard .button-primary.etn-btn-border{
            border-color: {$primary_color};
        }
        .schedule-tab-wrapper .etn-nav li a.etn-active,
        .etn-speaker-item.style-3 .etn-speaker-content{
            border-bottom-color: {$primary_color};
        }
        .schedule-tab-wrapper .etn-nav li a:after,
        .etn-event-list2 .etn-event-content,
        .schedule-tab-1 .etn-nav li a.etn-active:after{
            border-color: {$primary_color} transparent transparent transparent;
        }

        .etn-default-calendar-style .fc .fc-daygrid-bg-harness:first-of-type:before{
            background-color: {$primary_color}2A;
        }
		 .sidebar .etn-default-calendar-style .fc .fc-daygrid-bg-harness:nth-of-type(1)::before,
		 .left-sidebar .etn-default-calendar-style .fc .fc-daygrid-bg-harness:nth-of-type(1)::before,
		 .right-sidebar .etn-default-calendar-style .fc .fc-daygrid-bg-harness:nth-of-type(1)::before,
		  .widget .etn-default-calendar-style .fc .fc-daygrid-bg-harness:nth-of-type(1)::before,
		   .widgets .etn-default-calendar-style .fc .fc-daygrid-bg-harness:nth-of-type(1)::before,
		   .main-sidebar .etn-default-calendar-style .fc .fc-daygrid-bg-harness:nth-of-type(1)::before,
		    #sidebar .etn-default-calendar-style .fc .fc-daygrid-bg-harness:nth-of-type(1)::before{
				background-color: {$primary_color};
		 }

        .etn-event-item .etn-event-location,
        .etn-event-tag-list a:hover,
        .etn-schedule-wrap .etn-schedule-info .etn-schedule-time{
            color: {$secondary_color};
        }
        .etn-event-tag-list a:hover{
            border-color: {$secondary_color};
        }
        .etn-btn:hover, .attr-btn-primary:hover,
        .etn-attendee-form .etn-btn:hover,
        .etn-ticket-widget .etn-btn:hover,
        .speaker-style4 .etn-speaker-content p,
        .etn-btn, button.etn-btn.etn-btn-primary:hover,
        .etn-zoom-btn,
		.events_calendar_list .calendar-event-details .event-calendar-action .etn-btn, .events_calendar_list .calendar-event-details .event-calendar-action .etn-price.event-calendar-details-btn,
        .etn-speaker-item.style-3 .etn-speaker-content .etn-speakers-social a:hover,
        .etn-single-speaker-item .etn-speaker-thumb .etn-speakers-social a:hover,
		.etn-recurring-event-wrapper #seeMore:hover, .etn-recurring-event-wrapper #seeMore:focus,
        .etn-settings-dashboard .button-primary:hover{
            background-color: {$secondary_color};
        }
		.events_calendar_list .calendar-event-details .event-calendar-action .etn-btn {
			max-width: 120px;
			display: block;
			text-align: center;
			margin-left: auto;
		}
		
		.etn-order-purchase-create-form .ant-select:not(.ant-select-disabled):hover .ant-select-selector,
		.etn-order-purchase-create-form .ant-select-focused:where(.css-dev-only-do-not-override-5gkvuh).ant-select-outlined:not(.ant-select-disabled):not(.ant-select-customize-input):not(.ant-pagination-size-changer) .ant-select-selector,
		.etn-order-purchase-create-form .ant-select-outlined:not(.ant-select-disabled):not(.ant-select-customize-input):not(.ant-pagination-size-changer):hover .ant-select-selector {
			border-color: {$primary_color};
		}
		.etn-checkbox-group .ant-checkbox-wrapper:not(.ant-checkbox-wrapper-disabled):hover .ant-checkbox-checked:not(.ant-checkbox-disabled) .ant-checkbox-inner {
			background-color: {$primary_color};
			border-color: {$primary_color};
		}
		.etn-checkbox-group .ant-checkbox-checked .ant-checkbox-inner,
		.etn-checkbox-group .ant-checkbox:not(.ant-checkbox-disabled):hover .ant-checkbox-inner,
		.etn-checkbox-group .ant-checkbox-wrapper:hover .ant-checkbox .ant-checkbox-inner,
		.etn-checkbox-group .ant-checkbox-wrapper:hover .ant-checkbox-checked .ant-checkbox-inner {
			border-color: {$primary_color};
			background-color: {$primary_color};
		}

		.etn-extra-fields-container .ant-radio-checked .ant-radio-inner {
			background-color: {$primary_color};
			border-color: {$primary_color};
		}

		.etn-extra-fields-container .ant-radio-wrapper:hover .ant-radio-inner {
			border-color: {$primary_color};
		}

		.etn-extra-field-text-area .ant-input-outlined:hover,
		.etn-extra-field-text-area .ant-input-outlined:focus-within {
			border-color: {$primary_color};
			background-color: #fff;
		}

		.background-color-override {
			background-color: #fff !important;
		}

		";

		// add inline css.
		wp_register_style( 'etn-custom-css', false );
		wp_enqueue_style( 'etn-custom-css' );
		wp_add_inline_style( 'etn-custom-css', $etn_custom_css );
	}

	/**
	 * Product URL
	 *
	 * @return string
	 */
	public function get_pro_link() {
		return 'https://themewinter.com/eventin/';
	}

	/**
	 * Register ticket markup style
	 * 
	 * Todo: This function needs to be removed after the files global register for both pro and free plugin
	 * 
	 * @return void
	 */
	public function register_ticket_markup_style() {
		wp_register_style( 'etn-ticket-markup', \Wpeventin::plugin_url( 'assets/css/ticket-markup.css' ), array(), \Wpeventin::version() );
		wp_register_style( 'etn-public-css', \Wpeventin::plugin_url( 'build/css/event-manager-public-styles.css' ), array(), \Wpeventin::version() );
	}
}