<?php
/**
 * Template Api Class
 *
 * @package Eventin
 */
namespace Etn\Core\TemplateBuilder;

use Etn\Base\Api_Handler;
use Etn\Core\Attendee\Attendee_Model;
use Etn\Core\Event\Event_Model;
use Etn\Traits\Singleton;
use WP_Error;
use WP_REST_Response;

/**
 * Template api class
 */
class Api_Template extends Api_Handler {
    use Singleton;

    /**
     * Define prefix and parameter pattern
     *
     * @return  void
     */
    public function config() {
        $this->prefix = '';
        $this->param  = '';
    }

    /**
     * Template list api
     *
     * @return  JSON
     */
    public function get_templates() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return etn_permision_error();
        }

        $request  = $this->request;
        $per_page = ! empty( $request['per_page'] ) ? intval( $request['per_page'] ) : 20;
        $page     = ! empty( $request['page'] ) ? intval( $request['page'] ) : 1;
        $type     = ! empty( $request['type'] ) ? sanitize_text_field( $request['type'] ) : '';
        $status   = ! empty( $request['status'] ) ? sanitize_text_field( $request['status'] ) : '';

        $args = [
            'posts_per_page' => $per_page,
            'paged'          => $page,
        ];

        $query = [];

        if ( $type ) {
            $query[] = [
                'key'     => '_template_type',
                'value'   => $type,
                'compare' => '=',
            ];
        }

        if ( $status ) {
            $query[] = [
                'key'     => '_template_status',
                'value'   => $status,
                'compare' => '=',
            ];
        }

        if ( $query ) {
            $args['meta_query'] = $query;
        }

        $template = new Template_Model();

        $templates = $template->all( $args );

        $items = [];

        foreach ( $templates['items'] as $post ) {
            $items[] = $this->prepare_item_for_response( $post->ID );
        }

        return new WP_REST_Response( [
            'total' => $templates['total'],
            'items' => $items,
        ], 200 );
    }

    /**
     * Create template api
     *
     * @return  JSON
     */
    public function post_templates() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return etn_permision_error();
        }

        $request = $this->request;

        $name        = ! empty( $request['name'] ) ? sanitize_text_field( $request['name'] ) : '';
        $type        = ! empty( $request['type'] ) ? sanitize_text_field( $request['type'] ) : '';
        $status      = ! empty( $request['status'] ) ? sanitize_text_field( $request['status'] ) : '';
        $orientation = ! empty( $request['orientation'] ) ? sanitize_text_field( $request['orientation'] ) : '';
        $content     = ! empty( $request['content'] ) ? sanitize_text_field( $request['content'] ) : '';

        $validate = $this->validate( $request, [
            'name',
            'type',
            'status',
            'orientation',
            'content',
        ] );

        if ( is_wp_error( $validate ) ) {
            return new WP_REST_Response( [
                'status'   => 409,
                'meesages' => $validate->get_error_messages(),
            ], 409 );
        }

        $data = [
            'name'        => $name,
            'type'        => $type,
            'status'      => $status,
            'orientation' => $orientation,
            'content'     => $content,
        ];

        $template = new Template_Model();

        if ( $template->create( $data ) ) {
            return new WP_REST_Response( [
                'success' => true,
                'item'    => $this->prepare_item_for_response( $template->id ),
            ], 200 );
        }

        return new WP_REST_Response( [
            'success' => false,
            'message' => __( 'Couldn\'t create template', 'eventin' ),
        ], 409 );
    }

    /**
     * Get single template
     *
     * @return  JSON
     */
    public function get_template() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return etn_permision_error();
        }

        $request = $this->request;

        $id = ! empty( $request['id'] ) ? intval( $request['id'] ) : 0;

        $template = new Template_Model( $id );

        return new WP_REST_Response( $template->get_data(), 200 );
    }

    /**
     * Update template
     *
     * @return  JSON
     */
    public function put_templates() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return etn_permision_error();
        }

        $request = $this->request;

        // return $request['name'];

        $id          = ! empty( $request['id'] ) ? sanitize_text_field( $request['id'] ) : '';
        $name        = ! empty( $request['name'] ) ? sanitize_text_field( $request['name'] ) : '';
        $type        = ! empty( $request['type'] ) ? sanitize_text_field( $request['type'] ) : '';
        $status      = ! empty( $request['status'] ) ? sanitize_text_field( $request['status'] ) : '';
        $orientation = ! empty( $request['orientation'] ) ? sanitize_text_field( $request['orientation'] ) : '';
        $content     = ! empty( $request['content'] ) ? sanitize_text_field( $request['content'] ) : '';

        $validate = $this->validate( $request, [
            'name',
            'type',
            'status',
            'orientation',
            'content',
        ] );

        if ( is_wp_error( $validate ) ) {
            return new WP_REST_Response( [
                'status'   => 409,
                'meesages' => $validate->get_error_messages(),
            ], 409 );
        }

        $data = [
            'name'        => $name,
            'type'        => $type,
            'status'      => $status,
            'orientation' => $orientation,
            'content'     => $content,
        ];

        $template = new Template_Model( $id );

        if ( $template->update( $data ) ) {
            return new WP_REST_Response( [
                'success' => true,
                'item'    => $this->prepare_item_for_response( $template->id ),
            ], 200 );
        }

        return new WP_REST_Response( [
            'success' => false,
            'message' => __( 'Couldn\'t update template', 'eventin' ),
        ], 409 );
    }

    /**
     * Delete template
     *
     * @return  JSON
     */
    public function delete_templates() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return etn_permision_error();
        }

        $request = $this->request;

        $id = ! empty( $request['id'] ) ? intval( $request['id'] ) : 0;

        if ( $this->delete( $id ) ) {
            return new WP_REST_Response( [
                'message' => __( 'Successfully deleted template', 'eventin' ),
            ], 200 );
        }

        return new WP_REST_Response( [
            'message' => __( 'Couldn\'t  delete template. Please try again', 'eventin' ),
        ], 409 );
    }

    /**
     * Delete template
     *
     * @return  JSON
     */
    public function delete_bulk_templates() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return etn_permision_error();
        }

        $request = $this->request;

        $ids     = ! empty( $request['ids'] ) ? $request['ids'] : 0;
        $counter = 0;

        foreach ( $ids as $id ) {
            if ( $this->delete( $id ) ) {
                $counter++;
            }
        }

        if ( $counter == 0 ) {
            return new WP_REST_Response( [
                'message' => __( 'Couldn\'t  delete template. Please try again', 'eventin' ),
            ], 409 );
        }

        return new WP_REST_Response( [
            'message' => sprintf( __( 'Successfully deleted %s of %s template', 'eventin' ), $counter, count( $ids ) ),
        ], 200 );

    }

    /**
     * Assign certificate template
     *
     * @return  WP_JSON
     */
    public function get_assign_certificate_template() {
        $request     = $this->request;
        $event_id    = ! empty( $request['event_id'] ) ? intval( $request['event_id'] ) : 0;
        $template_id = ! empty( $request['template_id'] ) ? intval( $request['template_id'] ) : 0;

        $event = new Event_Model( $event_id );

        $updated = $event->update( [
            'certificate_template_id' => $template_id,
        ] );

        if ( ! $updated ) {
            return new WP_Error( 'certificate_error', __( 'Cound not assign certificate template. Please try again latter !', 'eventin' ), ['status' => 409] );
        }

        $data = [
            'success' => 1,
            'message' => __( 'Successfully assigned template', 'eventin' ),
        ];

        return rest_ensure_response( $data );
    }

    /**
     * Assign ticket template
     *
     * @return  WP_JSON
     */
    public function get_assign_ticket_template() {
        $request     = $this->request;
        $event_id    = ! empty( $request['event_id'] ) ? intval( $request['event_id'] ) : 0;
        $template_id = ! empty( $request['template_id'] ) ? intval( $request['template_id'] ) : 0;

        $event = new Event_Model( $event_id );

        $updated = $event->update( [
            'ticket_template_id' => $template_id,
        ] );

        if ( ! $updated ) {
            return new WP_Error( 'ticket_error', __( 'Cound not assigned ticket template. Please try again latter !', 'eventin' ), ['status' => 409] );
        }

        $data = [
            'success' => 1,
            'message' => __( 'Successfully assigned template', 'eventin' ),
        ];

        return rest_ensure_response( $data );
    }

    /**
     * Send certificate template
     *
     * @return  WP_JSON
     */
    public function get_send_certificate_template() {
        $request  = $this->request;
        $event_id = ! empty( $request['event_id'] ) ? intval( $request['event_id'] ) : 0;

        if ( ! $event_id ) {
            return new WP_Error( 'event_id_error', __( 'Event id can not be empty', 'eventin' ), ['status' => 409] );
        }

        $args = [
            'meta_query' => [
                [
                    'key'     => 'etn_event_id',
                    'value'   => $event_id,
                    'compare' => '=',
                ],
            ],
        ];

        $attendee = new Attendee_Model();

        $attendees = $attendee->all( $args );

        foreach ( $attendees['items'] as $attendee_id ) {
            $attendee = new Attendee_Model( $attendee_id );
            $url      = site_url();
            $url      = add_query_arg( [
                'event_id'    => $event_id,
                'attendee_id' => $attendee->id,
            ], $url );

            $content = sprintf( 'Hi, %s please download your certificate from heere. <a href="%s">%s</a>', $attendee->etn_name, $url, $url );

            if ( ! empty( $attendee->etn_email ) ) {
                wp_mail( $attendee->etn_email, $content );
            }
        }

        $data = [
            'success' => 1,
            'message' => __( 'Successfully send certificate', 'eventin' ),
        ];

        return rest_ensure_response( $data );
    }

    /**
     * Send certificate template
     *
     * @return  WP_JSON
     */
    public function get_send_ticket_template() {
        $request  = $this->request;
        $event_id = ! empty( $request['event_id'] ) ? intval( $request['event_id'] ) : 0;

        if ( ! $event_id ) {
            return new WP_Error( 'event_id_error', __( 'Event id can not be empty', 'eventin' ), ['status' => 409] );
        }

        $args = [
            'meta_query' => [
                [
                    'key'     => 'etn_event_id',
                    'value'   => $event_id,
                    'compare' => '=',
                ],
            ],
        ];

        $attendee = new Attendee_Model();

        $attendees = $attendee->all( $args );

        foreach ( $attendees['items'] as $attendee_id ) {
            $attendee = new Attendee_Model( $attendee_id );
            $url      = site_url();
            $url      = add_query_arg( [
                'event_id'    => $event_id,
                'attendee_id' => $attendee->id,
            ], $url );

            $content = sprintf( 'Hi, %s please download your certificate from heere. <a href="%s">%s</a>', $attendee->etn_name, $url, $url );

            if ( ! empty( $attendee->etn_email ) ) {
                wp_mail( $attendee->etn_email, $content );
            }
        }

        $data = [
            'success' => 1,
            'message' => __( 'Successfully send ticket', 'eventin' ),
        ];

        return rest_ensure_response( $data );

    }

    /**
     * Prepre item for response
     *
     * @param   integer  $post_id
     *
     * @return  array
     */
    private function prepare_item_for_response( $post_id ) {
        $template = new Template_Model( $post_id );

        return $template->get_data();
    }

    /**
     * Validate request data
     *
     * @param   array  $data
     * @param   array  $rules
     *
     * @return  WP_Error
     */
    private function validate( $data, $rules ) {
        $error = new WP_Error();

        foreach ( $rules as $rule ) {
            if ( empty( $data[$rule] ) ) {
                $error->add( $rule, __( ucfirst( $rule ) . ' can not be empty !', 'eventin' ) );
            }
        }

        if ( $error->has_errors() ) {
            return $error;
        }

        return true;
    }

    /**
     * Delete template
     *
     * @param   integer  $template_id  [$template_id description]
     *
     * @return
     */
    private function delete( $template_id ) {
        $template = new Template_Model( $template_id );

        return $template->delete();
    }
}