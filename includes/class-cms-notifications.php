<?php
/**
 * Notifications management class
 */
class CMS_Notifications {
    
    private $db;
    
    public function __construct() {
        $this->db = new CMS_Database();
    }
    
    /**
     * Send appointment reminders
     */
    public function send_appointment_reminders() {
        // Get appointments for tomorrow
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        
        $query = "SELECT a.*, p.first_name, p.last_name, p.email, p.phone,
                         u.display_name as doctor_name
                  FROM {$this->db->get_table_name('appointments')} a
                  JOIN {$this->db->get_table_name('patients')} p ON a.patient_id = p.patient_id
                  JOIN {$GLOBALS['wpdb']->users} u ON a.doctor_id = u.ID
                  WHERE a.appointment_date = %s AND a.status = 'scheduled'";
        
        $appointments = $this->db->get_results($query, array($tomorrow));
        
        foreach ($appointments as $appointment) {
            $this->send_appointment_reminder($appointment);
        }
        
        return count($appointments);
    }
    
    /**
     * Send payment reminders
     */
    public function send_payment_reminders() {
        // Get unpaid invoices that are overdue
        $query = "SELECT b.*, p.first_name, p.last_name, p.email, p.phone
                  FROM {$this->db->get_table_name('billing')} b
                  JOIN {$this->db->get_table_name('patients')} p ON b.patient_id = p.patient_id
                  WHERE b.payment_status != 'paid' 
                  AND b.due_date < CURDATE()";
        
        $invoices = $this->db->get_results($query);
        
        foreach ($invoices as $invoice) {
            $this->send_payment_reminder($invoice);
        }
        
        return count($invoices);
    }
    
    /**
     * Send appointment reminder
     */
    private function send_appointment_reminder($appointment) {
        $clinic_name = get_option('cms_clinic_name', 'Our Clinic');
        $clinic_phone = get_option('cms_clinic_phone', 'Our Phone');
        
        // Email reminder
        if ($appointment->email && get_option('cms_email_enabled', '1')) {
            $subject = 'Appointment Reminder - ' . $clinic_name;
            $message = "Dear {$appointment->first_name} {$appointment->last_name},\n\n";
            $message .= "This is a friendly reminder about your appointment tomorrow:\n\n";
            $message .= "Date: " . date('F j, Y', strtotime($appointment->appointment_date)) . "\n";
            $message .= "Time: " . date('g:i A', strtotime($appointment->appointment_time)) . "\n";
            $message .= "Doctor: Dr. {$appointment->doctor_name}\n";
            $message .= "Duration: {$appointment->duration} minutes\n\n";
            
            if (!empty($appointment->notes)) {
                $message .= "Notes: {$appointment->notes}\n\n";
            }
            
            $message .= "Please arrive 10 minutes before your scheduled time.\n\n";
            $message .= "If you need to reschedule or cancel, please contact us at {$clinic_phone}.\n\n";
            $message .= "Best regards,\n" . $clinic_name . " Team";
            
            wp_mail($appointment->email, $subject, $message);
        }
        
        // SMS reminder
        if ($appointment->phone && get_option('cms_sms_enabled', '0')) {
            $this->send_sms($appointment->phone, $this->format_appointment_sms($appointment));
        }
    }
    
    /**
     * Send payment reminder
     */
    private function send_payment_reminder($invoice) {
        $clinic_name = get_option('cms_clinic_name', 'Our Clinic');
        $clinic_phone = get_option('cms_clinic_phone', 'Our Phone');
        
        // Email reminder
        if ($invoice->email && get_option('cms_email_enabled', '1')) {
            $subject = 'Payment Reminder - Invoice #' . $invoice->invoice_number;
            $message = "Dear {$invoice->first_name} {$invoice->last_name},\n\n";
            $message .= "This is a friendly reminder that your invoice is overdue:\n\n";
            $message .= "Invoice #: {$invoice->invoice_number}\n";
            $message .= "Due Date: " . date('F j, Y', strtotime($invoice->due_date)) . "\n";
            $message .= "Total Amount: $" . number_format($invoice->total_amount, 2) . "\n";
            $message .= "Amount Paid: $" . number_format($invoice->paid_amount, 2) . "\n";
            $message .= "Balance Due: $" . number_format($invoice->total_amount - $invoice->paid_amount, 2) . "\n\n";
            
            if (!empty($invoice->notes)) {
                $message .= "Notes: {$invoice->notes}\n\n";
            }
            
            $message .= "Please make your payment as soon as possible to avoid any late fees.\n\n";
            $message .= "You can pay online through your patient portal or contact us at {$clinic_phone}.\n\n";
            $message .= "Best regards,\n" . $clinic_name . " Billing Department";
            
            wp_mail($invoice->email, $subject, $message);
        }
        
        // SMS reminder
        if ($invoice->phone && get_option('cms_sms_enabled', '0')) {
            $this->send_sms($invoice->phone, $this->format_payment_sms($invoice));
        }
    }
    
