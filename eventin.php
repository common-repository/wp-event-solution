<?php

use Eventin\Upgrade\Upgrade;
use Eventin\Upgrade\Upgraders\V_3_3_57;

defined( 'ABSPATH' ) || exit;

/**
 * Plugin Name:       Eventin
 * Plugin URI:        https://themewinter.com/eventin/
 * Description:       Simple and Easy to use Event Management Solution
 * Version:           4.0.14
 * Author:            Themewinter
 * Author URI:        https://themewinter.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       eventin
 * Domain Path:       /languages
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once plugin_dir_path( __FILE__ ) . '/utils/functions.php';

class Wpeventin {

	/**
	 * Instance of self
	 *
	 * @since 2.4.3
	 *
	 * @var Wpeventin
	 */
	public static $instance = null;

	/**
	 * Plugin Version
	 *
	 * @since 2.4.3
	 *
	 * @var string The plugin version.
	 */
	public static function version() {
		return '4.0.14';
	}

	/**
	 * Initializes the Wpeventin() class
	 *
	 * Checks for an existing Wpeventin() instance
	 * and if it doesn't find one, creates it.
	 */
	public static function init() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Instance of Wpeventin
	 */
	private function __construct() {

		$this->define_constants();

		$this->activate();
		$this->deactivate();

		add_action( 'plugins_loaded', array( $this, 'initialize_modules' ), 999 );

	}

	/**
	 * Define Plugin COnstants
	 *
	 * @return void
	 */
	public function define_constants() {
		// handle demo site features.
		define( 'ETN_ASSETS ', self::assets_dir() );
		define( 'ETN_PLUGIN_TEMPLATE_DIR', self::templates_dir() );
		define( 'ETN_THEME_TEMPLATE_DIR', self::theme_templates_dir() );
		define( 'ETN_DEMO_SITE', false );
		if ( ETN_DEMO_SITE === true ) {
			define( 'ETN_EVENT_TEMPLATE_ONE_ID', '41' );
			define( 'ETN_EVENT_TEMPLATE_TWO_ID', '13' );
			define( 'ETN_EVENT_TEMPLATE_THREE_ID', '39' );

			define( 'ETN_SPEAKER_TEMPLATE_ONE_ID', '8' );
			define( 'ETN_SPEAKER_TEMPLATE_TWO_LITE_ID', '7' );
			define( 'ETN_SPEAKER_TEMPLATE_TWO_ID', '9' );
			define( 'ETN_SPEAKER_TEMPLATE_THREE_ID', '6' );
		}

		define( 'ETN_DEFAULT_TICKET_NAME', 'DEFAULT' );

		global $wpdb;
		define( 'ETN_EVENT_PURCHASE_HISTORY_TABLE', $wpdb->prefix . 'etn_events' );
		define( 'ETN_EVENT_PURCHASE_HISTORY_META_TABLE', $wpdb->prefix . 'etn_trans_meta' );
	}

	/**
	 * Load Textdomain
	 *
	 * Load plugin localization files.
	 * Fired by `init` action hook.
	 *
	 * @since 2.4.3
	 *
	 * @access public
	 */
	public function i18n() {
		load_plugin_textdomain( 'eventin', false, self::plugin_dir() . 'languages/' );
	}

	/**
	 * Initialize Modules
	 *
	 * @since 2.4.3
	 */
	public function initialize_modules() {
		do_action( 'eventin/before_load' );

		require_once plugin_dir_path( __FILE__ ) . 'autoloader.php';
		require_once plugin_dir_path( __FILE__ ) . 'bootstrap.php';

		// block for showing banner.
		require_once plugin_dir_path( __FILE__ ) . '/utils/notice/notice.php';
		require_once plugin_dir_path( __FILE__ ) . '/utils/banner/banner.php';
		require_once plugin_dir_path( __FILE__ ) . '/utils/pro-awareness/pro-awareness.php';

		// Localization.
		$this->i18n();

		// init notice class.
		\Oxaim\Libs\Notice::init();

		// init pro menu class.
		\Wpmet\Libs\Pro_Awareness::init();

		// action plugin instance class.
		Etn\Bootstrap::instance()->init();

		do_action( 'eventin/after_load' );
	}


	/**
	 * Theme's Templates Folder Directory Path
	 *
	 * @since 2.4.3
	 *
	 * @return string
	 */
	public static function theme_templates_dir() {
		return trailingslashit( '/eventin/templates' );
	}

	/**
	 * Templates Folder Directory Path
	 *
	 * @since 2.4.3
	 *
	 * @return string
	 */
	public static function templates_dir() {
		return trailingslashit( self::plugin_dir() . 'templates' );
	}

	/**
	 * Utils Folder Directory Path
	 *
	 * @since 2.4.3
	 *
	 * @return string
	 */
	public static function utils_dir() {
		return trailingslashit( self::plugin_dir() . 'utils' );
	}

	/**
	 * Widgets Directory Url
	 *
	 * @return string
	 */
	public static function widgets_url() {
		return trailingslashit( self::plugin_url() . 'widgets' );
	}

	/**
	 * Widgets Folder Directory Path
	 *
	 * @since 2.4.3
	 *
	 * @return string
	 */
	public static function widgets_dir() {
		return trailingslashit( self::plugin_dir() . 'widgets' );
	}

	/**
	 * Assets Directory Url
	 *
	 * @return string
	 */
	public static function assets_url() {
		return trailingslashit( self::plugin_url() . 'assets' );
	}

	/**
	 * Assets Folder Directory Path
	 *
	 * @since 2.4.3
	 *
	 * @return string
	 */
	public static function assets_dir() {
		return trailingslashit( self::plugin_dir() . 'assets' );
	}

	/**
	 * Plugin Core File Directory Url
	 *
	 * @since 2.4.3
	 *
	 * @return string
	 */
	public static function core_url() {
		return trailingslashit( self::plugin_url() . 'core' );
	}

	/**
	 * Plugin Core File Directory Path
	 *
	 * @since 2.4.3
	 *
	 * @return string
	 */
	public static function core_dir() {
		return trailingslashit( self::plugin_dir() . 'core' );
	}

	/**
	 * Plugin Url
	 *
	 * @since 2.4.3
	 *
	 * @return string
	 */
	public static function plugin_url( $path = '' ) {
		return trailingslashit( plugin_dir_url( self::plugin_file() ) ) . $path;
	}

	/**
	 * Plugin Directory Path
	 *
	 * @since 2.4.3
	 *
	 * @return string
	 */
	public static function plugin_dir() {
		return trailingslashit( plugin_dir_path( self::plugin_file() ) );
	}

	/**
	 * Plugins Basename
	 *
	 * @since 2.4.3
	 *
	 * @return string
	 */
	public static function plugins_basename() {
		return plugin_basename( self::plugin_file() );
	}

	/**
	 * Plugin File
	 *
	 * @since 2.4.3
	 *
	 * @return string
	 */
	public static function plugin_file() {
		return __FILE__;
	}

	/**
     * Initialize on plugin activation
     *
     * @return  void
     */
    public function activate() {
		register_activation_hook( $this->plugin_file(), [ $this, 'activate_actions' ] );
    }

	/**
	 * Run on deactivation hook
	 *
	 * @return  void
	 */
	public function deactivate() {
		register_deactivation_hook( $this->plugin_file(), [ $this, 'deactivate_actions' ] );
	}

	/**
	 * Run on deactivation hooks
	 *
	 * @return  void
	 */
	public function deactivate_actions() {
		$current_version 	= self::version();

		if ( '4.0.0' == $current_version ) {
			$v3_3_57 = new V_3_3_57();

			$v3_3_57->run();
		}
	}

    /**
     * Create roles and capabilities.
     */
    public function prepare_roles_capabilities() {
        global $wp_roles;

        if ( ! class_exists( 'WP_Roles' ) ) {
            return;
        }

        if ( ! isset( $wp_roles ) ) {
            $wp_roles = new \WP_Roles();
        }

        $capabilities = $this->get_core_capabilities();

        foreach ( $capabilities as $cap_group ) {
            foreach ( $cap_group as $cap ) {
                $wp_roles->use_db = true;
                $wp_roles->add_cap( 'administrator', $cap );
                $wp_roles->add_cap( 'editor', $cap );
                $wp_roles->add_cap( 'author', $cap );
            }
        }

		flush_rewrite_rules();
    }

	public function activate_actions() {
		$this->prepare_roles_capabilities();
		Upgrade::register();

		// Update plugin version and existing user roles.
		$version			= get_option( 'etn_version', true );
		$current_version 	= self::version();

		if ( $current_version !== $version ) {
			$this->prepare_roles_capabilities();
			update_option( 'etn_version', $current_version );
		}

		delete_transient( 'etn_event_list' );

		flush_rewrite_rules();
	}

    /**
     * Get capabilities for WooCommerce - these are assigned to admin/shop manager during installation or reset.
     *
     * @return array
     */
    public function get_core_capabilities() {
        $capabilities = array();
        $capabilities['eventin'] = array(
            'manage_etn_event',
            'manage_etn_speaker',
            'manage_etn_schedule',
            'manage_etn_attendee',
            'manage_etn_zoom',
            'manage_etn_settings',
        );

        return $capabilities;
    }
}

/**
 * Load Wpeventin plugin when all plugins are loaded
 *
 * @return Wpeventin
 */
function wpeventin() {
	return Wpeventin::init();
}

// Let's Go...
wpeventin();