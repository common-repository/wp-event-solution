<?php

use Eventin\Settings;
use Eventin\Validation\Validator;

if ( ! function_exists( 'etn_array_csv_column' ) ) {
    /**
     * Convert array to CSV column
     *
     * @param array $data
     *
     * @return string
     */
    function etn_array_csv_column( $data = [] ) {
        $result_string = '';

        foreach ( $data as $data_key => $value ) {
            if ( ! is_array( $value ) ) {
                return etn_is_associative_array( $data ) ? etn_single_array_csv_column( $data ) : implode( ',', $data );
            }

            if ( etn_is_associative_array( $value ) ) {
                $valueString = etn_single_array_csv_column( $value );
                $result_string .= rtrim( $valueString, ', ' ) . '|';
            } else {
                $result_string .= implode( ',', $value ) . '|';
            }
        }

        // Remove the trailing '|'
        $result_string = rtrim( $result_string, '|' );

        return $result_string;
    }
}

if ( ! function_exists( 'etn_is_associative_array' ) ) {
    /**
     * Check an associative array or not
     *
     * @param array $array
     *
     * @return bool
     */
    function etn_is_associative_array( $array ) {
        return is_array( $array ) && count( array_filter( array_keys( $array ), 'is_string' ) ) > 0;
    }
}

if ( ! function_exists( 'etn_single_array_csv_column' ) ) {
    /**
     * Convert single array to csv column
     *
     * @param array $data
     *
     * @return string
     */
    function etn_single_array_csv_column( $data ) {
        if ( ! is_array( $data ) ) {
            return false;
        }

        $result_string = '';

        foreach ( $data as $key => $value ) {
            if ( is_array( $value ) ) {
                $result_string .= implode( ',', $value );
            } else {
                $result_string .= "$key:$value,";
            }
        }

        return rtrim( $result_string, ',' );
    }
}

if ( ! function_exists( 'etn_csv_column_array' ) ) {
    /**
     * Convert CSV column to array
     *
     * @param string $csvColumn
     *
     * @return array|bool
     */
    function etn_csv_column_array( $csv_column, $separator = '|' ) {
        // Explode the CSV column by '|' to get individual array elements
        if ( strpos( $csv_column, $separator ) !== false ) {
            return etn_csv_column_multi_dimension_array( $csv_column );
        }

        return etn_csv_column_single_array( $csv_column );
    }
}

if ( ! function_exists( 'etn_csv_column_multi_dimension_array' ) ) {
    /**
     * Convert CSV column to multi dimensional array
     *
     * @param   string  $csv_column
     * @param   string  $separator
     *
     * @return  array
     */
    function etn_csv_column_multi_dimension_array( $csv_column, $separator = '|' ) {
        $array_strings = explode( $separator, $csv_column );
        $result_array  = [];

        foreach ( $array_strings as $array_string ) {
            // Add the temporary array to the result array
            $result_array[] = etn_csv_column_single_array( $array_string );
        }

        return $result_array;
    }
}

if ( ! function_exists( 'etn_csv_column_single_array' ) ) {
    /**
     * Convert CSV column to multi dimensional array
     *
     * @param   string  $csv_column
     * @param   string  $separator
     *
     * @return  array
     */
    function etn_csv_column_single_array( $csv_column, $separator = ',' ) {
        $temp_array = [];

        if ( false !== strpos( $csv_column, ':' ) ) {
            $csv_column = explode( $separator, $csv_column );

            foreach ( $csv_column as $pair ) {
                // Explode key-value pairs by ':' and populate the temporary array
                list( $key, $value ) = explode( ':', $pair );
                $temp_array[$key]  = $value;
            }

            return $temp_array;
        }

        return explode( $separator, $csv_column );
    }
}

if ( ! function_exists( 'etn_is_request' ) ) {
    /**
     * What type of request is this?
     *
     * @param  string $type admin, ajax, cron or frontend.
     *
     * @return bool
     */
    function etn_is_request( $type ) {
        switch ( $type ) {
        case 'admin':
            return is_admin();

        case 'ajax':
            return defined( 'DOING_AJAX' );

        case 'rest':
            return defined( 'REST_REQUEST' );

        case 'cron':
            return defined( 'DOING_CRON' );

        case 'frontend':
            return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
        }
    }
}

