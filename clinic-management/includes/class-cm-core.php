<?php
/**
 * Core functionalities: capability check, admin menu registration, enqueue assets.
 *
 * @package ClinicManagement\Core
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CM_Core {

    /**
     * Singleton.
     *
     * @var self
     */
    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Admin menu.
        add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );

        // Enqueue scripts & styles.
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    /**
     * Register top-level admin menu.
     */
    public function register_admin_menu() {
        add_menu_page(
            __( 'Clinic Management', 'clinic-management' ),
            __( 'Clinic Management', 'clinic-management' ),
            'cm_access',
            'cm_dashboard',
            array( $this, 'render_dashboard' ),
            'dashicons-heart',
            26
        );
    }

    /**
     * Render basic dashboard page placeholder.
     */
    public function render_dashboard() {
        if ( ! current_user_can( 'cm_access' ) ) {
            wp_die( __( 'You do not have permission to access this page.', 'clinic-management' ) );
        }

        echo '<div class="wrap"><h1>' . esc_html__( 'Clinic Management Dashboard', 'clinic-management' ) . '</h1><p>' . esc_html__( 'Welcome to the Clinic Management System.', 'clinic-management' ) . '</p></div>';
    }

    /**
     * Enqueue admin scripts and styles.
     */
    public function enqueue_assets( $hook ) {
        // Only enqueue for our pages.
        if ( 0 !== strpos( $hook, 'toplevel_page_cm_' ) && 'dashboard' !== $hook ) {
            return;
        }

        wp_enqueue_style( 'cm-admin', CM_PLUGIN_URL . 'assets/css/cm-admin.css', array(), CM_VERSION );
        wp_enqueue_script( 'cm-admin', CM_PLUGIN_URL . 'assets/js/cm-admin.js', array( 'jquery' ), CM_VERSION, true );
    }
}