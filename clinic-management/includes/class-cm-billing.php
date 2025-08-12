<?php
/**
 * Billing and prescription generation.
 *
 * @package ClinicManagement\Billing
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CM_Billing {

    private static $instance;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Future hooks for bills and prescriptions.
    }

    /**
     * Generate invoice HTML for a given bill.
     *
     * @param int $bill_id Bill post ID.
     *
     * @return string HTML output.
     */
    public function render_invoice( $bill_id ) {
        $amount = get_post_meta( $bill_id, '_cm_amount', true );
        $status = get_post_meta( $bill_id, '_cm_status', true );
        $patient_id = get_post_meta( $bill_id, '_cm_patient_id', true );
        ob_start();
        ?>
        <h2><?php _e( 'Invoice', 'clinic-management' ); ?></h2>
        <p><?php printf( __( 'Patient: %s', 'clinic-management' ), esc_html( get_the_title( $patient_id ) ) ); ?></p>
        <p><?php printf( __( 'Amount: %s', 'clinic-management' ), esc_html( wc_price( $amount ) ) ); ?></p>
        <p><?php printf( __( 'Status: %s', 'clinic-management' ), esc_html( ucfirst( $status ) ) ); ?></p>
        <?php
        return ob_get_clean();
    }
}