if ( ! function_exists( 'etn_get_locale_data' ) ) {
    /**
     * Get locale data
     *
     * @return  array
     */
    function etn_get_locale_data() {
        $localize_vars   = include Wpeventin::plugin_dir() . 'utils/locale/vars.php';
        $localize_static = include Wpeventin::plugin_dir() . 'utils/locale/static.php';

        $data = array_merge( $localize_static, $localize_vars );

        return apply_filters( 'etn_locale_data', $data );
    }
}

if ( ! function_exists( 'etn_permision_error' ) ) {
    /**
     * Rest api error message
     *
     * @param   string  $message
     *
     * @return  \WP_REST_Response
     */
    function etn_permision_error( $message = '' ) {
        if ( ! $message ) {
            $message = __( 'Sorry, you are not allowed to do that.', 'eventin' );
        }

        $data = [
            'code'    => 'rest_forbidden',
            'message' => 'Sorry, you are not allowed to do that.',
            'data'    => [
                'status' => 403,
            ],
        ];

        return new WP_REST_Response( $data, 401 );
    }
}

if ( ! function_exists( 'etn_parse_block_content' ) ) {
    /**
     * Parses dynamic blocks out of `post_content` and re-renders them.
     *
     * @param   string  $content 
     *
     * @return  string
     */
    function etn_parse_block_content( $content ) {
        return do_blocks( $content );
    }
}

if ( ! function_exists( 'etn_validate' ) ) {
    /**
     * Validate user input
     *
     * @param   array  $request
     * @param   array  $rules
     *
     * @return  bool | WP_Error
     */
    function etn_validate( $request, $rules ) {
        $validator = new Validator( $request );

        $validator->set_rules( $rules );

        if ( ! $validator->validate() ) {
            return $validator->get_error();
        }

        return true;
    }
}

if ( ! function_exists( 'etn_get_option' ) ) {
    /**
     * Get option for eventin
     *
     * @since 1.0.0
     * @return  mixed
     */
    function etn_get_option( $key = '', $default = false ) {
        $value = Settings::get( $key );

        if ( ! $value ) {
            return $default;
        }

        return $value;
    }
}

if ( ! function_exists( 'etn_update_option' ) ) {

    /**
     * Update option
     *
     * @param   string  $key
     *
     * @since 1.0.0
     *
     * @return  boolean
     */
    function etn_update_option( $key = '', $value = false ) {
        if ( ! $key ) {
            return false;
        }

        return Settings::update( [
            $key => $value,
        ] );
    }  
}

if ( ! function_exists( 'etn_is_ticket_sale_end' ) ) {
    /**
     * Check an event has attendees or not
     *
     * @param   string  $end_date_time  Event ticket sale end date and time
     * @param   string  $timezone       Event timezone
     *
     * @return  bool
     */
    function etn_is_ticket_sale_end( $end_date_time, $timezone = 'Asia/Dhaka' ) {
        // Create a DateTime object for the end date and time in the given timezone
        $event_end_dt = new DateTime( $end_date_time, new DateTimeZone( $timezone ) );
    
        // Create a DateTime object for the current date and time in the given timezone
        $current_dt = new DateTime( 'now', new DateTimeZone( $timezone ) );
    
        // Compare the dates
        if ( $current_dt > $event_end_dt ) {
            return true;
        }

        return false;
    }
}

if ( ! function_exists( 'etn_is_ticket_sale_start' ) ) {
    /**
     * Check an event has attendees or not
     *
     * @param   string  $start_date_time  Event ticket sale start date and time
     * @param   string  $timezone         Event timezone
     *
     * @return  bool 
     */
    function etn_is_ticket_sale_start( $start_date_time, $timezone = 'Asia/Dhaka' ) {
        // Create a DateTime object for the start date and time in the given timezone
        $event_date = new DateTime( $start_date_time, new DateTimeZone( $timezone ) );
    
        // Create a DateTime object for the current date and time in the given timezone
        $current_datte = new DateTime('now', new DateTimeZone( $timezone ) );
    
        // Compare the dates
        if ( $current_datte < $event_date ) {
            return false;
        } 

        return true;
    }
}


