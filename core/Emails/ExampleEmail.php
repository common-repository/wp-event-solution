<?php
namespace Eventin\Emails;

use Eventin\Mails\Content;
use Eventin\Mails\Mailable;

/**
 * Example email
 * 
 * @package eventin
 */
class ExampleEmail extends Mailable {
    /**
     * Email subject
     *
     * @return  string
     */
    public function subject(): string {
        return "Example email";
    }

    /**
     * Email content
     *
     * @return  string  email body
     */
    public function content(): string {
        return Content::get( 'example-email-template' );
    }
}