<?php
/**
 * Uniqueue generator
 * 
 * @package Eventin
 */
namespace Eventin\Attendee\Attendee;

/**
 * Uniqueue ticket generator
 */
class TicketIdGenerator {

    /**
     * Generate uniqueue ticket id
     *
     * @param   integer  $length  [$length description]
     *
     * @return  string
     */
    public static function generate_ticket_id( $length = 10 ) {
        do {
            $ticket = self::make_ticket_id( $length );
        } while ( ! self::is_ticket_unique( $ticket ) );
    
        return $ticket;
    }

    /**
     * Generate uni ticket id
     *
     * @param   integer  $length
     *
     * @return  string  
     */
    private static function make_ticket_id( $length = 10 ) {
        $characters = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $characters_length = strlen( $characters );
        $random_string = '';
        
        // Generate a random string of the desired length
        for ( $i = 0; $i < $length; $i++ ) {
            $random_string .= $characters[ rand( 0, $characters_length - 1 ) ];
        }

        return $random_string;
    }

    /**
     * Check is unique
     *
     * @param   string  $ticket  [$ticket description]
     *
     * @return  string Uniqueue id
     */
    private static function is_ticket_unique( $ticket ) {
        $args = [
            'post_type'      => 'etn-attendee',
            'post_status'    => 'any',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_query'     => [
                [
                    'key'     => 'etn_unique_ticket_id',
                    'value'   => $ticket,
                    'compare' => '=',
                ]
            ]
        ];
    
        $attendees = get_posts( $args );
        
        return is_array( $attendees ) && count( $attendees ) < 1;
    }
}
