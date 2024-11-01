<?php
/**
 * Updater interface
 *
 * @package Eventin\Upgrades
 */
namespace Eventin\Upgrade\Upgraders;

interface UpdateInterface {
    /**
     * Run the updater
     *
     * @return  void
     */
    public function run();
}
