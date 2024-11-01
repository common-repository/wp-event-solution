<?php
namespace Etn\Base\Enqueue;

use Wpeventin;

/**
 * Admin class
 */
class Admin {
    /**
     * Initialize the class
     *
     * @return  void
     */
    public function __construct() {
        add_action( 'admin_enqueue_scripts', [$this, 'enqueue_scripts'] );
    }

    public function i18n_loader() {
        $data = [
            'baseUrl'     => false,
            'locale'      => determine_locale(),
            'domainMap'   => [],
            'domainPaths' => [],
        ];
        
        $lang_dir    = WP_LANG_DIR;
        $content_dir = WP_CONTENT_DIR;
        $abspath     = ABSPATH;
        
        if ( strpos( $lang_dir, $content_dir ) === 0 ) {
            $data['baseUrl'] = content_url( substr( trailingslashit( $lang_dir ), strlen( trailingslashit( $content_dir ) ) ) );
        } elseif ( strpos( $lang_dir, $abspath ) === 0 ) {
            $data['baseUrl'] = site_url( substr( trailingslashit( $lang_dir ), strlen( untrailingslashit( $abspath ) ) ) );
        }
        
        wp_enqueue_script('eventin-i18n');
        
        $data['domainMap']   = (object) $data['domainMap']; // Ensure it becomes a json object.
        $data['domainPaths'] = (object) $data['domainPaths']; // Ensure it becomes a json object.
        wp_add_inline_script( 'eventin-i18n', 'if (typeof wp.eventinI18nLoader === "undefined") { wp.eventinI18nLoader = {}; } wp.eventinI18nLoader.state = ' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES ) . ';' );
    }

    /**
     * Enqueue scripts and styles
     *
     * @return  void
     */
    public function enqueue_scripts( $top ) {
        $screens = [
            'toplevel_page_eventin',
            'eventin_page_etn-event-shortcode',
            'eventin_page_etn_addons',
            'eventin_page_etn-license',
            'eventin_page_eventin_get_help'
        ];

        if ( ! in_array( $top, $screens ) ) {
            return;
        }

        $this->i18n_loader();

        $screen    = get_current_screen();
		$screen_id = $screen->id;
		
		if ( 'toplevel_page_eventin' === $screen_id ) {
            wp_enqueue_style( 'etn-ai' );
            wp_enqueue_script( 'etn-ai' );
		}

        
        wp_enqueue_script( 'wp-color-picker' );
        wp_enqueue_script( 'media-upload' );
        wp_enqueue_script( 'thickbox' );
        wp_enqueue_script( 'jquery-ui-datepicker' );
        wp_enqueue_script( 'flatpickr' );
        wp_enqueue_script( 'jquery-repeater' );
        wp_enqueue_script( 'select2' );
        wp_enqueue_script( 'etn' );
        wp_enqueue_script( 'jquery-ui' );
        wp_set_script_translations( 'etn-app-index', 'eventin' );
        wp_enqueue_script( 'etn-app-index' );
       
        if ( ! did_action( 'wp_enqueue_media' ) ) {
            wp_enqueue_media();
        }

        if ( ! wp_style_is( 'wp-color-picker', 'enqueued' ) ) {
            wp_enqueue_style( 'wp-color-picker' );
        }
        wp_enqueue_style( 'thickbox' );
        wp_enqueue_style( 'select2' );
        wp_enqueue_style( 'etn-icon' );
        wp_enqueue_style( 'etn-ui' );
        wp_enqueue_style( 'jquery-ui' );
        wp_enqueue_style( 'flatpickr-min' );
        wp_enqueue_style( 'event-manager-admin' );
        wp_enqueue_style( 'etn-common' );
        wp_enqueue_style( 'etn-public-css' ); // Just for the grid system this file is loaded in the admin which could be removed by using some flex or grid css: https://prnt.sc/XxFHXh7Q8Gsx https://prnt.sc/KtqFLFMvYAWt
        wp_enqueue_style( 'etn-app-index' ); 

    }
}
