<?php
/**
 * Patient management class
 */
class CMS_Patient {
    
    private $db;
    
    public function __construct() {
        $this->db = new CMS_Database();
    }
    
    /**
     * Add a new patient
     */
    public function add_patient($data) {
        // Generate unique patient ID
        $data['patient_id'] = $this->generate_patient_id();
        
        // Sanitize data
        $sanitized_data = array(
            'patient_id' => sanitize_text_field($data['patient_id']),
            'first_name' => sanitize_text_field($data['first_name']),
            'last_name' => sanitize_text_field($data['last_name']),
            'email' => sanitize_email($data['email']),
            'phone' => sanitize_text_field($data['phone']),
            'date_of_birth' => sanitize_text_field($data['date_of_birth']),
            'gender' => sanitize_text_field($data['gender']),
            'address' => sanitize_textarea_field($data['address']),
            'emergency_contact' => sanitize_text_field($data['emergency_contact']),
            'emergency_phone' => sanitize_text_field($data['emergency_phone']),
            'blood_group' => sanitize_text_field($data['blood_group']),
            'allergies' => sanitize_textarea_field($data['allergies']),
            'medical_history' => sanitize_textarea_field($data['medical_history']),
            'photo_url' => esc_url_raw($data['photo_url'])
        );
        
        $result = $this->db->insert('patients', $sanitized_data);
        
        if ($result) {
            // Create portal user account
            $this->create_portal_user($data['patient_id'], $data['email'], $data['phone']);
            return $data['patient_id'];
        }
        
        return false;
    }
    
    /**
     * Update patient information
     */
    public function update_patient($patient_id, $data) {
        $sanitized_data = array();
        
        if (isset($data['first_name'])) $sanitized_data['first_name'] = sanitize_text_field($data['first_name']);
        if (isset($data['last_name'])) $sanitized_data['last_name'] = sanitize_text_field($data['last_name']);
        if (isset($data['email'])) $sanitized_data['email'] = sanitize_email($data['email']);
        if (isset($data['phone'])) $sanitized_data['phone'] = sanitize_text_field($data['phone']);
        if (isset($data['date_of_birth'])) $sanitized_data['date_of_birth'] = sanitize_text_field($data['date_of_birth']);
        if (isset($data['gender'])) $sanitized_data['gender'] = sanitize_text_field($data['gender']);
        if (isset($data['address'])) $sanitized_data['address'] = sanitize_textarea_field($data['address']);
        if (isset($data['emergency_contact'])) $sanitized_data['emergency_contact'] = sanitize_text_field($data['emergency_contact']);
        if (isset($data['emergency_phone'])) $sanitized_data['emergency_phone'] = sanitize_text_field($data['emergency_phone']);
        if (isset($data['blood_group'])) $sanitized_data['blood_group'] = sanitize_text_field($data['blood_group']);
        if (isset($data['allergies'])) $sanitized_data['allergies'] = sanitize_textarea_field($data['allergies']);
        if (isset($data['medical_history'])) $sanitized_data['medical_history'] = sanitize_textarea_field($data['medical_history']);
        if (isset($data['photo_url'])) $sanitized_data['photo_url'] = esc_url_raw($data['photo_url']);
        
        $sanitized_data['updated_at'] = current_time('mysql');
        
        return $this->db->update('patients', $sanitized_data, array('patient_id' => $patient_id));
    }
    
    /**
     * Delete patient
     */
    public function delete_patient($patient_id) {
        // Delete related records first
        $this->db->delete('appointments', array('patient_id' => $patient_id));
        $this->db->delete('prescriptions', array('patient_id' => $patient_id));
        $this->db->delete('billing', array('patient_id' => $patient_id));
        $this->db->delete('patient_files', array('patient_id' => $patient_id));
        $this->db->delete('portal_users', array('patient_id' => $patient_id));
        
        // Delete patient
        return $this->db->delete('patients', array('patient_id' => $patient_id));
    }
    
    /**
     * Get patient by ID
     */
    public function get_patient($patient_id) {
        $query = "SELECT * FROM {$this->db->get_table_name('patients')} WHERE patient_id = %s";
        return $this->db->get_row($query, array($patient_id));
    }
    
    /**
     * Get all patients with pagination
     */
    public function get_patients($page = 1, $per_page = 20, $search = '') {
        $offset = ($page - 1) * $per_page;
        
        $where_clause = '';
        $args = array();
        
        if (!empty($search)) {
            $where_clause = "WHERE first_name LIKE %s OR last_name LIKE %s OR patient_id LIKE %s OR phone LIKE %s";
            $search_term = '%' . $search . '%';
            $args = array($search_term, $search_term, $search_term, $search_term);
        }
        
        $query = "SELECT * FROM {$this->db->get_table_name('patients')} $where_clause ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $args[] = $per_page;
        $args[] = $offset;
        
        return $this->db->get_results($query, $args);
    }
    
