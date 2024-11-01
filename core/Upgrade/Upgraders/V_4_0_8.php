<?php
/**
 * Updater for version 4.0.8
 *
 * @package Eventin\Upgrade
 */
namespace Eventin\Upgrade\Upgraders;

use Etn\Core\Event\Event_Model;

/**
 * Updater class for v4.0.8
 *
 * @since 4.0.8
 */
class V_4_0_8 implements UpdateInterface {
    /**
     * Run the updater
     *
     * @return  void
     */
    public function run() {
        $this->migrate_speaker_post_type();

        // This function must run after speaker migration.
        $this->migrate_schedule_speaker();

        // Migrate event speakers post to users.
        $this->migrate_event_organizer_speaker();
    }

/*
* @since 4.0.8
*
* @var array $data
*/
public $data = [
    'user_login'        => '',
    'user_name'         => '',
    'user_email'        => '',
    'phone'             => '',
    'designation'       => '',
    'category'          => [],
    'speaker_group'     => [],
    'company_name'      => '',
    'summary'           => '',
    'summery'           => '',
    'image'             => '',
    'image_id'          => '',
    'company_logo'      => '',
    'company_logo_id'   => '',
    'social'            => [],
    'date'              => ''
    ];


    /*
    * @since 4.0.8
    *
    * @var string $speaker prefix
    */
    public $speaker_prefix = 'etn_speaker_';              
    /*
    * @since 1.0.1
    *
    * @var int $id
    */
    public $id;

    /*
    * @since 1.0.1
    *
    * return void
    *
    */
    public function set_id( $id ) {
        $this->id = $id;
    }

    /**
     * Update speaker user data.
     *
     * @since 1.0.1
     *
     * @return  void
     */
    private function save_userdata( $args = [] ) {
        
        foreach ( $args as $key => $value ) {
    
            if ( $key == 'first_name' || $key == 'last_name' ) {
                $meta_key = $key;
            } else {
                $meta_key =  $key;
            }        
        
            // Update user meta data
            update_user_meta( $this->id, $meta_key, $value );
        }
    }
    

    /**
     * Update speaker user data.
     *
     * @since 1.0.1
     *
     * @return  array
     */
    private function get_data( $id ) {
        if ( $id ) {
            $thumbnail_id       = get_post_thumbnail_id( $id );
            $src                = wp_get_attachment_image_src( $thumbnail_id, 'full' );
            $image              = isset( $src[0] ) ? esc_url( $src[0] ) : '';
            $company_logo       = get_post_meta( $id, 'etn_speaker_company_logo', true );
            $company_img        = wp_get_attachment_image_src( $company_logo, 'full' );
            $company_logo       = isset( $company_img[0] ) ? esc_url( $company_img[0] ) : '';

            return [
                'user_name'                 => get_post_meta( $id, 'etn_speaker_title', true ),
                'user_email'                => get_post_meta( $id, 'etn_speaker_website_email', true ),
                'etn_speaker_website_email' => get_post_meta( $id, 'etn_speaker_website_email', true ),
                'first_name'                => get_post_meta( $id, 'etn_speaker_title', true ),
                'user_login'                => get_post_meta( $id, 'etn_speaker_title', true ),
                'etn_speaker_title'         => get_post_meta( $id, 'etn_speaker_title', true ),
                'phone'                     => get_post_meta( $id, 'phone', true ),
                'etn_speaker_designation'   => get_post_meta( $id, 'etn_speaker_designation', true ),
                'etn_speaker_group'         => get_post_meta( $id, 'etn_speaker_group', true ),
                'etn_company_name'          => get_post_meta( $id, 'etn_company_name', true ),
                'etn_speaker_summery'       => get_post_meta( $id, 'etn_speaker_summery', true ),
                'image'                     => $image ,
                'image_id'                  => get_post_meta( $id, 'image_id', true ),
                'etn_speaker_company_logo'  => $company_logo,
                'etn_company_logo_id'       => get_post_meta( $id, 'etn_company_logo_id', true ),
                'etn_speaker_url'           => get_post_meta( $id, 'etn_speaker_url', true ),
                'company_url'               => get_post_meta( $id, 'etn_speaker_url', true ),
                'etn_speaker_social'        => get_post_meta( $id, 'etn_speaker_socials', true ),
                'date'                      => get_post_meta( $id, 'date', true ),
            ];
        }
    }


