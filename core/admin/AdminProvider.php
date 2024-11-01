<?php
namespace Eventin\Admin;

use Eventin\Abstracts\Provider;
use Eventin\Admin\Role\RoleManager;
use Eventin\Integrations\Integration;
use Eventin\Integrations\Webhook\WebhookIntegration;

/**
 * Admin Provider class
 * 
 * @package Eventin/Admin
 */

class AdminProvider extends Provider {
    /**
     * Holds classes that should be instantiated
     *
     * @var array
     */
    protected $services = [
        Integration::class,
        Menu::class,
        EventReminder::class,
        TemplateRender::class,
        OrderAttendee::class,
        OrderTicket::class,
        WebhookIntegration::class,
        RoleManager::class,
    ];
}
