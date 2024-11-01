<?php
/**
 * Frontend Assets Class
 * 
 * @package Eventin
 */
namespace Etn\Base\Enqueue;

/**
 * Admin Scripts and Styles class
 */
class FrontendAssets implements AssetsInterface {

    /**
     * Register scripts
     *
     * @return  array
     */
    public static function get_scripts() {
        $scripts = [
            'etn-pdf-gen' => [
                'src'       => \Wpeventin::plugin_url( 'assets/lib/js/jspdf.min.js'),
                'deps'      => ['jquery'],
                'in_footer' => false,
            ],
            'etn-html-2-canvas' => [
                'src'       => \Wpeventin::plugin_url( 'assets/lib/js/html2canvas.min.js' ),
                'deps'      => ['jquery'],
                'in_footer' => false,
            ],
            'etn-dom-purify-pdf' => [
                'src'       => \Wpeventin::plugin_url( 'assets/lib/js/purify.min.js' ),
                'deps'      => ['jquery'],
                'in_footer' => false,
            ],
            'html-to-image' => [
                'src'       => \Wpeventin::plugin_url( 'assets/lib/js/html-to-image.js' ),
                'deps'      => ['jquery'],
                'in_footer' => false,
            ],
            'etn-public' => [
                'src'       => \Wpeventin::plugin_url( 'build/js/event-manager-public.js' ),
                'deps'      => ['jquery']
            ],
            'etn-app-index'     => [
                'src'       => \Wpeventin::plugin_url( 'build/js/index-calendar.js' ),
                'deps'      => [ 'jquery' ],
                'in_footer' => true,
            ],
            'eventin-block-js' => [
                'src'       => \Wpeventin::plugin_url( 'build/js/index-gutten-block.js' ),
                'deps'      => [ 'jquery' ],
                'in_footer' => false,
            ],
             'etn-module-purchase' => [
                'src'       => \Wpeventin::plugin_url( 'build/js/module-purchase.js' ),
                'deps'      => ['etn-packages','underscore','wp-i18n'],
                'in_footer' => true,
            ],
        ];

        return apply_filters( 'etn_frontend_register_scripts', $scripts );
    }

    /**
     * Get styles
     *
     * @return  array
     */
    public static function get_styles() {
        $styles = [
            'etn-rtl'     => [
                'src' => \Wpeventin::plugin_url( 'assets/css/rtl.css' ),
            ],
            'etn-icon'    => [
                'src' => \Wpeventin::plugin_url( 'assets/css/etn-icon.css' ),
            ],
            'etn-app-index'     => [
                'src' => \Wpeventin::plugin_url( 'build/css/index-calendar.css' ),
            ],
            'etn-public-css'    => [
                'src' => \Wpeventin::plugin_url( 'build/css/event-manager-public-styles.css' ),
            ],
            'etn-ticket-markup' => [
                'src' => \Wpeventin::plugin_url( 'assets/css/ticket-markup.css' ),
            ],
        ];

        return apply_filters( 'etn_frontend_register_styles', $styles );
    }
}