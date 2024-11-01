<?php
/**
 * Payment Factory Class
 * 
 * @package Eventin
 */
namespace Eventin\Order;

use Eventin\Integrations\WC\WCPayment;
use Exception;
/**
 * PaymentFactory
 */
class PaymentFactory {

    /**
     * Get selected payment method
     *
     * @param   string  $payment_methods  [$payment_methods description]
     *
     * @return PaymentMethodInterface
     */
    public static function get_method( $payment_methods ) {
        $methods = self::get_payment_methods();

        if ( ! empty( $methods[$payment_methods] ) ) {
            return new $methods[$payment_methods];
        }

        throw new Exception( __( 'Unknown payment method.', 'eventin' ) );
    }

    /**
     * Get all available payment methods
     *
     * @return  array  All available payment methods
     */
    private static function get_payment_methods() {
        $methods = [
            'wc' => WCPayment::class,
        ];

        return apply_filters( 'eventin_payment_methods', $methods );
    }
}