    /**
     * Search patients
     */
    public function search_patients($search_term) {
        $query = "SELECT * FROM {$this->db->get_table_name('patients')} 
                  WHERE first_name LIKE %s OR last_name LIKE %s OR patient_id LIKE %s OR phone LIKE %s 
                  ORDER BY first_name, last_name LIMIT 10";
        
        $search_term = '%' . $search_term . '%';
        return $this->db->get_results($query, array($search_term, $search_term, $search_term, $search_term));
    }
    
    /**
     * Get patient count
     */
    public function get_patient_count($search = '') {
        $where_clause = '';
        $args = array();
        
        if (!empty($search)) {
            $where_clause = "WHERE first_name LIKE %s OR last_name LIKE %s OR patient_id LIKE %s OR phone LIKE %s";
            $search_term = '%' . $search . '%';
            $args = array($search_term, $search_term, $search_term, $search_term);
        }
        
        $query = "SELECT COUNT(*) FROM {$this->db->get_table_name('patients')} $where_clause";
        return $this->db->get_var($query, $args);
    }
    
    /**
     * Get patient history
     */
    public function get_patient_history($patient_id) {
        $patient = $this->get_patient($patient_id);
        
        if (!$patient) {
            return false;
        }
        
        // Get appointments
        $appointments_query = "SELECT * FROM {$this->db->get_table_name('appointments')} 
                              WHERE patient_id = %s ORDER BY appointment_date DESC, appointment_time DESC";
        $appointments = $this->db->get_results($appointments_query, array($patient_id));
        
        // Get prescriptions
        $prescriptions_query = "SELECT * FROM {$this->db->get_table_name('prescriptions')} 
                               WHERE patient_id = %s ORDER BY created_at DESC";
        $prescriptions = $this->db->get_results($prescriptions_query, array($patient_id));
        
        // Get billing
        $billing_query = "SELECT * FROM {$this->db->get_table_name('billing')} 
                          WHERE patient_id = %s ORDER BY created_at DESC";
        $billing = $this->db->get_results($billing_query, array($patient_id));
        
        // Get files
        $files_query = "SELECT * FROM {$this->db->get_table_name('patient_files')} 
                        WHERE patient_id = %s ORDER BY uploaded_at DESC";
        $files = $this->db->get_results($files_query, array($patient_id));
        
        return array(
            'patient' => $patient,
            'appointments' => $appointments,
            'prescriptions' => $prescriptions,
            'billing' => $billing,
            'files' => $files
        );
    }
    
    /**
     * Generate unique patient ID
     */
    private function generate_patient_id() {
        $prefix = 'P';
        $year = date('Y');
        $month = date('m');
        
        // Get count of patients for this month
        $query = "SELECT COUNT(*) FROM {$this->db->get_table_name('patients')} 
                  WHERE YEAR(created_at) = %d AND MONTH(created_at) = %d";
        $count = $this->db->get_var($query, array($year, $month));
        
        $count++;
        return $prefix . $year . $month . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * Create portal user account
     */
    private function create_portal_user($patient_id, $email, $phone) {
        $username = 'patient_' . $patient_id;
        $password = wp_generate_password(8, false);
        $password_hash = wp_hash_password($password);
        
        $user_data = array(
            'patient_id' => $patient_id,
            'username' => $username,
            'password_hash' => $password_hash,
            'email' => $email
        );
        
        $this->db->insert('portal_users', $user_data);
        
        // Send welcome email with credentials
        $this->send_welcome_email($email, $username, $password);
    }
    
    /**
     * Send welcome email
     */
    private function send_welcome_email($email, $username, $password) {
        $subject = 'Welcome to ' . get_option('cms_clinic_name', 'Our Clinic');
        $message = "Welcome to our clinic management system!\n\n";
        $message .= "Your login credentials:\n";
        $message .= "Username: $username\n";
        $message .= "Password: $password\n\n";
        $message .= "You can use these credentials to access your patient portal.\n\n";
        $message .= "Best regards,\n" . get_option('cms_clinic_name', 'Clinic Team');
        
        wp_mail($email, $subject, $message);
    }
    
    /**
     * Get patient statistics
     */
    public function get_patient_stats() {
        $total_patients = $this->db->get_var("SELECT COUNT(*) FROM {$this->db->get_table_name('patients')}");
        
        $new_this_month = $this->db->get_var(
            "SELECT COUNT(*) FROM {$this->db->get_table_name('patients')} 
             WHERE YEAR(created_at) = %d AND MONTH(created_at) = %d",
            array(date('Y'), date('m'))
        );
        
        $new_this_week = $this->db->get_var(
            "SELECT COUNT(*) FROM {$this->db->get_table_name('patients')} 
             WHERE YEARWEEK(created_at) = YEARWEEK(NOW())",
            array()
        );
        
        return array(
            'total' => $total_patients,
            'new_this_month' => $new_this_month,
            'new_this_week' => $new_this_week
        );
    }
}