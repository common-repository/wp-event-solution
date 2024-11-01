<?php
namespace Eventin\Integrations\WC;

use Eventin\Order\OrderModel;
use Eventin\Order\PaymentInterface;

/**
 * Pay using Woocommerce payment
 * 
 * @package Eventin
 */
class WCPayment implements PaymentInterface {
    /**
     * Create payment for woocommerce payment methods
     *
     * @return  void
     */
    public function create_payment( $order ) {
        WC()->cart->empty_cart();

        if ( WC()->session->get( 'event_order_id' ) ) {
            WC()->session->__unset( 'event_order_id' );
        }
        
        WC()->session->set( 'event_order_id', $order->id );

        $cart_id  = WC()->cart->add_to_cart( $order->event_id );

        return [
            'id' => $cart_id,
        ];
    }
}
