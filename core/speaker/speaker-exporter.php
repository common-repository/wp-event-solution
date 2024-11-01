<?php
/**
 * Speaker Exporter Class
 *
 * @package Eventin
 */
namespace Etn\Core\Speaker;

use Etn\Base\Exporter\Exporter_Factory;
use Etn\Base\Exporter\Post_Exporter_Interface;

/**
 * Class Speaker Exporter
 *
 * Export Speaker Data
 */
class Speaker_Exporter implements Post_Exporter_Interface {
    /**
     * Store file name
     *
     * @var string
     */
    private $file_name = 'speaker-data';

    /**
     * Store attendee data
     *
     * @var array
     */
    private $data;

    /**
     * Store format
     *
     * @var string
     */
    private $format;

    /**
     * Export attendee data
     *
     * @return void
     */
    public function export( $data, $format ) {
        $this->data   = $data;
        $this->format = $format;

        $rows      = $this->prepare_data();
        $columns   = $this->get_columns();
        $file_name = $this->file_name;

        try {
            $exporter = Exporter_Factory::get_exporter( $format );

            $exporter->export( $rows, $columns, $file_name );
        } catch(\Exception $e) {
            return new \WP_Error( 'export_error', $e->getMessage(), ['status' => 409] );
        }
    }

    /**
     * Prepare data to export
     *
     * @return  array
     */
    private function prepare_data() {
        $ids           = $this->data;
        $exported_data = [];

        foreach ( $ids as $id ) {
            $social    = get_user_meta( $id, 'etn_speaker_social', true );
            $group     = get_user_meta( $id, 'etn_speaker_group', true );
            $category  = get_user_meta( $id, 'etn_speaker_category', true );
            $user_data = get_userdata( $id );

            if ( ! $user_data ) {
                continue;
            }

            if ( 'csv' === $this->format ) {
                $social    = json_encode( $social );
                $group     = json_encode( $group );
                $category  = json_encode( $category );
            }

            $speaker_data = [
                'id'               => $id,
                'name'             => get_user_meta( $id, 'first_name', true ),
                'email'            => $user_data->user_email,
                'image'            => get_user_meta( $id, 'image', true ),
                'designation'      => get_user_meta( $id, 'etn_speaker_designation', true ),
                'summary'          => get_user_meta( $id, 'etn_speaker_summery', true ),
                'social'           => $social,
                'company_logo'     => get_user_meta( $id, 'etn_speaker_company_logo', true ),
                'company_url'      => get_user_meta( $id, 'etn_speaker_url', true ),
                'speaker_group'    => $group,
                'speaker_category' => $category,
                'company_name'     => get_user_meta( $id, 'etn_company_name', true ),
                'author_url'       => get_user_meta( $id, 'author_url', true ),
                'role'             => get_user_meta( $id, 'role', true ),
            ];

            array_push( $exported_data, $speaker_data );
        }

        return $exported_data;
    }

    /**
     * Get columns
     *
     * @return  array
     */
    private function get_columns() {
        return [
            'id'               => esc_html__( 'Id', 'eventin' ),
            'name'             => esc_html__( 'Name', 'eventin' ),
            'image'            => esc_html__( 'Image', 'eventin' ),
            'designation'      => esc_html__( 'Designation', 'eventin' ),
            'email'            => esc_html__( 'Email', 'eventin' ),
            'summary'          => esc_html__( 'Summary', 'eventin' ),
            'social'           => esc_html__( 'Social', 'eventin' ),
            'company_logo'     => esc_html__( 'Company Logo', 'eventin' ),
            'company_url'      => esc_html__( 'Company Url', 'eventin' ),
            'company_name'     => esc_html__( 'Company Name', 'eventin' ),
            'author_url'       => esc_html__( 'Author Url', 'eventin' ),
            'role'             => esc_html__( 'Role', 'eventin' ),
            'speaker_group'    => esc_html__( 'Speaker Group', 'eventin' ),
            'speaker_category' => esc_html__( 'Speaker Category', 'eventin' ),
        ];
    }
}