if ( ! function_exists( 'etn_create_date_timezone' ) ) {
    /**
     * Create datetimezone object
     *
     * @param   string  $timezoneString  Timezone
     *
     * @return  string
     */
    function etn_create_date_timezone( $timezoneString ) {
         // List of valid named timezones
        $validTimezones = DateTimeZone::listIdentifiers();

        // Check if the provided timezone is a valid named timezone
        if ( in_array( $timezoneString, $validTimezones ) ) {
            return $timezoneString;
        }

        // Check if the provided timezone is an offset timezone like UTC+6 or UTC-4.5
        if ( preg_match('/^UTC([+-]\d{1,2})(?:\.(\d))?$/i', $timezoneString, $matches ) ) {
            // Convert the matched offset to a format recognized by DateTimeZone
            $hours = intval( $matches[1] );
            $minutes = isset( $matches[2] ) ? intval($matches[2]) * 6 : 0; // 0.1 fractional part means 6 minutes

            // Ensure the format is like +06:30 or -04:30
            $formattedOffset = sprintf( '%+03d:%02d', $hours, $minutes );
            return $formattedOffset;
        }

        // If the timezone string doesn't match any known format, throw an exception
        throw new Exception('Unknown or bad timezone: ' . $timezoneString);
    }
}

if ( ! function_exists( 'etn_convert_to_date' ) ) {
    /**
     * Convert to date from date time string
     *
     * @param   string  $datetimeString  Datetime string
     *
     * @return  string  Date string
     */
    function etn_convert_to_date( $datetimeString ) {
        try {
            // Create a DateTime object using the provided datetime string
            $datetime = new DateTime( $datetimeString );
            
            // Return the formatted date in 'Y-m-d' format
            return $datetime->format( 'Y-m-d' );
        } catch ( Exception $e ) {
            return 'Error: ' . $e->getMessage();
        }
    }
}

if ( ! function_exists( 'etn_get_currency' ) ) {
    /**
     * Get currency list
     *
     * @return  array
     */
    function etn_get_currency () {
        $currencies = require Wpeventin::plugin_dir() . '/utils/currency.php';

        return $currencies;
    }
}

if ( ! function_exists( 'etn_get_currency_by' ) ) {
    /**
     * Get currency by name, symbol
     *
     * @return  string
     */
    function etn_get_currency_by_name( $name ) {
        $currencies = etn_get_currency();

        foreach( $currencies as $currencie ) {
            if ( $currencie['name'] === $name ) {
                return $currencie;
            }
        }

        return null;
    }
}

if ( ! function_exists( 'etn_get_currency_symbol' ) ) {
    /**
     * Get currency by name, symbol
     *
     * @return  string
     */
    function etn_get_currency_symbol( $name ) {
        $currency = etn_get_currency_by_name( $name );

        if ( $currency ) {
            return $currency['symbol'];
        }
    }
}

if ( ! function_exists( 'etn_event_url_editable' ) ) {
    /**
     * Check editable url
     *
     * @return  bool
     */
    function etn_event_url_editable () {
        $permalink_structure = get_option('permalink_structure');

        if ( strpos( $permalink_structure, '%postname%' ) !== false) {
            return true;
        }

        return false;
    }
}

if ( ! function_exists( 'etn_get_timezone' ) ) {
    /**
     * Get valid timezonelists
     *
     * @return  array Timezone lists
     */
    function etn_get_timezone() {
        $validTimezones = DateTimeZone::listIdentifiers();

        return $validTimezones;
    }
}

if ( ! function_exists( 'etn_prepare_address' ) ) {
    /**
     * Prepare event address from event location
     * 
     * This function is written for temporary solution. We have a nested location issue @since 4.0.0. To resolve this we impletent a temporary function. We have to remove this function when v4.0 is completely statble from location issue. Before remove this function make sure remove from all of the place where we used this. 
     *
     * @param   array  $location  
     *
     * @return  string
     */
    function etn_prepare_address( $location ) {
        static $depth = 0;
    
        if ( $depth >= 10 ) {
            return '';
        }
    
        $depth++;
    
        if ( ! is_array( $location ) ) {
            return $location;
        }
    
        $address = ! empty( $location['address'] ) ? $location['address'] : '';
    
        return etn_prepare_address( $address );
    }
}

