<?php
/**
 * JSON Reader Class
 * 
 * @package Eventin
 */
namespace Etn\Base\Importer;

/**
 * JSON Reader Class
 */
class JSON_Reader implements Reader_Interface {
    /**
     * Store file
     *
     * @var string
     */
    private $file;

    /**
     * JSON Reader Constructor
     *
     * @param   string  $file
     *
     * @return  void
     */
    public function __construct( $file ) {
        $this->file = $file;
    }

    /**
     * Read JSON File
     *
     * @return  array
     */
    public function read_file() {
        $data = $this->prepare_data();

        return $data;
    }

    /**
     * Prepare data from file
     *
     * @return  array
     */
    private function prepare_data() {
        $content = file_get_contents( $this->file );
        return json_decode( $content, true );
    }
}
