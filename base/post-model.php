<?php
/**
 * Class Post Model
 *
 * @package Eventin
 */
namespace Etn\Base;

use Exception;
use WP_Query;

/**
 * Class Post Model
 */
abstract class Post_Model {
    /**
     * Store post type
     *
     * @var string
     */
    protected $post_type;

    /**
     * Store meta prefix
     *
     * @var string
     */
    protected $meta_prefix = '';

    /**
     * Store model data
     *
     * @var array
     */
    protected $data = [];

    /**
     * Store id
     *
     * @var integer
     */
    public $id;

    /**
     * Constructor for Post Model Class
     *
     * @return  void
     */
    public function __construct( $post = 0 ) {
        if ( $post instanceof self ) {
            $this->id = $post->id;
        } elseif ( ! empty( $post->id ) ) {
            $this->id = $post->id;
        } elseif ( is_numeric( $post ) && $post > 0 ) {
            $this->id = $post;
        }
    }

    /**
     * Get property of the object
     *
     * @param   string  $key
     *
     * @return  string
     */
    public function __get( $key ) {
        if ( ! isset( $this->data[$key] ) ) {
            throw new Exception( __( 'Undefined property', 'eventin' ) );
        }

        $data = $this->get_data();

        return $data[$key];
    }

    /**
     * Magic method for calling a instance method statically
     *
     * @param   string  $method       [$method description]
     * @param   array  $arguments  [$arguments description]
     *
     * @return  mixed
     */
    public static function __callStatic( $method, $arguments ) {
        if ( ! method_exists( new self, $method ) ) {
            throw new Exception( __( 'Call to undefined method', 'eventin' ) );
        }
        
        call_user_func( $method, $arguments );
    }

    /**
     * Create post
     *
     * @param   array  $args
     *
     * @return  mixed
     */
    public function create( $args = [] ) {
        $defaults = [
            'post_type'   => $this->post_type,
            'post_status' => 'draft',
            'post_author' => get_current_user_id(),
        ];

        $args    = wp_parse_args( $args, $defaults );
        $post_id = wp_insert_post( $args );

        if ( ! is_wp_error( $post_id ) ) {
            $this->id = $post_id;
            $this->update_meta( $args );

            return true;
        }

        return false;
    }

    /**
     * Update post
     *
     * @param   array  $args
     *
     * @return  bool
     */
    public function update( $args = [] ) {
        $defaults = [
            'ID'        => $this->id,
            'post_type' => $this->post_type,
        ];

        $args    = wp_parse_args( $args, $defaults );
        $post_id = wp_update_post( $args );

        if ( ! is_wp_error( $post_id ) ) {
            $this->id = $post_id;

            $this->update_meta( $args );

            return true;
        }

        return false;
    }

    /**
     * Delete post
     *
     * @return  bool
     */
    public function delete() {
        return wp_delete_post( $this->id );
    }

    /**
     * Update post meta
     *
     * @param   array  $data
     *
     * @return  void
     */
    public function update_meta( $data = [] ) {
        foreach ( $data as $key => $value ) {
            if ( array_key_exists( $key, $this->data ) ) {
                $meta_key = $this->meta_prefix . $key;
                update_post_meta( $this->id, $meta_key, $value );
            }
        }
    }

    /**
     * Assign post terms
     *
     * @param   Array  $terms
     *
     * @return mixed
     */
    public function assign_post_terms( $taxonomy, $new_terms = [] ) {
        // Update event categories.
        $terms = get_the_terms( $this->id, $taxonomy );
        $terms = $terms ? array_column( $terms, 'term_id' ) : [];

        if ( $terms ) {
            wp_remove_object_terms( $this->id, $terms, $taxonomy );
        }

        wp_set_post_terms( $this->id, $new_terms, $taxonomy, true );
    }

    /**
     * Get post term ids
     *
     * @param   string  $taxonomy  Taxonomy of the post
     *
     * @return  array   Return term ids
     */
    public function get_term_ids( $taxonomy ) {
        $terms = get_the_terms( $this->id, $taxonomy );
        $terms = $terms ? array_column( $terms, 'term_id' ) : [];

        return $terms;
    }

    /**
     * Get data
     *
     * @return  array
     */
    public function get_data() {
        $response_data = [
            'id' => $this->id,
        ];

        foreach ( $this->data as $key => $value ) {
            $meta_key = $this->meta_prefix . $key;

            $meta_value = get_post_meta( $this->id, $meta_key, true );

            $response_data[$key] = $meta_value;
        }

        return $response_data;
    }

    /**
     * Get all posts
     *
     * @param   array  $args
     *
     * @return  array
     */
    public function all( $args = [] ) {
        $defaults = [
            'post_type'      => $this->post_type,
            'post_status'    => 'publish',
            'posts_per_page' => 20,
            'paged'          => 1,
        ];

        $args = wp_parse_args( $args, $defaults );

        $query = new WP_Query( $args );

        return [
            'items' => $query->posts,
            'total' => $query->found_posts,
        ];
    }

    /**
     * Clone an object
     *
     * @param   integer  $id
     *
     * @return  Object
     */
    public function clone () {
        $post                 = get_post( $this->id );
        $data                 = $this->get_data();
        $data['post_title']   = $post->post_title;
        $data['post_content'] = $post->post_content;
        $data['post_status']  = $post->post_status;
        $data['is_clone'] = true;

        $this->create( $data );

        set_post_thumbnail( $this->id, get_post_meta( $this->id, 'event_banner_id', true ) );

        return $this;
    }
}
