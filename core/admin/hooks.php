<?php
/**
 * Admin Hooks Class
 *
 * @package Eventin
 */
namespace Etn\Core\Admin;

use Etn\Base\Exporter\Post_Exporter;
use Etn\Base\Importer\Post_Importer;
use Etn\Core\Event\Event_Model;
use Etn\Traits\Singleton;
use Eventin\Integrations\Zoom\ZoomCredential;
use Eventin\Upgrade\Upgrade;
use Eventin\Upgrade\Upgraders\V_4_0_8;
use WP_Error;
use Wpeventin;
use Wpeventin_Pro;

/**
 * Admin Hooks Class
 */
class Hooks {
    use Singleton;

    /**
     * Initialize
     *
     * @return  void
     */
    public function init() {
        // Add export and import tab on post types
        add_action( 'manage_posts_extra_tablenav', [$this, 'add_export_import_button'] );

        add_action( 'admin_init', [$this, 'export_data'] );

        add_action( 'wp_ajax_etn_file_import', [$this, 'import_file'] );

        add_action( 'save_post', [$this, 'add_flush_rules'] );

        add_filter( 'eventin_settings', [$this, 'add_settings'] );

        add_filter( 'get_edit_post_link', [ $this, 'modifiy_event_edit_link' ], 10, 2 );

        add_action( 'in_plugin_update_message-' . Wpeventin::plugins_basename(), function( $plugin_data ) {
			$this->version_update_warning( Wpeventin::version(), $plugin_data['new_version'] );
		} );

        add_action( 'admin_notices', [ $this, 'migration_notice' ] );

        add_action( 'wp_ajax_etn_run_migration', [ $this, 'run_migration' ] );

        add_action( 'admin_init', [ $this, 'do_upgrade' ] );

        add_action( 'admin_init', [ $this, 'save_addons' ] );

        add_action( 'admin_init', [$this, 'etn_speaker_group_insert_to_user'] );

        add_filter( 'eventin_settings', [ $this, 'update_extra_field_settings' ] );

        add_action( 'eventin_event_updated', [ $this, 'update_seat_price' ] );

        add_action( 'eventin_event_after_clone', [ $this, 'update_clone_event_sold_tickets' ] );

    }