    /**
     * Send SMS notification
     */
    private function send_sms($phone, $message) {
        // This would integrate with an SMS service provider like Twilio, MessageBird, etc.
        // For now, we'll just log the SMS
        
        $log_data = array(
            'phone' => $phone,
            'message' => $message,
            'sent_at' => current_time('mysql'),
            'status' => 'sent'
        );
        
        // You would implement the actual SMS sending here
        // Example with Twilio:
        /*
        try {
            $account_sid = get_option('cms_twilio_account_sid');
            $auth_token = get_option('cms_twilio_auth_token');
            $from_number = get_option('cms_twilio_from_number');
            
            $client = new Twilio\Rest\Client($account_sid, $auth_token);
            $message = $client->messages->create(
                $phone,
                array(
                    'from' => $from_number,
                    'body' => $message
                )
            );
            
            $log_data['status'] = 'delivered';
            $log_data['message_sid'] = $message->sid;
        } catch (Exception $e) {
            $log_data['status'] = 'failed';
            $log_data['error'] = $e->getMessage();
        }
        */
        
        // Log SMS attempt
        $this->log_sms($log_data);
    }
    
    /**
     * Format appointment SMS
     */
    private function format_appointment_sms($appointment) {
        $clinic_name = get_option('cms_clinic_name', 'Clinic');
        $date = date('M j', strtotime($appointment->appointment_date));
        $time = date('g:i A', strtotime($appointment->appointment_time));
        
        return "{$clinic_name}: Your appointment is tomorrow {$date} at {$time}. Please arrive 10 min early. Call us if you need to reschedule.";
    }
    
    /**
     * Format payment SMS
     */
    private function format_payment_sms($invoice) {
        $clinic_name = get_option('cms_clinic_name', 'Clinic');
        $amount = number_format($invoice->total_amount - $invoice->paid_amount, 2);
        
        return "{$clinic_name}: Your invoice #{$invoice->invoice_number} is overdue. Balance due: \${$amount}. Please contact us to arrange payment.";
    }
    
    /**
     * Log SMS
     */
    private function log_sms($data) {
        // Create SMS logs table if it doesn't exist
        $this->create_sms_logs_table();
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'cms_sms_logs';
        
        $wpdb->insert($table_name, $data, array('%s', '%s', '%s', '%s', '%s'));
    }
    
