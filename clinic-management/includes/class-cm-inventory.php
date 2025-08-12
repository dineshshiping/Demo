<?php
/**
 * Medicine inventory management.
 *
 * @package ClinicManagement\Inventory
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CM_Inventory {

    private static $instance;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Low stock cron.
        add_action( 'cm_check_low_stock', array( $this, 'check_low_stock' ) );

        if ( ! wp_next_scheduled( 'cm_check_low_stock' ) ) {
            wp_schedule_event( time(), 'hourly', 'cm_check_low_stock' );
        }
    }

    /**
     * Check inventory and send alerts.
     */
    public function check_low_stock() {
        $medicines = get_posts( array( 'post_type' => 'cm_medicine', 'numberposts' => -1 ) );
        foreach ( $medicines as $med ) {
            $qty        = (int) get_post_meta( $med->ID, '_cm_qty', true );
            $threshold  = (int) get_post_meta( $med->ID, '_cm_low_stock_threshold', true );

            if ( $threshold && $qty <= $threshold ) {
                // Send alert via email.
                $subject = __( 'Low Stock Alert', 'clinic-management' );
                $message = sprintf( __( 'Medicine %s is low on stock (%d items left).', 'clinic-management' ), $med->post_title, $qty );
                wp_mail( get_option( 'admin_email' ), $subject, $message );
            }
        }
    }
}