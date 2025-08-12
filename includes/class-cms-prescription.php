<?php
/**
 * Prescription management class
 */
class CMS_Prescription {
    
    private $db;
    
    public function __construct() {
        $this->db = new CMS_Database();
    }
    
    /**
     * Add a new prescription
     */
    public function add_prescription($data) {
        // Sanitize data
        $sanitized_data = array(
            'patient_id' => sanitize_text_field($data['patient_id']),
            'doctor_id' => intval($data['doctor_id']),
            'appointment_id' => !empty($data['appointment_id']) ? intval($data['appointment_id']) : null,
            'diagnosis' => sanitize_textarea_field($data['diagnosis']),
            'symptoms' => sanitize_textarea_field($data['symptoms']),
            'treatment_plan' => sanitize_textarea_field($data['treatment_plan']),
            'medicines' => sanitize_textarea_field($data['medicines']),
            'dosage_instructions' => sanitize_textarea_field($data['dosage_instructions']),
            'follow_up_date' => !empty($data['follow_up_date']) ? sanitize_text_field($data['follow_up_date']) : null,
            'notes' => sanitize_textarea_field($data['notes'])
        );
        
        $result = $this->db->insert('prescriptions', $sanitized_data);
        
        if ($result) {
            // Update appointment status if appointment_id is provided
            if (!empty($data['appointment_id'])) {
                $this->update_appointment_status($data['appointment_id'], 'completed');
            }
            
            // Generate prescription PDF
            $this->generate_prescription_pdf($result, $sanitized_data);
            
            return $result;
        }
        
        return false;
    }
    
    /**
     * Update prescription
     */
    public function update_prescription($prescription_id, $data) {
        $sanitized_data = array();
        
        if (isset($data['diagnosis'])) $sanitized_data['diagnosis'] = sanitize_textarea_field($data['diagnosis']);
        if (isset($data['symptoms'])) $sanitized_data['symptoms'] = sanitize_textarea_field($data['symptoms']);
        if (isset($data['treatment_plan'])) $sanitized_data['treatment_plan'] = sanitize_textarea_field($data['treatment_plan']);
        if (isset($data['medicines'])) $sanitized_data['medicines'] = sanitize_textarea_field($data['medicines']);
        if (isset($data['dosage_instructions'])) $sanitized_data['dosage_instructions'] = sanitize_textarea_field($data['dosage_instructions']);
        if (isset($data['follow_up_date'])) $sanitized_data['follow_up_date'] = sanitize_text_field($data['follow_up_date']);
        if (isset($data['notes'])) $sanitized_data['notes'] = sanitize_textarea_field($data['notes']);
        
        return $this->db->update('prescriptions', $sanitized_data, array('id' => $prescription_id));
    }
    
    /**
     * Delete prescription
     */
    public function delete_prescription($prescription_id) {
        return $this->db->delete('prescriptions', array('id' => $prescription_id));
    }
    
    /**
     * Get prescription by ID
     */
    public function get_prescription($prescription_id) {
        $query = "SELECT p.*, pt.first_name, pt.last_name, pt.patient_id as patient_code,
                         u.display_name as doctor_name
                  FROM {$this->db->get_table_name('prescriptions')} p
                  JOIN {$this->db->get_table_name('patients')} pt ON p.patient_id = pt.patient_id
                  JOIN {$GLOBALS['wpdb']->users} u ON p.doctor_id = u.ID
                  WHERE p.id = %d";
        return $this->db->get_row($query, array($prescription_id));
    }
    
    /**
     * Get prescriptions for a patient
     */
    public function get_patient_prescriptions($patient_id) {
        $query = "SELECT p.*, u.display_name as doctor_name
                  FROM {$this->db->get_table_name('prescriptions')} p
                  JOIN {$GLOBALS['wpdb']->users} u ON p.doctor_id = u.ID
                  WHERE p.patient_id = %s ORDER BY p.created_at DESC";
        
        return $this->db->get_results($query, array($patient_id));
    }
    
