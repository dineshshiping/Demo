<?php
/**
 * Secure patient portal front-end.
 *
 * @package ClinicManagement\PatientPortal
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CM_Patient_Portal {

    private static $instance;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_shortcode( 'cm_patient_portal', array( $this, 'portal_shortcode' ) );
    }

    /**
     * Render patient portal.
     */
    public function portal_shortcode( $atts, $content = null ) {
        if ( ! is_user_logged_in() ) {
            return wp_login_form( array( 'echo' => false ) );
        }

        $user_id  = get_current_user_id();
        $patient_query = new WP_Query( array(
            'post_type'  => 'cm_patient',
            'author'     => $user_id,
            'post_status' => 'any',
            'numberposts' => 1,
        ) );
        if ( ! $patient_query->have_posts() ) {
            return '<p>' . esc_html__( 'No patient record linked to your account.', 'clinic-management' ) . '</p>';
        }
        $patient = $patient_query->posts[0];

        ob_start();
        ?>
        <div class="cm-portal">
            <h2><?php _e( 'Your Appointments', 'clinic-management' ); ?></h2>
            <?php $this->render_appointments( $patient->ID ); ?>

            <h2><?php _e( 'Your Bills', 'clinic-management' ); ?></h2>
            <?php $this->render_bills( $patient->ID ); ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_appointments( $patient_id ) {
        $appointments = get_posts( array(
            'post_type'  => 'cm_appointment',
            'meta_key'   => '_cm_patient_id',
            'meta_value' => $patient_id,
            'numberposts' => -1,
        ) );
        if ( empty( $appointments ) ) {
            echo '<p>' . esc_html__( 'No appointments found.', 'clinic-management' ) . '</p>';
            return;
        }
        echo '<ul class="cm-portal-appointments">';
        foreach ( $appointments as $appt ) {
            $date = get_post_meta( $appt->ID, '_cm_date', true );
            $time = get_post_meta( $appt->ID, '_cm_time', true );
            echo '<li>' . esc_html( $date . ' ' . $time ) . '</li>';
        }
        echo '</ul>';
    }

    private function render_bills( $patient_id ) {
        $bills = get_posts( array(
            'post_type'  => 'cm_bill',
            'meta_key'   => '_cm_patient_id',
            'meta_value' => $patient_id,
            'numberposts' => -1,
        ) );
        if ( empty( $bills ) ) {
            echo '<p>' . esc_html__( 'No bills found.', 'clinic-management' ) . '</p>';
            return;
        }
        echo '<ul class="cm-portal-bills">';
        foreach ( $bills as $bill ) {
            $amount = get_post_meta( $bill->ID, '_cm_amount', true );
            $status = get_post_meta( $bill->ID, '_cm_status', true );
            echo '<li>' . esc_html( $bill->post_title ) . ' - ' . esc_html( $amount ) . ' (' . esc_html( ucfirst( $status ) ) . ')</li>';
        }
        echo '</ul>';
    }
}