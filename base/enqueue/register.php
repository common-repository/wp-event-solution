<?php
namespace Etn\Base\Enqueue;

/**
 * Scripts and Styles class
 */
class Register {
    /**
     * Initialize
     *
     * @return  void
     */
    public function __construct() {
        if ( is_admin() ) {
            add_action( 'admin_enqueue_scripts', [$this, 'register'], 5 );
        } else {
            add_action( 'wp_enqueue_scripts', [$this, 'register'], 5 );
        }
    }

    /**
     * Register app scripts and styles
     *
     * @return  void
     */
    public function register() {
        $this->register_global_scripts();
        $this->register_scripts( $this->get_scripts() );
        $this->register_styles( $this->get_styles() );
    }

    /**
     * Register scripts
     *
     * @param  array $scripts
     *
     * @return void
     */
    private function register_scripts( $scripts ) {
        foreach ( $scripts as $handle => $script ) {
            $deps      = isset( $script['deps'] ) ? $script['deps'] : [];
            $in_footer = isset( $script['in_footer'] ) ? $script['in_footer'] : true;
            $version   = isset( $script['version'] ) ? $script['version'] : $this->get_version( $script['src'] );

            $deps = $this->get_dependencies( $script['src'], $deps );

            if ( in_array( 'wp-i18n', $deps ) ) {
                $deps[] = 'eventin-i18n';
            }

            wp_register_script( $handle, $script['src'], $deps, $version, $in_footer );

            // Set localize data.
            $this->set_localize( $handle );
        }
    }

    /**
     * Register global scripts
     *
     * @return  void
     */
    private function register_global_scripts() {
        $scripts = [
            'eventin-i18n' => [
                'src' => \Wpeventin::plugin_url( 'build/js/i18n-loader.js' ),
            ],
        ];

        foreach ( $scripts as $handle => $script ) {
            $deps      = isset( $script['deps'] ) ? $script['deps'] : [];
            $in_footer = isset( $script['in_footer'] ) ? $script['in_footer'] : true;
            $version   = isset( $script['version'] ) ? $script['version'] : $this->get_version( $script['src'] );

            $deps = $this->get_dependencies( $script['src'], $deps );

            wp_register_script( $handle, $script['src'], $deps, $version, $in_footer );
        }

    }

    /**
     * Set localize data
     *
     * @param   string  $handle Script handler name that will be registered
     *
     * @return  void
     */
    public function set_localize( $handle ) {
        $localize_data = etn_get_locale_data();
        wp_localize_script( $handle, 'localized_data_obj', $localize_data );
    }

    /**
     * Register styles
     *
     * @param  array $styles
     *
     * @return void
     */
    public function register_styles( $styles ) {
        foreach ( $styles as $handle => $style ) {
            $deps = isset( $style['deps'] ) ? $style['deps'] : false;

            wp_register_style( $handle, $style['src'], $deps, \Wpeventin::version() );
        }
    }

    /**
     * Get all registered scripts
     *
     * @return array
     */
    public function get_scripts() {
        $prefix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '.min' : '';

        $scripts = array_merge( AdminAssets::get_scripts(), FrontendAssets::get_scripts() );

        return apply_filters( 'etn_register_scripts', $scripts );
    }
    /**
     * Get registered styles
     *
     * @return array
     */
    public function get_styles() {

        $styles = array_merge( AdminAssets::get_styles(), FrontendAssets::get_styles() );

        return apply_filters( 'etn_register_styles', $styles );
    }

    /**
     * Get script and style file dependencies
     *
     * @param   string  $file_name
     * @param   array  $deps
     *
     * @return  array
     */
    private function get_dependencies( $file_name, $deps = [] ) {
        $assets = $this->get_file_assets( $file_name );

        $assets_deps = ! empty( $assets['dependencies'] ) ? $assets['dependencies'] : [];

        $merged_deps = array_merge( $assets_deps, $deps );
        return $merged_deps;
    }

    /**
     * Get script file version
     *
     * @param   string  $file_name
     *
     * @return  string
     */
    private function get_version( $file_name ) {
        $assets      = $this->get_file_assets( $file_name );
        $assets_vers = ! empty( $assets['version'] ) ? $assets['version'] : \Wpeventin::version();
        return $assets_vers;
    }

    /**
     * Get file assets
     *
     * @param   string  $file_name
     *
     * @return  array
     */
    private function get_file_assets( $file_url ) {
        $file   = $this->get_file_path( $file_url );
        $assets = [];

        if ( file_exists( $file ) ) {
            $assets = include $file;
        }

        return $assets;
    }

    /**
     * Get file path from url
     *
     * @param   string  $url
     *
     * @return string
     */
    private function get_file_path( $url ) {
        // Check if the URL is valid
        if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
            return false;
        }

        // Parse the URL
        $url_parts = parse_url( $url );

        // Check if the URL has a path component
        if ( ! isset( $url_parts['path'] ) ) {
            return false; // URL does not contain a path
        }

        $clean_path = str_replace( '.js', '.asset.php', $url_parts['path'] );

        // Get the file path from the URL path
        $file_path = $_SERVER['DOCUMENT_ROOT'] . $clean_path;

        // Check if the file exists
        if ( ! file_exists( $file_path ) ) {
            return false; // File does not exist
        }

        return $file_path;
    }
}