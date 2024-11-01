<?php
/**
 * Speaker Importer Class
 *
 * @package Eventin
 */
namespace Etn\Core\Speaker;

use Etn\Base\Importer\Post_Importer_Interface;
use Etn\Base\Importer\Reader_Factory;

/**
 * Class Speaker Importer
 */
class Speaker_Importer implements Post_Importer_Interface {
    /**
     * Store File
     *
     * @var string
     */
    private $file;

    /**
     * Store data
     *
     * @var array
     */
    private $data;
    
    /**
     * Schedule import
     *
     * @return  void
     */
    public function import( $file ) {
        $this->file  = $file;
        $file_reader = Reader_Factory::get_reader( $file );

        $this->data = $file_reader->read_file();

        $this->create_speaker();
    }

    /**
     * Create schedule
     *
     * @return  void
     */
    private function create_speaker() {
        $file_type  = ! empty( $this->file['type'] ) ? $this->file['type'] : '';
        $rows       = $this->data;

        foreach( $rows as $row ) {
            $speaker = new User_Model();
            $social = ! empty( $row['social'] ) ? $row['social'] : '';
            $group  = ! empty( $row['speaker_group'] ) ? $row['speaker_group'] : '';

            if ( 'text/csv' == $file_type ) {
                $social = json_decode( $social, true );
                $group  = json_decode( $group, true );
            }

            $args = [
                'first_name'                => ! empty( $row['name'] ) ? $row['name'] : '',
                'etn_speaker_website_email' => ! empty( $row['email'] ) ? $row['email'] : '',
                'image'                     => ! empty( $row['image'] ) ? $row['image'] : '',
                'etn_speaker_designation'   => ! empty( $row['designation'] ) ? $row['designation'] : '',
                'etn_speaker_summery'       => ! empty( $row['summary'] ) ? $row['summary'] : '',
                'etn_speaker_social'        => $social,
                'etn_speaker_company_logo'  => ! empty( $row['company_logo'] ) ? $row['company_logo'] : '',
                'etn_speaker_url'           => ! empty( $row['company_url'] ) ? $row['company_url'] : '',
                'etn_speaker_group'         => $group,
                'etn_speaker_category'      => $group,
                'etn_company_name'          => ! empty( $row['company_name'] ) ? $row['company_name'] : '',
                'author_url'                => ! empty( $row['author_url'] ) ? $row['author_url'] : '',
                'role'                      => ! empty( $row['role'] ) ? $row['role'] : '',
            ];

            $args['user_login'] = $row['email'];
    
            $speaker->create( $args );
        }
    }
}
