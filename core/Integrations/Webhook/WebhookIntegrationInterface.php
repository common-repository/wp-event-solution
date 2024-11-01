<?php
/**
 * Webhook integration interface
 * 
 * @package Eventin
 */
namespace Eventin\Integrations\Webhook;

/**
 * WebhookIntegrationInterface
 */
interface WebhookIntegrationInterface {
    /**
     * Webhook integration interface
     *
     * @return  void
     */
    public function run();
}