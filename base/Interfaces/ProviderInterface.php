<?php
namespace Eventin\Interfaces;

/**
 * Provider interface
 *
 * @package Eventin\Interface
 */
interface ProviderInterface {
    /**
     * Registers the services provided by the provider
     *
     * @return  void
     */
    public function register(): void;
}
