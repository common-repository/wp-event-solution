<?php
namespace Etn\Core\Speaker;


use WP_Error;
use WP_Query;

defined( 'ABSPATH' ) || exit;

/**
 * Class Spearcker Api
 */
class Api extends \Etn\Base\Api_Handler {
    /**
     * Define prefix and parameter pattern
     *
     * @return  void
     */
    public function config() {
        $this->prefix = 'speaker';
        $this->param  = '';
    }

    /**
     * Create speaker route
     *
     * @return  Array  [return description]
     */
    public function post_speakers() {
        return $this->save_post();
    }

    /**
     * Get a certain speaker by id
     *
     * @return  Object  A certain object
     */
    public function get_speaker( $post_id ) {
        $post = get_post( $post_id );

        if ( ! $post_id ) {
            return new WP_Error( 'invalid_post_id', __( 'Please enter a valid post id.', 'eventin' ) );
        }

        if ( ! $post ) {
            return new WP_Error( 'not_found_post', __( 'No speaker/organization found', 'eventin' ) );
        }

        if ( 'etn-speaker' != $post->post_type ) {
            return new WP_Error( 'post_type_error', __( 'No spear/organizer found for thid id', 'eventin' ) );
        }

        return rest_ensure_response( $this->prepare_speaker( $post_id ) );
    }

