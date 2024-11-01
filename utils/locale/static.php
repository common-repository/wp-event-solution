<?php
$data = [
    'empty'              => esc_html__( 'Please fill the field', 'eventin' ),
    'invalid'            => esc_html__( 'Invalid input', 'eventin' ),
    'only_number'        => esc_html__( 'Only number allowed', 'eventin' ),
    'text'               => esc_html__( 'Please fill the field', 'eventin' ),
    'number'             => esc_html__( 'Please input a number', 'eventin' ),
    'date'               => esc_html__( 'Please fill the field', 'eventin' ),
    'radio'              => esc_html__( 'Please check the field', 'eventin' ),
    'expired'            => esc_html__( 'Expired', 'eventin' ),
    'scanner_common_msg' => esc_html__( 'Something went wrong! Please try again.', 'eventin' ),
    'scanner_common_msg' => esc_html__( 'Something went wrong! Please try again.', 'eventin' ),
];

return apply_filters( 'etn_locale_static',  $data );
