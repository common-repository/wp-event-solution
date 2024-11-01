<?php
/**
 * Speaker Model Class
 *
 * @package Eventin
 */
namespace Etn\Core\Speaker;

use Etn\Base\Post_Model;

/**
 * Speaker Model
 */
class Speaker_Model extends Post_Model {
    /**
     * Store speaker post type
     *
     * @var string
     */
    protected $post_type = 'etn-speaker';

    /**
     * Store speaker data
     *
     * @var array
     */
    protected $data = [
        'etn_speaker_title'         => '',
        'etn_speaker_designation'   => '',
        'etn_speaker_website_email' => '',
        'etn_speaker_summery'       => '',
        'etn_speaker_socials'       => '',
        'etn_speaker_company_logo'  => '',
        'etn_speaker_url'           => '',
        'image'                     => '',
        'image_id'                  => '',
    ];
}
