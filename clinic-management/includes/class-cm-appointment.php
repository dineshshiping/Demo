<?php
/**
 * Appointment-specific logic: calendar, status, patient linkage.
 *
 * @package ClinicManagement\Appointment
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CM_Appointment {

    private static $instance;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        add_action( 'save_post_cm_appointment', array( $this, 'save_meta' ) );
        // Dashboard widget handled separately.
    }

    public function add_meta_boxes() {
        add_meta_box( 'cm_appointment_details', __( 'Appointment Details', 'clinic-management' ), array( $this, 'render_meta' ), 'cm_appointment', 'normal', 'default' );
    }

    public function render_meta( $post ) {
        wp_nonce_field( 'cm_save_appointment', 'cm_appointment_nonce' );

        $patient_id = get_post_meta( $post->ID, '_cm_patient_id', true );
        $date       = get_post_meta( $post->ID, '_cm_date', true );
        $time       = get_post_meta( $post->ID, '_cm_time', true );
        $status     = get_post_meta( $post->ID, '_cm_status', true );

        // Patient selector dropdown.
        $patients = get_posts( array( 'post_type' => 'cm_patient', 'numberposts' => -1 ) );

        echo '<p><label>' . __( 'Patient', 'clinic-management' ) . '</label><br/><select name="cm_patient_id" class="widefat">';
        echo '<option value="">' . __( '-- Select Patient --', 'clinic-management' ) . '</option>';
        foreach ( $patients as $patient ) {
            $selected = selected( $patient_id, $patient->ID, false );
            echo '<option value="' . esc_attr( $patient->ID ) . '" ' . $selected . '>' . esc_html( $patient->post_title ) . '</option>';
        }
        echo '</select></p>';

        echo '<p><label>' . __( 'Date', 'clinic-management' ) . '</label><br/><input type="date" name="cm_date" value="' . esc_attr( $date ) . '" class="widefat"></p>';
        echo '<p><label>' . __( 'Time', 'clinic-management' ) . '</label><br/><input type="time" name="cm_time" value="' . esc_attr( $time ) . '" class="widefat"></p>';

        echo '<p><label>' . __( 'Status', 'clinic-management' ) . '</label><br/>';
        echo '<select name="cm_status" class="widefat">';
        $statuses = array( 'scheduled' => __( 'Scheduled', 'clinic-management' ), 'completed' => __( 'Completed', 'clinic-management' ), 'cancelled' => __( 'Cancelled', 'clinic-management' ) );
        foreach ( $statuses as $key => $label ) {
            $selected = selected( $status, $key, false );
            echo '<option value="' . esc_attr( $key ) . '" ' . $selected . '>' . esc_html( $label ) . '</option>';
        }
        echo '</select></p>';
    }

    public function save_meta( $post_id ) {
        if ( ! isset( $_POST['cm_appointment_nonce'] ) || ! wp_verify_nonce( $_POST['cm_appointment_nonce'], 'cm_save_appointment' ) ) {
            return;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( ! current_user_can( 'cm_access' ) ) {
            return;
        }

        $fields = array( 'cm_patient_id', 'cm_date', 'cm_time', 'cm_status' );
        foreach ( $fields as $field ) {
            if ( isset( $_POST[ $field ] ) ) {
                update_post_meta( $post_id, '_' . $field, sanitize_text_field( $_POST[ $field ] ) );
            }
        }
    }
}