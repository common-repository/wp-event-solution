<?php
namespace Eventin\Emails;

use Etn\Core\Event\Event_Model;
use Eventin\Mails\Content;
use Eventin\Mails\Mailable;

/**
 * Admin Rsvp Email
 * 
 * @package eventin
 */
class AdminRsvpEmail extends Mailable {
    /**
     * Email settings
     *
     * @var array
     */
    private $email_settings;

    /**
     * Store event object
     *
     * @var Event_Model
     */
    private $event;

    /**
     * Store attendees
     *
     * @var array
     */
    private $attendees;

    /**
     * Constructor for Admin Order Class
     *
     * @return  void
     */
    public function __construct( Event_Model $event, $attendees = [] ) {
        $this->email_settings = etn_get_email_settings( 'rsv_email' );
        $this->event          = $event;
        $this->attendees      = $attendees;
    }

    /**
     * Email subject
     *
     * @return  string
     */
    public function subject(): string {
        return $this->email_settings['subject'];
    }

    /**
     * Email content
     *
     * @return  string  email body
     */
    public function content(): string {
        $content = $this->prepare_content();

        return Content::get( 'admin-rsvp-email-template', [
            'content'       => $content,
            'event'         => $this->event,
            'attendees'     => $this->attendees,
        ] );
    }

    /**
     * Prepare email content that need to send
     *
     * @return  string Email content
     */
    private function prepare_content() {
        $event      = $this->event;

        $post       = get_post( $event->id );
        $location   = get_post_meta( $event->id, 'etn_event_location', true );
        $address    = ! empty( $location['address'] ) ? $location['address'] : '';

        $placeholder = [
            '{%site_name%}' 	 => get_bloginfo( 'name' ),
            '{%site_link%}' 	 => site_url(),
            '{%site_logo%}' 	 => get_bloginfo('logo'),
            '{%event_title%}'    => $post->post_title,
            '{%event_date%}' 	 => $event->etn_start_date,
            '{%event_time%}' 	 => $event->etn_start_time,
            '{%event_location%}' => $address,
        ];

        $order_email_message = $this->email_settings['body'];

        $order_email_message = strtr( $order_email_message, $placeholder );

        return $order_email_message;
    }
}
