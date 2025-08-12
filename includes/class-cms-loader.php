<?php
/**
 * The main plugin loader class
 */
class CMS_Loader {
    
    protected $plugin_name;
    protected $version;
    
    public function __construct() {
        $this->plugin_name = 'clinic-management-system';
        $this->version = CMS_VERSION;
        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->define_ajax_hooks();
    }
    
    private function load_dependencies() {
        // Core classes
        require_once CMS_PLUGIN_PATH . 'includes/class-cms-database.php';
        require_once CMS_PLUGIN_PATH . 'includes/class-cms-patient.php';
        require_once CMS_PLUGIN_PATH . 'includes/class-cms-appointment.php';
        require_once CMS_PLUGIN_PATH . 'includes/class-cms-prescription.php';
        require_once CMS_PLUGIN_PATH . 'includes/class-cms-billing.php';
        require_once CMS_PLUGIN_PATH . 'includes/class-cms-inventory.php';
        require_once CMS_PLUGIN_PATH . 'includes/class-cms-notifications.php';
        require_once CMS_PLUGIN_PATH . 'includes/class-cms-reports.php';
        
        // Admin classes
        require_once CMS_PLUGIN_PATH . 'admin/class-cms-admin.php';
        require_once CMS_PLUGIN_PATH . 'admin/class-cms-dashboard-widget.php';
        
        // Public classes
        require_once CMS_PLUGIN_PATH . 'public/class-cms-public.php';
        require_once CMS_PLUGIN_PATH . 'public/class-cms-patient-portal.php';
    }
    
    private function define_admin_hooks() {
        $plugin_admin = new CMS_Admin($this->get_plugin_name(), $this->get_version());
        
        // Admin menu and pages
        add_action('admin_menu', array($plugin_admin, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($plugin_admin, 'enqueue_styles'));
        add_action('admin_enqueue_scripts', array($plugin_admin, 'enqueue_scripts'));
        
        // Dashboard widget
        $dashboard_widget = new CMS_Dashboard_Widget();
        add_action('wp_dashboard_setup', array($dashboard_widget, 'add_dashboard_widget'));
        
        // AJAX handlers for admin
        add_action('wp_ajax_cms_add_patient', array($plugin_admin, 'ajax_add_patient'));
        add_action('wp_ajax_cms_update_patient', array($plugin_admin, 'ajax_update_patient'));
        add_action('wp_ajax_cms_delete_patient', array($plugin_admin, 'ajax_delete_patient'));
        add_action('wp_ajax_cms_add_appointment', array($plugin_admin, 'ajax_add_appointment'));
        add_action('wp_ajax_cms_update_appointment', array($plugin_admin, 'ajax_update_appointment'));
        add_action('wp_ajax_cms_delete_appointment', array($plugin_admin, 'ajax_delete_appointment'));
        add_action('wp_ajax_cms_add_prescription', array($plugin_admin, 'ajax_add_prescription'));
        add_action('wp_ajax_cms_add_billing', array($plugin_admin, 'ajax_add_billing'));
        add_action('wp_ajax_cms_update_inventory', array($plugin_admin, 'ajax_update_inventory'));
    }
    
    private function define_public_hooks() {
        $plugin_public = new CMS_Public($this->get_plugin_name(), $this->get_version());
        $patient_portal = new CMS_Patient_Portal();
        
        // Public scripts and styles
        add_action('wp_enqueue_scripts', array($plugin_public, 'enqueue_styles'));
        add_action('wp_enqueue_scripts', array($plugin_public, 'enqueue_scripts'));
        
        // Shortcodes
        add_shortcode('cms_patient_portal', array($patient_portal, 'render_portal'));
        add_shortcode('cms_appointment_booking', array($patient_portal, 'render_booking_form'));
        
        // AJAX handlers for public
        add_action('wp_ajax_nopriv_cms_book_appointment', array($patient_portal, 'ajax_book_appointment'));
        add_action('wp_ajax_cms_book_appointment', array($patient_portal, 'ajax_book_appointment'));
        add_action('wp_ajax_cms_patient_login', array($patient_portal, 'ajax_patient_login'));
        add_action('wp_ajax_nopriv_cms_patient_login', array($patient_portal, 'ajax_patient_login'));
    }
    
    private function define_ajax_hooks() {
        // Common AJAX handlers
        add_action('wp_ajax_cms_search_patients', array($this, 'ajax_search_patients'));
        add_action('wp_ajax_cms_get_patient_history', array($this, 'ajax_get_patient_history'));
        add_action('wp_ajax_cms_upload_file', array($this, 'ajax_upload_file'));
    }
    
    public function run() {
        // Initialize database tables
        $this->init_database();
        
        // Schedule cron jobs for notifications
        if (!wp_next_scheduled('cms_daily_notifications')) {
            wp_schedule_event(time(), 'daily', 'cms_daily_notifications');
        }
        add_action('cms_daily_notifications', array($this, 'send_daily_notifications'));
    }
    
    private function init_database() {
        $database = new CMS_Database();
        $database->create_tables();
    }
    
    public function send_daily_notifications() {
        $notifications = new CMS_Notifications();
        $notifications->send_appointment_reminders();
        $notifications->send_payment_reminders();
    }
    
    public function get_plugin_name() {
        return $this->plugin_name;
    }
    
    public function get_version() {
        return $this->version;
    }
    
    // AJAX handlers
    public function ajax_search_patients() {
        check_ajax_referer('cms_nonce', 'nonce');
        
        if (!current_user_can('view_patients')) {
            wp_die('Unauthorized');
        }
        
        $search_term = sanitize_text_field($_POST['search_term']);
        $patient = new CMS_Patient();
        $results = $patient->search_patients($search_term);
        
        wp_send_json_success($results);
    }
    
    public function ajax_get_patient_history() {
        check_ajax_referer('cms_nonce', 'nonce');
        
        if (!current_user_can('view_patients')) {
            wp_die('Unauthorized');
        }
        
        $patient_id = intval($_POST['patient_id']);
        $patient = new CMS_Patient();
        $history = $patient->get_patient_history($patient_id);
        
        wp_send_json_success($history);
    }
    
    public function ajax_upload_file() {
        check_ajax_referer('cms_nonce', 'nonce');
        
        if (!current_user_can('edit_patients')) {
            wp_die('Unauthorized');
        }
        
        if (!isset($_FILES['file'])) {
            wp_send_json_error('No file uploaded');
        }
        
        $file = $_FILES['file'];
        $patient_id = intval($_POST['patient_id']);
        $file_type = sanitize_text_field($_POST['file_type']);
        
        $upload_dir = wp_upload_dir();
        $target_dir = $upload_dir['basedir'] . '/clinic-files/' . $patient_id . '/';
        
        if (!file_exists($target_dir)) {
            wp_mkdir_p($target_dir);
        }
        
        $file_name = sanitize_file_name($file['name']);
        $target_file = $target_dir . $file_name;
        
        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            $file_url = $upload_dir['baseurl'] . '/clinic-files/' . $patient_id . '/' . $file_name;
            
            // Save file record to database
            global $wpdb;
            $wpdb->insert(
                $wpdb->prefix . 'cms_patient_files',
                array(
                    'patient_id' => $patient_id,
                    'file_name' => $file_name,
                    'file_url' => $file_url,
                    'file_type' => $file_type,
                    'uploaded_at' => current_time('mysql')
                ),
                array('%d', '%s', '%s', '%s', '%s')
            );
            
            wp_send_json_success(array('file_url' => $file_url, 'file_name' => $file_name));
        } else {
            wp_send_json_error('Failed to upload file');
        }
    }
}