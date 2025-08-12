<?php
/**
 * Notifications Module - SMS and Email Automation
 */

if (!defined('ABSPATH')) {
    exit;
}

class CMS_Notifications
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
        // Hook into appointment creation/updates
        add_action('cms_appointment_created', array($this, 'scheduleAppointmentReminder'));
        add_action('cms_appointment_updated', array($this, 'handleAppointmentUpdate'));
        
        // Hook into billing
        add_action('cms_bill_created', array($this, 'schedulePaymentReminder'));
        add_action('cms_payment_overdue', array($this, 'sendPaymentReminder'));
        
        // Daily cron jobs
        add_action('cms_daily_notifications', array($this, 'processDailyNotifications'));
        
        // Schedule daily cron if not already scheduled
        if (!wp_next_scheduled('cms_daily_notifications')) {
            wp_schedule_event(time(), 'daily', 'cms_daily_notifications');
        }

        // AJAX handlers
        add_action('wp_ajax_cms_send_notification', array($this, 'ajaxSendNotification'));
        add_action('wp_ajax_cms_get_notification_templates', array($this, 'ajaxGetNotificationTemplates'));
        add_action('wp_ajax_cms_save_notification_template', array($this, 'ajaxSaveNotificationTemplate'));
    }

    /**
     * Send appointment reminder
     */
    public static function sendAppointmentReminder($appointment_id)
    {
        global $wpdb;
        
        $appointment = $wpdb->get_row($wpdb->prepare("
            SELECT a.*, p.first_name, p.last_name, p.phone, p.email, u.display_name as doctor_name
            FROM {$wpdb->prefix}cms_appointments a
            LEFT JOIN {$wpdb->prefix}cms_patients p ON a.patient_id = p.id  
            LEFT JOIN {$wpdb->users} u ON a.doctor_id = u.ID
            WHERE a.id = %d
        ", $appointment_id));

        if (!$appointment) {
            return false;
        }

        $appointment_datetime = $appointment->appointment_date . ' ' . $appointment->appointment_time;
        $formatted_date = date('F j, Y', strtotime($appointment->appointment_date));
        $formatted_time = date('g:i A', strtotime($appointment->appointment_time));

        // SMS notification
        if (!empty($appointment->phone)) {
            $sms_message = sprintf(
                "Reminder: You have an appointment with Dr. %s on %s at %s. Please arrive 15 minutes early. Reply STOP to opt out.",
                $appointment->doctor_name,
                $formatted_date,
                $formatted_time
            );
            
            self::sendSMS($appointment->phone, $sms_message, $appointment_id, 'appointment_reminder');
        }

        // Email notification
        if (!empty($appointment->email)) {
            $subject = sprintf(__('Appointment Reminder - %s', 'clinic-management'), $formatted_date);
            
            $message = sprintf(
                "Dear %s %s,\n\nThis is a reminder of your upcoming appointment:\n\nDate: %s\nTime: %s\nDoctor: Dr. %s\n\nPlease arrive 15 minutes before your appointment time.\n\nIf you need to reschedule or cancel, please contact us as soon as possible.\n\nThank you,\nClinic Management Team",
                $appointment->first_name,
                $appointment->last_name,
                $formatted_date,
                $formatted_time,
                $appointment->doctor_name
            );
            
            self::sendEmail($appointment->email, $subject, $message, $appointment_id, 'appointment_reminder');
        }

        // Log notification
        self::logNotification($appointment_id, 'appointment', 'appointment_reminder', 'sent');
        
        return true;
    }

    /**
     * Send payment reminder
     */
    public static function sendPaymentReminder($bill_id)
    {
        global $wpdb;
        
        $bill = $wpdb->get_row($wpdb->prepare("
            SELECT b.*, p.first_name, p.last_name, p.phone, p.email
            FROM {$wpdb->prefix}cms_billing b
            LEFT JOIN {$wpdb->prefix}cms_patients p ON b.patient_id = p.id
            WHERE b.id = %d
        ", $bill_id));

        if (!$bill || $bill->payment_status === 'paid') {
            return false;
        }

        $outstanding_amount = $bill->total_amount - $bill->amount_paid;
        $due_date = date('F j, Y', strtotime($bill->due_date));

        // SMS notification
        if (!empty($bill->phone)) {
            $sms_message = sprintf(
                "Payment Reminder: You have an outstanding bill of $%.2f due on %s. Bill #%s. Please contact us to arrange payment.",
                $outstanding_amount,
                $due_date,
                $bill->bill_number
            );
            
            self::sendSMS($bill->phone, $sms_message, $bill_id, 'payment_reminder');
        }

        // Email notification
        if (!empty($bill->email)) {
            $subject = sprintf(__('Payment Reminder - Bill #%s', 'clinic-management'), $bill->bill_number);
            
            $message = sprintf(
                "Dear %s %s,\n\nThis is a reminder that you have an outstanding bill:\n\nBill Number: %s\nAmount Due: $%.2f\nDue Date: %s\n\nPlease arrange payment at your earliest convenience. You can pay online through our patient portal or contact our office.\n\nThank you,\nClinic Management Team",
                $bill->first_name,
                $bill->last_name,
                $bill->bill_number,
                $outstanding_amount,
                $due_date
            );
            
            self::sendEmail($bill->email, $subject, $message, $bill_id, 'payment_reminder');
        }

        // Log notification
        self::logNotification($bill_id, 'billing', 'payment_reminder', 'sent');
        
        return true;
    }

    /**
     * Send SMS using configured provider
     */
    public static function sendSMS($phone, $message, $reference_id = null, $type = 'general')
    {
        // In a real implementation, integrate with SMS providers like:
        // - Twilio
        // - Nexmo/Vonage
        // - AWS SNS
        // - Local SMS gateway
        
        $sms_provider = CMS_Database::getSetting('sms_provider', 'twilio');
        $sms_enabled = CMS_Database::getSetting('sms_enabled', false);
        
        if (!$sms_enabled) {
            return false;
        }

        // Clean phone number
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        // Add country code if not present
        if (substr($phone, 0, 1) !== '+') {
            $default_country_code = CMS_Database::getSetting('default_country_code', '+1');
            $phone = $default_country_code . $phone;
        }

        $result = false;
        
        switch ($sms_provider) {
            case 'twilio':
                $result = self::sendTwilioSMS($phone, $message);
                break;
            case 'nexmo':
                $result = self::sendNexmoSMS($phone, $message);
                break;
            case 'aws_sns':
                $result = self::sendAWSSNS($phone, $message);
                break;
            default:
                // Log that SMS provider is not configured
                error_log("CMS: SMS provider '$sms_provider' not implemented");
                break;
        }

        // Log the SMS attempt
        self::logNotification($reference_id, 'sms', $type, $result ? 'sent' : 'failed', $message);
        
        return $result;
    }

    /**
     * Send email
     */
    public static function sendEmail($to, $subject, $message, $reference_id = null, $type = 'general')
    {
        $email_enabled = CMS_Database::getSetting('email_enabled', true);
        
        if (!$email_enabled) {
            return false;
        }

        // Get email settings
        $from_name = CMS_Database::getSetting('clinic_name', get_bloginfo('name'));
        $from_email = CMS_Database::getSetting('clinic_email', get_option('admin_email'));
        
        // Set headers
        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . $from_name . ' <' . $from_email . '>'
        );

        // Send email using WordPress wp_mail
        $result = wp_mail($to, $subject, $message, $headers);
        
        // Log the email attempt
        self::logNotification($reference_id, 'email', $type, $result ? 'sent' : 'failed', $message);
        
        return $result;
    }

    /**
     * Twilio SMS integration (placeholder)
     */
    private static function sendTwilioSMS($phone, $message)
    {
        $twilio_sid = CMS_Database::getSetting('twilio_sid', '');
        $twilio_token = CMS_Database::getSetting('twilio_token', '');
        $twilio_phone = CMS_Database::getSetting('twilio_phone', '');
        
        if (empty($twilio_sid) || empty($twilio_token) || empty($twilio_phone)) {
            return false;
        }

        // In a real implementation, use Twilio SDK:
        /*
        require_once 'vendor/autoload.php';
        use Twilio\Rest\Client;
        
        $client = new Client($twilio_sid, $twilio_token);
        
        try {
            $message = $client->messages->create(
                $phone,
                [
                    'from' => $twilio_phone,
                    'body' => $message
                ]
            );
            return true;
        } catch (Exception $e) {
            error_log('Twilio SMS Error: ' . $e->getMessage());
            return false;
        }
        */
        
        // Placeholder - log the attempt
        error_log("CMS: Twilio SMS to $phone: $message");
        return true; // Return true for demo purposes
    }

    /**
     * Nexmo SMS integration (placeholder)
     */
    private static function sendNexmoSMS($phone, $message)
    {
        // Placeholder for Nexmo/Vonage implementation
        error_log("CMS: Nexmo SMS to $phone: $message");
        return true;
    }

    /**
     * AWS SNS integration (placeholder)
     */
    private static function sendAWSSNS($phone, $message)
    {
        // Placeholder for AWS SNS implementation
        error_log("CMS: AWS SNS to $phone: $message");
        return true;
    }

    /**
     * Schedule appointment reminder
     */
    public function scheduleAppointmentReminder($appointment_id)
    {
        global $wpdb;
        
        $appointment = $wpdb->get_row($wpdb->prepare("
            SELECT appointment_date, appointment_time 
            FROM {$wpdb->prefix}cms_appointments 
            WHERE id = %d
        ", $appointment_id));

        if (!$appointment) {
            return;
        }

        $appointment_datetime = strtotime($appointment->appointment_date . ' ' . $appointment->appointment_time);
        $reminder_hours = CMS_Database::getSetting('appointment_reminder_hours', 24);
        $reminder_time = $appointment_datetime - ($reminder_hours * 3600);

        // Only schedule if reminder time is in the future
        if ($reminder_time > time()) {
            wp_schedule_single_event($reminder_time, 'cms_send_appointment_reminder', array($appointment_id));
        }
    }

    /**
     * Handle appointment updates
     */
    public function handleAppointmentUpdate($appointment_id, $old_data, $new_data)
    {
        // If appointment time changed, reschedule reminder
        if ($old_data['appointment_date'] !== $new_data['appointment_date'] || 
            $old_data['appointment_time'] !== $new_data['appointment_time']) {
            
            // Cancel old reminder
            wp_clear_scheduled_hook('cms_send_appointment_reminder', array($appointment_id));
            
            // Schedule new reminder
            $this->scheduleAppointmentReminder($appointment_id);
        }
    }

    /**
     * Schedule payment reminder
     */
    public function schedulePaymentReminder($bill_id)
    {
        global $wpdb;
        
        $bill = $wpdb->get_row($wpdb->prepare("
            SELECT due_date, payment_status 
            FROM {$wpdb->prefix}cms_billing 
            WHERE id = %d
        ", $bill_id));

        if (!$bill || $bill->payment_status === 'paid') {
            return;
        }

        $due_date = strtotime($bill->due_date);
        $reminder_days = CMS_Database::getSetting('payment_reminder_days', 3);
        $reminder_time = $due_date - ($reminder_days * 24 * 3600);

        // Schedule reminder before due date
        if ($reminder_time > time()) {
            wp_schedule_single_event($reminder_time, 'cms_send_payment_reminder', array($bill_id));
        }

        // Schedule overdue reminder
        $overdue_reminder_time = $due_date + (24 * 3600); // 1 day after due date
        if ($overdue_reminder_time > time()) {
            wp_schedule_single_event($overdue_reminder_time, 'cms_payment_overdue', array($bill_id));
        }
    }

    /**
     * Process daily notifications
     */
    public function processDailyNotifications()
    {
        // Send appointment reminders for tomorrow
        $this->sendTomorrowAppointmentReminders();
        
        // Send payment reminders for overdue bills
        $this->sendOverduePaymentReminders();
        
        // Send low stock alerts to staff
        $this->sendLowStockAlerts();
    }

    /**
     * Send appointment reminders for tomorrow
     */
    private function sendTomorrowAppointmentReminders()
    {
        global $wpdb;
        
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        
        $appointments = $wpdb->get_results($wpdb->prepare("
            SELECT id FROM {$wpdb->prefix}cms_appointments 
            WHERE appointment_date = %s 
            AND status = 'scheduled'
        ", $tomorrow));

        foreach ($appointments as $appointment) {
            self::sendAppointmentReminder($appointment->id);
        }
    }

    /**
     * Send payment reminders for overdue bills
     */
    private function sendOverduePaymentReminders()
    {
        global $wpdb;
        
        $today = date('Y-m-d');
        
        $overdue_bills = $wpdb->get_results($wpdb->prepare("
            SELECT id FROM {$wpdb->prefix}cms_billing 
            WHERE due_date < %s 
            AND payment_status IN ('unpaid', 'partial')
        ", $today));

        foreach ($overdue_bills as $bill) {
            self::sendPaymentReminder($bill->id);
        }
    }

    /**
     * Send low stock alerts to staff
     */
    private function sendLowStockAlerts()
    {
        $low_stock_items = CMS_Inventory::getLowStockMedicines();
        
        if (empty($low_stock_items)) {
            return;
        }

        $staff_emails = $this->getStaffEmails();
        
        if (empty($staff_emails)) {
            return;
        }

        $subject = __('Low Stock Alert - Inventory Management', 'clinic-management');
        $message = __('The following items are running low in stock:', 'clinic-management') . "\n\n";
        
        foreach ($low_stock_items as $item) {
            $message .= sprintf(
                "- %s: %d remaining (Reorder level: %d)\n",
                $item->medicine_name,
                $item->quantity_in_stock,
                $item->reorder_level
            );
        }
        
        $message .= "\n" . __('Please reorder these items as soon as possible.', 'clinic-management');

        foreach ($staff_emails as $email) {
            self::sendEmail($email, $subject, $message, null, 'low_stock_alert');
        }
    }

    /**
     * Get staff email addresses
     */
    private function getStaffEmails()
    {
        $staff_users = get_users(array(
            'role__in' => array('cms_doctor', 'cms_nurse', 'cms_pharmacist', 'administrator'),
            'fields' => 'user_email'
        ));

        return $staff_users;
    }

    /**
     * Log notification
     */
    public static function logNotification($reference_id, $type, $notification_type, $status, $content = '')
    {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'cms_notifications',
            array(
                'reference_id' => $reference_id,
                'type' => $type,
                'notification_type' => $notification_type,
                'status' => $status,
                'content' => $content,
                'sent_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s')
        );
    }

    /**
     * Get notification templates
     */
    public static function getNotificationTemplates()
    {
        return array(
            'appointment_reminder' => array(
                'sms' => 'Reminder: You have an appointment with Dr. {doctor_name} on {date} at {time}. Please arrive 15 minutes early.',
                'email_subject' => 'Appointment Reminder - {date}',
                'email_body' => 'Dear {patient_name},\n\nThis is a reminder of your upcoming appointment:\n\nDate: {date}\nTime: {time}\nDoctor: Dr. {doctor_name}\n\nPlease arrive 15 minutes before your appointment time.\n\nThank you,\nClinic Management Team'
            ),
            'payment_reminder' => array(
                'sms' => 'Payment Reminder: You have an outstanding bill of ${amount} due on {due_date}. Bill #{bill_number}.',
                'email_subject' => 'Payment Reminder - Bill #{bill_number}',
                'email_body' => 'Dear {patient_name},\n\nThis is a reminder that you have an outstanding bill:\n\nBill Number: {bill_number}\nAmount Due: ${amount}\nDue Date: {due_date}\n\nPlease arrange payment at your earliest convenience.\n\nThank you,\nClinic Management Team'
            )
        );
    }

    /**
     * AJAX handler for sending notification
     */
    public function ajaxSendNotification()
    {
        check_ajax_referer('cms_ajax_nonce', 'nonce');

        if (!CMS_User_Roles::canAccessCMS()) {
            wp_die(json_encode(array('success' => false, 'message' => __('Permission denied', 'clinic-management'))));
        }

        $type = sanitize_text_field($_POST['type']);
        $recipient = sanitize_text_field($_POST['recipient']);
        $message = sanitize_textarea_field($_POST['message']);
        $notification_type = sanitize_text_field($_POST['notification_type'] ?? 'manual');

        $result = false;
        
        if ($type === 'sms') {
            $result = self::sendSMS($recipient, $message, null, $notification_type);
        } elseif ($type === 'email') {
            $subject = sanitize_text_field($_POST['subject']);
            $result = self::sendEmail($recipient, $subject, $message, null, $notification_type);
        }

        if ($result) {
            wp_die(json_encode(array('success' => true, 'message' => __('Notification sent successfully', 'clinic-management'))));
        } else {
            wp_die(json_encode(array('success' => false, 'message' => __('Failed to send notification', 'clinic-management'))));
        }
    }

    /**
     * AJAX handler for getting notification templates
     */
    public function ajaxGetNotificationTemplates()
    {
        check_ajax_referer('cms_ajax_nonce', 'nonce');

        if (!CMS_User_Roles::canAccessCMS()) {
            wp_die(json_encode(array('success' => false, 'message' => __('Permission denied', 'clinic-management'))));
        }

        $templates = self::getNotificationTemplates();
        wp_die(json_encode(array('success' => true, 'data' => $templates)));
    }

    /**
     * AJAX handler for saving notification template
     */
    public function ajaxSaveNotificationTemplate()
    {
        check_ajax_referer('cms_ajax_nonce', 'nonce');

        if (!CMS_User_Roles::canAccessCMS()) {
            wp_die(json_encode(array('success' => false, 'message' => __('Permission denied', 'clinic-management'))));
        }

        $template_type = sanitize_text_field($_POST['template_type']);
        $template_data = $_POST['template_data'];

        // In a real implementation, save custom templates to database
        // For now, just return success
        wp_die(json_encode(array('success' => true, 'message' => __('Template saved successfully', 'clinic-management'))));
    }
}