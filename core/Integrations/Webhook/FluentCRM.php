<?php
/**
 * FluentCRM integration
 * 
 * @package Eventin
 */
namespace Eventin\Integrations\Webhook;

/**
 * FluentCRM Webhook integration
 */
class FluentCRM implements WebhookIntegrationInterface {
    /**
     * Run action
     *
     * @return  void
     */
    public function run() {
        add_action( 'eventin_after_order_create', [$this, 'send_data'] );
    }

    /**
     * Send data to fluentcrm webhook
     *
     * @param   OrderModel  $order  [$order description]
     *
     * @return  void
     */
    public function send_data( $order) { 
        $event_id           = $order->event_id; 
        $fluentCRM_enable   = get_post_meta( $event_id, 'fluent_crm', true );
        $fluentcrm_webhook  = get_post_meta( $event_id, 'fluent_crm_webhook', true ); 

        $body = array(
            'email'      => $order->customer_email,
            'first_name' => $order->customer_fname,
        );  
 
        if ( $fluentCRM_enable ==='yes' && !empty( $fluentcrm_webhook ) ) { 
            $response_user = wp_remote_post($fluentcrm_webhook, ['body' => $body]);
        }
    }
}
