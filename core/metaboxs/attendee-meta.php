<?php

namespace Etn\Core\Metaboxs;
use \Etn\Utils\Helper;

defined( 'ABSPATH' ) || exit;

class Attendee_Meta extends Event_manager_metabox {

    public $metabox_id              = 'etn_attendee_meta';
    public $default_attendee_fields = [];
    public $cpt_id                  = 'etn-attendee';

    public function register_meta_boxes() {

        add_meta_box(
            $this->metabox_id,
            esc_html__( 'Attendee Details', 'eventin' ),
            [$this, 'display_callback'],
            $this->cpt_id
        );

    }

    public function etn_attendee_meta_fields() {

        $default_attendee_fields = array();
        $settings          = Helper::get_settings();
        $include_phone     = !empty( $settings["reg_require_phone"] ) ? true : false;
        // list of WooCommerce orders
        $order_id = get_post_meta( get_the_ID() , 'etn_attendee_order_id',true);

		$default_attendee_fields['etn_name']   = [
			'label' => esc_html__( 'Attendee Name', 'eventin' ),
			'type'  => 'text',
			'value' => '',
			'desc'  => esc_html__( 'Enter Attendee Name', 'eventin' ),
			'attr'  => ['class' => 'etn-label-item'],
							'group'    => 'etn-label-group',
		];

		$default_attendee_fields['etn_email']   = [
			'label' => esc_html__( 'Email', 'eventin' ),
			'type'  => 'email',
			'value' => '',
			'desc'  => esc_html__( 'Enter Attendee Email Address', 'eventin' ),
			'attr'  => ['class' => 'etn-label-item'],
							'group'    => 'etn-label-group',
		];
        
        if( $include_phone ) {
            $default_attendee_fields['etn_phone']   = [
                'label' => esc_html__( 'Phone', 'eventin' ),
                'type'  => 'text',
                'value' => '',
                'desc'  => esc_html__( 'Enter Attendee Phone Number', 'eventin' ),
                'attr'  => ['class' => 'etn-label-item'],
                'group' => 'etn-label-group',
                'tooltip_title' => '',
                'tooltip_desc' =>  ''
            ];
        }

        $default_attendee_fields['etn_attendeee_ticket_status'] = [
            'label'    => esc_html__( 'Ticket', 'eventin' ),
            'desc'     => esc_html__( 'Attendee ticket status', 'eventin' ),
            'type'     => 'select_single',
            'options'  => [
                'unused' => esc_html__( 'Unused', 'eventin' ),
                'used' => esc_html__( 'Used', 'eventin' ),
            ],
            'priority' => 1,
            'attr'     => ['class' => 'etn-label-item'],
						'group'    => 'etn-label-group',
        ];

        $default_attendee_fields['etn_status'] = [
            'label'    => esc_html__( 'Payment', 'eventin' ),
            'desc'     => esc_html__( 'Attendee payment status', 'eventin' ),
            'type'     => 'select_single',
            'options'  => [
                'success' => esc_html__( 'Success', 'eventin' ),
                'failed' => esc_html__( 'Failed', 'eventin' ),
            ],
            'priority' => 1,
            'attr'     => ['class' => 'etn-label-item'],
            'group'    => 'etn-label-group',
        ];
        if( !empty($_GET['post']) && !empty( $order_id ) ) {

            $post = get_post( $order_id );

            if ( 'etn-stripe-order' === $post->post_type ) {
                $button_url = admin_url( 'admin.php?page=etn_stripe_orders_report&order_id=' . $order_id ); 
            }else{
				$order = wc_get_order($order_id);
				$button_url = $order->get_edit_order_url();
			}

			$default_attendee_fields['etn_order_details'] = [
					'label'       => esc_html__( 'Order Details', 'eventin' ),
					'text'        => esc_html__( 'View', 'eventin' ),
					'url'         => $button_url,
					'desc'        => esc_html__( 'Order details here', 'eventin' ),
					'type'        => 'button',
					'attr'        => ['class' => 'etn-label-item'],
					'group'    => 'etn-label-group',
			];

        }

		else if(!empty($_GET['post'])){
            $default_attendee_fields['etn_attendee_order_id'] = [
                'label'    => esc_html__( 'Order ID', 'eventin' ),
                'desc'     => esc_html__( 'WooCommerce Order ID', 'eventin' ),
                'type'     => 'select_single',
                'options'  => $this->get_order_items(),
                'priority' => 1,
                'attr'     => ['class' => 'etn-label-item'],
								'group'    => 'etn-label-group',
            ];
        }
		$get_attendee_seat = get_post_meta( get_the_ID() , 'attendee_seat' , true );
		if(!empty($_GET['post']) && "" !== $get_attendee_seat ){
            $default_attendee_fields['attendee_seat'] = [
				'label'         => esc_html__( 'Seat ID', 'eventin' ),
				'desc'          => esc_html__( 'Seat ID for the event', 'eventin' ),
				'type'          => 'text',
				'value'         => "",
				'priority'      => 1,
				'readonly'      => true,
				'disabled'      => true,
				'placeholder'   => esc_html__( 'Seat ID', 'eventin' ),
				'attr'          => [ 'class' => 'etn-label-item' ],
				'group'         => 'etn-label-group',
            ];
        }

        $this->default_attendee_fields = apply_filters( 'etn_attendee_fields', $default_attendee_fields );

        return $this->default_attendee_fields;
    }

    /**
     * get the order items for metabox select option
     * @since 1.1.0
     * @return void
     */
    public function get_order_items() {
		if ( !class_exists('WooCommerce') ) {
			return;
		}
		
        $orders = wc_get_orders(array(
            'limit' => -1
        ));

        $order_items = array(
            "" => ""
        );

        foreach( $orders as $order){
            if ( is_a( $order, 'WC_Order_Refund' ) ) {
                $order = wc_get_order( $order->get_parent_id() );
            }
            $order_items[ $order->get_id() ] = $order->get_id(). " - ".$order->get_billing_email();
        }

        return $order_items;
    }

}
