<?php
namespace Eventin;

use Eventin\Admin\AdminProvider;
use Eventin\Interfaces\ProviderInterface;

/**
 * Class Bootstrap
 *
 * Handles the plugin's bootstrap process
 *
 * @package Eventin
 */
class Bootstrap {
    /**
     * Holds plugin's provider classes.
     *
     * @var array
     */
    protected static $providers = [
        AdminProvider::class,
    ];

    /**
     * Runs the plugins bootstrap
     *
     * @return  void
     */
    public static function run(): void {
        add_action( 'init', [ self::class, 'init' ] );
        add_action( 'rest_api_init', [ ApiManager::class, 'register' ] );
    }

    /**
     * Bootstraps the plugin. Load all necessary providers
     *
     * @return  void
     */
    public static function init(): void {
        self::register_providers();
        CustomEndpoint::register();
    }

    /**
     * Registers providers
     *
     * @return  void
     */
    protected static function register_providers(): void {
        foreach ( self::$providers as $provider ) {
            if ( class_exists( $provider ) && is_subclass_of( $provider, ProviderInterface::class ) ) {
                new $provider();
            }
        }
    }
}
