<?php
/**
 * Plugin Name: Clinic Management System
 * Plugin URI: https://example.com/clinic-management-system
 * Description: A comprehensive all-in-one clinic management solution for medical practices, including patient management, appointments, billing, prescriptions, and more.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: clinic-management-system
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CMS_VERSION', '1.0.0');
define('CMS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CMS_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('CMS_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Include required files
require_once CMS_PLUGIN_PATH . 'includes/class-cms-loader.php';
require_once CMS_PLUGIN_PATH . 'includes/class-cms-activator.php';
require_once CMS_PLUGIN_PATH . 'includes/class-cms-deactivator.php';

// Activation and deactivation hooks
register_activation_hook(__FILE__, array('CMS_Activator', 'activate'));
register_deactivation_hook(__FILE__, array('CMS_Deactivator', 'deactivate'));

// Initialize the plugin
function cms_init() {
    $plugin = new CMS_Loader();
    $plugin->run();
}
add_action('plugins_loaded', 'cms_init');

// Add custom capabilities on plugin activation
function cms_add_custom_capabilities() {
    $role = get_role('administrator');
    if ($role) {
        $role->add_cap('manage_clinic');
        $role->add_cap('view_patients');
        $role->add_cap('edit_patients');
        $role->add_cap('delete_patients');
        $role->add_cap('manage_appointments');
        $role->add_cap('manage_billing');
        $role->add_cap('manage_prescriptions');
        $role->add_cap('manage_inventory');
        $role->add_cap('view_reports');
    }
    
    // Add Doctor role
    add_role('doctor', 'Doctor', array(
        'read' => true,
        'view_patients' => true,
        'edit_patients' => true,
        'manage_appointments' => true,
        'manage_prescriptions' => true,
        'view_reports' => true
    ));
}
register_activation_hook(__FILE__, 'cms_add_custom_capabilities');