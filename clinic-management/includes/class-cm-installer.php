<?php
/**
 * Handles plugin activation and deactivation routines.
 *
 * @package ClinicManagement\Installer
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CM_Installer {

    /**
     * Plugin activation callback.
     */
    public static function activate() {
        self::add_roles();
        self::maybe_create_tables();

        // Flush rewrite rules to register CPT permalinks.
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation callback.
     */
    public static function deactivate() {
        // Optionally remove custom roles & capabilities.
        remove_role( 'doctor' );

        flush_rewrite_rules();
    }

    /**
     * Add custom roles and capabilities.
     */
    private static function add_roles() {
        add_role(
            'doctor',
            __( 'Doctor', 'clinic-management' ),
            array(
                'read'                   => true,
                'edit_posts'             => false,
                'upload_files'           => true,
                'cm_access'              => true, // Custom capability to access clinic plugin.
            )
        );

        // Add capabilities to administrator.
        $admin = get_role( 'administrator' );
        if ( $admin ) {
            $admin->add_cap( 'cm_access' );
        }
    }

    /**
     * Create custom database tables (e.g., for prescriptions and billing) if necessary.
     * Keeping CPT meta-driven storage by default; extend here for advanced storage.
     */
    private static function maybe_create_tables() {
        global $wpdb;
        // Using dbDelta for future complex tables.
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // Example prescriptions table.
        $charset_collate = $wpdb->get_charset_collate();
        $table_name      = $wpdb->prefix . 'cm_payments';

        $sql = "CREATE TABLE {$table_name} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            patient_id BIGINT(20) UNSIGNED NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'unpaid',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY patient_id (patient_id)
        ) {$charset_collate};";

        dbDelta( $sql );
    }
}