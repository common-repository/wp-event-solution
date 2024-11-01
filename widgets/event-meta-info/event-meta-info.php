<?php
namespace Elementor;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;
use Etn\Utils\Helper;

defined( 'ABSPATH' ) || exit;

class Etn_Event_Meta_Info extends Widget_Base {

    /**
     * Retrieve the widget name.
     * @return string Widget name.
     */
    public function get_name() {
        return 'etn-event-meta-info';
    }

    /**
     * Retrieve the widget title.
     * @return string Widget title.
     */
    public function get_title() {
        return esc_html__( 'Event Meta Info', 'eventin' );
    }

    /**
     * Retrieve the widget icon.
     * @return string Widget icon.
     */
    public function get_icon() {
        return 'eicon-calendar';
    }

    /**
     * Retrieve the list of categories the widget belongs to.
     * Used to determine where to display the widget in the editor.
     * @return array Widget categories.
     */
    public function get_categories() {
        return ['etn-event'];
    }

    /**
     * Register the widget controls.
     * @access protected
     */
    protected function register_controls() {

        // Start of event section
        $this->start_controls_section(
            'section_tab',
            [
                'label' => esc_html__( 'Eventin Event', 'eventin' ),
            ]
        );

        $this->add_control(
            'style',
            [
                'label'   => esc_html__( 'Style', 'eventin' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'style-1',
                'options' => [
                    'style-1' => esc_html__( 'Style 1', 'eventin' ),
                ],
            ]
        );
        

        $this->add_control(
            "event_id",
            [
                "label"     => esc_html__("Select Event", "eventin"),
                "type"      => Controls_Manager::SELECT2,
                "multiple"  => false,
                "options"   => Helper::get_events(),
            ]
        );

        $this->end_controls_section();

         // Title style section
         $this->start_controls_section(
            'title_section',
            [
                'label' => esc_html__( 'List Style', 'eventin' ),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'     => 'title_typography',
                'label'    => esc_html__( 'Typography', 'eventin' ),
                'selector' => '{{WRAPPER}} .etn-event-meta-info ul li',
            ]
        );

        $this->end_controls_section();

    }

    protected function render() {
        $settings           = $this->get_settings();
        $single_event_id        = !empty( $settings['event_id'] ) ? $settings['event_id'] : 0;;

        echo do_shortcode("[etn_event_meta_info event_id='$single_event_id']");
    }
}