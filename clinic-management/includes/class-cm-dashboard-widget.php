<?php
/**
 * Adds WordPress dashboard widget for today's appointments.
 *
 * @package ClinicManagement\Dashboard
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CM_Dashboard_Widget {
    private static $instance;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'wp_dashboard_setup', array( $this, 'register_widget' ) );
    }

    public function register_widget() {
        wp_add_dashboard_widget( 'cm_today_appointments', __( "Today's Appointments", 'clinic-management' ), array( $this, 'render_widget' ) );
    }

    public function render_widget() {
        $today = current_time( 'Y-m-d' );
        $args  = array(
            'post_type'  => 'cm_appointment',
            'numberposts' => -1,
            'meta_query' => array(
                array(
                    'key'   => '_cm_date',
                    'value' => $today,
                ),
            ),
        );
        $appointments = get_posts( $args );

        if ( empty( $appointments ) ) {
            echo '<p>' . esc_html__( 'No appointments for today.', 'clinic-management' ) . '</p>';
            return;
        }

        echo '<ul class="cm-dashboard-appointments">';
        foreach ( $appointments as $appt ) {
            $time   = get_post_meta( $appt->ID, '_cm_time', true );
            $status = get_post_meta( $appt->ID, '_cm_status', true );
            $patient_id = get_post_meta( $appt->ID, '_cm_patient_id', true );
            $patient    = $patient_id ? get_the_title( $patient_id ) : __( 'Unknown', 'clinic-management' );

            echo '<li><strong>' . esc_html( $time ) . '</strong> - ' . esc_html( $patient ) . ' (' . esc_html( ucfirst( $status ) ) . ')</li>';
        }
        echo '</ul>';
    }
}