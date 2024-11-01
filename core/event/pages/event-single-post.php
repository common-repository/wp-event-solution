<?php

namespace Etn\Core\Event\Pages;

defined( 'ABSPATH' ) || exit;

class Event_single_post {

    use \Etn\Traits\Singleton;
    public function __construct() {
        add_filter('template_include', [$this, 'event_single_page'], 99);
        add_filter('template_include', [$this, 'event_archive_template'], 99);
    }

    public function event_archive_template($template) {
        if (is_post_type_archive('etn')) {
            $default_file = \Wpeventin::plugin_dir() . 'core/event/views/event-archive-page.php';
            if (file_exists($default_file)) {
                 return $default_file;
            } 
        }
        return $template;
    }

    public function event_single_page($template) {
        global $post;
        if ($post->post_type == 'etn' && is_singular('etn')) {
            $default_file = \Wpeventin::plugin_dir() . 'core/event/views/event-single-page.php';
            if (file_exists($default_file)) {
                 return $default_file;
            }  
        }
        return $template;
    }

}
