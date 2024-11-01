<?php
/**
 * Template Builder Model
 *
 * @package Eventin
 */
namespace Etn\Core\TemplateBuilder;

use Etn\Base\Post_Model;

/**
 * Template Model Class
 */
class Template_Model extends Post_Model {
    /**
     * Store template post type
     *
     * @var string
     */
    protected $post_type = 'etn-template';

    /**
     * Store meta prefix
     *
     * @var string
     */
    protected $meta_prefix = '_template';

    /**
     * Store data
     *
     * @var array
     */
    protected $data = [
        'name'        => '',
        'type'        => '',
        'status'      => '',
        'orientation' => '',
        'content'     => '',
    ];
}