    /**
     * Migrate speaker post type to users
     *
     * @return void
     */
    public function migrate_speaker_post_type() {

        $args = [
            'post_type'      => 'etn-speaker',
            'post_status'    => 'any',
            'posts_per_page' => -1
        ];

        $speakers = get_posts( $args );

        foreach ( $speakers as $speaker ) {

            $user_data = $this->get_data( $speaker->ID ); 

            if ( ! $user_data['user_email'] && ! $user_data['user_login'] ) {
                return;
            }

            $term_obj_list           = get_the_terms( $speaker->ID, 'etn_speaker_category' );
            $user_data['user_pass']  = wp_generate_password( );

            if ( ! empty( $term_obj_list ) && is_array( $term_obj_list ) ) {
                $user_data['etn_speaker_group'] = wp_list_pluck( $term_obj_list, 'term_id' );
            }

            // Assign roll.
            $user_data['role']                 = 'etn-speaker';
            $user_data['etn_speaker_category'] = [ 'speaker' ];

            // Check if a user with the same username or email already exists
            $existing_user_name     = get_user_by('login', $user_data['user_login']);
            $existing_user_email    = get_user_by('email', $user_data['user_email']);

            // If either the username or email already exists, skip insertion
            if ( $existing_user_name || $existing_user_email ) {
                continue;
            }

            // Insert user.
            $user = wp_insert_user( $user_data );

            if ( ! is_wp_error( $user ) ) {
                $user = get_userdata( $user );

                // Add the assigned roles.
                $user->add_role( 'etn-speaker' );

                $author_url = get_author_posts_url( $user->ID, $user->user_nicename );
            
                // Add the author URL to the user_data array
                $user_data['author_url'] = $author_url;
                $user_data['etn_speaker_post_id'] = $speaker->ID;
            
                $this->set_id( $user->ID );
                $this->save_userdata( $user_data );
            }   
        }
    }

    /**
     * Migrate schedule speaker with user speaker
     *
     * @return  void
     */
    public function migrate_schedule_speaker() {
        $args = [
            'post_type'         => 'etn-schedule',
            'post_status'       => 'any',
            'posts_per_page'    => -1,
        ];

        $schedules = get_posts( $args );

        if ( ! $schedules ) {
            return;
        }

        foreach( $schedules as $schedule ) {
            $topics         = get_post_meta( $schedule->ID, 'etn_schedule_topics', true);
            $updated_topics = $this->prepare_topics_speaker( $topics );

            // Update Schedule topics with updated user speaker.
            update_post_meta( $schedule->ID, 'etn_schedule_topics',  $updated_topics );
        }
    }

    /**
     * Prepare for shedule topics modifying with user speaker
     *
     * @param   array  $topics  [$topics description]
     *
     * @return  array           [return description]
     */
    private function prepare_topics_speaker( $topics ) {
        if ( ! $topics ) {
            return;
        }

        $new_topics  = [];

        foreach( $topics as $topic ) {
            $old_speakers  = ! empty( $topic['etn_shedule_speaker'] ) ? $topic['etn_shedule_speaker'] : [];

            $speakers  = ! empty( $topic['speakers'] ) ? $topic['speakers'] : [];

            if ( empty( $speakers ) ) {
                $speakers = $old_speakers;
            }

            $speaker_ids = [];

            foreach( $speakers as $speaker ) {
                $speaker_ids[] = $this->get_user_by_speaker( $speaker );
            }

            if ( is_array( $topic ) ) {
                $topic['speakers'] = $speaker_ids;
            }

            $new_topics[] = $topic;
        }

        return $new_topics;
    }

