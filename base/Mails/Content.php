<?php
/**
 * Mail Content Class
 * 
 * @package Eventin
 */
namespace Eventin\Mails;

/**
 * Content class
 */
class Content {
    /**
     * Store template path
     *
     * @var string
     */
    private static $template;

    /**
     * Store email data
     *
     * @var array
     */
    private static $data = [];

    /**
     * Email content
     *
     * @return  string
     */
    public static function get( $template, $data = [] ) {
        self::$data = $data;
        self::$template = $template;

        return self::prepare_content();
    }

    /**
     * Prepare content from template with dynamic data
     *
     * @return  string  Email body
     */
    private static function prepare_content() {
        $template = self::$template;

        extract( self::$data );
        $email_template = \Wpeventin::templates_dir() . 'emails/email.php';

        ob_start();

        include $email_template;

        return ob_get_clean();
    }
}
