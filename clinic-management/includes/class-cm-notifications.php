<?php
/**
 * Email and SMS notifications.
 *
 * @package ClinicManagement\Notifications
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CM_Notifications {

    private static $instance;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Schedule daily reminder check.
        add_action( 'cm_daily_notifications', array( $this, 'process_notifications' ) );
        if ( ! wp_next_scheduled( 'cm_daily_notifications' ) ) {
            wp_schedule_event( time(), 'hourly', 'cm_daily_notifications' );
        }
    }

    /**
     * Process notifications: Appointment reminders.
     */
    public function process_notifications() {
        $tomorrow = date( 'Y-m-d', strtotime( '+1 day', current_time( 'timestamp' ) ) );
        $appointments = get_posts( array(
            'post_type'   => 'cm_appointment',
            'numberposts' => -1,
            'meta_query'  => array(
                array(
                    'key'   => '_cm_date',
                    'value' => $tomorrow,
                ),
            ),
        ) );

        foreach ( $appointments as $appt ) {
            $patient_id = get_post_meta( $appt->ID, '_cm_patient_id', true );
            $patient_email = get_post_meta( $patient_id, '_cm_email', true );
            $time = get_post_meta( $appt->ID, '_cm_time', true );
            if ( $patient_email ) {
                $subject = __( 'Appointment Reminder', 'clinic-management' );
                $message = sprintf( __( 'Dear patient, this is a reminder for your appointment tomorrow at %s.', 'clinic-management' ), $time );
                wp_mail( $patient_email, $subject, $message );
            }
            // SMS gateway integration placeholder.
        }
    }
}