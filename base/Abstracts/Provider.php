<?php
namespace Eventin\Abstracts;

use Eventin\Eventin;
use Eventin\Interfaces\HookableInterface;
use Eventin\Interfaces\ProviderInterface;

/**
 * Handles installations of services
 *
 * @package Eventin\Abastracts
 */
abstract class Provider implements ProviderInterface {
    /**
     * Holds classes that should be instantiated
     *
     * @var array
     */
    protected $services = [];

    /**
     * Service provider
     *
     * @param   array  $services  All services that should be instantiated
     *
     * @return void
     */
    public function __construct( array $services = [] ) {
        if ( ! empty( $services ) ) {
            $this->services = $services;
        }

        $this->register();
    }

    /**
     * Registers services
     *
     * @return  void
     */
    public function register(): void {
        foreach ( $this->services as $service ) {
            if ( ! class_exists( $service ) ) {
                continue;
            }

            $service = Eventin::$container->get( $service );

            if ( $service instanceof HookableInterface ) {
                $service->register_hooks();
            }
        }
    }
}
