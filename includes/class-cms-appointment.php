<?php
/**
 * Appointment management class
 */
class CMS_Appointment {
    
    private $db;
    
    public function __construct() {
        $this->db = new CMS_Database();
    }
    
    /**
     * Add a new appointment
     */
    public function add_appointment($data) {
        // Check for conflicts
        if ($this->has_conflict($data['doctor_id'], $data['appointment_date'], $data['appointment_time'], $data['duration'])) {
            return false;
        }
        
        // Sanitize data
        $sanitized_data = array(
            'patient_id' => sanitize_text_field($data['patient_id']),
            'doctor_id' => intval($data['doctor_id']),
            'appointment_date' => sanitize_text_field($data['appointment_date']),
            'appointment_time' => sanitize_text_field($data['appointment_time']),
            'duration' => intval($data['duration']),
            'status' => 'scheduled',
            'notes' => sanitize_textarea_field($data['notes'])
        );
        
        $result = $this->db->insert('appointments', $sanitized_data);
        
        if ($result) {
            // Send confirmation notification
            $this->send_appointment_confirmation($data['patient_id'], $sanitized_data);
            return $result;
        }
        
        return false;
    }
    
    /**
     * Update appointment
     */
    public function update_appointment($appointment_id, $data) {
        $sanitized_data = array();
        
        if (isset($data['appointment_date'])) $sanitized_data['appointment_date'] = sanitize_text_field($data['appointment_date']);
        if (isset($data['appointment_time'])) $sanitized_data['appointment_time'] = sanitize_text_field($data['appointment_time']);
        if (isset($data['duration'])) $sanitized_data['duration'] = intval($data['duration']);
        if (isset($data['status'])) $sanitized_data['status'] = sanitize_text_field($data['status']);
        if (isset($data['notes'])) $sanitized_data['notes'] = sanitize_textarea_field($data['notes']);
        
        $sanitized_data['updated_at'] = current_time('mysql');
        
        return $this->db->update('appointments', $sanitized_data, array('id' => $appointment_id));
    }
    
    /**
     * Delete appointment
     */
    public function delete_appointment($appointment_id) {
        return $this->db->delete('appointments', array('id' => $appointment_id));
    }
    
    /**
     * Get appointment by ID
     */
    public function get_appointment($appointment_id) {
        $query = "SELECT a.*, p.first_name, p.last_name, p.patient_id as patient_code 
                  FROM {$this->db->get_table_name('appointments')} a
                  JOIN {$this->db->get_table_name('patients')} p ON a.patient_id = p.patient_id
                  WHERE a.id = %d";
        return $this->db->get_row($query, array($appointment_id));
    }
    
    /**
     * Get appointments for a specific date
     */
    public function get_appointments_by_date($date, $doctor_id = null) {
        $where_clause = "WHERE appointment_date = %s";
        $args = array($date);
        
        if ($doctor_id) {
            $where_clause .= " AND doctor_id = %d";
            $args[] = $doctor_id;
        }
        
        $query = "SELECT a.*, p.first_name, p.last_name, p.patient_id as patient_code 
                  FROM {$this->db->get_table_name('appointments')} a
                  JOIN {$this->db->get_table_name('patients')} p ON a.patient_id = p.patient_id
                  $where_clause ORDER BY appointment_time ASC";
        
        return $this->db->get_results($query, $args);
    }
    
    /**
     * Get today's appointments
     */
    public function get_todays_appointments($doctor_id = null) {
        return $this->get_appointments_by_date(date('Y-m-d'), $doctor_id);
    }
    
    /**
     * Get upcoming appointments
     */
    public function get_upcoming_appointments($days = 7, $doctor_id = null) {
        $where_clause = "WHERE appointment_date >= CURDATE() AND appointment_date <= DATE_ADD(CURDATE(), INTERVAL %d DAY)";
        $args = array($days);
        
        if ($doctor_id) {
            $where_clause .= " AND doctor_id = %d";
            $args[] = $doctor_id;
        }
        
        $query = "SELECT a.*, p.first_name, p.last_name, p.patient_id as patient_code 
                  FROM {$this->db->get_table_name('appointments')} a
                  JOIN {$this->db->get_table_name('patients')} p ON a.patient_id = p.patient_id
                  $where_clause ORDER BY appointment_date ASC, appointment_time ASC";
        
        return $this->db->get_results($query, $args);
    }
    
    /**
     * Get appointments for a patient
     */
    public function get_patient_appointments($patient_id) {
        $query = "SELECT a.*, u.display_name as doctor_name 
                  FROM {$this->db->get_table_name('appointments')} a
                  JOIN {$GLOBALS['wpdb']->users} u ON a.doctor_id = u.ID
                  WHERE a.patient_id = %s ORDER BY appointment_date DESC, appointment_time DESC";
        
        return $this->db->get_results($query, array($patient_id));
    }
    
    /**
     * Check for appointment conflicts
     */
    private function has_conflict($doctor_id, $date, $time, $duration) {
        $start_time = $time;
        $end_time = date('H:i:s', strtotime($time) + ($duration * 60));
        
        $query = "SELECT COUNT(*) FROM {$this->db->get_table_name('appointments')} 
                  WHERE doctor_id = %d AND appointment_date = %s AND status != 'cancelled'
                  AND (
                      (appointment_time <= %s AND DATE_ADD(appointment_time, INTERVAL duration MINUTE) > %s)
                      OR (appointment_time < %s AND DATE_ADD(appointment_time, INTERVAL duration MINUTE) >= %s)
                      OR (appointment_time >= %s AND appointment_time < %s)
                  )";
        
        $count = $this->db->get_var($query, array($doctor_id, $date, $start_time, $start_time, $end_time, $end_time, $start_time, $end_time));
        
        return $count > 0;
    }
    
