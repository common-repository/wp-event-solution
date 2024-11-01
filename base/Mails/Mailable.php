<?php
namespace Eventin\Mails;

use Exception;

/**
 * Mailable Class
 * 
 * @package Eventin
 */
abstract class Mailable {
    /**
     * Email subject
     *
     * @var string
     */
    protected $subject;

    /**
     * Email message
     *
     * @var string
     */
    protected $content;

    /**
     * Email subject abstract method
     *
     * @return  string  Email subject
     */
    abstract public function subject(): string;

    /**
     * Email content
     *
     * @return  string  Email body
     */
    abstract public function content(): string;

    /**
     * Set email subject
     *
     * @param   string  $subject  Email subject
     *
     * @return  self Mailable Class
     */
    public function set_subject( $subject ): self {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Set email content
     *
     * @param   string  $content  Email content
     *
     * @return  self    Mailable Class
     */
    public function set_content( $content ): self {
        $this->content = $content;

        return $this;
    }

    /**
     * Get email subject
     *
     * @return  string
     */
    public function get_subject(): string {
        return $this->subject;
    }

    /**
     * Get email content
     *
     * @return  string
     */
    public function get_content(): string {
        return $this->content;
    }

    /**
     * Render email template
     *
     * @param   string  $content  Email template path
     * @param   array  $data          Email data that need be send
     *
     * @return  string
     */
    public function render_content( $content, $data = [] ): string {
        if ( ! is_file( $content ) ) {
            return $content;
        }

        if ( file_exists( $content ) ) {
            ob_start();
            extract($data);
            include $content;
            return ob_get_clean();
        }

        throw new Exception("Template not found: {$content}");
    }
}