    /**
     * Get prescriptions by doctor
     */
    public function get_doctor_prescriptions($doctor_id, $date_from = null, $date_to = null) {
        $where_clause = "WHERE p.doctor_id = %d";
        $args = array($doctor_id);
        
        if ($date_from && $date_to) {
            $where_clause .= " AND DATE(p.created_at) BETWEEN %s AND %s";
            $args[] = $date_from;
            $args[] = $date_to;
        }
        
        $query = "SELECT p.*, pt.first_name, pt.last_name, pt.patient_id as patient_code
                  FROM {$this->db->get_table_name('prescriptions')} p
                  JOIN {$this->db->get_table_name('patients')} pt ON p.patient_id = pt.patient_id
                  $where_clause ORDER BY p.created_at DESC";
        
        return $this->db->get_results($query, $args);
    }
    
    /**
     * Get recent prescriptions
     */
    public function get_recent_prescriptions($limit = 10) {
        $query = "SELECT p.*, pt.first_name, pt.last_name, pt.patient_id as patient_code,
                         u.display_name as doctor_name
                  FROM {$this->db->get_table_name('prescriptions')} p
                  JOIN {$this->db->get_table_name('patients')} pt ON p.patient_id = pt.patient_id
                  JOIN {$GLOBALS['wpdb']->users} u ON p.doctor_id = u.ID
                  ORDER BY p.created_at DESC LIMIT %d";
        
        return $this->db->get_results($query, array($limit));
    }
    
    /**
     * Update appointment status
     */
    private function update_appointment_status($appointment_id, $status) {
        $this->db->update('appointments', array('status' => $status), array('id' => $appointment_id));
    }
    
    /**
     * Generate prescription PDF
     */
    private function generate_prescription_pdf($prescription_id, $data) {
        // This would integrate with a PDF generation library like TCPDF or DOMPDF
        // For now, we'll create a simple HTML version that can be printed
        
        $prescription = $this->get_prescription($prescription_id);
        if (!$prescription) {
            return false;
        }
        
        $html = $this->generate_prescription_html($prescription);
        
        // Save HTML to file for printing
        $upload_dir = wp_upload_dir();
        $prescription_dir = $upload_dir['basedir'] . '/clinic-files/' . $data['patient_id'] . '/prescriptions/';
        
        if (!file_exists($prescription_dir)) {
            wp_mkdir_p($prescription_dir);
        }
        
        $filename = 'prescription_' . $prescription_id . '_' . date('Y-m-d') . '.html';
        $filepath = $prescription_dir . $filename;
        
        if (file_put_contents($filepath, $html)) {
            // Save file record to database
            $file_url = $upload_dir['baseurl'] . '/clinic-files/' . $data['patient_id'] . '/prescriptions/' . $filename;
            
            $this->db->insert('patient_files', array(
                'patient_id' => $data['patient_id'],
                'file_name' => $filename,
                'file_url' => $file_url,
                'file_type' => 'prescription'
            ));
            
            return $file_url;
        }
        
        return false;
    }
    
