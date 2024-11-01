<?php

namespace Eventin;

use Eventin\Container\Container;

/**
 * Eeventin Main class
 */
class Eventin {
    /**
     * Plugin version.
     *
     * @var string
     */
    public static $version;

    /**
     * Plugin file.
     *
     * @var string
     */
    public static $plugin_file;

    /**
     * Plugin directory.
     *
     * @var string
     */
    public static $plugin_directory;

    /**
     * @var string
     */
    public static $build_url;

    /**
     * Plugin base name.
     *
     * @var string
     */
    public static $basename;

    /**
     * Plugin text directory path.
     *
     * @var string
     */
    public static $text_domain_directory;

    /**
     * Plugin text directory path.
     *
     * @var string
     */
    public static $template_directory;

    /**
     * Plugin assets directory path.
     *
     * @var string
     */
    public static $assets_url;

    /**
     * Plugin url.
     *
     * @var string
     */
    public static $plugin_url;

    /**
     * Container that holds all the services.
     *
     * @var Container
     */
    public static $container;

    /**
     * Eventin Constructor.
     *
     * @return  void
     */
    public function __construct() {
        $this->register_lifecycle();

        Bootstrap::run();
    }

    /**
     * Register life cycle hooks
     *
     * @return  void
     */
    public function register_lifecycle(): void {
        $this->register_container();
        // register_activation_hook( self::$plugin_file, [Activate::class, 'handle'] );
        // register_deactivation_hook( self::$plugin_file, [Deactivate::class, 'handle'] );
    }

    /**
     * Initializes the container
     *
     * @return  void
     */
    protected function register_container(): void {
        self::$container = new Container();
    }

    /**
     * Initializes the Eventin class.
     *
     * Checks for an existing Eventin instance
     * and if it doesn't find one, creates it.
     *
     * @return Eventin
     */
    public static function instance(): Eventin {
        static $instance = false;

        if ( ! $instance ) {
            $instance = new self();
        }

        return $instance;
    }
}