    /**
     * Add export and import button
     *
     * @return  void
     */
    public function add_export_import_button( $which ) {

        if ( 'top' != $which ) {
            return;
        }

        global $post_type_object;

        $export_posts = ['etn', 'etn-schedule', 'etn-speaker', 'etn-attendee'];
        $import_posts = ['etn-schedule', 'etn-speaker', 'etn', 'etn-attendee'];
        $nonce_action = 'etn_data_export_nonce_action';
        $nonce_name   = 'etn_data_export_nonce';

        $url      = admin_url( 'edit.php?post_type=' . $post_type_object->name );
        $json_url = $url . '&etn-action=export&format=json';
        $csv_url  = $url . '&etn-action=export&format=csv';

        // Export button.
        if ( in_array( $post_type_object->name, $export_posts ) ) {
            printf( '
            <div class="dropdown">
                <a href="#" class="button etn-post-export">%s</a>
                    <div class="dropdown-content">
                        <a href="%s">%s</a>
                        <a href="%s">%s</a>
                    </div>
            </div>
        ', __( 'Export', 'eventin' ), wp_nonce_url( $json_url, $nonce_action, $nonce_name ),  __( 'Export JSON Format', 'eventin' ), wp_nonce_url( $csv_url, $nonce_action, $nonce_name ), __( 'Export CSV Format', 'eventin' ) );
        }

        // Import Button.
        if ( in_array( $post_type_object->name, $import_posts ) ) {
            printf( '
            <a href="%s" class="button etn-post-import">%s</a>
        ', $url . '&action=import', __( 'Import', 'eventin' ) );

        }
    }

    /**
     * Export data
     *
     * @return  void
     */
    public function export_data() {
        $nonce = isset( $_GET['etn_data_export_nonce'] ) ? sanitize_text_field( $_GET['etn_data_export_nonce'] ) : '';

        if ( ! wp_verify_nonce( $nonce, 'etn_data_export_nonce_action' ) ) {
            return;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $action    = isset( $_GET['etn-action'] ) ? sanitize_text_field( $_GET['etn-action'] ) : '';
        $post_type = isset( $_GET['post_type'] ) ? sanitize_text_field( $_GET['post_type'] ) : '';
        $format    = isset( $_GET['format'] ) ? sanitize_text_field( $_GET['format'] ) : '';

        if ( 'export' != $action ) {
            return;
        }

        $post_ids      = $this->get_post_ids( $post_type );
        $post_exporter = Post_Exporter::get_post_exporter( $post_type );

        $post_exporter->export( $post_ids, $format );
    }

    /**
     * Get post ids
     *
     * @param   string  $post_type
     *
     * @return  array
     */
    private function get_post_ids( $post_type ) {
        $args = [
            'post_type'   => $post_type,
            'numberposts' => -1,
            'post_status' => 'publish',
            'fields'      => 'ids',
        ];

        $posts = get_posts( $args );

        return $posts;
    }

    /**
     * Import file
     *
     * @return  void
     */
    public function import_file() {
        $nonce      = isset( $_POST['etn_data_import_nonce'] ) ? sanitize_text_field( $_POST['etn_data_import_nonce'] ) : '';

        if ( ! wp_verify_nonce( $nonce, 'etn_data_import_action' ) ) {
            return;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $file       = isset( $_FILES['file'] ) ? $_FILES['file'] : '';
        $post_type  = isset( $_POST['post_type'] ) ? sanitize_text_field( $_POST['post_type'] ) : '';

        if ( ! $file ) {
            return new WP_Error( 'file_error', __( 'File can not be empty', 'eventin' ) );
        }

        $importer = Post_Importer::get_importer( $post_type );
        $importer->import( $file );

        wp_send_json_success( [
            'success' => 1,
            'message' => __( 'Successfully imported file', 'eventin' ),
        ] );
    }

    /**
     * Add flush rewrite rules after saving a post
     *
     * @param   integer  $pos_id
     *
     * @return  void
     */
    public function add_flush_rules( $pos_id ) {
        $post_type = ! empty( $_POST['post_type'] ) ? sanitize_text_field( $_POST['post_type'] ) : '';

        $post_types = [
            'etn', 
            'etn-schedule', 
            'etn-speaker', 
            'etn-attendee', 
            'etn-zoom-meeting',
        ];

        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        if ( ! in_array( $post_type, $post_types ) ) {
            return;
        }

        flush_rewrite_rules();
    }

    /**
     * Added settings
     *
     * @param   array  $settings
     *
     * @return  array
     */
    public function add_settings( $settings ) {
        $payment_method = etn_get_option( 'payment_method' );
        $sells_engine   = etn_get_option( 'etn_sells_engine_stripe' ) ?: 'woocommerce';
        $payment_method = $payment_method ? $payment_method : $sells_engine; 

        $new_settings = [
            'wc_enabled'         => function_exists( 'WC' ),
            'payment_method'     => $payment_method,
            'plugin_version'     => Wpeventin::version(),  
            'modules'            => get_option( 'etn_addons_options' ),
            'zoom_authorize_url' => ZoomCredential::get_auth_url(),
            'event_url_editable' => etn_event_url_editable(),
            'email'              => etn_get_email_settings(), 
            'etn_settings_country_currency' => etn_currency(),
            'decimal_separator'  => etn_get_decimal_separator(),
            'thousand_separator' => etn_get_thousand_separator(),
            'decimals'           => etn_get_decimals(),
            'price_format'       => etn_get_price_format(),
            'currency_position'  => etn_get_currency_position(), 
        ];

        return array_merge( $settings, $new_settings );
    }

    /**
     * Modify event edit link
     *
     * @param   string  $link
     * @param   integer  $post_id
     *
     * @return  string
     */
    public function modifiy_event_edit_link( $link, $post_id ) {
        $post_type = get_post_type( $post_id );

        if ( 'etn' !== $post_type ) {
            return $link;
        }

        $url = admin_url( "admin.php?page=eventin#/events/create/{$post_id}/basic" );

        return $url;
    }

    /**
     * Plugin upgrade warning notification
     *
     * @param   string  $current_version  Plugin current version
     * @param   string  $new_version      Plugin new version
     *
     * @return  void
     */
    public function version_update_warning( $current_version, $new_version ) {
        if ( version_compare( $current_version, $new_version, '>=',  ) ) {
            return;
        }

        ?>
            <hr class="e-major-update-warning__separator" />
            <div class="e-major-update-warning">
                <div class="e-major-update-warning__icon">
                    <i class="eicon-info-circle"></i>
                </div>
                <div>
                    <div class="e-major-update-warning__title">
                        <?php echo esc_html__('Heads up! Please backup before upgrading!', 'eventin'); ?>
                    </div>
                    <div class="e-major-update-warning__message">
                        <?php
                        printf(
                            esc_html__( 'Eventin 4.0, the latest version, includes major changes across different areas of the plugin. For a smooth transition, we strongly advise you to backup your site before upgrading and testing it in a staging environment first.', 'eventin' )
                        );
                        ?>
                    </div>
                </div>
            </div>
        <?php
    }

    public function migrate_speaker_organizer() {
        $installed_version = get_option( 'etn_version' );
        $upgrade_versions  = ['4.0.0'];

        if ( $installed_version && version_compare( $installed_version, end( $upgrade_versions ), '<' ) ) {
            return;
        }

        $args = [
            'post_type'      => 'etn',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
        ];
        $events = [];

        $post_query   = new \WP_Query();
        $query_result = $post_query->query( $args );

        foreach ( $query_result as $post ) {
            $event = new Event_Model( $post->ID );

            $this->migrate_event_speaker_organizer( $event );
        }
    }

    /**
     * Migrate event speaker and organizer
     *
     * @param   Event_Model  $event  [$event description]
     *
     * @return  void
     */
    protected function migrate_event_speaker_organizer( $event ) {
        $organizer = get_post_meta( $event->id, 'etn_event_organizer', true );
        $speaker   = get_post_meta( $event->id, 'etn_event_speaker', true );

        

        $speaker_category   = get_term_by( 'slug', 'speaker', 'etn_speaker_category' );
        $organizer_category = get_term_by( 'slug', 'organizer', 'etn_speaker_category' );

        if ( $speaker_category ) {
            $speaker_category = $speaker_category->term_id;
        }

        if ( $organizer_category ) {
            $organizer_category = $organizer_category->term_id;
        }

        if ( $organizer ) {
            $event->update( [
                'etn_event_organizer' => $this->prepare_organizer(),
                'organizer_type'      => 'group',
                'organizer_group'     => [$organizer_category],
            ] );
        }

        if ( $speaker ) {
            $event->update( [
                'etn_event_speaker' => $this->prepare_speaker(),
                'speaker_type'      => 'group',
                'speaker_group'     => [$speaker_category],
            ] );
        }
    }

    /**
     * Get organizer by term slug
     *
     * @return  array
     */
    protected function prepare_organizer() {
        $args = array(
            'numberposts'   => -1,
            'post_type'     => 'etn-speaker',
            'post_status'   => 'any',
            'fields'        => 'ids',
            
            'tax_query' => array(
                'relation' => 'AND',
                [
                    'taxonomy' => 'etn_speaker_category',
                    'field'    => 'slug',
                    'terms'    => 'organizer'
                ]
            )
        );

        $organizers = get_posts( $args );

        return $organizers;
    }

    /**
     * Get speaker by term slug
     *
     * @return  array
     */
    protected function prepare_speaker() {
        $args = array(
            'numberposts'   => -1,
            'post_type'     => 'etn-speaker',
            'post_status'   => 'any',
            'fields'        => 'ids',
            
            'tax_query' => array(
                'relation' => 'AND',
                [
                    'taxonomy' => 'etn_speaker_category',
                    'field'    => 'slug',
                    'terms'    => 'speaker'
                ]
            )
        );

        $speakers = get_posts( $args );

        return $speakers;
    }

    /**
     * Migration notice
     *
     * @return  void
     */
    public function migration_notice() {

        $is_migrated = get_option( 'etn_is_migrated' );

        if ( get_transient( 'etn_migration_success' ) ) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php _e('Migration completed successfully!', 'eventin' ); ?></p>
            </div>
            <?php
            // Delete the transient after displaying the message
            delete_transient( 'etn_migration_success' );
        }
        
        if ( $is_migrated ) {
            return;
        }  

        $ajaxurl = admin_url( 'admin-ajax.php' );
        $nonce   = wp_create_nonce( 'etn-migration-nonce' );

        ?>
            <div class="notice notice-warning">
                <p><?php _e('You didn\'t run the Eventin migration. Click the button below to run it.', 'eventin'); ?></p>
                <p><button id="etn-migrate-button" class="button button-primary"><?php _e('Run Eventin Migration', 'eventin'); ?></button></p>
            </div>

            <script>
                jQuery(document).ready(function($) {
                    $('#etn-migrate-button').on('click', function(e) {
                        e.preventDefault();

                        $.ajax( {
                            url: "<?php echo $ajaxurl; ?>",
                            type: 'POST',
                            data: {
                                action: 'etn_run_migration',
                                nonce: "<?php echo $nonce; ?>"
                            },
                            success: function(response) {
                                if ( response.success ) {
                                    location.reload();
                                } else {
                                    alert('Migration failed.');
                                }
                            },
                            error: function() {
                                alert('An error occurred while running the migration.');
                            }
                        } );
                    });
                });

            </script>
        <?php
    }

    /**
     * Run migration
     *
     * @return  void
     */
    public function run_migration() {
        $nonce = ! empty( $_POST['nonce'] ) ? $_POST['nonce'] : '';

        if ( ! wp_verify_nonce( $nonce, 'etn-migration-nonce' ) ) {
            return;
        }

        if ( ! current_user_can( 'manage_options' ) ) { 
            return; 
        }

        $is_migrated = get_option( 'etn_is_migrated' );

        if ( $is_migrated ) {
            return;
        } 
        
        Upgrade::register();

        set_transient( 'etn_migration_success', true, 60 );

        wp_send_json_success( array( 'message' => __( 'Migration completed successfully!', 'eventin' ) ) );

    }

    /**
     * Upgrade the plugin migration
     *
     * @return  void
     */
    public function do_upgrade() {
        $db_migration    = get_option( 'etn_db_migration' );
        $current_version = Wpeventin::version();

        if ( ! $db_migration || version_compare( $current_version, $db_migration, '>' ) ) {
            
            Upgrade::register();
            update_option( 'etn_db_migration', $current_version, true );
        }
    }

    /**
     * Save addons settings
     *
     * @return  void
     */
    public function save_addons() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $nonce = ! empty( $_POST['eventin-addons-page'] ) ? sanitize_text_field( $_POST['eventin-addons-page'] ) : '';

        if ( ! wp_verify_nonce( $nonce, 'eventin-addons-page' ) ) {
            return;
        }

        $post_arr = filter_input_array( INPUT_POST, FILTER_SANITIZE_SPECIAL_CHARS );

        update_option( 'etn_addons_options', $post_arr );
    }

    /**
	 * Include speaker group to user
	 * 
	 * @since 4.0.7
	 * return void
	 */
    public function etn_speaker_group_insert_to_user() {

        // Check if the 'Uncategorized' term exists in the 'etn_speaker_category' taxonomy
        $term = term_exists('Uncategorized', 'etn_speaker_category');

        // If the term doesn't exist, create it
        if ( ! $term ) {
            $term = wp_insert_term( 'Uncategorized', 'etn_speaker_category' );
        }
        // Get the term_id
        $term_id    = is_array( $term ) ? $term['term_id'] : '';
        $args       = array(
            'role__in' => array('etn-speaker', 'etn-organizer'),
            'meta_key' => 'etn_speaker_group',
            'meta_compare' => 'NOT EXISTS'
        );
    
        $users      = get_users( $args );
    
        if ( $users ) {
            foreach ( $users as $user ) {
                update_user_meta($user->ID, 'etn_speaker_group', $term_id, true);
            }

            // Determine the 'etn_speaker_category' value based on the user's role
            if ( in_array('etn-speaker', $user->roles ) ) {
                $category_value = ['speaker'];
            } elseif ( in_array('etn-organizer', $user->roles) ) {
                $category_value = ['organizer'];
            }

            // Update or add the 'etn_speaker_category' user meta
            update_user_meta($user->ID, 'etn_speaker_category', $category_value, true);
        }
        
    }

    /**
     * Update settins extra fields
     *
     * @param   array  $settings
     *
     * @return  array
     */
    public function update_extra_field_settings( $settings ) {
        $extra_fields = $extra_fields = etn_get_option( 'extra_fields', [] ) ?: etn_get_option( 'attendee_extra_fields', [] );

        $settings['extra_fields'] = $extra_fields;
        unset($settings['attendee_extra_fields']);

        return $settings;
    }
    
    /**
     * Update seat price when update event tickets
     *
     * @param   Event_Model  $event  [$event description]
     *
     * @return  void
     */
    public function update_seat_price( $event ) {
        $event_id          = $event->id;
        $tickets           = $event->etn_ticket_variations;
        $seats             = $event->seat_plan;

        if ( ! $seats ) {
            return;
        }

        foreach ( $seats as $seat_key => $seat ) {
            $ticket_price = $event->get_ticket_price_by_name( $seat['ticketType'] );

            if ( 'table' === $seat['type'] ) {
                $chairs = [];
                foreach( $seat['chairs'] as $chair_key => $chair ) {
                    $chair['price'] = $ticket_price;

                    $chairs[] = $chair;
                }
                $seats[$seat_key]['chairs'] = $chairs;
            } else {
                $seats[$seat_key]['price'] = $ticket_price;
            }
        }
    
        $event->update( [
            'seat_plan' => $seats
        ] );
    }

    /**
     * Update sold tickets on event clone
     *
     * @param   Event_Model  $event  [$event description]
     *
     * @return  void
     */
    public function update_clone_event_sold_tickets( $event ) {
        $tickets = $event->etn_ticket_variations;

        if ( is_array( $tickets ) ) {
            foreach( $tickets as &$ticket ) {
                $ticket['etn_sold_tickets'] = 0;
            }
        }
        
        $event->update([
            'etn_ticket_variations' => $tickets
        ]);
    }
}