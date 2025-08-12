<?php
/**
 * Admin interface management class
 */
class CMS_Admin {
    
    private $plugin_name;
    private $version;
    
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Check if user has clinic management capabilities
        if (!current_user_can('manage_clinic')) {
            return;
        }
        
        // Main menu
        add_menu_page(
            'Clinic Management',
            'Clinic Management',
            'manage_clinic',
            'clinic-management',
            array($this, 'render_dashboard_page'),
            'dashicons-heart',
            30
        );
        
        // Submenus
        add_submenu_page(
            'clinic-management',
            'Dashboard',
            'Dashboard',
            'manage_clinic',
            'clinic-management',
            array($this, 'render_dashboard_page')
        );
        
        add_submenu_page(
            'clinic-management',
            'Patients',
            'Patients',
            'view_patients',
            'cms-patients',
            array($this, 'render_patients_page')
        );
        
        add_submenu_page(
            'clinic-management',
            'Appointments',
            'Appointments',
            'manage_appointments',
            'cms-appointments',
            array($this, 'render_appointments_page')
        );
        
        add_submenu_page(
            'clinic-management',
            'Prescriptions',
            'Prescriptions',
            'manage_prescriptions',
            'cms-prescriptions',
            array($this, 'render_prescriptions_page')
        );
        
        add_submenu_page(
            'clinic-management',
            'Billing',
            'Billing',
            'manage_billing',
            'cms-billing',
            array($this, 'render_billing_page')
        );
        
        add_submenu_page(
            'clinic-management',
            'Inventory',
            'Inventory',
            'manage_inventory',
            'cms-inventory',
            array($this, 'render_inventory_page')
        );
        
        add_submenu_page(
            'clinic-management',
            'Reports',
            'Reports',
            'view_reports',
            'cms-reports',
            array($this, 'render_reports_page')
        );
        
