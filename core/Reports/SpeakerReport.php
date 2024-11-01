<?php
namespace Eventin\Reports;

use Eventin\Input;

/**
 * Speaker Report class
 * 
 * @package Eventin
 */
class SpeakerReport extends AbstractReport {
    /**
     * Get total speaker
     *
     * @param   array  $dates  Date range
     *
     * @return  array Total number of speaker
     */
    public static function get_total_speaker( $dates = [] ) {
        $speakers = self::get_speakers( $dates );

        if ( is_array( $speakers ) ) {
            return count( $speakers );
        }

        return 0;
    }

    /**
     * Get speakers
     *
     * @param   array  $data  Speaker data
     *
     * @return  array All speaker data
     */
    private static function get_speakers( $data ) {
        $input      = new Input( $data );
        $start_date = $input->get( 'start_date' );
        $end_date   = $input->get( 'end_date' );
        $roles      = [ 'etn-speaker', 'etn-organizer' ];

        return self::get_users( [
            'start_date' => $start_date,
            'end_date'   => $end_date,
            'roles'      => $roles
        ] );
    }
}
