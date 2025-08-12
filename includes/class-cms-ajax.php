<?php
/**
 * AJAX handlers for the clinic management system
 */

if (!defined('ABSPATH')) {
    exit;
}

class CMS_Ajax
{
    private static $instance = null;

    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        // Dashboard AJAX handlers
        add_action('wp_ajax_cms_dashboard_data', array($this, 'getDashboardData'));
        
        // General AJAX handlers
        add_action('wp_ajax_cms_get_doctors', array($this, 'getDoctors'));
        add_action('wp_ajax_cms_get_patients_list', array($this, 'getPatientsList'));
        add_action('wp_ajax_cms_upload_file', array($this, 'uploadFile'));
        
        // Settings AJAX handlers
        add_action('wp_ajax_cms_save_settings', array($this, 'saveSettings'));
        add_action('wp_ajax_cms_get_settings', array($this, 'getSettings'));
    }

    /**
     * Get dashboard data
     */
    public function getDashboardData()
    {
        check_ajax_referer('cms_ajax_nonce', 'nonce');

        if (!CMS_User_Roles::canAccessCMS()) {
            wp_die(json_encode(array('success' => false, 'message' => __('Permission denied', 'clinic-management'))));
        }

        $data_type = sanitize_text_field($_POST['data_type'] ?? '');

        switch ($data_type) {
            case 'todays_appointments':
                $data = $this->getTodaysAppointments();
                break;
            case 'recent_patients':
                $data = $this->getRecentPatients();
                break;
            case 'pending_bills':
                $data = $this->getPendingBills();
                break;
            case 'low_stock':
                $data = $this->getLowStockItems();
                break;
            default:
                wp_die(json_encode(array('success' => false, 'message' => __('Invalid data type', 'clinic-management'))));
        }

        wp_die(json_encode(array('success' => true, 'data' => $data)));
    }

    /**
     * Get today's appointments for dashboard
     */
    private function getTodaysAppointments()
    {
        global $wpdb;
        
        $today = current_time('Y-m-d');
        
        $appointments = $wpdb->get_results($wpdb->prepare("
            SELECT a.*, p.first_name, p.last_name, p.phone, u.display_name as doctor_name
            FROM {$wpdb->prefix}cms_appointments a
            LEFT JOIN {$wpdb->prefix}cms_patients p ON a.patient_id = p.id
            LEFT JOIN {$wpdb->users} u ON a.doctor_id = u.ID
            WHERE a.appointment_date = %s AND a.status != 'cancelled'
            ORDER BY a.appointment_time ASC
            LIMIT 10
        ", $today));

        $html = '';
        if (empty($appointments)) {
            $html = '<div class="text-center text-muted">' . __('No appointments scheduled for today.', 'clinic-management') . '</div>';
        } else {
            foreach ($appointments as $appointment) {
                $status_class = 'secondary';
                switch ($appointment->status) {
                    case 'confirmed': $status_class = 'success'; break;
                    case 'in_progress': $status_class = 'primary'; break;
                    case 'completed': $status_class = 'info'; break;
                    case 'no_show': $status_class = 'danger'; break;
                }

                $html .= '<div class="cms-appointment-item d-flex justify-content-between align-items-center py-2 border-bottom">';
                $html .= '<div>';
                $html .= '<strong>' . esc_html($appointment->first_name . ' ' . $appointment->last_name) . '</strong>';
                $html .= '<br><small class="text-muted">' . esc_html(date('g:i A', strtotime($appointment->appointment_time))) . ' - Dr. ' . esc_html($appointment->doctor_name) . '</small>';
                $html .= '</div>';
                $html .= '<span class="badge badge-' . $status_class . '">' . esc_html(ucfirst($appointment->status)) . '</span>';
                $html .= '</div>';
            }
        }

        return $html;
    }

    /**
     * Get recent patients for dashboard
     */
    private function getRecentPatients()
    {
        global $wpdb;
        
        $patients = $wpdb->get_results("
            SELECT patient_id, first_name, last_name, phone, created_at
            FROM {$wpdb->prefix}cms_patients
            ORDER BY created_at DESC
            LIMIT 10
        ");

        $html = '';
        if (empty($patients)) {
            $html = '<div class="text-center text-muted">' . __('No patients registered yet.', 'clinic-management') . '</div>';
        } else {
            foreach ($patients as $patient) {
                $html .= '<div class="cms-patient-item d-flex justify-content-between align-items-center py-2 border-bottom">';
                $html .= '<div>';
                $html .= '<strong>' . esc_html($patient->first_name . ' ' . $patient->last_name) . '</strong>';
                $html .= '<br><small class="text-muted">' . esc_html($patient->patient_id) . ' • ' . esc_html($patient->phone) . '</small>';
                $html .= '</div>';
                $html .= '<small class="text-muted">' . esc_html(human_time_diff(strtotime($patient->created_at), current_time('timestamp'))) . ' ago</small>';
                $html .= '</div>';
            }
        }

        return $html;
    }

    /**
     * Get pending bills for dashboard
     */
    private function getPendingBills()
    {
        global $wpdb;
        
        $bills = $wpdb->get_results("
            SELECT b.*, p.first_name, p.last_name, p.patient_id as patient_code
            FROM {$wpdb->prefix}cms_billing b
            LEFT JOIN {$wpdb->prefix}cms_patients p ON b.patient_id = p.id
            WHERE b.payment_status IN ('unpaid', 'partial')
            ORDER BY b.bill_date DESC
            LIMIT 10
        ");

        $currency_symbol = CMS_Database::getSetting('currency_symbol', '$');
        $html = '';
        
        if (empty($bills)) {
            $html = '<div class="text-center text-muted">' . __('No pending bills.', 'clinic-management') . '</div>';
        } else {
            foreach ($bills as $bill) {
                $due_amount = $bill->total_amount - $bill->paid_amount;
                $status_class = $bill->payment_status === 'partial' ? 'warning' : 'danger';
                
                $html .= '<div class="cms-bill-item d-flex justify-content-between align-items-center py-2 border-bottom">';
                $html .= '<div>';
                $html .= '<strong>' . esc_html($bill->first_name . ' ' . $bill->last_name) . '</strong>';
                $html .= '<br><small class="text-muted">' . esc_html($bill->bill_number) . ' • ' . esc_html(date('M j, Y', strtotime($bill->bill_date))) . '</small>';
                $html .= '</div>';
                $html .= '<div class="text-right">';
                $html .= '<span class="badge badge-' . $status_class . '">' . esc_html($currency_symbol . number_format($due_amount, 2)) . '</span>';
                $html .= '</div>';
                $html .= '</div>';
            }
        }

        return $html;
    }

    /**
     * Get low stock items for dashboard
     */
    private function getLowStockItems()
    {
        global $wpdb;
        
        $low_stock_medicines = $wpdb->get_results("
            SELECT medicine_name, quantity_in_stock, minimum_stock_level
            FROM {$wpdb->prefix}cms_inventory
            WHERE status = 'active' AND quantity_in_stock <= minimum_stock_level
            ORDER BY quantity_in_stock ASC
            LIMIT 10
        ");

        $html = '';
        if (empty($low_stock_medicines)) {
            $html = '<div class="text-center text-success">';
            $html .= '<i class="fas fa-check-circle fa-2x mb-2"></i>';
            $html .= '<div>' . __('All medicines are in stock.', 'clinic-management') . '</div>';
            $html .= '</div>';
        } else {
            foreach ($low_stock_medicines as $medicine) {
                $urgency_class = $medicine->quantity_in_stock == 0 ? 'danger' : 'warning';
                
                $html .= '<div class="cms-stock-item d-flex justify-content-between align-items-center py-2 border-bottom">';
                $html .= '<div>';
                $html .= '<strong>' . esc_html($medicine->medicine_name) . '</strong>';
                $html .= '<br><small class="text-muted">' . sprintf(__('Min. level: %d', 'clinic-management'), $medicine->minimum_stock_level) . '</small>';
                $html .= '</div>';
                $html .= '<span class="badge badge-' . $urgency_class . '">' . sprintf(__('%d left', 'clinic-management'), $medicine->quantity_in_stock) . '</span>';
                $html .= '</div>';
            }
        }

        return $html;
    }

    /**
     * Get doctors list
     */
    public function getDoctors()
    {
        check_ajax_referer('cms_ajax_nonce', 'nonce');

        if (!CMS_User_Roles::canAccessCMS()) {
            wp_die(json_encode(array('success' => false, 'message' => __('Permission denied', 'clinic-management'))));
        }

        $doctors = CMS_User_Roles::getDoctors();
        
        $results = array();
        foreach ($doctors as $doctor) {
            $results[] = array(
                'id' => $doctor->ID,
                'name' => $doctor->display_name,
                'email' => $doctor->user_email,
            );
        }

        wp_die(json_encode(array('success' => true, 'data' => $results)));
    }

    /**
     * Get patients list for dropdowns
     */
    public function getPatientsList()
    {
        check_ajax_referer('cms_ajax_nonce', 'nonce');

        if (!CMS_User_Roles::canAccessCMS()) {
            wp_die(json_encode(array('success' => false, 'message' => __('Permission denied', 'clinic-management'))));
        }

        $search_term = sanitize_text_field($_POST['search'] ?? '');
        $limit = intval($_POST['limit'] ?? 20);

        if (empty($search_term)) {
            $patients = CMS_Patients::getPatients($limit, 0, 'first_name', 'ASC');
        } else {
            $patients = CMS_Patients::searchPatients($search_term, $limit);
        }

        $results = array();
        foreach ($patients as $patient) {
            $results[] = array(
                'id' => $patient->id,
                'patient_id' => $patient->patient_id,
                'name' => $patient->first_name . ' ' . $patient->last_name,
                'phone' => $patient->phone,
                'age' => CMS_Patients::calculateAge($patient->date_of_birth),
            );
        }

        wp_die(json_encode(array('success' => true, 'data' => $results)));
    }

    /**
     * Upload file
     */
    public function uploadFile()
    {
        check_ajax_referer('cms_ajax_nonce', 'nonce');

        if (!CMS_User_Roles::canAccessCMS()) {
            wp_die(json_encode(array('success' => false, 'message' => __('Permission denied', 'clinic-management'))));
        }

        $file_type = sanitize_text_field($_POST['file_type'] ?? 'general');
        
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            wp_die(json_encode(array('success' => false, 'message' => __('No file uploaded or upload error', 'clinic-management'))));
        }

        // Validate file type based on purpose
        $allowed_types = array(
            'image' => array('jpg', 'jpeg', 'png', 'gif'),
            'document' => array('pdf', 'doc', 'docx'),
            'medical_report' => array('pdf', 'jpg', 'jpeg', 'png'),
            'general' => array('pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'),
        );

        $file_extension = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        $valid_types = $allowed_types[$file_type] ?? $allowed_types['general'];

        if (!in_array($file_extension, $valid_types)) {
            wp_die(json_encode(array('success' => false, 'message' => __('Invalid file type', 'clinic-management'))));
        }

        // Handle file upload
        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }

        $upload_overrides = array('test_form' => false);
        $uploaded_file = wp_handle_upload($_FILES['file'], $upload_overrides);

        if (isset($uploaded_file['error'])) {
            wp_die(json_encode(array('success' => false, 'message' => $uploaded_file['error'])));
        }

        wp_die(json_encode(array(
            'success' => true, 
            'message' => __('File uploaded successfully', 'clinic-management'),
            'url' => $uploaded_file['url'],
            'file' => $uploaded_file['file']
        )));
    }

    /**
     * Save settings
     */
    public function saveSettings()
    {
        check_ajax_referer('cms_ajax_nonce', 'nonce');

        if (!CMS_User_Roles::userCan('cms_manage_settings')) {
            wp_die(json_encode(array('success' => false, 'message' => __('Permission denied', 'clinic-management'))));
        }

        $settings = $_POST['settings'] ?? array();
        $saved_count = 0;

        foreach ($settings as $key => $value) {
            $key = sanitize_text_field($key);
            
            // Sanitize value based on setting type
            if (in_array($key, array('clinic_address', 'prescription_template', 'prescription_footer'))) {
                $value = sanitize_textarea_field($value);
            } elseif (in_array($key, array('clinic_email'))) {
                $value = sanitize_email($value);
            } elseif (in_array($key, array('consultation_fee_allopathy', 'consultation_fee_ayurveda', 'appointment_duration', 'appointment_reminder_hours', 'low_stock_threshold'))) {
                $value = intval($value);
            } else {
                $value = sanitize_text_field($value);
            }
            
            if (CMS_Database::updateSetting($key, $value)) {
                $saved_count++;
            }
        }

        if ($saved_count > 0) {
            wp_die(json_encode(array('success' => true, 'message' => sprintf(__('%d settings saved successfully', 'clinic-management'), $saved_count))));
        } else {
            wp_die(json_encode(array('success' => false, 'message' => __('No settings were updated', 'clinic-management'))));
        }
    }

    /**
     * Get settings
     */
    public function getSettings()
    {
        check_ajax_referer('cms_ajax_nonce', 'nonce');

        if (!CMS_User_Roles::canAccessCMS()) {
            wp_die(json_encode(array('success' => false, 'message' => __('Permission denied', 'clinic-management'))));
        }

        global $wpdb;
        
        $settings = $wpdb->get_results("
            SELECT setting_key, setting_value, setting_type 
            FROM {$wpdb->prefix}cms_settings 
            ORDER BY setting_key
        ");

        $settings_array = array();
        foreach ($settings as $setting) {
            $settings_array[$setting->setting_key] = array(
                'value' => $setting->setting_value,
                'type' => $setting->setting_type,
            );
        }

        wp_die(json_encode(array('success' => true, 'data' => $settings_array)));
    }
}