        add_submenu_page(
            'clinic-management',
            'Settings',
            'Settings',
            'manage_clinic',
            'cms-settings',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Enqueue admin styles
     */
    public function enqueue_styles($hook) {
        if (strpos($hook, 'clinic-management') === false && strpos($hook, 'cms-') === false) {
            return;
        }
        
        wp_enqueue_style(
            $this->plugin_name,
            CMS_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            $this->version,
            'all'
        );
        
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_style('jquery-ui-datepicker');
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_scripts($hook) {
        if (strpos($hook, 'clinic-management') === false && strpos($hook, 'cms-') === false) {
            return;
        }
        
        wp_enqueue_script(
            $this->plugin_name,
            CMS_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'wp-color-picker', 'jquery-ui-datepicker'),
            $this->version,
            false
        );
        
        wp_localize_script($this->plugin_name, 'cms_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cms_nonce'),
            'strings' => array(
                'confirm_delete' => 'Are you sure you want to delete this item?',
                'loading' => 'Loading...',
                'success' => 'Operation completed successfully!',
                'error' => 'An error occurred. Please try again.'
            )
        ));
    }
    
    /**
     * Render dashboard page
     */
    public function render_dashboard_page() {
        $reports = new CMS_Reports();
        $stats = $reports->get_dashboard_stats();
        
        include CMS_PLUGIN_PATH . 'admin/views/dashboard.php';
    }
    
    /**
     * Render patients page
     */
    public function render_patients_page() {
        $patient = new CMS_Patient();
        $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
        
        $patients = $patient->get_patients($page, 20, $search);
        $total_patients = $patient->get_patient_count($search);
        $total_pages = ceil($total_patients / 20);
        
        include CMS_PLUGIN_PATH . 'admin/views/patients.php';
    }
    
    /**
     * Render appointments page
     */
    public function render_appointments_page() {
        $appointment = new CMS_Appointment();
        $date = isset($_GET['date']) ? sanitize_text_field($_GET['date']) : date('Y-m-d');
        $doctor_id = isset($_GET['doctor']) ? intval($_GET['doctor']) : null;
        
        $appointments = $appointment->get_appointments_by_date($date, $doctor_id);
        $doctors = get_users(array('role__in' => array('administrator', 'doctor')));
        
        include CMS_PLUGIN_PATH . 'admin/views/appointments.php';
    }
    
    /**
     * Render prescriptions page
     */
    public function render_prescriptions_page() {
        $prescription = new CMS_Prescription();
        $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
        
        $prescriptions = $prescription->get_recent_prescriptions(20);
        
        include CMS_PLUGIN_PATH . 'admin/views/prescriptions.php';
    }
    
    /**
     * Render billing page
     */
    public function render_billing_page() {
        $billing = new CMS_Billing();
        $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
        
        $invoices = $billing->get_billing_records($page, 20, $status, $search);
        $total_invoices = $billing->get_billing_count($status, $search);
        $total_pages = ceil($total_invoices / 20);
        
        include CMS_PLUGIN_PATH . 'admin/views/billing.php';
    }
    
    /**
     * Render inventory page
     */
    public function render_inventory_page() {
        $inventory = new CMS_Inventory();
        $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $category = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '';
        $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
        
        $medicines = $inventory->get_medicines($page, 20, $category, $search);
        $total_medicines = $inventory->get_medicine_count($category, $search);
        $total_pages = ceil($total_medicines / 20);
        $categories = $inventory->get_medicine_categories();
        
        include CMS_PLUGIN_PATH . 'admin/views/inventory.php';
    }
    
    /**
     * Render reports page
     */
    public function render_reports_page() {
        $reports = new CMS_Reports();
        $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : date('Y-m-01');
        $end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : date('Y-m-t');
        
        $report_data = $reports->generate_comprehensive_report($start_date, $end_date);
        $monthly_revenue = $reports->get_monthly_revenue_data();
        $patient_growth = $reports->get_patient_growth_data();
        $appointment_trends = $reports->get_appointment_trends();
        
        include CMS_PLUGIN_PATH . 'admin/views/reports.php';
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (isset($_POST['submit'])) {
            $this->save_settings();
        }
        
        include CMS_PLUGIN_PATH . 'admin/views/settings.php';
    }
    
    /**
     * Save settings
     */
    private function save_settings() {
        if (!current_user_can('manage_clinic')) {
            return;
        }
        
        check_admin_referer('cms_settings_nonce');
        
        $settings = array(
            'cms_clinic_name' => sanitize_text_field($_POST['clinic_name']),
            'cms_clinic_address' => sanitize_textarea_field($_POST['clinic_address']),
            'cms_clinic_phone' => sanitize_text_field($_POST['clinic_phone']),
            'cms_clinic_email' => sanitize_email($_POST['clinic_email']),
            'cms_consultation_fee' => floatval($_POST['consultation_fee']),
            'cms_appointment_duration' => intval($_POST['appointment_duration']),
            'cms_working_hours' => sanitize_text_field($_POST['working_hours']),
            'cms_currency' => sanitize_text_field($_POST['currency']),
            'cms_timezone' => sanitize_text_field($_POST['timezone']),
            'cms_email_enabled' => isset($_POST['email_enabled']) ? '1' : '0',
            'cms_sms_enabled' => isset($_POST['sms_enabled']) ? '1' : '0'
        );
        
        foreach ($settings as $key => $value) {
            update_option($key, $value);
        }
        
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success is-dismissible"><p>Settings saved successfully!</p></div>';
        });
    }
    
    // AJAX Handlers
    
    /**
     * Add patient AJAX handler
     */
    public function ajax_add_patient() {
        check_ajax_referer('cms_nonce', 'nonce');
        
        if (!current_user_can('edit_patients')) {
            wp_die('Unauthorized');
        }
        
        $patient = new CMS_Patient();
        $result = $patient->add_patient($_POST);
        
        if ($result) {
            wp_send_json_success(array('patient_id' => $result, 'message' => 'Patient added successfully'));
        } else {
            wp_send_json_error('Failed to add patient');
        }
    }
    
    /**
     * Update patient AJAX handler
     */
    public function ajax_update_patient() {
        check_ajax_referer('cms_nonce', 'nonce');
        
        if (!current_user_can('edit_patients')) {
            wp_die('Unauthorized');
        }
        
        $patient_id = sanitize_text_field($_POST['patient_id']);
        $patient = new CMS_Patient();
        $result = $patient->update_patient($patient_id, $_POST);
        
        if ($result) {
            wp_send_json_success('Patient updated successfully');
        } else {
            wp_send_json_error('Failed to update patient');
        }
    }
    
    /**
     * Delete patient AJAX handler
     */
    public function ajax_delete_patient() {
        check_ajax_referer('cms_nonce', 'nonce');
        
        if (!current_user_can('delete_patients')) {
            wp_die('Unauthorized');
        }
        
        $patient_id = sanitize_text_field($_POST['patient_id']);
        $patient = new CMS_Patient();
        $result = $patient->delete_patient($patient_id);
        
        if ($result) {
            wp_send_json_success('Patient deleted successfully');
        } else {
            wp_send_json_error('Failed to delete patient');
        }
    }
    
    /**
     * Add appointment AJAX handler
     */
    public function ajax_add_appointment() {
        check_ajax_referer('cms_nonce', 'nonce');
        
        if (!current_user_can('manage_appointments')) {
            wp_die('Unauthorized');
        }
        
        $appointment = new CMS_Appointment();
        $result = $appointment->add_appointment($_POST);
        
        if ($result) {
            wp_send_json_success(array('appointment_id' => $result, 'message' => 'Appointment added successfully'));
        } else {
            wp_send_json_error('Failed to add appointment - time slot may be unavailable');
        }
    }
    
    /**
     * Update appointment AJAX handler
     */
    public function ajax_update_appointment() {
        check_ajax_referer('cms_nonce', 'nonce');
        
        if (!current_user_can('manage_appointments')) {
            wp_die('Unauthorized');
        }
        
        $appointment_id = intval($_POST['appointment_id']);
        $appointment = new CMS_Appointment();
        $result = $appointment->update_appointment($appointment_id, $_POST);
        
        if ($result) {
            wp_send_json_success('Appointment updated successfully');
        } else {
            wp_send_json_error('Failed to update appointment');
        }
    }
    
    /**
     * Delete appointment AJAX handler
     */
    public function ajax_delete_appointment() {
        check_ajax_referer('cms_nonce', 'nonce');
        
        if (!current_user_can('manage_appointments')) {
            wp_die('Unauthorized');
        }
        
        $appointment_id = intval($_POST['appointment_id']);
        $appointment = new CMS_Appointment();
        $result = $appointment->delete_appointment($appointment_id);
        
        if ($result) {
            wp_send_json_success('Appointment deleted successfully');
        } else {
            wp_send_json_error('Failed to delete appointment');
        }
    }
    
    /**
     * Add prescription AJAX handler
     */
    public function ajax_add_prescription() {
        check_ajax_referer('cms_nonce', 'nonce');
        
        if (!current_user_can('manage_prescriptions')) {
            wp_die('Unauthorized');
        }
        
        $prescription = new CMS_Prescription();
        $result = $prescription->add_prescription($_POST);
        
        if ($result) {
            wp_send_json_success(array('prescription_id' => $result, 'message' => 'Prescription added successfully'));
        } else {
            wp_send_json_error('Failed to add prescription');
        }
    }
    
    /**
     * Add billing AJAX handler
     */
    public function ajax_add_billing() {
        check_ajax_referer('cms_nonce', 'nonce');
        
        if (!current_user_can('manage_billing')) {
            wp_die('Unauthorized');
        }
        
        $billing = new CMS_Billing();
        $result = $billing->add_billing($_POST);
        
        if ($result) {
            wp_send_json_success(array('billing_id' => $result, 'message' => 'Billing record added successfully'));
        } else {
            wp_send_json_error('Failed to add billing record');
        }
    }
    
    /**
     * Update inventory AJAX handler
     */
    public function ajax_update_inventory() {
        check_ajax_referer('cms_nonce', 'nonce');
        
        if (!current_user_can('manage_inventory')) {
            wp_die('Unauthorized');
        }
        
        $inventory = new CMS_Inventory();
        $medicine_id = intval($_POST['medicine_id']);
        $result = $inventory->update_medicine($medicine_id, $_POST);
        
        if ($result) {
            wp_send_json_success('Inventory updated successfully');
        } else {
            wp_send_json_error('Failed to update inventory');
        }
    }
}