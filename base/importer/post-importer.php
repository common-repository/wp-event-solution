<?php
/**
 * Post Importer Class
 *
 * @package Eventin
 */
namespace Etn\Base\Importer;

use Etn\Core\Attendee\Attendee_Importer;
use Etn\Core\Event\Event_Importer;
use Etn\Core\Schedule\Schedule_Importer;
use Etn\Core\Speaker\Speaker_Importer;

/**
 * Post Importer Class
 */
class Post_Importer {

    /**
     * Post importer class
     *
     * @param   string  $post_type
     *
     * @return
     */
    public static function get_importer( $post_type ) {
        $exporters = [
            'etn-speaker'  => Speaker_Importer::class,
            'etn-schedule' => Schedule_Importer::class,
            'etn'          => Event_Importer::class,
            'etn-attendee' => Attendee_Importer::class,
        ];

        $exporters = apply_filters( 'etn_post_importers', $exporters );

        if ( ! empty( $exporters[$post_type] ) ) {
            return new $exporters[$post_type]();
        }

        throw new \Exception( __( 'Unknown Post Type', 'eventin' ) );
    }
}
