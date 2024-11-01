<?php
/**
 * Mail Class
 * 
 * @package eventin
 */
namespace Eventin\Mails;

class Mail {
    /**
     * The person the message is from
     *
     * @var array
     */
    public $from = [];

    /**
     * The "to" recipients of the message 
     *
     * @var array
     */
    public $to = [];

    /**
     * The "to" recipients of the message 
     *
     * @var array
     */
    public $cc = [];

    /**
     * The "to" recipients of the message 
     *
     * @var array
     */
    public $bcc = [];

    /**
     * The "to" recipients of the message 
     *
     * @var array
     */
    public $replay_to = [];

    /**
     * Set email addres
     *
     * @param   array|string|object  $address  Email address
     *
     * @return  self 
     */
    public static function to( $address, $name = null ): self {
        return new self( $address, $name );
    }

    /**
     * Set email addres
     *
     * @param   array|string|object  $address  Email address
     *
     * @return  self 
     */
    public function from( $address, $name = null ) {
        return $this->set_address( $address, $name, 'from' );
    }

    /**
     * Set recipients of the message
     *
     * @return  self
     */
    public function cc( $address, $name = null ) {
        return $this->set_address( $address, $name, 'cc' );
    }

    /**
     * Set recipients of the message
     *
     * @return  self
     */
    public function bcc( $address, $name = null ) {
        return $this->set_address( $address, $name, 'bcc' );
    }

    /**
     * Email Recipients
     *
     * @var array
     */
    protected $recipients = [];

    /**
     * Constructor for mail class
     *
     * @param array|string|object  $address  Email address
     *
     * @return  void
     */
    public function __construct( $address, $name = null ) {
        $this->set_address( $address, $name );
    }

    /**
     * Process email address
     *
     * @param   array|string|object  $address  Email address
     *
     * @return  void
     */
    protected function set_address( $address, $name, $property = 'to' ) {
        if ( empty( $address ) ) {
            return $this;
        }

        foreach( $this->address_to_array( $address ) as $recipient ) {
            $recipient = $this->normalize_recipient( $recipient );

            if ( $recipient ) {
                $this->{$property}[] = $recipient;
            }
        }

        return $this;
    }

    /**
     * Convert address as array
     *
     * @param   string | array | object  $address  Recipients
     *
     * @return  array
     */
    protected function address_to_array( $address ) {
        if ( ! is_array( $address ) && ! is_object( $address ) ) {
            $address = ['email' => $address];
        }

        return $address;
    }

    /**
     * Convert the given recipient as recipient email address
     *
     * @return  string
     */
    protected function normalize_recipient( $recipient ) {
        if ( is_array( $recipient ) ) {
            return ! empty( $recipient['email'] ) ? $recipient['email'] : '';
        } elseif( is_object( $recipient ) ) {
            return ! empty( $recipient->email ) ? $recipient->email : '';
        }
    
        return $recipient;
    }

    /**
     * Send email to all of the recipents
     *
     * @param   Mailable  $mailable
     *
     * @return  bool
     */
    public function send( Mailable $mailable ) {
        $subject = $mailable->subject();
        $message = $mailable->content();
        $headers = $this->get_headers();
        
        wp_mail( $this->to, $subject, $message, $headers );
    }

    /**
     * Get headers of the email
     *
     * @return  array
     */
    protected function get_headers() {
        $mime_version = "MIME-Version: 1.0" . "\r\n";
        $content_type = "Content-type:text/html;charset=UTF-8" . "\r\n";
        
        $headers = [
            $mime_version,
            $content_type,
        ];

        if ( ! empty( $this->from ) ) {
            $from = $this->from[0];
            $headers[] = "From: {$from}";
        }

        if ( ! empty( $this->cc ) ) {
            $cc = implode( ',', $this->cc );
            $headers[] = "Cc: {$cc}";
        }

        if ( ! empty( $this->bcc ) ) {
            $bcc = implode( ',', $this->bcc );
            $headers[] = "Bcc: {$bcc}";
        }

        return apply_filters( 'eventin_email_headers', $headers );
    }
}
