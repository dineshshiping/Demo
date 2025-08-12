<?php
/**
 * Reports and analytics module.
 *
 * @package ClinicManagement\Reports
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CM_Reports {

    private static $instance;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_menu', array( $this, 'add_reports_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    public function add_reports_menu() {
        add_submenu_page( 'cm_dashboard', __( 'Analytics', 'clinic-management' ), __( 'Analytics', 'clinic-management' ), 'cm_access', 'cm_reports', array( $this, 'render_reports_page' ) );
    }

    public function enqueue_assets( $hook ) {
        if ( 'clinic-management_page_cm_reports' !== $hook ) {
            return;
        }
        wp_enqueue_script( 'chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '4.4.0', true );
        wp_enqueue_script( 'cm-reports', CM_PLUGIN_URL . 'assets/js/cm-admin.js', array( 'chart-js' ), CM_VERSION, true );
    }

    public function render_reports_page() {
        echo '<div class="wrap"><h1>' . esc_html__( 'Clinic Analytics', 'clinic-management' ) . '</h1>';
        echo '<canvas id="cm-revenue-chart" width="400" height="200"></canvas>';
        echo '</div>';

        // Prepare data.
        $revenue_by_month = $this->get_revenue_by_month();
        $labels           = array_keys( $revenue_by_month );
        $data             = array_values( $revenue_by_month );

        wp_localize_script( 'cm-reports', 'CM_REPORTS_DATA', array(
            'labels' => $labels,
            'data'   => $data,
        ) );
    }

    private function get_revenue_by_month() {
        global $wpdb;
        $results = $wpdb->get_results( "SELECT DATE_FORMAT(created_at, '%Y-%m') AS mth, SUM(amount) AS total FROM {$wpdb->prefix}cm_payments WHERE status='paid' GROUP BY mth ORDER BY mth ASC" );
        $data = array();
        foreach ( $results as $row ) {
            $data[ $row->mth ] = (float) $row->total;
        }
        return $data;
    }
}