    /**
     * Create SMS logs table
     */
    private function create_sms_logs_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cms_sms_logs';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
        if ($table_exists) {
            return;
        }
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            phone varchar(20) NOT NULL,
            message text NOT NULL,
            sent_at datetime DEFAULT CURRENT_TIMESTAMP,
            status varchar(20) DEFAULT 'sent',
            message_sid varchar(100),
            error text,
            PRIMARY KEY (id),
            KEY phone (phone),
            KEY status (status),
            KEY sent_at (sent_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Send welcome notification to new patients
     */
    public function send_welcome_notification($patient_data) {
        if (empty($patient_data['email'])) {
            return false;
        }
        
        $clinic_name = get_option('cms_clinic_name', 'Our Clinic');
        $clinic_phone = get_option('cms_clinic_phone', 'Our Phone');
        $clinic_address = get_option('cms_clinic_address', 'Our Address');
        
        $subject = 'Welcome to ' . $clinic_name;
        $message = "Dear {$patient_data['first_name']} {$patient_data['last_name']},\n\n";
        $message .= "Welcome to {$clinic_name}! We're pleased to have you as a patient.\n\n";
        $message .= "Clinic Information:\n";
        $message .= "Address: {$clinic_address}\n";
        $message .= "Phone: {$clinic_phone}\n\n";
        $message .= "Your patient ID is: {$patient_data['patient_id']}\n\n";
        $message .= "You can now:\n";
        $message .= "- Book appointments online through your patient portal\n";
        $message .= "- View your medical history and prescriptions\n";
        $message .= "- Access your billing information\n";
        $message .= "- Download medical reports\n\n";
        $message .= "If you have any questions, please don't hesitate to contact us.\n\n";
        $message .= "Best regards,\n" . $clinic_name . " Team";
        
        return wp_mail($patient_data['email'], $subject, $message);
    }
    
    /**
     * Send follow-up reminder
     */
    public function send_follow_up_reminder($prescription) {
        $patient = $this->db->get_row(
            "SELECT * FROM {$this->db->get_table_name('patients')} WHERE patient_id = %s",
            array($prescription->patient_id)
        );
        
        if (!$patient || !$patient->email) {
            return false;
        }
        
        $clinic_name = get_option('cms_clinic_name', 'Our Clinic');
        $clinic_phone = get_option('cms_clinic_phone', 'Our Phone');
        
        $subject = 'Follow-up Reminder - ' . $clinic_name;
        $message = "Dear {$patient->first_name} {$patient->last_name},\n\n";
        $message .= "This is a reminder about your scheduled follow-up appointment:\n\n";
        $message .= "Follow-up Date: " . date('F j, Y', strtotime($prescription->follow_up_date)) . "\n";
        $message .= "Doctor: Dr. " . get_user_by('id', $prescription->doctor_id)->display_name . "\n\n";
        $message .= "Please contact us to schedule your follow-up appointment if you haven't already.\n\n";
        $message .= "Phone: {$clinic_phone}\n\n";
        $message .= "Best regards,\n" . $clinic_name . " Team";
        
        return wp_mail($patient->email, $subject, $message);
    }
    
    /**
     * Send low stock alerts to staff
     */
    public function send_low_stock_alert_to_staff($medicine_data) {
        $admin_email = get_option('admin_email');
        $clinic_name = get_option('cms_clinic_name', 'Your Clinic');
        
        $subject = 'Low Stock Alert - ' . $clinic_name;
        $message = "Low stock alert for the following medicine:\n\n";
        $message .= "Medicine: {$medicine_data['medicine_name']}\n";
        $message .= "Generic Name: {$medicine_data['generic_name']}\n";
        $message .= "Current Stock: {$medicine_data['current_stock']}\n";
        $message .= "Minimum Stock: {$medicine_data['minimum_stock']}\n";
        $message .= "Category: {$medicine_data['category']}\n";
        $message .= "Manufacturer: {$medicine_data['manufacturer']}\n\n";
        $message .= "Please reorder this medicine to maintain adequate stock levels.\n\n";
        $message .= "Best regards,\n" . $clinic_name . " Management System";
        
        return wp_mail($admin_email, $subject, $message);
    }
    
    /**
     * Get notification statistics
     */
    public function get_notification_stats() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cms_sms_logs';
        
        // Check if SMS logs table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
        
        if (!$table_exists) {
            return array(
                'total_sms' => 0,
                'delivered_sms' => 0,
                'failed_sms' => 0,
                'total_emails' => 0
            );
        }
        
        $total_sms = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $delivered_sms = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'delivered'");
        $failed_sms = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'failed'");
        
        return array(
            'total_sms' => $total_sms ?: 0,
            'delivered_sms' => $delivered_sms ?: 0,
            'failed_sms' => $failed_sms ?: 0,
            'total_emails' => 0 // WordPress doesn't track email counts by default
        );
    }
    
    /**
     * Test notification system
     */
    public function test_notification_system($test_email = null) {
        if (!$test_email) {
            $test_email = get_option('admin_email');
        }
        
        $clinic_name = get_option('cms_clinic_name', 'Our Clinic');
        
        $subject = 'Test Notification - ' . $clinic_name;
        $message = "This is a test notification from the Clinic Management System.\n\n";
        $message .= "If you receive this email, the notification system is working correctly.\n\n";
        $message .= "Test sent at: " . current_time('mysql') . "\n\n";
        $message .= "Best regards,\n" . $clinic_name . " Management System";
        
        $email_result = wp_mail($test_email, $subject, $message);
        
        return array(
            'email_sent' => $email_result,
            'test_email' => $test_email,
            'timestamp' => current_time('mysql')
        );
    }
}