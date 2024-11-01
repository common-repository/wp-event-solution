<?php
/**
 * CSV Exporter
 * 
 * @package Eventin
 */
namespace Etn\Base\Exporter;

/**
 * CSV Exporter Class
 */
class CSV_Exporter implements Exporter_Interface {
    /**
     * Exported data
     *
     * @var array
     */
    private $data;

    /**
     * Store column name that will be exported
     *
     * @var array
     */
    private $columns;

    /**
     * Exported file name
     *
     * @var string
     */
    private $file_name = 'data';

    /**
     * Export data
     *
     * @param   array  $data
     *
     * @return  void
     */
    public function export( $data, $columns = [], $file_name = '' ) {
        $this->data = $data;
        $this->columns = $columns;
        $this->file_name = $file_name;
        $this->export_csv();
    }

    /**
     * Get columns as csv
     *
     * @return  string
     */
    protected function export_columns() {
        $colunms = $this->columns;
        ob_clean();
        $output = fopen( 'php://output', 'w' );
        ob_start();

        fputcsv( $output, $colunms );

        fclose( $output );

        return ob_get_clean();
    }

    /**
     * Export rows
     *
     * @return  string
     */
    protected function export_rows() {
        $data   = $this->data;
        ob_clean();
        $buffer = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen
        ob_start();

        array_walk( $data, array( $this, 'export_row' ), $buffer );

        return ob_get_clean();
    }

    /**
     * Export row
     *
     * @param   array  $row_data  [$row_data description]
     * @param   [type]  $key       [$key description]
     * @param   file  $buffer    [$buffer description]
     *
     * @return  void
     */
    protected function export_row( $row_data, $key, $buffer ) {
        $colunms    = $this->columns;
        $export_row = [];

        foreach ( $colunms as $key => $colunm ) {
            if ( ! empty( $row_data[$key] ) ) {
                $export_row[] = is_array( $row_data[$key] ) ? etn_array_csv_column( $row_data[$key] ) : $row_data[$key];
            } else {
                $export_row[] = '';
            }
        }

        fputcsv( $buffer, $export_row );
    }

    /**
     * Print the content that will be exported
     *
     * @param   string  $content
     *
     * @return  void
     */
    protected function send_content( $content ) {
        echo $content;
    }

    /**
     * Export data to csv format
     *
     * @return  void
     */
    public function export_csv() {
        $this->send_headers();
        $this->send_content( $this->export_columns() . $this->export_rows() );
        die();
    }

    /**
     * Set content type
     *
     * @return void
     */
    protected function send_headers() {
        header( 'Content-Type: application/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=' . $this->file_name . '.csv' );
        header( 'Pragma: no-cache' );
        header( 'Expires: 0' );
    }
}