    /**
     * Get user id by speaker post id
     *
     * @param   integer  $speaker_id  
     *
     * @return  integer     
     */
    private function get_user_by_speaker( $speaker_id ) {
        $args = array(
            'fields'     => 'ID',
            'number'     => -1,
            'meta_query' => array(
                array(
                    'key'     => 'etn_speaker_post_id', 
                    'value'   => $speaker_id,
                    'compare' => '=',
                ),
            ),
        );
    
        $user_ids = get_users( $args );

        if ( $user_ids ) {
            return intval( $user_ids[0] );
        }
    }

    /**
     * Get user ids by group id
     *
     * @param   array  $groups  User group
     *
     * @return  array
     */
    private function get_user_by_group( $groups ) {
        $user_ids = [];

        if ( ! $groups && ! is_array( $groups ) ) {
            return $user_ids;
        }

        foreach( $groups as $group_id ) {
            $args = array(
                'fields'     => 'ID',
                'number'     => -1,
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key'     => 'etn_speaker_group',
                        'value'   => strval( $group_id ),
                        'compare' => 'LIKE',
                    ),
                ),
            );

            $user_ids = array_merge( $user_ids, get_users( $args ) );

            $user_ids = array_unique( $user_ids );
        }

        return $user_ids;
    }

    public function migrate_event_organizer_speaker() {
        $args = [
            'post_type'         => 'etn',
            'post_status'       => 'any',
            'posts_per_page'    => -1,
            'fields'            => 'ids'
        ];

        $events = get_posts( $args );

        foreach ( $events as $event_id ) {
            $speaker_type   = get_post_meta( $event_id, 'speaker_type', true );
            $organizer_type = get_post_meta( $event_id, 'organizer_type', true );
            $speaker_ids    = [];
            $organizer_ids  = [];

            if ( 'group' === $speaker_type ) {
                $speaker_ids = $this->get_event_group_speaker( $event_id );
            } else {
                $speaker_ids = $this->get_event_single_speaker( $event_id );
            }

            if ( 'group' === $organizer_type ) {
                $organizer_ids = $this->get_event_group_organizer( $event_id );
            } else {
                $organizer_ids = $this->get_event_single_organizer( $event_id );
            }

            update_post_meta( $event_id, 'etn_event_speaker', $speaker_ids );
            update_post_meta( $event_id, 'etn_event_organizer', $organizer_ids );
        }
    }

    /**
     * Get user ids by speaker id
     *
     * @param   integer  $event_id
     *
     * @return  array
     */
    private function get_event_single_speaker( $event_id ) {
        $speaker_ids = get_post_meta( $event_id, 'etn_event_speaker', true );

        $user_ids = [];

        if ( $speaker_ids && is_array( $speaker_ids ) ) {

            foreach( $speaker_ids as $speaker_id ) {
                $user_ids[] = $this->get_user_by_speaker( $speaker_id );
            }
        }

        return $user_ids;
    }

    /**
     * Get event group speakers
     *
     * @param   integer  $event_id
     *
     * @return  array
     */
    private function get_event_group_speaker( $event_id ) {
        $groups = get_post_meta( $event_id, 'speaker_group', true );

        return $this->get_user_by_group( $groups );
    }

    /**
     * Get user ids by organizer id
     *
     * @param   integer  $event_id
     *
     * @return  array
     */
    private function get_event_single_organizer( $event_id ) {
        $organizer_ids = get_post_meta( $event_id, 'etn_event_organizer', true );

        $user_ids = [];

        if ( $organizer_ids && is_array( $organizer_ids ) ) {

            foreach( $organizer_ids as $organizer_id ) {
                $user_ids[] = $this->get_user_by_speaker( $organizer_id );
            }
        }

        return $user_ids;
    }

    /**
     * Get organizers by group
     *
     * @param   integer  $event_id  Event id that need to be convert
     *
     * @return  array
     */
    private function get_event_group_organizer( $event_id ) {
        $groups = get_post_meta( $event_id, 'organizer_group', true );

        return $this->get_user_by_group( $groups );
    }
}