if ( ! function_exists( 'etn_get_email_settings' ) ) {

    /**
     * Get email settings
     *
     * @param   string  $email  Email name for the email setting
     *
     * @return  array
     */
    function etn_get_email_settings( $email = '' ) {

        $email_settings = etn_get_option( 'email' );

        $defaults = etn_get_default_email_settings();

        $email_settings = etn_recursive_wp_parse_args( $email_settings, $defaults );

        if ( ! $email ) {
            return $email_settings;
        }

        return $email_settings[$email];
    }
}

if ( ! function_exists( 'etn_get_default_email_settings' )  ) {
    /**
     * Get default email settings for the email template
     *
     * @return  array
     */
    function etn_get_default_email_settings() {

        $email_settings = [
            'purchase_email' => [
                'from'    => get_option( 'admin_email' ),
                'subject' => sprintf( __( 'Event Ticket', 'eventin' ) ),
                'body'    => __( 'You have purchased ticket(s). Attendee ticket details are as follows.', 'eventin' ),
                'send_to_admin' => true,
            ],
            'rsv_email' => [
                'from'          => get_option( 'admin_email' ),
                'response_type' => 'going',
                'subject'       => sprintf( __( 'RSVP request', 'eventin' ) ),
                'body'          => sprintf( __( 'We received your RSVP request', 'eventin' ) ),
                'send_to_admin' => true,
            ],
            'reminder_email' => [
                'from'    => get_option( 'admin_email' ),
                'subject' => sprintf( __( 'Reminder email', 'eventin' ) ),
                'body'    => __( 'Just sending you a quick reminder about our retailer meet-up you\'ve registered to attend in two days time. If you\'ve misplaced the Invitation that contained all the details. don\'t worry. Cve added them rn below for you.', 'eventin' ),
                'send_to_admin' => true,
            ]
        ];

        return apply_filters( 'etn_default_email_settings', $email_settings );
    }
}

if ( ! function_exists( 'etn_recursive_wp_parse_args' ) ) {
    /**
     * Perse args recursively
     *
     * @param   array  $args      [$args description]
     * @param   array  $defaults  [$defaults description]
     *
     * @return  array             [return description]
     */
    function etn_recursive_wp_parse_args( $args, $defaults ) {
        $args = (array) $args; // Ensure args is an array
    
        // Loop through each default value and apply wp_parse_args recursively if it's an array
        foreach ( $defaults as $key => $value ) {
            if ( is_array( $value ) ) {
                // If the key is an array, call recursively
                $args[$key] = etn_recursive_wp_parse_args( isset( $args[$key] ) ? $args[$key] : [], $value );
            } else {
                // Otherwise, use wp_parse_args for the non-array values
                if ( ! isset( $args[$key] ) ) {
                    $args[$key] = $value;
                }
            }
        }
    
        return $args;
    }
}

