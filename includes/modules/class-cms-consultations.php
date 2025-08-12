<?php
/**
 * Consultations management module
 */

if (!defined('ABSPATH')) {
    exit;
}

class CMS_Consultations
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
        add_action('wp_ajax_cms_save_consultation', array($this, 'ajaxSaveConsultation'));
        add_action('wp_ajax_cms_get_consultation', array($this, 'ajaxGetConsultation'));
        add_action('wp_ajax_cms_get_consultations', array($this, 'ajaxGetConsultations'));
        add_action('wp_ajax_cms_get_consultation_templates', array($this, 'ajaxGetConsultationTemplates'));
    }

    /**
     * Create new consultation
     */
    public static function createConsultation($data)
    {
        global $wpdb;

        // Validate required fields
        if (empty($data['patient_id']) || empty($data['doctor_id']) || empty($data['consultation_date'])) {
            return new WP_Error('missing_data', __('Patient, doctor, and consultation date are required.', 'clinic-management'));
        }

        // Validate consultation type
        $allowed_types = array('allopathy', 'ayurveda');
        $consultation_type = !empty($data['consultation_type']) ? $data['consultation_type'] : 'allopathy';
        if (!in_array($consultation_type, $allowed_types)) {
            $consultation_type = 'allopathy';
        }

        // Prepare vital signs data
        $vital_signs = array();
        if (!empty($data['vital_signs'])) {
            $vital_signs = array(
                'height' => sanitize_text_field($data['vital_signs']['height'] ?? ''),
                'weight' => sanitize_text_field($data['vital_signs']['weight'] ?? ''),
                'temperature' => sanitize_text_field($data['vital_signs']['temperature'] ?? ''),
                'blood_pressure_systolic' => sanitize_text_field($data['vital_signs']['blood_pressure_systolic'] ?? ''),
                'blood_pressure_diastolic' => sanitize_text_field($data['vital_signs']['blood_pressure_diastolic'] ?? ''),
                'pulse_rate' => sanitize_text_field($data['vital_signs']['pulse_rate'] ?? ''),
                'respiratory_rate' => sanitize_text_field($data['vital_signs']['respiratory_rate'] ?? ''),
                'oxygen_saturation' => sanitize_text_field($data['vital_signs']['oxygen_saturation'] ?? ''),
                'bmi' => self::calculateBMI($data['vital_signs']['height'] ?? '', $data['vital_signs']['weight'] ?? ''),
            );
        }

        // Prepare data
        $insert_data = array(
            'patient_id' => intval($data['patient_id']),
            'appointment_id' => !empty($data['appointment_id']) ? intval($data['appointment_id']) : null,
            'doctor_id' => intval($data['doctor_id']),
            'consultation_date' => sanitize_text_field($data['consultation_date']),
            'consultation_type' => $consultation_type,
            'chief_complaint' => !empty($data['chief_complaint']) ? sanitize_textarea_field($data['chief_complaint']) : null,
            'symptoms' => !empty($data['symptoms']) ? sanitize_textarea_field($data['symptoms']) : null,
            'examination_findings' => !empty($data['examination_findings']) ? sanitize_textarea_field($data['examination_findings']) : null,
            'diagnosis' => !empty($data['diagnosis']) ? sanitize_textarea_field($data['diagnosis']) : null,
            'treatment_plan' => !empty($data['treatment_plan']) ? sanitize_textarea_field($data['treatment_plan']) : null,
            'vital_signs' => !empty($vital_signs) ? json_encode($vital_signs) : null,
            'follow_up_date' => !empty($data['follow_up_date']) ? sanitize_text_field($data['follow_up_date']) : null,
            'notes' => !empty($data['notes']) ? sanitize_textarea_field($data['notes']) : null,
            'consultation_fee' => !empty($data['consultation_fee']) ? floatval($data['consultation_fee']) : self::getDefaultConsultationFee($consultation_type),
            'created_by' => get_current_user_id(),
        );

        $result = $wpdb->insert(
            $wpdb->prefix . 'cms_consultations',
            $insert_data,
            array('%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%d')
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Failed to create consultation record.', 'clinic-management'));
        }

        $consultation_id = $wpdb->insert_id;

        // Update appointment status if linked
        if (!empty($data['appointment_id'])) {
            CMS_Appointments::updateAppointment($data['appointment_id'], array('status' => 'completed'));
        }

        return $consultation_id;
    }

    /**
     * Update consultation
     */
    public static function updateConsultation($consultation_id, $data)
    {
        global $wpdb;

        // Validate consultation exists
        $consultation = self::getConsultation($consultation_id);
        if (!$consultation) {
            return new WP_Error('consultation_not_found', __('Consultation not found.', 'clinic-management'));
        }

        // Prepare update data
        $update_data = array();
        $update_formats = array();

        // Prepare vital signs if provided
        if (!empty($data['vital_signs'])) {
            $vital_signs = array(
                'height' => sanitize_text_field($data['vital_signs']['height'] ?? ''),
                'weight' => sanitize_text_field($data['vital_signs']['weight'] ?? ''),
                'temperature' => sanitize_text_field($data['vital_signs']['temperature'] ?? ''),
                'blood_pressure_systolic' => sanitize_text_field($data['vital_signs']['blood_pressure_systolic'] ?? ''),
                'blood_pressure_diastolic' => sanitize_text_field($data['vital_signs']['blood_pressure_diastolic'] ?? ''),
                'pulse_rate' => sanitize_text_field($data['vital_signs']['pulse_rate'] ?? ''),
                'respiratory_rate' => sanitize_text_field($data['vital_signs']['respiratory_rate'] ?? ''),
                'oxygen_saturation' => sanitize_text_field($data['vital_signs']['oxygen_saturation'] ?? ''),
                'bmi' => self::calculateBMI($data['vital_signs']['height'] ?? '', $data['vital_signs']['weight'] ?? ''),
            );
            $data['vital_signs'] = json_encode($vital_signs);
        }

        $fields = array(
            'patient_id' => '%d',
            'appointment_id' => '%d',
            'doctor_id' => '%d',
            'consultation_date' => '%s',
            'consultation_type' => '%s',
            'chief_complaint' => '%s',
            'symptoms' => '%s',
            'examination_findings' => '%s',
            'diagnosis' => '%s',
            'treatment_plan' => '%s',
            'vital_signs' => '%s',
            'follow_up_date' => '%s',
            'notes' => '%s',
            'consultation_fee' => '%f',
        );

        foreach ($fields as $field => $format) {
            if (isset($data[$field])) {
                if (in_array($field, array('patient_id', 'appointment_id', 'doctor_id'))) {
                    $update_data[$field] = intval($data[$field]);
                } elseif ($field === 'consultation_fee') {
                    $update_data[$field] = floatval($data[$field]);
                } elseif (in_array($field, array('chief_complaint', 'symptoms', 'examination_findings', 'diagnosis', 'treatment_plan', 'notes'))) {
                    $update_data[$field] = sanitize_textarea_field($data[$field]);
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
            $wpdb->prefix . 'cms_consultations',
            $update_data,
            array('id' => $consultation_id),
            $update_formats,
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Failed to update consultation record.', 'clinic-management'));
        }

        return true;
    }

    /**
     * Get consultation by ID
     */
    public static function getConsultation($consultation_id)
    {
        global $wpdb;

        $consultation = $wpdb->get_row($wpdb->prepare("
            SELECT c.*, p.first_name, p.last_name, p.patient_id as patient_code, p.date_of_birth,
                   u.display_name as doctor_name,
                   a.appointment_time, a.appointment_type
            FROM {$wpdb->prefix}cms_consultations c
            LEFT JOIN {$wpdb->prefix}cms_patients p ON c.patient_id = p.id
            LEFT JOIN {$wpdb->users} u ON c.doctor_id = u.ID
            LEFT JOIN {$wpdb->prefix}cms_appointments a ON c.appointment_id = a.id
            WHERE c.id = %d
        ", $consultation_id));

        if ($consultation && !empty($consultation->vital_signs)) {
            $consultation->vital_signs = json_decode($consultation->vital_signs, true);
        }

        return $consultation;
    }

    /**
     * Get consultations with filters
     */
    public static function getConsultations($filters = array(), $limit = 20, $offset = 0)
    {
        global $wpdb;

        $where_conditions = array('1=1');
        $prepare_values = array();

        // Date range filter
        if (!empty($filters['date_from'])) {
            $where_conditions[] = "c.consultation_date >= %s";
            $prepare_values[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where_conditions[] = "c.consultation_date <= %s";
            $prepare_values[] = $filters['date_to'];
        }

        // Doctor filter
        if (!empty($filters['doctor_id'])) {
            $where_conditions[] = "c.doctor_id = %d";
            $prepare_values[] = $filters['doctor_id'];
        }

        // Patient filter
        if (!empty($filters['patient_id'])) {
            $where_conditions[] = "c.patient_id = %d";
            $prepare_values[] = $filters['patient_id'];
        }

        // Consultation type filter
        if (!empty($filters['consultation_type'])) {
            $where_conditions[] = "c.consultation_type = %s";
            $prepare_values[] = $filters['consultation_type'];
        }

        // Search filter
        if (!empty($filters['search'])) {
            $search_term = '%' . $wpdb->esc_like($filters['search']) . '%';
            $where_conditions[] = "(p.first_name LIKE %s OR p.last_name LIKE %s OR p.patient_id LIKE %s OR c.diagnosis LIKE %s)";
            $prepare_values[] = $search_term;
            $prepare_values[] = $search_term;
            $prepare_values[] = $search_term;
            $prepare_values[] = $search_term;
        }

        $where_clause = implode(' AND ', $where_conditions);

        // Add limit and offset
        $prepare_values[] = $limit;
        $prepare_values[] = $offset;

        $sql = "
            SELECT c.*, p.first_name, p.last_name, p.patient_id as patient_code,
                   u.display_name as doctor_name
            FROM {$wpdb->prefix}cms_consultations c
            LEFT JOIN {$wpdb->prefix}cms_patients p ON c.patient_id = p.id
            LEFT JOIN {$wpdb->users} u ON c.doctor_id = u.ID
            WHERE {$where_clause}
            ORDER BY c.consultation_date DESC, c.created_at DESC
            LIMIT %d OFFSET %d
        ";

        return $wpdb->get_results($wpdb->prepare($sql, $prepare_values));
    }

    /**
     * Get consultations count
     */
    public static function getConsultationsCount($filters = array())
    {
        global $wpdb;

        $where_conditions = array('1=1');
        $prepare_values = array();

        // Apply same filters as getConsultations
        if (!empty($filters['date_from'])) {
            $where_conditions[] = "c.consultation_date >= %s";
            $prepare_values[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where_conditions[] = "c.consultation_date <= %s";
            $prepare_values[] = $filters['date_to'];
        }

        if (!empty($filters['doctor_id'])) {
            $where_conditions[] = "c.doctor_id = %d";
            $prepare_values[] = $filters['doctor_id'];
        }

        if (!empty($filters['patient_id'])) {
            $where_conditions[] = "c.patient_id = %d";
            $prepare_values[] = $filters['patient_id'];
        }

        if (!empty($filters['consultation_type'])) {
            $where_conditions[] = "c.consultation_type = %s";
            $prepare_values[] = $filters['consultation_type'];
        }

        if (!empty($filters['search'])) {
            $search_term = '%' . $wpdb->esc_like($filters['search']) . '%';
            $where_conditions[] = "(p.first_name LIKE %s OR p.last_name LIKE %s OR p.patient_id LIKE %s OR c.diagnosis LIKE %s)";
            $prepare_values[] = $search_term;
            $prepare_values[] = $search_term;
            $prepare_values[] = $search_term;
            $prepare_values[] = $search_term;
        }

        $where_clause = implode(' AND ', $where_conditions);

        $sql = "
            SELECT COUNT(*)
            FROM {$wpdb->prefix}cms_consultations c
            LEFT JOIN {$wpdb->prefix}cms_patients p ON c.patient_id = p.id
            WHERE {$where_clause}
        ";

        if (empty($prepare_values)) {
            return $wpdb->get_var($sql);
        }

        return $wpdb->get_var($wpdb->prepare($sql, $prepare_values));
    }

    /**
     * Get default consultation fee based on type
     */
    public static function getDefaultConsultationFee($consultation_type)
    {
        $setting_key = 'consultation_fee_' . $consultation_type;
        $default_fee = $consultation_type === 'ayurveda' ? 400 : 500;
        
        return floatval(CMS_Database::getSetting($setting_key, $default_fee));
    }

    /**
     * Calculate BMI
     */
    public static function calculateBMI($height, $weight)
    {
        if (empty($height) || empty($weight) || $height == 0) {
            return '';
        }

        $height_m = floatval($height) / 100; // Convert cm to meters
        $weight_kg = floatval($weight);
        
        if ($height_m > 0 && $weight_kg > 0) {
            return round($weight_kg / ($height_m * $height_m), 1);
        }

        return '';
    }

    /**
     * Get consultation templates
     */
    public static function getConsultationTemplates($type = 'allopathy')
    {
        $templates = array();

        if ($type === 'allopathy') {
            $templates = array(
                'general' => array(
                    'name' => __('General Consultation', 'clinic-management'),
                    'fields' => array('chief_complaint', 'symptoms', 'examination_findings', 'diagnosis', 'treatment_plan', 'vital_signs'),
                ),
                'follow_up' => array(
                    'name' => __('Follow-up Consultation', 'clinic-management'),
                    'fields' => array('symptoms', 'examination_findings', 'treatment_plan', 'vital_signs'),
                ),
                'pediatric' => array(
                    'name' => __('Pediatric Consultation', 'clinic-management'),
                    'fields' => array('chief_complaint', 'symptoms', 'examination_findings', 'diagnosis', 'treatment_plan', 'vital_signs'),
                ),
            );
        } else {
            $templates = array(
                'ayurvedic_general' => array(
                    'name' => __('General Ayurvedic Consultation', 'clinic-management'),
                    'fields' => array('chief_complaint', 'symptoms', 'examination_findings', 'diagnosis', 'treatment_plan', 'vital_signs'),
                ),
                'panchakosha' => array(
                    'name' => __('Panchakosha Assessment', 'clinic-management'),
                    'fields' => array('chief_complaint', 'symptoms', 'examination_findings', 'diagnosis', 'treatment_plan'),
                ),
                'prakriti_analysis' => array(
                    'name' => __('Prakriti Analysis', 'clinic-management'),
                    'fields' => array('examination_findings', 'diagnosis', 'treatment_plan'),
                ),
            );
        }

        return $templates;
    }

    /**
     * AJAX: Save consultation
     */
    public function ajaxSaveConsultation()
    {
        check_ajax_referer('cms_ajax_nonce', 'nonce');

        if (!CMS_User_Roles::userCan('cms_manage_consultations')) {
            wp_die(json_encode(array('success' => false, 'message' => __('Permission denied', 'clinic-management'))));
        }

        $consultation_id = !empty($_POST['consultation_id']) ? intval($_POST['consultation_id']) : 0;
        $consultation_data = $_POST['consultation_data'];

        if ($consultation_id > 0) {
            // Update existing consultation
            $result = self::updateConsultation($consultation_id, $consultation_data);
        } else {
            // Create new consultation
            $result = self::createConsultation($consultation_data);
        }

        if (is_wp_error($result)) {
            wp_die(json_encode(array('success' => false, 'message' => $result->get_error_message())));
        }

        $response_data = array('success' => true, 'message' => __('Consultation saved successfully', 'clinic-management'));
        if ($consultation_id === 0) {
            $response_data['consultation_id'] = $result;
        }

        wp_die(json_encode($response_data));
    }

    /**
     * AJAX: Get consultation
     */
    public function ajaxGetConsultation()
    {
        check_ajax_referer('cms_ajax_nonce', 'nonce');

        if (!CMS_User_Roles::userCan('cms_view_consultations')) {
            wp_die(json_encode(array('success' => false, 'message' => __('Permission denied', 'clinic-management'))));
        }

        $consultation_id = intval($_POST['consultation_id']);
        $consultation = self::getConsultation($consultation_id);

        if (!$consultation) {
            wp_die(json_encode(array('success' => false, 'message' => __('Consultation not found', 'clinic-management'))));
        }

        wp_die(json_encode(array('success' => true, 'data' => $consultation)));
    }

    /**
     * AJAX: Get consultations
     */
    public function ajaxGetConsultations()
    {
        check_ajax_referer('cms_ajax_nonce', 'nonce');

        if (!CMS_User_Roles::userCan('cms_view_consultations')) {
            wp_die(json_encode(array('success' => false, 'message' => __('Permission denied', 'clinic-management'))));
        }

        $filters = $_POST['filters'] ?? array();
        $page = intval($_POST['page'] ?? 1);
        $per_page = 20;
        $offset = ($page - 1) * $per_page;

        $consultations = self::getConsultations($filters, $per_page, $offset);
        $total = self::getConsultationsCount($filters);

        $response_data = array(
            'success' => true,
            'data' => $consultations,
            'total' => $total,
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($total / $per_page)
        );

        wp_die(json_encode($response_data));
    }

    /**
     * AJAX: Get consultation templates
     */
    public function ajaxGetConsultationTemplates()
    {
        check_ajax_referer('cms_ajax_nonce', 'nonce');

        if (!CMS_User_Roles::userCan('cms_manage_consultations')) {
            wp_die(json_encode(array('success' => false, 'message' => __('Permission denied', 'clinic-management'))));
        }

        $consultation_type = sanitize_text_field($_POST['consultation_type'] ?? 'allopathy');
        $templates = self::getConsultationTemplates($consultation_type);

        wp_die(json_encode(array('success' => true, 'data' => $templates)));
    }

    /**
     * Get consultation statistics
     */
    public static function getConsultationStats()
    {
        global $wpdb;

        $stats = array();
        $today = current_time('Y-m-d');
        $this_month = current_time('Y-m');

        // Today's consultations
        $stats['today'] = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}cms_consultations 
            WHERE consultation_date = %s
        ", $today));

        // This month's consultations
        $stats['this_month'] = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}cms_consultations 
            WHERE consultation_date LIKE %s
        ", $this_month . '%'));

        // Consultations by type
        $stats['by_type'] = $wpdb->get_results("
            SELECT consultation_type, COUNT(*) as count 
            FROM {$wpdb->prefix}cms_consultations 
            GROUP BY consultation_type
        ");

        // Consultations by doctor (this month)
        $stats['by_doctor'] = $wpdb->get_results($wpdb->prepare("
            SELECT u.display_name as doctor_name, COUNT(*) as count,
                   AVG(c.consultation_fee) as avg_fee
            FROM {$wpdb->prefix}cms_consultations c
            LEFT JOIN {$wpdb->users} u ON c.doctor_id = u.ID
            WHERE c.consultation_date LIKE %s
            GROUP BY c.doctor_id, u.display_name
            ORDER BY count DESC
        ", $this_month . '%'));

        // Revenue this month
        $stats['revenue_this_month'] = $wpdb->get_var($wpdb->prepare("
            SELECT SUM(consultation_fee) FROM {$wpdb->prefix}cms_consultations 
            WHERE consultation_date LIKE %s
        ", $this_month . '%'));

        return $stats;
    }
}