    /**
     * Get available time slots for a date
     */
    public function get_available_slots($date, $doctor_id) {
        $working_hours = get_option('cms_working_hours', '09:00-17:00');
        $duration = get_option('cms_appointment_duration', 30);
        
        list($start_time, $end_time) = explode('-', $working_hours);
        
        $slots = array();
        $current_time = strtotime($start_time);
        $end_timestamp = strtotime($end_time);
        
        while ($current_time < $end_timestamp) {
            $slot_time = date('H:i:s', $current_time);
            
            if (!$this->has_conflict($doctor_id, $date, $slot_time, $duration)) {
                $slots[] = $slot_time;
            }
            
            $current_time += ($duration * 60);
        }
        
        return $slots;
    }
    
    /**
     * Send appointment confirmation
     */
    private function send_appointment_confirmation($patient_id, $appointment_data) {
        $patient = $this->db->get_row(
            "SELECT * FROM {$this->db->get_table_name('patients')} WHERE patient_id = %s",
            array($patient_id)
        );
        
        if (!$patient || !$patient->email) {
            return;
        }
        
        $subject = 'Appointment Confirmation - ' . get_option('cms_clinic_name', 'Our Clinic');
        $message = "Dear {$patient->first_name} {$patient->last_name},\n\n";
        $message .= "Your appointment has been confirmed:\n\n";
        $message .= "Date: " . date('F j, Y', strtotime($appointment_data['appointment_date'])) . "\n";
        $message .= "Time: " . date('g:i A', strtotime($appointment_data['appointment_time'])) . "\n";
        $message .= "Duration: {$appointment_data['duration']} minutes\n\n";
        
        if (!empty($appointment_data['notes'])) {
            $message .= "Notes: {$appointment_data['notes']}\n\n";
        }
        
        $message .= "Please arrive 10 minutes before your scheduled time.\n\n";
        $message .= "Best regards,\n" . get_option('cms_clinic_name', 'Clinic Team');
        
        wp_mail($patient->email, $subject, $message);
    }
    
    /**
     * Get appointment statistics
     */
    public function get_appointment_stats() {
        $today = date('Y-m-d');
        $this_month = date('Y-m');
        
        $today_count = $this->db->get_var(
            "SELECT COUNT(*) FROM {$this->db->get_table_name('appointments')} WHERE appointment_date = %s",
            array($today)
        );
        
        $month_count = $this->db->get_var(
            "SELECT COUNT(*) FROM {$this->db->get_table_name('appointments')} WHERE DATE_FORMAT(appointment_date, '%%Y-%%m') = %s",
            array($this_month)
        );
        
        $pending_count = $this->db->get_var(
            "SELECT COUNT(*) FROM {$this->db->get_table_name('appointments')} WHERE status = 'scheduled' AND appointment_date >= CURDATE()"
        );
        
        $completed_count = $this->db->get_var(
            "SELECT COUNT(*) FROM {$this->db->get_table_name('appointments')} WHERE status = 'completed' AND DATE_FORMAT(appointment_date, '%%Y-%%m') = %s",
            array($this_month)
        );
        
        return array(
            'today' => $today_count,
            'this_month' => $month_count,
            'pending' => $pending_count,
            'completed' => $completed_count
        );
    }
    
    /**
     * Cancel appointment
     */
    public function cancel_appointment($appointment_id, $reason = '') {
        $data = array(
            'status' => 'cancelled',
            'notes' => !empty($reason) ? 'Cancelled: ' . $reason : 'Appointment cancelled'
        );
        
        $result = $this->update_appointment($appointment_id, $data);
        
        if ($result) {
            // Send cancellation notification
            $appointment = $this->get_appointment($appointment_id);
            if ($appointment) {
                $this->send_cancellation_notification($appointment, $reason);
            }
        }
        
        return $result;
    }
    
    /**
     * Send cancellation notification
     */
    private function send_cancellation_notification($appointment, $reason) {
        $patient = $this->db->get_row(
            "SELECT * FROM {$this->db->get_table_name('patients')} WHERE patient_id = %s",
            array($appointment->patient_id)
        );
        
        if (!$patient || !$patient->email) {
            return;
        }
        
        $subject = 'Appointment Cancelled - ' . get_option('cms_clinic_name', 'Our Clinic');
        $message = "Dear {$patient->first_name} {$patient->last_name},\n\n";
        $message .= "Your appointment has been cancelled:\n\n";
        $message .= "Date: " . date('F j, Y', strtotime($appointment->appointment_date)) . "\n";
        $message .= "Time: " . date('g:i A', strtotime($appointment->appointment_time)) . "\n\n";
        
        if (!empty($reason)) {
            $message .= "Reason: $reason\n\n";
        }
        
        $message .= "Please contact us to reschedule your appointment.\n\n";
        $message .= "Best regards,\n" . get_option('cms_clinic_name', 'Clinic Team');
        
        wp_mail($patient->email, $subject, $message);
    }
}