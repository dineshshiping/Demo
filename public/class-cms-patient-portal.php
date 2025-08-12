<?php
/**
 * Patient portal management class
 */
class CMS_Patient_Portal {
    
    private $db;
    
    public function __construct() {
        $this->db = new CMS_Database();
    }
    
    /**
     * Render patient portal
     */
    public function render_portal() {
        // Check if patient is logged in
        if (!$this->is_patient_logged_in()) {
            return $this->render_login_form();
        }
        
        $patient_data = $this->get_logged_in_patient();
        if (!$patient_data) {
            return $this->render_login_form();
        }
        
        return $this->render_portal_dashboard($patient_data);
    }
    
    /**
     * Render appointment booking form
     */
    public function render_booking_form() {
        if (!$this->is_patient_logged_in()) {
            return '<p>Please <a href="#patient-login">login</a> to book appointments.</p>';
        }
        
        $patient_data = $this->get_logged_in_patient();
        $doctors = get_users(array('role__in' => array('administrator', 'doctor')));
        
        ob_start();
        include CMS_PLUGIN_PATH . 'public/views/appointment-booking.php';
        return ob_get_clean();
    }
    
    /**
     * Check if patient is logged in
     */
    private function is_patient_logged_in() {
        return isset($_SESSION['cms_patient_logged_in']) && $_SESSION['cms_patient_logged_in'] === true;
    }
    
    /**
     * Get logged in patient data
     */
    private function get_logged_in_patient() {
        if (!isset($_SESSION['cms_patient_id'])) {
            return false;
        }
        
        $patient_id = $_SESSION['cms_patient_id'];
        $patient = new CMS_Patient();
        return $patient->get_patient($patient_id);
    }
    
