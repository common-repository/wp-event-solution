<?php
namespace Etn\Base\Enqueue;

/**
 * Frontend class
 */
class Frontend {
    /**
     * Initialize the class
     *
     * @return  void
     */
    public function __construct() {
        add_action( 'wp_enqueue_scripts', [$this, 'enqueue_scripts'] );
    }

    /**
     * Enqueue scripts and styles
     *
     * @return  void
     */
    public function enqueue_scripts( $top ) {
        wp_enqueue_script('eventin-i18n');
        wp_enqueue_style( 'etn-icon' );
        wp_enqueue_style( 'etn-public-css' );
        wp_enqueue_script( 'etn-public' ); 
		//set translations
		
		wp_set_script_translations( 'etn-public', 'eventin' );
        // wp_enqueue_script( 'html-to-image' ); // Don't need this. Without this file it's working fine
		
		//set frontend translation
		wp_set_script_translations( 
		'etn-module-purchase',  // The script handle
		'eventin',              // Text domain
		plugin_dir_path(__FILE__) . 'languages' //path to language folder
		);

        // Load RTL CSS.
        if ( is_rtl() ) {
            wp_enqueue_style( 'etn-rtl');
        }


        	// localize data.
		$translated_data                       = array();
		$translated_data['ajax_url']           = admin_url( 'admin-ajax.php' );
		$translated_data['site_url']           = site_url();
		$translated_data['evnetin_pro_active'] = ( class_exists( 'Wpeventin_Pro' ) ) ? true : false;
		$translated_data['locale_name']        = strtolower( str_replace( '_', '-', get_locale() ) );
		$translated_data['start_of_week']      = get_option( 'start_of_week' );
		$translated_data['expired']            = esc_html__( 'Expired', 'eventin' );
		$translated_data['author_id']          = get_current_user_id();
		$translated_data['nonce']              = wp_create_nonce( 'wp_rest' );

		$translated_data['scanner_common_msg']  = esc_html__( 'Something went wrong! Please try again.', 'eventin' );
		$ticket_scanner_link                    = admin_url( '/edit.php?post_type=etn-attendee' );
		$translated_data['ticket_scanner_link'] = $ticket_scanner_link;
		$currency_symbol                        = etn_currency_symbol();

		$attendee_form_validation_msg = array();

		$email_error_msg            = array();
		$email_error_msg['invalid'] = esc_html__( 'Email is not valid', 'eventin' );
		$email_error_msg['empty']   = esc_html__( 'Please fill the field', 'eventin' );

		$tel_error_msg                = array();
		$tel_error_msg['empty']       = esc_html__( 'Please fill the field', 'eventin' );
		$tel_error_msg['invalid']     = esc_html__( 'Invalid phone number', 'eventin' );
		$tel_error_msg['only_number'] = esc_html__( 'Only number allowed', 'eventin' );

		$attendee_form_validation_msg['email']           = $email_error_msg;
		$attendee_form_validation_msg['tel']             = $tel_error_msg;
		$attendee_form_validation_msg['text']            = esc_html__( 'Please fill the field', 'eventin' );
		$attendee_form_validation_msg['number']          = esc_html__( 'Please input a number', 'eventin' );
		$attendee_form_validation_msg['date']            = esc_html__( 'Please fill the field', 'eventin' );
		$attendee_form_validation_msg['radio']           = esc_html__( 'Please check the field', 'eventin' );
		$translated_data['attendee_form_validation_msg'] = $attendee_form_validation_msg;
		$translated_data['post_id']						 = get_the_ID();
		$translated_data['currency_symbol']              = $currency_symbol;

		wp_localize_script( 'etn-public', 'localized_data_obj', $translated_data );
		
    }
}