<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Adso_Plugin_Settings {

    public function __construct() {
        add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_tab' ), 50 );
        add_action( 'woocommerce_settings_tabs_adso_settings', array( $this, 'settings_tab' ) );
        add_action( 'woocommerce_update_options_adso_settings', array( $this, 'update_settings' ) );
    }

    public function add_settings_tab( $settings_tabs ) {
        $settings_tabs['adso_settings'] = __( 'ADSO Settings', 'adso-plugin' );
        return $settings_tabs;
    }

    public function settings_tab() {
        woocommerce_admin_fields( $this->get_settings() );
    }

    public function update_settings() {
        woocommerce_update_options( $this->get_settings() );
    }

    public function get_settings() {
        $settings = array(
            'section_title' => array(
                'name'     => __( 'ADSO Plugin Settings', 'adso-plugin' ),
                'type'     => 'title',
                'desc'     => '',
                'id'       => 'adso_settings_section_title'
            ),
            'title' => array(
                'name' => __( 'Title', 'adso-plugin' ),
                'type' => 'text',
                'desc' => __( 'This is some helper text', 'adso-plugin' ),
                'id'   => 'adso_settings_title'
            ),
            'description' => array(
                'name' => __( 'Description', 'adso-plugin' ),
                'type' => 'textarea',
                'desc' => __( 'This is a paragraph describing the setting.', 'adso-plugin' ),
                'id'   => 'adso_settings_description'
            ),
            'section_end' => array(
                 'type' => 'sectionend',
                 'id' => 'adso_settings_section_end'
            )
        );
        return apply_filters( 'adso_settings', $settings );
    }
}