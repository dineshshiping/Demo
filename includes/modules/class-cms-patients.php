<?php
/**
 * Patient management module
 */

if (!defined('ABSPATH')) {
    exit;
}

class CMS_Patients
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
        add_action('wp_ajax_cms_search_patients', array($this, 'ajaxSearchPatients'));
        add_action('wp_ajax_cms_get_patient_details', array($this, 'ajaxGetPatientDetails'));
        add_action('wp_ajax_cms_save_patient', array($this, 'ajaxSavePatient'));
        add_action('wp_ajax_cms_upload_patient_photo', array($this, 'ajaxUploadPatientPhoto'));
        add_action('wp_ajax_cms_upload_medical_report', array($this, 'ajaxUploadMedicalReport'));
    }

    /**
     * Generate unique patient ID
     */
    public static function generatePatientId()
    {
        global $wpdb;
        
        $prefix = CMS_Database::getSetting('patient_id_prefix', 'PAT');
        $year = date('Y');
        
        // Get the last patient ID for current year
        $last_number = $wpdb->get_var($wpdb->prepare("
            SELECT MAX(CAST(SUBSTRING(patient_id, %d) AS UNSIGNED)) 
            FROM {$wpdb->prefix}cms_patients 
            WHERE patient_id LIKE %s
        ", strlen($prefix . $year) + 1, $prefix . $year . '%'));
        
        $next_number = ($last_number ? $last_number + 1 : 1);
        
        return $prefix . $year . str_pad($next_number, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Create new patient
     */
    public static function createPatient($data)
    {
        global $wpdb;

        // Validate required fields
        if (empty($data['first_name']) || empty($data['last_name']) || empty($data['phone'])) {
            return new WP_Error('missing_data', __('First name, last name, and phone are required.', 'clinic-management'));
        }

        // Check if patient with same phone exists
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}cms_patients WHERE phone = %s",
            $data['phone']
        ));

        if ($existing) {
            return new WP_Error('patient_exists', __('Patient with this phone number already exists.', 'clinic-management'));
        }

        // Generate patient ID
        $patient_id = self::generatePatientId();

        // Prepare data
        $insert_data = array(
            'patient_id' => $patient_id,
            'first_name' => sanitize_text_field($data['first_name']),
            'last_name' => sanitize_text_field($data['last_name']),
            'date_of_birth' => !empty($data['date_of_birth']) ? sanitize_text_field($data['date_of_birth']) : null,
            'gender' => !empty($data['gender']) ? sanitize_text_field($data['gender']) : null,
            'phone' => sanitize_text_field($data['phone']),
            'email' => !empty($data['email']) ? sanitize_email($data['email']) : null,
            'address' => !empty($data['address']) ? sanitize_textarea_field($data['address']) : null,
            'emergency_contact' => !empty($data['emergency_contact']) ? sanitize_text_field($data['emergency_contact']) : null,
            'emergency_phone' => !empty($data['emergency_phone']) ? sanitize_text_field($data['emergency_phone']) : null,
            'blood_group' => !empty($data['blood_group']) ? sanitize_text_field($data['blood_group']) : null,
            'allergies' => !empty($data['allergies']) ? sanitize_textarea_field($data['allergies']) : null,
            'medical_history' => !empty($data['medical_history']) ? sanitize_textarea_field($data['medical_history']) : null,
            'photo_url' => !empty($data['photo_url']) ? sanitize_url($data['photo_url']) : null,
            'created_by' => get_current_user_id(),
        );

        $result = $wpdb->insert(
            $wpdb->prefix . 'cms_patients',
            $insert_data,
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d')
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Failed to create patient record.', 'clinic-management'));
        }

        return $wpdb->insert_id;
    }

    /**
     * Update patient
     */
    public static function updatePatient($patient_id, $data)
    {
        global $wpdb;

        // Validate patient exists
        $patient = self::getPatient($patient_id);
        if (!$patient) {
            return new WP_Error('patient_not_found', __('Patient not found.', 'clinic-management'));
        }

        // Check if phone is being changed and already exists
        if (!empty($data['phone']) && $data['phone'] !== $patient->phone) {
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}cms_patients WHERE phone = %s AND id != %d",
                $data['phone'], $patient_id
            ));

            if ($existing) {
                return new WP_Error('phone_exists', __('Another patient with this phone number already exists.', 'clinic-management'));
            }
        }

        // Prepare update data
        $update_data = array();
        $update_formats = array();

        $fields = array(
            'first_name' => '%s',
            'last_name' => '%s',
            'date_of_birth' => '%s',
            'gender' => '%s',
            'phone' => '%s',
            'email' => '%s',
            'address' => '%s',
            'emergency_contact' => '%s',
            'emergency_phone' => '%s',
            'blood_group' => '%s',
            'allergies' => '%s',
            'medical_history' => '%s',
            'photo_url' => '%s',
        );

        foreach ($fields as $field => $format) {
            if (isset($data[$field])) {
                if (in_array($field, array('first_name', 'last_name', 'phone'))) {
                    $update_data[$field] = sanitize_text_field($data[$field]);
                } elseif ($field === 'email') {
                    $update_data[$field] = sanitize_email($data[$field]);
                } elseif (in_array($field, array('address', 'allergies', 'medical_history'))) {
                    $update_data[$field] = sanitize_textarea_field($data[$field]);
                } elseif ($field === 'photo_url') {
                    $update_data[$field] = sanitize_url($data[$field]);
                } else {
                    $update_data[$field] = sanitize_text_field($data[$field]);
                }
                $update_formats[] = $format;
            }
        }

        if (empty($update_data)) {
            return new WP_Error('no_data', __('No data to update.', 'clinic-management'));
        }

        $result = $wpdb->update(
            $wpdb->prefix . 'cms_patients',
            $update_data,
            array('id' => $patient_id),
            $update_formats,
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Failed to update patient record.', 'clinic-management'));
        }

        return true;
    }

    /**
     * Get patient by ID
     */
    public static function getPatient($patient_id)
    {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}cms_patients WHERE id = %d",
            $patient_id
        ));
    }

    /**
     * Get patient by patient ID (not database ID)
     */
    public static function getPatientByPatientId($patient_id)
    {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}cms_patients WHERE patient_id = %s",
            $patient_id
        ));
    }

    /**
     * Search patients
     */
    public static function searchPatients($search_term, $limit = 20, $offset = 0)
    {
        global $wpdb;

        $search_term = '%' . $wpdb->esc_like($search_term) . '%';

        return $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}cms_patients 
            WHERE first_name LIKE %s 
               OR last_name LIKE %s 
               OR phone LIKE %s 
               OR patient_id LIKE %s
               OR email LIKE %s
            ORDER BY first_name, last_name
            LIMIT %d OFFSET %d
        ", $search_term, $search_term, $search_term, $search_term, $search_term, $limit, $offset));
    }

    /**
     * Get all patients with pagination
     */
    public static function getPatients($limit = 20, $offset = 0, $orderby = 'created_at', $order = 'DESC')
    {
        global $wpdb;

        $allowed_orderby = array('created_at', 'first_name', 'last_name', 'patient_id');
        $allowed_order = array('ASC', 'DESC');

        if (!in_array($orderby, $allowed_orderby)) {
            $orderby = 'created_at';
        }

        if (!in_array($order, $allowed_order)) {
            $order = 'DESC';
        }

        return $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}cms_patients 
            ORDER BY {$orderby} {$order}
            LIMIT %d OFFSET %d
        ", $limit, $offset));
    }

    /**
     * Get patients count
     */
    public static function getPatientsCount($search_term = '')
    {
        global $wpdb;

        if (empty($search_term)) {
            return $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}cms_patients");
        }

        $search_term = '%' . $wpdb->esc_like($search_term) . '%';

        return $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}cms_patients 
            WHERE first_name LIKE %s 
               OR last_name LIKE %s 
               OR phone LIKE %s 
               OR patient_id LIKE %s
               OR email LIKE %s
        ", $search_term, $search_term, $search_term, $search_term, $search_term));
    }

    /**
     * Get patient's medical history
     */
    public static function getPatientMedicalHistory($patient_id)
    {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare("
            SELECT c.*, u.display_name as doctor_name,
                   p.medicines, p.instructions as prescription_instructions,
                   b.total_amount, b.payment_status
            FROM {$wpdb->prefix}cms_consultations c
            LEFT JOIN {$wpdb->users} u ON c.doctor_id = u.ID
            LEFT JOIN {$wpdb->prefix}cms_prescriptions p ON c.id = p.consultation_id
            LEFT JOIN {$wpdb->prefix}cms_billing b ON c.id = b.consultation_id
            WHERE c.patient_id = %d
            ORDER BY c.consultation_date DESC
        ", $patient_id));
    }

    /**
     * Get patient's medical reports
     */
    public static function getPatientMedicalReports($patient_id)
    {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare("
            SELECT r.*, u.display_name as uploaded_by_name
            FROM {$wpdb->prefix}cms_medical_reports r
            LEFT JOIN {$wpdb->users} u ON r.uploaded_by = u.ID
            WHERE r.patient_id = %d
            ORDER BY r.report_date DESC, r.uploaded_at DESC
        ", $patient_id));
    }

    /**
     * Upload medical report
     */
    public static function uploadMedicalReport($patient_id, $file_data, $report_data)
    {
        global $wpdb;

        // Validate patient exists
        $patient = self::getPatient($patient_id);
        if (!$patient) {
            return new WP_Error('patient_not_found', __('Patient not found.', 'clinic-management'));
        }

        // Handle file upload
        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }

        $upload_overrides = array('test_form' => false);
        $uploaded_file = wp_handle_upload($file_data, $upload_overrides);

        if (isset($uploaded_file['error'])) {
            return new WP_Error('upload_error', $uploaded_file['error']);
        }

        // Insert report record
        $insert_data = array(
            'patient_id' => $patient_id,
            'consultation_id' => !empty($report_data['consultation_id']) ? intval($report_data['consultation_id']) : null,
            'report_type' => !empty($report_data['report_type']) ? sanitize_text_field($report_data['report_type']) : 'general',
            'report_name' => sanitize_text_field($report_data['report_name']),
            'file_url' => $uploaded_file['url'],
            'file_type' => pathinfo($uploaded_file['file'], PATHINFO_EXTENSION),
            'report_date' => !empty($report_data['report_date']) ? sanitize_text_field($report_data['report_date']) : current_time('Y-m-d'),
            'notes' => !empty($report_data['notes']) ? sanitize_textarea_field($report_data['notes']) : null,
            'uploaded_by' => get_current_user_id(),
        );

        $result = $wpdb->insert(
            $wpdb->prefix . 'cms_medical_reports',
            $insert_data,
            array('%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d')
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Failed to save report record.', 'clinic-management'));
        }

        return $wpdb->insert_id;
    }

    /**
     * AJAX: Search patients
     */
    public function ajaxSearchPatients()
    {
        check_ajax_referer('cms_ajax_nonce', 'nonce');

        if (!CMS_User_Roles::userCan('cms_manage_patients')) {
            wp_die(json_encode(array('success' => false, 'message' => __('Permission denied', 'clinic-management'))));
        }

        $search_term = sanitize_text_field($_POST['search_term']);
        $patients = self::searchPatients($search_term, 10);

        $results = array();
        foreach ($patients as $patient) {
            $results[] = array(
                'id' => $patient->id,
                'patient_id' => $patient->patient_id,
                'name' => $patient->first_name . ' ' . $patient->last_name,
                'phone' => $patient->phone,
                'age' => self::calculateAge($patient->date_of_birth),
            );
        }

        wp_die(json_encode(array('success' => true, 'data' => $results)));
    }

    /**
     * AJAX: Get patient details
     */
    public function ajaxGetPatientDetails()
    {
        check_ajax_referer('cms_ajax_nonce', 'nonce');

        if (!CMS_User_Roles::userCan('cms_manage_patients')) {
            wp_die(json_encode(array('success' => false, 'message' => __('Permission denied', 'clinic-management'))));
        }

        $patient_id = intval($_POST['patient_id']);
        $patient = self::getPatient($patient_id);

        if (!$patient) {
            wp_die(json_encode(array('success' => false, 'message' => __('Patient not found', 'clinic-management'))));
        }

        // Get additional data
        $medical_history = self::getPatientMedicalHistory($patient_id);
        $medical_reports = self::getPatientMedicalReports($patient_id);

        $patient_data = array(
            'patient' => $patient,
            'age' => self::calculateAge($patient->date_of_birth),
            'medical_history' => $medical_history,
            'medical_reports' => $medical_reports,
        );

        wp_die(json_encode(array('success' => true, 'data' => $patient_data)));
    }

    /**
     * AJAX: Save patient
     */
    public function ajaxSavePatient()
    {
        check_ajax_referer('cms_ajax_nonce', 'nonce');

        if (!CMS_User_Roles::userCan('cms_manage_patients')) {
            wp_die(json_encode(array('success' => false, 'message' => __('Permission denied', 'clinic-management'))));
        }

        $patient_id = !empty($_POST['patient_id']) ? intval($_POST['patient_id']) : 0;
        $patient_data = $_POST['patient_data'];

        if ($patient_id > 0) {
            // Update existing patient
            $result = self::updatePatient($patient_id, $patient_data);
        } else {
            // Create new patient
            $result = self::createPatient($patient_data);
        }

        if (is_wp_error($result)) {
            wp_die(json_encode(array('success' => false, 'message' => $result->get_error_message())));
        }

        $response_data = array('success' => true, 'message' => __('Patient saved successfully', 'clinic-management'));
        if ($patient_id === 0) {
            $response_data['patient_id'] = $result;
        }

        wp_die(json_encode($response_data));
    }

    /**
     * AJAX: Upload patient photo
     */
    public function ajaxUploadPatientPhoto()
    {
        check_ajax_referer('cms_ajax_nonce', 'nonce');

        if (!CMS_User_Roles::userCan('cms_manage_patients')) {
            wp_die(json_encode(array('success' => false, 'message' => __('Permission denied', 'clinic-management'))));
        }

        if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            wp_die(json_encode(array('success' => false, 'message' => __('No file uploaded or upload error', 'clinic-management'))));
        }

        // Handle file upload
        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }

        $upload_overrides = array('test_form' => false);
        $uploaded_file = wp_handle_upload($_FILES['photo'], $upload_overrides);

        if (isset($uploaded_file['error'])) {
            wp_die(json_encode(array('success' => false, 'message' => $uploaded_file['error'])));
        }

        wp_die(json_encode(array('success' => true, 'url' => $uploaded_file['url'])));
    }

    /**
     * AJAX: Upload medical report
     */
    public function ajaxUploadMedicalReport()
    {
        check_ajax_referer('cms_ajax_nonce', 'nonce');

        if (!CMS_User_Roles::userCan('cms_manage_patients')) {
            wp_die(json_encode(array('success' => false, 'message' => __('Permission denied', 'clinic-management'))));
        }

        if (!isset($_FILES['report_file']) || $_FILES['report_file']['error'] !== UPLOAD_ERR_OK) {
            wp_die(json_encode(array('success' => false, 'message' => __('No file uploaded or upload error', 'clinic-management'))));
        }

        $patient_id = intval($_POST['patient_id']);
        $report_data = $_POST['report_data'];

        $result = self::uploadMedicalReport($patient_id, $_FILES['report_file'], $report_data);

        if (is_wp_error($result)) {
            wp_die(json_encode(array('success' => false, 'message' => $result->get_error_message())));
        }

        wp_die(json_encode(array('success' => true, 'message' => __('Medical report uploaded successfully', 'clinic-management'), 'report_id' => $result)));
    }

    /**
     * Calculate age from date of birth
     */
    public static function calculateAge($date_of_birth)
    {
        if (empty($date_of_birth)) {
            return '';
        }

        $dob = new DateTime($date_of_birth);
        $now = new DateTime();
        $age = $dob->diff($now);

        return $age->y;
    }

    /**
     * Get patient statistics
     */
    public static function getPatientStats()
    {
        global $wpdb;

        $stats = array();

        // Total patients
        $stats['total'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}cms_patients");

        // New patients this month
        $this_month = current_time('Y-m');
        $stats['new_this_month'] = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}cms_patients 
            WHERE created_at LIKE %s
        ", $this_month . '%'));

        // Patients by gender
        $stats['by_gender'] = $wpdb->get_results("
            SELECT gender, COUNT(*) as count 
            FROM {$wpdb->prefix}cms_patients 
            WHERE gender IS NOT NULL 
            GROUP BY gender
        ");

        // Patients by age group
        $stats['by_age_group'] = $wpdb->get_results("
            SELECT 
                CASE 
                    WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) < 18 THEN 'Under 18'
                    WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 18 AND 30 THEN '18-30'
                    WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 31 AND 50 THEN '31-50'
                    WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 51 AND 70 THEN '51-70'
                    WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) > 70 THEN 'Over 70'
                    ELSE 'Unknown'
                END as age_group,
                COUNT(*) as count
            FROM {$wpdb->prefix}cms_patients 
            GROUP BY age_group
        ");

        return $stats;
    }
}