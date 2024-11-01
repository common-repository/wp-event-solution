<?php
namespace Eventin\Interfaces;

/**
 * Interface Hookable
 * 
 * Provides a hookable interface for classes.
 */
interface HookableInterface {

    /**
     * Register all hooks for the class
     *
     * @return  void
     */
    public function register_hooks(): void;
}