if ( !function_exists( 'etn_editor_settings' ) ) {
    /**
     * Retrieves the settings for the Gutenberg editor.
     *
     * This function retrieves the settings for the Gutenberg editor, including the allowed block types,
     * typography, color palette, and other experimental features. It also applies filters to allow
     * customization of the settings.
     *
     * @return array The settings for the Gutenberg editor.
     */
    function etn_editor_settings() {
        
        
        $coreSettings            = get_block_editor_settings( [], 'post' );
        $wordpressCoreTypography = $coreSettings['__experimentalFeatures']['typography'];
        $coreExperimentalSpacing = $coreSettings['__experimentalFeatures']['spacing'];
        

        $themePref     = getThemePrefScheme();

        $settings = array(
            'gradients'                         => [],
            'alignWide'                         => false,
            'allowedMimeTypes'                  => get_allowed_mime_types(),
            '__experimentalBlockPatterns'       => [],
            '__experimentalFeatures'            => [
                'appearanceTools' => true,
                'border'          => [
                    'color'  => false,
                    'radius' => true,
                    'style'  => false,
                    'width'  => false,
                ],
                'color'           => [
                    'background'       => true,
                    'customDuotone'    => false,
                    'defaultGradients' => false,
                    'defaultPalette'   => false,
                    'duotone'          => [],
                    'gradients'        => [],
                    'link'             => false,
                    'palette'          => [
                        'theme' => $themePref['colors'],
                    ],
                    'text'             => true,
                ],
                'spacing'         => $coreExperimentalSpacing,
                'typography'      => $wordpressCoreTypography,
                'blocks'          => [
                    'core/button' => [
                        'border'     => [
                            'radius' => true,
                            "style"  => true,
                            "width"  => true,
                        ],
                        'typography' => [
                            'fontSizes' => [],
                        ],
                        'spacing'    => $coreExperimentalSpacing,
                    ],

                ],
            ],
            '__experimentalSetIsInserterOpened' => false,
            'disableCustomColors'               => get_theme_support( 'disable-custom-colors' ),
            'disableCustomFontSizes'            => false,
            'disableCustomGradients'            => true,
            'enableCustomLineHeight'            => get_theme_support( 'custom-line-height' ),
            'enableCustomSpacing'               => get_theme_support( 'custom-spacing' ),
            'enableCustomUnits'                 => false,
            'keepCaretInsideBlock'              => false,
            'mediaLibrary'                      =>  ['type' => true, 'date' => true, 'allowedTypes' => ['image', 'video', 'audio', 'application']],
            'mediaUpload'                       => true,
        );

        $color_palette = current( (array) get_theme_support( 'editor-color-palette' ) );
        if ( false !== $color_palette ) {
            $settings['colors'] = $color_palette;
        } else {
            $settings['colors'] = [];
        }

        return $settings;
    }
}

if ( !function_exists( 'getThemePrefScheme' ) ) {
    /**
     * Retrieves the theme preference scheme.
     *
     * This function retrieves the theme preference scheme, which includes the color palette and font sizes.
     * The color palette is an array of objects, each representing a color with its name, slug, and hex code.
     * The font sizes is an array of objects, each representing a font size with its name, short name, size, and slug.
     * The function applies filters to allow customization of the theme preference scheme.
     *
     * @return array The theme preference scheme.
     */
    function getThemePrefScheme() {
        static $pref;
        if ( !$pref ) {

            $color_palette = [
                [
                    "name"  => __( "Black", "eventin" ),
                    "slug"  => "black",
                    "color" => "#000000",
                ],
                [
                    "name"  => __( "Cyan bluish gray", "eventin" ),
                    "slug"  => "cyan-bluish-gray",
                    "color" => "#abb8c3",
                ],
                [
                    "name"  => __( "White", "eventin" ),
                    "slug"  => "white",
                    "color" => "#ffffff",
                ],
                [
                    "name"  => __( "Pale pink", "eventin" ),
                    "slug"  => "pale-pink",
                    "color" => "#f78da7",
                ],
                [
                    "name"  => __( "Luminous vivid orange", "eventin" ),
                    "slug"  => "luminous-vivid-orange",
                    "color" => "#ff6900",
                ],
                [
                    "name"  => __( "Luminous vivid amber", "eventin" ),
                    "slug"  => "luminous-vivid-amber",
                    "color" => "#fcb900",
                ],
                [
                    "name"  => __( "Light green cyan", "eventin" ),
                    "slug"  => "light-green-cyan",
                    "color" => "#7bdcb5",
                ],
                [
                    "name"  => __( "Vivid green cyan", "eventin" ),
                    "slug"  => "vivid-green-cyan",
                    "color" => "#00d084",
                ],
                [
                    "name"  => __( "Pale cyan blue", "eventin" ),
                    "slug"  => "pale-cyan-blue",
                    "color" => "#8ed1fc",
                ],
                [
                    "name"  => __( "Vivid cyan blue", "eventin" ),
                    "slug"  => "vivid-cyan-blue",
                    "color" => "#0693e3",
                ],
                [
                    "name"  => __( "Vivid purple", "eventin" ),
                    "slug"  => "vivid-purple",
                    "color" => "#9b51e0",
                ],
            ];

            $font_sizes = [
                [
                    'name'      => __( 'Small', 'eventin' ),
                    'shortName' => 'S',
                    'size'      => 14,
                    'slug'      => 'small',
                ],
                [
                    'name'      => __( 'Medium', 'eventin' ),
                    'shortName' => 'M',
                    'size'      => 18,
                    'slug'      => 'medium',
                ],
                [
                    'name'      => __( 'Large', 'eventin' ),
                    'shortName' => 'L',
                    'size'      => 24,
                    'slug'      => 'large',
                ],
                [
                    'name'      => __( 'Larger', 'eventin' ),
                    'shortName' => 'XL',
                    'size'      => 32,
                    'slug'      => 'larger',
                ],
            ];

            $pref = apply_filters( 'eventin/theme_pref', [
                'colors'     => (array) $color_palette,
                'font_sizes' => (array) $font_sizes,
            ] );
        }

        return $pref;

    }
}

