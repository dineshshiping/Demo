<?php
/**
 * Plugin Name: Clinic Management System
 * Plugin URI:  https://example.com/clinic-management
 * Description: A comprehensive, secure, and professional Clinic Management System for WordPress that digitizes and automates medical clinic workflows. Includes patient management, appointments, billing, inventory, patient portal, analytics, and notifications.
 * Version:     1.0.0
 * Author:      Your Name
 * Author URI:  https://example.com
 * License:     GPL-2.0-or-later
 * Text Domain: clinic-management
 *
 * @package ClinicManagement
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// -----------------------------------------------------------------------------
// Global Defines
// -----------------------------------------------------------------------------

define( 'CM_VERSION', '1.0.0' );

define( 'CM_PLUGIN_FILE', __FILE__ );

define( 'CM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

define( 'CM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Autoloader for CM classes (PSR-4–like but simplified).
 *
 * @param string $class Class name being requested.
 */
function cm_autoloader( $class ) {
    if ( 0 !== strpos( $class, 'CM_' ) ) {
        return;
    }

    $file = CM_PLUGIN_DIR . 'includes/class-' . strtolower( str_replace( '_', '-', $class ) ) . '.php';
    if ( file_exists( $file ) ) {
        require_once $file;
    }
}

spl_autoload_register( 'cm_autoloader' );

// -----------------------------------------------------------------------------
// Core Plugin Bootstrap
// -----------------------------------------------------------------------------

/**
 * Main plugin bootstrap class.
 */
class CM_Bootstrap {

    /**
     * Singleton instance.
     *
     * @var CM_Bootstrap
     */
    private static $instance = null;

    /**
     * Allowed roles for accessing the CMS features.
     *
     * @var array
     */
    public $allowed_roles = array( 'administrator', 'doctor' );

    /**
     * Get singleton instance.
     *
     * @return CM_Bootstrap
     */
    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        // Activation / deactivation hooks.
        register_activation_hook( CM_PLUGIN_FILE, array( 'CM_Installer', 'activate' ) );
        register_deactivation_hook( CM_PLUGIN_FILE, array( 'CM_Installer', 'deactivate' ) );

        // Initialize subsystems after plugins_loaded to ensure dependencies are ready.
        add_action( 'plugins_loaded', array( $this, 'init' ) );
    }

    /**
     * Initialize plugin modules.
     */
    public function init() {
        // Load localisation.
        load_plugin_textdomain( 'clinic-management', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

        // Core systems.
        CM_Core::instance(); // Handles roles & capabilities.
        CM_Post_Types::instance(); // Registers CPTs & taxonomies.
        CM_Dashboard_Widget::instance(); // Admin dashboard widgets (today's appointments).
        CM_Appointment::instance(); // Appointment management.
        CM_Billing::instance(); // Billing & prescriptions.
        CM_Inventory::instance(); // Pharmacy stock management.
        CM_Patient_Portal::instance(); // Front-end patient portal.
        CM_Reports::instance(); // Analytics & reports.
        CM_Notifications::instance(); // Email/SMS notifications.
    }
}

// Initialise plugin.
CM_Bootstrap::instance();