<?php
/**
 * Speaker Categories
 *
 * @package Eventin
 */

namespace Etn\Core\Speaker;

use Etn\Base\Api_Handler;
use WP_Error;

class ApiTaxonomy extends Api_Handler {
    /**
     * Define prefix and parameter pattern
     *
     * @return  void
     */
    public function config() {
        $this->prefix = 'speaker-category';
        $this->param  = '';
    }

    /**
     * Create term
     *
     * @return array
     */
    public function post_term() {
        return $this->save_term();
    }

    /**
     * Get all terms for the speaker category
     *
     * @return  array
     */
    public function get_terms() {
        $terms = get_terms( [
            'taxonomy'   => 'etn_speaker_category',
            'hide_empty' => false,
        ] );

        $terms_data = [];
        foreach ( $terms as $term ) {
            $terms_data[] = $this->prepare_term( $term );
        }

        return rest_ensure_response( $terms_data );
    }

    /**
     * Prepare term for the response
     *
     * @param   Object  $item  Term object
     *
     * @return array  Term
     */
    public function prepare_term( $item ) {
        $term = [
            'id'          => $item->term_id,
            'name'        => $item->name,
            'slug'        => $item->slug,
            'count'       => $item->count,
            'taxonomy'    => $item->taxonomy,
            'description' => $item->description,
        ];

        return $term;
    }

    /**
     * Update term
     *
     * @return  Object Update the term
     */
    public function put_term() {
        return $this->save_term();
    }

    /**
     * Save term
     *
     * @return  Object  Term Object
     */
    public function save_term() {
        $request = $this->request;

        $name = ! empty( $request->get_param( 'name' ) ) ? sanitize_text_field( $request->get_param( 'name' ) ) : '';
        $id = ! empty( $request->get_param( 'id' ) ) ? intval( $request->get_param( 'id' ) ) : 0;

        if ( ! $name ) {
            return new WP_Error( 'name_error', __( 'Term name can\'t be empty.', 'eventin' ) );
        }

        if ( $id ) {
            $term = wp_update_term( $id, 'etn_speaker_category', [
                'name'  => $name 
            ] );
        } else {
            $term = wp_insert_term( $name, 'etn_speaker_category' );
        }

        if ( is_wp_error( $term ) ) {
            return [
                'message'     => $term->get_error_message(),
                'status_code' => 304,
            ];
        }
        
        $term_object = get_term( $term['term_id']);

        return rest_ensure_response( $this->prepare_term( $term_object ) );
    }

    /**
     * Delete terms
     *
     * @return  WP_Error | array
     */
    public function delete_terms() {
        $request = $this->request;

        $ids = ! empty( $request->get_param( 'ids' ) ) ? explode( ',', $request->get_param( 'ids' ) ) : [];

        foreach ( $ids as $term_id ) {
            $term_deleted = wp_delete_term( $term_id, 'etn_speaker_category' );

            if ( is_wp_error( $term_deleted ) ) {
                return $term_deleted->get_error_message();
            }
        }

        $data = [
            'message'     => __( 'Category deleted successfuly !', 'eventin' ),
            'status_code' => 200,
        ];

        return rest_ensure_response( $data );
    }
}

new ApiTaxonomy();