if ( ! function_exists( 'etn_currency' ) ) {
    /**
     * Get currecny
     *
     * @return  string
     */
    function etn_currency() {
        $payment_method = etn_get_option( 'payment_method' );
        $is_enabled_wc = 'woocommerce' === $payment_method;

        if ( function_exists('WC') &&  $is_enabled_wc ) {
            return get_woocommerce_currency();
        }

        $currency = etn_get_option( 'etn_settings_country_currency', 'USD' );

        return $currency;
    }
}

if ( ! function_exists( 'etn_currency_symbol' ) ) {
    /**
     * Get currency symbol
     *
     * @return  string
     */
    function etn_currency_symbol() {
        $currency = etn_currency();

        return etn_get_currency_symbol( $currency );
    }
}

if ( ! function_exists( 'etn_is_enable_wc' ) ) {
    /**
     * Check event in is used woocommerce payment method
     *
     * @return  bool
     */
    function etn_is_enable_wc() {
        $payment_method = etn_get_option( 'payment_method' );

        return function_exists( 'WC' ) && 'woocommerce' === $payment_method;
    }
}

if ( ! function_exists( 'etn_get_thousand_separator' ) ) {

    /**
     * Thousand separator
     *
     * @return  string
     */
    function etn_get_thousand_separator() {
        if ( etn_is_enable_wc() ) {
            return wc_get_price_thousand_separator();
        }

        $thousand_separator = etn_get_option( 'thousand_separator', ',' );

        return apply_filters( 'etn_thousand_separator', $thousand_separator );
    }
}

if ( ! function_exists( 'etn_get_decimal_separator' ) ) {

    /**
     * Get descimal separator
     *
     * @return  string
     */
    function etn_get_decimal_separator() {
        if ( etn_is_enable_wc() ) {
            return wc_get_price_decimal_separator();
        }

        $decimal_separator = etn_get_option( 'decimal_separator', 'comma_dot' );

        return apply_filters( 'etn_decimal_separator', $decimal_separator );
    }
}

if ( ! function_exists( 'etn_get_decimals' ) ) {

    /**
     * Get number of decimals
     *
     * @return  string
     */
    function etn_get_decimals() {
        if ( etn_is_enable_wc() ) {
            return wc_get_price_decimals();
        }

        $decimals = etn_get_option( 'decimals', 2 );

        return apply_filters( 'etn_decimals', $decimals );
    }
}

if ( ! function_exists( 'etn_get_price_format' ) ) {

    /**
     * Get price format
     *
     * @return  string
     */
    function etn_get_price_format() {
        if ( etn_is_enable_wc() ) {
            return get_woocommerce_price_format();
        }

        $currency_pos = get_option( 'currency_position' );
        $format       = '%1$s%2$s';

        switch ( $currency_pos ) {
            case 'left':
                $format = '%1$s%2$s';
                break;
            case 'right':
                $format = '%2$s%1$s';
                break;
            case 'left_space':
                $format = '%1$s&nbsp;%2$s';
                break;
            case 'right_space':
                $format = '%2$s&nbsp;%1$s';
                break;
        }

        return apply_filters( 'etn_price_format', $format, $currency_pos );
    }
}

if ( ! function_exists( 'etn_get_currency_position' ) ) {

    /**
     * Get price format
     *
     * @return  string
     */
    function etn_get_currency_position() {
        if ( etn_is_enable_wc() ) {
            $currency_pos = get_option( 'woocommerce_currency_pos' );

            return $currency_pos;
        }

        $currency_pos = etn_get_option( 'currency_position', 'left' );

        return apply_filters( 'etn_currency_position', $currency_pos );
    }
}