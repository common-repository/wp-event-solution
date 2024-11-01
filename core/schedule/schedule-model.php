<?php
/**
 * Schedule Model Class
 *
 * @package Eventin
 */
namespace Etn\Core\Schedule;

use Etn\Base\Post_Model;

/**
 * Schedule Model
 */
class Schedule_Model extends Post_Model {
    /**
     * Store schedule post type
     *
     * @var string
     */
    protected $post_type = 'etn-schedule';

    /**
     * Store schedule data
     *
     * @var array
     */
    protected $data = [
        'etn_schedule_title'  => '',
        'etn_schedule_date'   => '',
        'etn_schedule_day'    => '',
        'etn_schedule_topics' => '',
    ];
}
