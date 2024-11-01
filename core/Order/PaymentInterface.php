<?php
/**
 * Payment method interface
 */
namespace Eventin\Order;

interface PaymentInterface {
    /**
     * Create payment for certain payment method
     *
     * @return  
     */
    public function create_payment( OrderModel $order );
}
