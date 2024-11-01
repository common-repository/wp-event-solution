<?php
namespace Eventin\Admin;

use Elementor\Modules\WpCli\Update;
use Etn\Core\Event\Event_Model;
use Eventin\Emails\AttendeeOrderEmail;
use Eventin\Interfaces\HookableInterface;
use Eventin\Mails\Mail;
use Wpeventin;

class OrderTicket implements HookableInterface {
    /**
     * Register service
     *
     * @return  void
     */
    public function register_hooks(): void {
        add_action( 'eventin_order_completed', [$this, 'update_event_ticket'] );

        add_action( 'eventin_attendee_created', [ $this, 'send_attendee_ticket' ] );

        add_action( 'eventin_attendee_created', [ $this, 'decrease_ticket_after_attendee_create' ] );
    }

    /**
     * After booking an event ticket decrese ticket amount
     *
     * @return  void
     */
    public function update_event_ticket( $order ) {
        if ( 'completed' !== $order->status ) {
            return;
        }

        $event = new Event_Model( $order->event_id );

        $event_tickets = $event->etn_ticket_variations;

        $updated_tickets = [];

        if ( $event_tickets ) {
            foreach( $event_tickets as $ticket ) {
                $updated_ticket = $this->prepare_event_ticket( $order, $ticket );

                $updated_tickets[] = $updated_ticket;
            }
        }
        
        $event->update([
            'etn_ticket_variations' => $updated_tickets
        ]);

        $this->update_booked_seat($event, $order);
    }

    /**
     * Prepare updated event ticket
     *
     * @param   OrderModel  $order  [$order description]
     * @param   string  $slug   [$slug description]
     *
     * @return  array          [return description]
     */
    private function prepare_event_ticket( $order, $event_ticket ) {
        $order_tickets = $order->tickets;

        foreach( $order_tickets as $ticket ) {
            if ( $ticket['ticket_slug'] === $event_ticket['etn_ticket_slug'] ) {
                $event_ticket['etn_sold_tickets'] = $event_ticket['etn_sold_tickets'] + $ticket['ticket_quantity'];
                break;
            }
        }

        return $event_ticket;
    }

    /**
     * Update booked event booked seats
     *
     * @param   Event_Model  $event  [$event description]
     * @param   Order_Model  $order  [$order description]
     *
     * @return  void
     */
    private function update_booked_seat( $event, $order ) {
        $event_seats = get_post_meta( $event->id, '_etn_seat_unique_id', true );

        $order_seats = $order->seat_ids;

        if ( ! $order_seats ) {
            return;
        }

        $event_seats = explode(',', $event_seats );

        $event_seats = array_merge( $event_seats, $order_seats );
        $event_seats = implode( ',', array_unique( $event_seats ) );

        update_post_meta( $event->id, '_etn_seat_unique_id', $event_seats );
    }

    /**
     * Send attendee ticket after creating a attendee
     *
     * @param   Attendee_Model  $attendee  [$attendee description]
     *
     * @return  void             [return description]
     */
    public function send_attendee_ticket( $attendee ) {
        if ( $attendee->etn_email ) {
            $from  = etn_get_email_settings( 'purchase_email' )['from'];
            $event = new Event_Model( $attendee->etn_event_id );
            Mail::to($attendee->etn_email)->from( $from )->send(new AttendeeOrderEmail($event, $attendee));
        }
    }

    /**
     * Update event ticket quantity after attendee create
     *
     * @return  void
     */
    public function decrease_ticket_after_attendee_create( $attendee ) {
        $event = new Event_Model( $attendee->etn_event_id );

        $event_tickets = $event->etn_ticket_variations;

        if ( $event_tickets ) {
            foreach( $event_tickets as &$ticket ) {
                if ( $ticket['etn_ticket_name'] === $attendee->ticket_name ) {
                    $ticket['etn_sold_tickets'] = $ticket['etn_sold_tickets'] + 1;
                }
            }
        }

        $event->update([
            'etn_ticket_variations' => $event_tickets,
            'etn_total_sold_tickets' => (int) $event->etn_total_sold_tickets + 1
        ]);
    }
}
