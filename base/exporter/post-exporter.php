<?php
/**
 * Post Exporter Class
 *
 * @package Eventin
 */
namespace Etn\Base\Exporter;

use Etn\Core\Attendee\Attendee_Exporter;
use Etn\Core\Event\Event_Exporter;
use Etn\Core\Schedule\Schedule_Exporter;
use Etn\Core\Speaker\Speaker_Exporter;

/**
 * Post Exporter Class
 */
class Post_Exporter {
    /**
     * Get post exporter
     *
     * @return
     */
    public static function get_post_exporter( $post_type ) {

        $exporters = [
            'etn'          => Event_Exporter::class,
            'etn-attendee' => Attendee_Exporter::class,
            'etn-speaker'  => Speaker_Exporter::class,
            'etn-schedule' => Schedule_Exporter::class,
            'etn-attendee' => Attendee_Exporter::class,
        ];

        $exporters = apply_filters( 'etn_post_exporters', $exporters );

        if ( ! empty( $exporters[$post_type] ) ) {
            return new $exporters[$post_type]();
        }

        throw new \Exception( __( 'Unknown Post Type', 'eventin' ) );
    }
}