    /**
     * Generate prescription HTML
     */
    private function generate_prescription_html($prescription) {
        $clinic_name = get_option('cms_clinic_name', 'Your Clinic Name');
        $clinic_address = get_option('cms_clinic_address', 'Your Clinic Address');
        $clinic_phone = get_option('cms_clinic_phone', 'Your Clinic Phone');
        
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Prescription - ' . $prescription->patient_code . '</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 30px; }
        .clinic-info { margin-bottom: 30px; }
        .patient-info { margin-bottom: 30px; }
        .prescription-content { margin-bottom: 30px; }
        .footer { margin-top: 50px; text-align: center; border-top: 1px solid #ccc; padding-top: 20px; }
        .section { margin-bottom: 20px; }
        .section h3 { color: #333; border-bottom: 1px solid #ccc; padding-bottom: 5px; }
        .medicine-item { margin-bottom: 10px; padding: 10px; background: #f9f9f9; border-left: 3px solid #007cba; }
        @media print { body { margin: 0; } .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="header">
        <h1>' . $clinic_name . '</h1>
        <p>' . $clinic_address . '</p>
        <p>Phone: ' . $clinic_phone . '</p>
    </div>
    
    <div class="clinic-info">
        <p><strong>Date:</strong> ' . date('F j, Y', strtotime($prescription->created_at)) . '</p>
        <p><strong>Doctor:</strong> Dr. ' . $prescription->doctor_name . '</p>
    </div>
    
    <div class="patient-info">
        <h3>Patient Information</h3>
        <p><strong>Name:</strong> ' . $prescription->first_name . ' ' . $prescription->last_name . '</p>
        <p><strong>Patient ID:</strong> ' . $prescription->patient_code . '</p>
    </div>
    
    <div class="prescription-content">
        <div class="section">
            <h3>Diagnosis</h3>
            <p>' . nl2br(esc_html($prescription->diagnosis)) . '</p>
        </div>
        
        <div class="section">
            <h3>Symptoms</h3>
            <p>' . nl2br(esc_html($prescription->symptoms)) . '</p>
        </div>
        
        <div class="section">
            <h3>Treatment Plan</h3>
            <p>' . nl2br(esc_html($prescription->treatment_plan)) . '</p>
        </div>
        
        <div class="section">
            <h3>Medicines</h3>
            <div class="medicine-item">' . nl2br(esc_html($prescription->medicines)) . '</div>
        </div>
        
        <div class="section">
            <h3>Dosage Instructions</h3>
            <p>' . nl2br(esc_html($prescription->dosage_instructions)) . '</p>
        </div>';
        
        if (!empty($prescription->follow_up_date)) {
            $html .= '<div class="section">
                <h3>Follow-up Date</h3>
                <p>' . date('F j, Y', strtotime($prescription->follow_up_date)) . '</p>
            </div>';
        }
        
        if (!empty($prescription->notes)) {
            $html .= '<div class="section">
                <h3>Additional Notes</h3>
                <p>' . nl2br(esc_html($prescription->notes)) . '</p>
            </div>';
        }
        
        $html .= '</div>
    
    <div class="footer">
        <p><strong>Doctor\'s Signature:</strong> _________________________</p>
        <p><strong>Date:</strong> _________________________</p>
        <p class="no-print">This prescription was generated electronically on ' . date('F j, Y \a\t g:i A') . '</p>
    </div>
</body>
</html>';
        
        return $html;
    }
    
    /**
     * Get prescription statistics
     */
    public function get_prescription_stats() {
        $this_month = date('Y-m');
        
        $month_count = $this->db->get_var(
            "SELECT COUNT(*) FROM {$this->db->get_table_name('prescriptions')} WHERE DATE_FORMAT(created_at, '%%Y-%%m') = %s",
            array($this_month)
        );
        
        $total_count = $this->db->get_var(
            "SELECT COUNT(*) FROM {$this->db->get_table_name('prescriptions')}"
        );
        
        $follow_up_count = $this->db->get_var(
            "SELECT COUNT(*) FROM {$this->db->get_table_name('prescriptions')} 
             WHERE follow_up_date >= CURDATE() AND follow_up_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)"
        );
        
        return array(
            'this_month' => $month_count,
            'total' => $total_count,
            'follow_up_this_week' => $follow_up_count
        );
    }
    
    /**
     * Search prescriptions
     */
    public function search_prescriptions($search_term, $doctor_id = null) {
        $where_clause = "WHERE (pt.first_name LIKE %s OR pt.last_name LIKE %s OR pt.patient_id LIKE %s OR p.diagnosis LIKE %s)";
        $args = array('%' . $search_term . '%', '%' . $search_term . '%', '%' . $search_term . '%', '%' . $search_term . '%');
        
        if ($doctor_id) {
            $where_clause .= " AND p.doctor_id = %d";
            $args[] = $doctor_id;
        }
        
        $query = "SELECT p.*, pt.first_name, pt.last_name, pt.patient_id as patient_code,
                         u.display_name as doctor_name
                  FROM {$this->db->get_table_name('prescriptions')} p
                  JOIN {$this->db->get_table_name('patients')} pt ON p.patient_id = pt.patient_id
                  JOIN {$GLOBALS['wpdb']->users} u ON p.doctor_id = u.ID
                  $where_clause ORDER BY p.created_at DESC LIMIT 20";
        
        return $this->db->get_results($query, $args);
    }
}