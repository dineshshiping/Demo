<?php
/**
 * Plugin Name: Clinic Management System
 * Plugin URI: https://yourwebsite.com/clinic-management-system
 * Description: A comprehensive clinic management system for WordPress with patient management, appointments, billing, inventory, and more.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: clinic-management
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CMS_PLUGIN_VERSION', '1.0.0');
define('CMS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CMS_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('CMS_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Clinic Management System Class
 */
class ClinicManagementSystem
{
    /**
     * Single instance of the class
     */
    private static $instance = null;

    /**
     * Get single instance
     */
    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct()
    {
        add_action('init', array($this, 'init'));
        add_action('plugins_loaded', array($this, 'loadTextdomain'));
        
        // Activation and deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Include required files
        $this->includeFiles();
        
        // Initialize components
        add_action('wp_loaded', array($this, 'initComponents'));
    }

    /**
     * Include required files
     */
    private function includeFiles()
    {
        // Core files
        require_once CMS_PLUGIN_PATH . 'includes/class-cms-database.php';
        require_once CMS_PLUGIN_PATH . 'includes/class-cms-user-roles.php';
        require_once CMS_PLUGIN_PATH . 'includes/class-cms-admin-menu.php';
        require_once CMS_PLUGIN_PATH . 'includes/class-cms-dashboard-widgets.php';
        
        // Module files
        require_once CMS_PLUGIN_PATH . 'includes/modules/class-cms-patients.php';
        require_once CMS_PLUGIN_PATH . 'includes/modules/class-cms-appointments.php';
        require_once CMS_PLUGIN_PATH . 'includes/modules/class-cms-consultations.php';
        require_once CMS_PLUGIN_PATH . 'includes/modules/class-cms-prescriptions.php';
        require_once CMS_PLUGIN_PATH . 'includes/modules/class-cms-billing.php';
        require_once CMS_PLUGIN_PATH . 'includes/modules/class-cms-inventory.php';
        require_once CMS_PLUGIN_PATH . 'includes/modules/class-cms-patient-portal.php';
        require_once CMS_PLUGIN_PATH . 'includes/modules/class-cms-reports.php';
        require_once CMS_PLUGIN_PATH . 'includes/modules/class-cms-notifications.php';
        
        // Ajax handlers
        require_once CMS_PLUGIN_PATH . 'includes/class-cms-ajax.php';
        
        // Frontend
        require_once CMS_PLUGIN_PATH . 'includes/class-cms-frontend.php';
    }

    /**
     * Initialize the plugin
     */
    public function init()
    {
        // Check user capabilities
        if (!current_user_can('manage_options') && !current_user_can('cms_access')) {
            return;
        }
        
        // Enqueue scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'adminEnqueueScripts'));
        add_action('wp_enqueue_scripts', array($this, 'frontendEnqueueScripts'));
    }

    /**
     * Initialize components
     */
    public function initComponents()
    {
        // Initialize user roles
        CMS_User_Roles::getInstance();
        
        // Initialize admin menu
        CMS_Admin_Menu::getInstance();
        
        // Initialize dashboard widgets
        CMS_Dashboard_Widgets::getInstance();
        
        // Initialize modules
        CMS_Patients::getInstance();
        CMS_Appointments::getInstance();
        CMS_Consultations::getInstance();
        CMS_Prescriptions::getInstance();
        CMS_Billing::getInstance();
        CMS_Inventory::getInstance();
        CMS_Patient_Portal::getInstance();
        CMS_Reports::getInstance();
        CMS_Notifications::getInstance();
        
        // Initialize AJAX handlers
        CMS_Ajax::getInstance();
        
        // Initialize frontend
        CMS_Frontend::getInstance();
    }

    /**
     * Load text domain for translations
     */
    public function loadTextdomain()
    {
        load_plugin_textdomain('clinic-management', false, dirname(CMS_PLUGIN_BASENAME) . '/languages');
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function adminEnqueueScripts($hook)
    {
        // Only load on clinic management pages
        if (strpos($hook, 'clinic-management') === false) {
            return;
        }

        // CSS
        wp_enqueue_style('cms-admin-style', CMS_PLUGIN_URL . 'assets/css/admin.css', array(), CMS_PLUGIN_VERSION);
        wp_enqueue_style('cms-bootstrap', CMS_PLUGIN_URL . 'assets/css/bootstrap.min.css', array(), '5.1.3');
        wp_enqueue_style('cms-fontawesome', CMS_PLUGIN_URL . 'assets/css/fontawesome.min.css', array(), '6.0.0');
        
        // JavaScript
        wp_enqueue_script('jquery');
        wp_enqueue_script('cms-bootstrap-js', CMS_PLUGIN_URL . 'assets/js/bootstrap.bundle.min.js', array('jquery'), '5.1.3', true);
        wp_enqueue_script('cms-chart-js', CMS_PLUGIN_URL . 'assets/js/chart.min.js', array(), '3.9.1', true);
        wp_enqueue_script('cms-admin-js', CMS_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), CMS_PLUGIN_VERSION, true);
        
        // Localize script
        wp_localize_script('cms-admin-js', 'cms_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cms_ajax_nonce'),
            'text_domain' => 'clinic-management'
        ));
        
        // WordPress media uploader
        if (function_exists('wp_enqueue_media')) {
            wp_enqueue_media();
        }
    }

    /**
     * Enqueue frontend scripts and styles
     */
    public function frontendEnqueueScripts()
    {
        if (is_page() && has_shortcode(get_post()->post_content, 'cms_patient_portal')) {
            wp_enqueue_style('cms-frontend-style', CMS_PLUGIN_URL . 'assets/css/frontend.css', array(), CMS_PLUGIN_VERSION);
            wp_enqueue_style('cms-bootstrap', CMS_PLUGIN_URL . 'assets/css/bootstrap.min.css', array(), '5.1.3');
            
            wp_enqueue_script('cms-bootstrap-js', CMS_PLUGIN_URL . 'assets/js/bootstrap.bundle.min.js', array('jquery'), '5.1.3', true);
            wp_enqueue_script('cms-frontend-js', CMS_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), CMS_PLUGIN_VERSION, true);
            
            wp_localize_script('cms-frontend-js', 'cms_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('cms_ajax_nonce'),
                'text_domain' => 'clinic-management'
            ));
        }
    }

    /**
     * Plugin activation
     */
    public function activate()
    {
        // Create database tables
        CMS_Database::createTables();
        
        // Create user roles
        CMS_User_Roles::createRoles();
        
        // Create patient portal page
        $this->createPatientPortalPage();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate()
    {
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Create patient portal page
     */
    private function createPatientPortalPage()
    {
        $page_title = 'Patient Portal';
        $page_content = '[cms_patient_portal]';
        $page_template = '';
        
        $page_check = get_page_by_title($page_title);
        
        if (!isset($page_check->ID)) {
            $page = array(
                'post_type' => 'page',
                'post_title' => $page_title,
                'post_content' => $page_content,
                'post_status' => 'publish',
                'post_author' => 1,
                'post_slug' => 'patient-portal'
            );
            
            $page_id = wp_insert_post($page);
            
            if (!empty($page_template)) {
                update_post_meta($page_id, '_wp_page_template', $page_template);
            }
        }
    }
}

// Initialize the plugin
function cms_init()
{
    return ClinicManagementSystem::getInstance();
}

// Start the plugin
cms_init();