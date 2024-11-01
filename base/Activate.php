<?php
namespace Eventin;

/**
 * Activation class
 * 
 * @package Eventin
 */
class Activate {
    /**
     * Activation hook
     *
     * @return  void
     */
    public static function handle(): void {
        Installer::run();
    }
}