    /**
     * Get all speakers
     *
     * @return  Array  All speakers
     */
    public function get_speakers() {
        $request        = $this->request;
        $posts_per_page = isset( $request['posts_per_page'] ) ? intval( $request['posts_per_page'] ) : 20;
        $paged          = isset( $request['paged'] ) ? intval( $request['paged'] ) : 1;
        $category       = isset( $request['category'] ) ? sanitize_text_field( $request['category'] ) : '';
        $user_id        = isset( $request['user_id'] ) ? intval( $request['user_id'] ) : 0;
        $post_id        = isset( $request['id'] ) ? $request['id'] : false;
        $group_id       = isset( $request['group_id'] ) ? intval( $request['group_id'] ) : 0;

        if ( $post_id ) {
            return $this->get_speaker( $post_id );
        }

        // Fetch speakers and organizezr.
        $args = [
            'post_type'      => 'etn-speaker',
            'posts_per_page' => $posts_per_page,
            'paged'          => $paged,
            'post_status'    => 'publish',
        ];

        if( $category ) {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'etn_speaker_category',
                    'field'    => 'slug',
                    'terms'    => $category,
                ],
            ];
        }
        
        if ( $group_id ) {
            $args['meta_query'] = [
                [
                    'key'   =>  'etn_bp_group_id',
                    'value' =>  $group_id,
                ]
            ]; 
        }

        if ( $user_id ) {
            $args['author'] = $user_id;
        }

        $items = new WP_Query( $args );

        $items_data = [];

        if ( $items->posts ) {
            foreach ( $items->posts as $item ) {
                $items_data[] = $this->prepare_speaker( $item->ID );
            }
        }

        $data = [
            'total_pages' => $items->max_num_pages,
            'total_items' => $items->found_posts,
            'items'       => $items_data,
        ];

        return rest_ensure_response( $data );
    }

    /**
     * Prepare speaker Object
     *
     * @param   Object  $item  Speaker Object
     *
     * @return  array  Speaker Object
     */
    public function prepare_speaker( $post_id ) {

        $company_logo_id = get_post_meta( $post_id, 'etn_speaker_company_logo', true );
        $company_logo_id = ! empty( $company_logo_id ) ? $company_logo_id : 0;
        $permalink       = get_permalink( $post_id );
        $speaker_image   = wp_get_attachment_url( get_post_thumbnail_id( $post_id ) );

        $speaker = [
            'id'               => $post_id,
            'name'             => get_post_meta( $post_id, 'etn_speaker_title', true ),
            'designation'      => get_post_meta( $post_id, 'etn_speaker_designation', true ),
            'email'            => get_post_meta( $post_id, 'etn_speaker_website_email', true ),
            'summary'          => get_post_meta( $post_id, 'etn_speaker_summery', true ),
            'socials'          => get_post_meta( $post_id, 'etn_speaker_socials', true ),
            'company_url'      => get_post_meta( $post_id, 'etn_speaker_url', true ),
            'company_logo'     => $company_logo_id,
            'company_logo_url' => wp_get_attachment_image_url( $company_logo_id ),
            'perma_link'       => $permalink,
            'image'            => $speaker_image,
            'thumbnail_id'     => get_post_thumbnail_id( $post_id ),
            'category'         => wp_get_object_terms( $post_id, 'etn_speaker_category' ),  
        ];

        return $speaker;
    }

    /**
     * Update speaker
     *
     * @return  Object  JSON Objet for updated speaker
     */
    public function put_speakers() {
        if ( ! current_user_can( 'edit_posts' ) ) {
            return new WP_Error( 'unauthorized', __( 'Unauthorized user. Sorry you are not allowed to do that', 'eventin' ), [ 'status' => 403 ] );
        }

        return $this->save_post();
    }

    /**
     * Delete speaker
     *
     * @return  Object  A success object
     */
    public function delete_speakers() {
        if ( ! current_user_can( 'delete_posts' ) ) {
            return new WP_Error( 'unauthorized', __( 'Unauthorized user. Sorry you are not allowed to do that', 'eventin' ), [ 'status' => 403 ] );
        }
        
        $request  = $this->request;
        $post_ids = isset( $request['ids'] ) ? explode( ',', $request['ids'] ) : [];

        if ( empty( $post_ids ) ) {
            return [
                'status_code' => 403,
                'message'     => __( 'Speaker or organizer id required.', 'eventin' ),
            ];
        }

        foreach ( $post_ids as $post_id ) {

            $post    = get_post( intval( $post_id ) );
            $user_id = get_current_user_id();

            if ( ! $post ) {
                return new WP_Error( 'not_found_error', __( 'Not found.', 'eventin' ) );
            }

            if ( 'etn-speaker' != $post->post_type ) {
                return new WP_Error( 'permission_error', __( 'You are not allowed to delete this speaker/organizer', 'eventin' ) );
            }

            if ( is_wp_error( wp_delete_post( $post_id, true ) ) ) {
                return [
                    'status_code' => 403,
                    'message'     => __( 'Failed to delete one or more speaker/organizer. Please try again', 'eventin' ),
                ];
            }
        }

        return [
            'status_code' => 200,
            'message'     => __( 'Deleted successfully.', 'eventin' ),
        ];
    }

    /**
     * Create or update new speaker
     *
     * @return  Object  JSON Object as api response
     */
    private function save_post() {
        $request = $this->request;
        $speaker = json_decode( $request->get_body() );

        $id           = ! empty( $speaker->id ) ? wp_unslash( intval( $speaker->id ) ) : false;
        $name         = ! empty( $speaker->name ) ? wp_unslash( sanitize_text_field( $speaker->name ) ) : '';
        $designation  = ! empty( $speaker->designation ) ? wp_unslash( sanitize_text_field( $speaker->designation ) ) : '';
        $email        = ! empty( $speaker->email ) ? wp_unslash( sanitize_email( $speaker->email ) ) : '';
        $summary      = ! empty( $speaker->summary ) ? wp_unslash( sanitize_text_field( $speaker->summary ) ) : '';
        $company_url  = ! empty( $speaker->company_url ) ? wp_unslash( sanitize_text_field( $speaker->company_url ) ) : '';
        $category     = ! empty( $speaker->category ) ? explode( ',', $speaker->category ) : 'speaker';
        $socials      = ! empty( $speaker->socials ) ? $speaker->socials : [];
        $company_logo = ! empty( $speaker->company_logo ) ? $speaker->company_logo : 0;
        $user_id      = ! empty( $speaker->user_id ) ? $speaker->user_id : 0;
        $thumbnail_id = ! empty( $speaker->thumbnail_id ) ? intval( $speaker->thumbnail_id ) : 0;
        $group_id     = ! empty( $speaker->group_id ) ? $speaker->group_id : 0;

        // Prepare social items.
        $social_item = [];
        if ( $socials ) {
            foreach( $socials as $social ) {
                $social_item[] = (array) $social;
            }
        }

        // Validate name field
        if ( ! $name ) {
            return new WP_Error( 'speaker_name_error', __( 'Name field is required', 'eventin' ) );
        }

        if ( $id ) {
            // Create new post.
            $post_id = wp_update_post( [
                'ID'                => $id,
                'etn_speaker_title' => $name,
                'post_status'       => 'publish',
                'post_type'         => 'etn-speaker',
                'post_author'       => $user_id,
            ] );
        } else {
            // Create new post.
            $post_id = wp_insert_post( [
                'etn_speaker_title' => $name,
                'post_type'         => 'etn-speaker',
                'post_status'       => 'publish',
                'post_author'       => $user_id,
            ] );
        }

    

        // Set speaker terms
        wp_set_post_terms( $post_id, $category, 'etn_speaker_category' );

        // Prepare meta field.
        $meta_fields = [
            'etn_speaker_title'         => $name,
            'etn_speaker_designation'   => $designation,
            'etn_speaker_website_email' => $email,
            'etn_speaker_summery'       => $summary,
            'etn_speaker_socials'       => $social_item,
            'etn_speaker_company_logo'  => $company_logo,
            'etn_speaker_url'           => $company_url,
            '_thumbnail_id'             => $thumbnail_id,
        ];

        if ( $group_id ) {
            $meta_fields['etn_bp_group_id'] = $group_id;
        }

        // Update meta field.
        foreach ( $meta_fields as $key => $value ) {
            update_post_meta( $post_id, $key, $value );
        }

        // Prepare response
        return rest_ensure_response( $this->prepare_speaker( $post_id ) );
    }
}

/**
 * Instantiate the Api class
 */
new Api();