    /**
     * Render login form
     */
    private function render_login_form() {
        ob_start();
        ?>
        <div class="cms-patient-portal">
            <div class="cms-login-form">
                <h2>Patient Portal Login</h2>
                <form id="cms-patient-login-form" method="post">
                    <div class="form-group">
                        <label for="username">Username or Email</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="cms-btn cms-btn-primary">Login</button>
                    </div>
                    
                    <div class="form-group">
                        <a href="#" class="cms-forgot-password">Forgot Password?</a>
                    </div>
                </form>
                
                <div class="cms-login-message"></div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render portal dashboard
     */
    private function render_portal_dashboard($patient_data) {
        $appointment = new CMS_Appointment();
        $prescription = new CMS_Prescription();
        $billing = new CMS_Billing();
        
        $upcoming_appointments = $appointment->get_patient_appointments($patient_data->patient_id);
        $prescriptions = $prescription->get_patient_prescriptions($patient_data->patient_id);
        $billing_records = $billing->get_patient_billing($patient_data->patient_id);
        
        ob_start();
        include CMS_PLUGIN_PATH . 'public/views/portal-dashboard.php';
        return ob_get_clean();
    }
    
    /**
     * AJAX patient login handler
     */
    public function ajax_patient_login() {
        check_ajax_referer('cms_nonce', 'nonce');
        
        $username = sanitize_text_field($_POST['username']);
        $password = $_POST['password'];
        
        // Validate credentials
        $user = $this->validate_patient_credentials($username, $password);
        
        if ($user) {
            // Start session and set login status
            if (!session_id()) {
                session_start();
            }
            
            $_SESSION['cms_patient_logged_in'] = true;
            $_SESSION['cms_patient_id'] = $user->patient_id;
            $_SESSION['cms_patient_username'] = $user->username;
            
            // Update last login
            $this->update_last_login($user->id);
            
            wp_send_json_success(array(
                'message' => 'Login successful',
                'redirect_url' => $this->get_portal_url()
            ));
        } else {
            wp_send_json_error('Invalid username or password');
        }
    }
    
    /**
     * AJAX book appointment handler
     */
    public function ajax_book_appointment() {
        check_ajax_referer('cms_nonce', 'nonce');
        
        if (!$this->is_patient_logged_in()) {
            wp_send_json_error('Please login to book appointments');
        }
        
        $patient_data = $this->get_logged_in_patient();
        if (!$patient_data) {
            wp_send_json_error('Patient not found');
        }
        
        // Validate appointment data
        $appointment_data = array(
            'patient_id' => $patient_data->patient_id,
            'doctor_id' => intval($_POST['doctor_id']),
            'appointment_date' => sanitize_text_field($_POST['appointment_date']),
            'appointment_time' => sanitize_text_field($_POST['appointment_time']),
            'notes' => sanitize_textarea_field($_POST['notes'])
        );
        
        // Check if time slot is available
        $appointment = new CMS_Appointment();
        if ($appointment->has_conflict($appointment_data['doctor_id'], $appointment_data['appointment_date'], $appointment_data['appointment_time'], 30)) {
            wp_send_json_error('Selected time slot is not available');
        }
        
        $result = $appointment->add_appointment($appointment_data);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => 'Appointment booked successfully',
                'appointment_id' => $result
            ));
        } else {
            wp_send_json_error('Failed to book appointment');
        }
    }
    
    /**
     * Validate patient credentials
     */
    private function validate_patient_credentials($username, $password) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cms_portal_users';
        
        $user = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE (username = %s OR email = %s) AND is_active = 1",
                $username,
                $username
            )
        );
        
        if ($user && wp_check_password($password, $user->password_hash)) {
            return $user;
        }
        
        return false;
    }
    
    /**
     * Update last login
     */
    private function update_last_login($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cms_portal_users';
        
        $wpdb->update(
            $table_name,
            array('last_login' => current_time('mysql')),
            array('id' => $user_id),
            array('%s'),
            array('%d')
        );
    }
    
    /**
     * Get portal URL
     */
    private function get_portal_url() {
        // This would be the page where the portal shortcode is placed
        return home_url('/patient-portal/');
    }
    
    /**
     * Patient logout
     */
    public function logout() {
        if (session_id()) {
            session_destroy();
        }
        
        wp_redirect(home_url());
        exit;
    }
    
    /**
     * Reset patient password
     */
    public function reset_password($email) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cms_portal_users';
        
        $user = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table_name WHERE email = %s AND is_active = 1", $email)
        );
        
        if (!$user) {
            return false;
        }
        
        // Generate new password
        $new_password = wp_generate_password(8, false);
        $password_hash = wp_hash_password($new_password);
        
        // Update password
        $wpdb->update(
            $table_name,
            array('password_hash' => $password_hash),
            array('id' => $user->id),
            array('%s'),
            array('%d')
        );
        
        // Send password reset email
        $this->send_password_reset_email($user->email, $new_password);
        
        return true;
    }
    
    /**
     * Send password reset email
     */
    private function send_password_reset_email($email, $new_password) {
        $clinic_name = get_option('cms_clinic_name', 'Our Clinic');
        
        $subject = 'Password Reset - ' . $clinic_name;
        $message = "Your password has been reset.\n\n";
        $message .= "New Password: $new_password\n\n";
        $message .= "Please login with your new password and change it immediately.\n\n";
        $message .= "Best regards,\n" . $clinic_name . " Team";
        
        wp_mail($email, $subject, $message);
    }
    
    /**
     * Get patient appointments for calendar
     */
    public function get_patient_calendar_data($patient_id, $start_date, $end_date) {
        $appointment = new CMS_Appointment();
        $appointments = $appointment->get_patient_appointments($patient_id);
        
        $calendar_data = array();
        foreach ($appointments as $apt) {
            if ($apt->appointment_date >= $start_date && $apt->appointment_date <= $end_date) {
                $calendar_data[] = array(
                    'id' => $apt->id,
                    'title' => 'Appointment with Dr. ' . $apt->doctor_name,
                    'start' => $apt->appointment_date . 'T' . $apt->appointment_time,
                    'end' => $apt->appointment_date . 'T' . date('H:i:s', strtotime($apt->appointment_time) + ($apt->duration * 60)),
                    'status' => $apt->status,
                    'color' => $this->get_appointment_color($apt->status)
                );
            }
        }
        
        return $calendar_data;
    }
    
    /**
     * Get appointment color based on status
     */
    private function get_appointment_color($status) {
        switch ($status) {
            case 'scheduled':
                return '#007cba';
            case 'confirmed':
                return '#28a745';
            case 'completed':
                return '#6c757d';
            case 'cancelled':
                return '#dc3545';
            case 'no_show':
                return '#ffc107';
            default:
                return '#007cba';
        }
    }
    
    /**
     * Cancel patient appointment
     */
    public function cancel_appointment($appointment_id, $patient_id) {
        // Verify appointment belongs to patient
        $appointment = new CMS_Appointment();
        $apt_data = $appointment->get_appointment($appointment_id);
        
        if (!$apt_data || $apt_data->patient_id !== $patient_id) {
            return false;
        }
        
        return $appointment->cancel_appointment($appointment_id, 'Cancelled by patient');
    }
    
    /**
     * Get patient medical history
     */
    public function get_patient_medical_history($patient_id) {
        $patient = new CMS_Patient();
        return $patient->get_patient_history($patient_id);
    }
    
    /**
     * Update patient profile
     */
    public function update_patient_profile($patient_id, $data) {
        $patient = new CMS_Patient();
        return $patient->update_patient($patient_id, $data);
    }
}