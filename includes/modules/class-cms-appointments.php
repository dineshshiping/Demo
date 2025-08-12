<?php
/**
 * Appointment management module
 */

if (!defined('ABSPATH')) {
    exit;
}

class CMS_Appointments
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
        add_action('wp_ajax_cms_save_appointment', array($this, 'ajaxSaveAppointment'));
        add_action('wp_ajax_cms_get_appointments', array($this, 'ajaxGetAppointments'));
        add_action('wp_ajax_cms_update_appointment_status', array($this, 'ajaxUpdateAppointmentStatus'));
        add_action('wp_ajax_cms_get_available_slots', array($this, 'ajaxGetAvailableSlots'));
        add_action('wp_ajax_cms_delete_appointment', array($this, 'ajaxDeleteAppointment'));
        
        // For patient portal
        add_action('wp_ajax_nopriv_cms_book_appointment', array($this, 'ajaxBookAppointmentPortal'));
        add_action('wp_ajax_cms_book_appointment', array($this, 'ajaxBookAppointmentPortal'));
    }

    /**
     * Create new appointment
     */
    public static function createAppointment($data)
    {
        global $wpdb;

        // Validate required fields
        if (empty($data['patient_id']) || empty($data['doctor_id']) || empty($data['appointment_date']) || empty($data['appointment_time'])) {
            return new WP_Error('missing_data', __('Patient, doctor, date and time are required.', 'clinic-management'));
        }

        // Check if slot is available
        $slot_available = self::isSlotAvailable($data['doctor_id'], $data['appointment_date'], $data['appointment_time'], $data['duration'] ?? 30);
        if (!$slot_available) {
            return new WP_Error('slot_unavailable', __('Selected time slot is not available.', 'clinic-management'));
        }

        // Prepare data
        $insert_data = array(
            'patient_id' => intval($data['patient_id']),
            'doctor_id' => intval($data['doctor_id']),
            'appointment_date' => sanitize_text_field($data['appointment_date']),
            'appointment_time' => sanitize_text_field($data['appointment_time']),
            'duration' => !empty($data['duration']) ? intval($data['duration']) : 30,
            'appointment_type' => !empty($data['appointment_type']) ? sanitize_text_field($data['appointment_type']) : 'consultation',
            'notes' => !empty($data['notes']) ? sanitize_textarea_field($data['notes']) : null,
            'status' => !empty($data['status']) ? sanitize_text_field($data['status']) : 'scheduled',
            'created_by' => get_current_user_id(),
        );

        $result = $wpdb->insert(
            $wpdb->prefix . 'cms_appointments',
            $insert_data,
            array('%d', '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%d')
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Failed to create appointment.', 'clinic-management'));
        }

        $appointment_id = $wpdb->insert_id;

        // Schedule reminder notification
        self::scheduleAppointmentReminder($appointment_id);

        return $appointment_id;
    }

    /**
     * Update appointment
     */
    public static function updateAppointment($appointment_id, $data)
    {
        global $wpdb;

        // Validate appointment exists
        $appointment = self::getAppointment($appointment_id);
        if (!$appointment) {
            return new WP_Error('appointment_not_found', __('Appointment not found.', 'clinic-management'));
        }

        // If changing date/time, check availability
        if (!empty($data['appointment_date']) || !empty($data['appointment_time'])) {
            $doctor_id = !empty($data['doctor_id']) ? $data['doctor_id'] : $appointment->doctor_id;
            $date = !empty($data['appointment_date']) ? $data['appointment_date'] : $appointment->appointment_date;
            $time = !empty($data['appointment_time']) ? $data['appointment_time'] : $appointment->appointment_time;
            $duration = !empty($data['duration']) ? $data['duration'] : $appointment->duration;

            $slot_available = self::isSlotAvailable($doctor_id, $date, $time, $duration, $appointment_id);
            if (!$slot_available) {
                return new WP_Error('slot_unavailable', __('Selected time slot is not available.', 'clinic-management'));
            }
        }

        // Prepare update data
        $update_data = array();
        $update_formats = array();

        $fields = array(
            'patient_id' => '%d',
            'doctor_id' => '%d',
            'appointment_date' => '%s',
            'appointment_time' => '%s',
            'duration' => '%d',
            'appointment_type' => '%s',
            'notes' => '%s',
            'status' => '%s',
        );

        foreach ($fields as $field => $format) {
            if (isset($data[$field])) {
                if (in_array($field, array('patient_id', 'doctor_id', 'duration'))) {
                    $update_data[$field] = intval($data[$field]);
                } elseif ($field === 'notes') {
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
            $wpdb->prefix . 'cms_appointments',
            $update_data,
            array('id' => $appointment_id),
            $update_formats,
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Failed to update appointment.', 'clinic-management'));
        }

        return true;
    }

    /**
     * Get appointment by ID
     */
    public static function getAppointment($appointment_id)
    {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare("
            SELECT a.*, p.first_name, p.last_name, p.phone, p.patient_id as patient_code,
                   u.display_name as doctor_name
            FROM {$wpdb->prefix}cms_appointments a
            LEFT JOIN {$wpdb->prefix}cms_patients p ON a.patient_id = p.id
            LEFT JOIN {$wpdb->users} u ON a.doctor_id = u.ID
            WHERE a.id = %d
        ", $appointment_id));
    }

    /**
     * Get appointments with filters
     */
    public static function getAppointments($filters = array(), $limit = 20, $offset = 0)
    {
        global $wpdb;

        $where_conditions = array('1=1');
        $prepare_values = array();

        // Date range filter
        if (!empty($filters['date_from'])) {
            $where_conditions[] = "a.appointment_date >= %s";
            $prepare_values[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where_conditions[] = "a.appointment_date <= %s";
            $prepare_values[] = $filters['date_to'];
        }

        // Doctor filter
        if (!empty($filters['doctor_id'])) {
            $where_conditions[] = "a.doctor_id = %d";
            $prepare_values[] = $filters['doctor_id'];
        }

        // Patient filter
        if (!empty($filters['patient_id'])) {
            $where_conditions[] = "a.patient_id = %d";
            $prepare_values[] = $filters['patient_id'];
        }

        // Status filter
        if (!empty($filters['status'])) {
            $where_conditions[] = "a.status = %s";
            $prepare_values[] = $filters['status'];
        }

        // Search filter
        if (!empty($filters['search'])) {
            $search_term = '%' . $wpdb->esc_like($filters['search']) . '%';
            $where_conditions[] = "(p.first_name LIKE %s OR p.last_name LIKE %s OR p.phone LIKE %s OR p.patient_id LIKE %s)";
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
            SELECT a.*, p.first_name, p.last_name, p.phone, p.patient_id as patient_code,
                   u.display_name as doctor_name
            FROM {$wpdb->prefix}cms_appointments a
            LEFT JOIN {$wpdb->prefix}cms_patients p ON a.patient_id = p.id
            LEFT JOIN {$wpdb->users} u ON a.doctor_id = u.ID
            WHERE {$where_clause}
            ORDER BY a.appointment_date DESC, a.appointment_time DESC
            LIMIT %d OFFSET %d
        ";

        return $wpdb->get_results($wpdb->prepare($sql, $prepare_values));
    }

    /**
     * Get appointments count
     */
    public static function getAppointmentsCount($filters = array())
    {
        global $wpdb;

        $where_conditions = array('1=1');
        $prepare_values = array();

        // Apply same filters as getAppointments but without limit/offset
        if (!empty($filters['date_from'])) {
            $where_conditions[] = "a.appointment_date >= %s";
            $prepare_values[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where_conditions[] = "a.appointment_date <= %s";
            $prepare_values[] = $filters['date_to'];
        }

        if (!empty($filters['doctor_id'])) {
            $where_conditions[] = "a.doctor_id = %d";
            $prepare_values[] = $filters['doctor_id'];
        }

        if (!empty($filters['patient_id'])) {
            $where_conditions[] = "a.patient_id = %d";
            $prepare_values[] = $filters['patient_id'];
        }

        if (!empty($filters['status'])) {
            $where_conditions[] = "a.status = %s";
            $prepare_values[] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $search_term = '%' . $wpdb->esc_like($filters['search']) . '%';
            $where_conditions[] = "(p.first_name LIKE %s OR p.last_name LIKE %s OR p.phone LIKE %s OR p.patient_id LIKE %s)";
            $prepare_values[] = $search_term;
            $prepare_values[] = $search_term;
            $prepare_values[] = $search_term;
            $prepare_values[] = $search_term;
        }

        $where_clause = implode(' AND ', $where_conditions);

        $sql = "
            SELECT COUNT(*)
            FROM {$wpdb->prefix}cms_appointments a
            LEFT JOIN {$wpdb->prefix}cms_patients p ON a.patient_id = p.id
            WHERE {$where_clause}
        ";

        if (empty($prepare_values)) {
            return $wpdb->get_var($sql);
        }

        return $wpdb->get_var($wpdb->prepare($sql, $prepare_values));
    }

    /**
     * Check if time slot is available
     */
    public static function isSlotAvailable($doctor_id, $date, $time, $duration = 30, $exclude_appointment_id = null)
    {
        global $wpdb;

        // Calculate end time
        $start_datetime = $date . ' ' . $time;
        $end_datetime = date('Y-m-d H:i:s', strtotime($start_datetime . ' +' . $duration . ' minutes'));

        $sql = "
            SELECT COUNT(*) 
            FROM {$wpdb->prefix}cms_appointments 
            WHERE doctor_id = %d 
            AND appointment_date = %s 
            AND status NOT IN ('cancelled', 'completed')
            AND (
                (appointment_time <= %s AND DATE_ADD(CONCAT(appointment_date, ' ', appointment_time), INTERVAL duration MINUTE) > %s)
                OR
                (appointment_time < %s AND DATE_ADD(CONCAT(appointment_date, ' ', appointment_time), INTERVAL duration MINUTE) >= %s)
            )
        ";

        $params = array($doctor_id, $date, $time, $start_datetime, $end_datetime, $end_datetime);

        if ($exclude_appointment_id) {
            $sql .= " AND id != %d";
            $params[] = $exclude_appointment_id;
        }

        $conflicting_appointments = $wpdb->get_var($wpdb->prepare($sql, $params));

        return $conflicting_appointments == 0;
    }

    /**
     * Get available time slots for a doctor on a specific date
     */
    public static function getAvailableSlots($doctor_id, $date, $duration = 30)
    {
        $working_hours_start = CMS_Database::getSetting('working_hours_start', '09:00');
        $working_hours_end = CMS_Database::getSetting('working_hours_end', '18:00');
        $working_days = explode(',', CMS_Database::getSetting('working_days', 'monday,tuesday,wednesday,thursday,friday,saturday'));

        // Check if the date is a working day
        $day_of_week = strtolower(date('l', strtotime($date)));
        if (!in_array($day_of_week, $working_days)) {
            return array();
        }

        // Generate all possible slots
        $slots = array();
        $current_time = strtotime($date . ' ' . $working_hours_start);
        $end_time = strtotime($date . ' ' . $working_hours_end);

        while ($current_time < $end_time) {
            $slot_time = date('H:i:s', $current_time);
            
            if (self::isSlotAvailable($doctor_id, $date, $slot_time, $duration)) {
                $slots[] = $slot_time;
            }
            
            $current_time += ($duration * 60);
        }

        return $slots;
    }

    /**
     * Schedule appointment reminder notification
     */
    public static function scheduleAppointmentReminder($appointment_id)
    {
        global $wpdb;

        $appointment = self::getAppointment($appointment_id);
        if (!$appointment) {
            return false;
        }

        $reminder_hours = CMS_Database::getSetting('appointment_reminder_hours', 24);
        $scheduled_time = date('Y-m-d H:i:s', strtotime($appointment->appointment_date . ' ' . $appointment->appointment_time . ' -' . $reminder_hours . ' hours'));

        // Insert reminder notification
        $wpdb->insert(
            $wpdb->prefix . 'cms_notifications',
            array(
                'patient_id' => $appointment->patient_id,
                'appointment_id' => $appointment_id,
                'notification_type' => 'appointment_reminder',
                'recipient_phone' => $appointment->phone,
                'message' => sprintf(
                    __('Reminder: You have an appointment with Dr. %s on %s at %s', 'clinic-management'),
                    $appointment->doctor_name,
                    date('F j, Y', strtotime($appointment->appointment_date)),
                    date('g:i A', strtotime($appointment->appointment_time))
                ),
                'scheduled_time' => $scheduled_time,
                'created_by' => get_current_user_id(),
            ),
            array('%d', '%d', '%s', '%s', '%s', '%s', '%d')
        );

        return true;
    }

    /**
     * AJAX: Save appointment
     */
    public function ajaxSaveAppointment()
    {
        check_ajax_referer('cms_ajax_nonce', 'nonce');

        if (!CMS_User_Roles::userCan('cms_manage_appointments')) {
            wp_die(json_encode(array('success' => false, 'message' => __('Permission denied', 'clinic-management'))));
        }

        $appointment_id = !empty($_POST['appointment_id']) ? intval($_POST['appointment_id']) : 0;
        $appointment_data = $_POST['appointment_data'];

        if ($appointment_id > 0) {
            // Update existing appointment
            $result = self::updateAppointment($appointment_id, $appointment_data);
        } else {
            // Create new appointment
            $result = self::createAppointment($appointment_data);
        }

        if (is_wp_error($result)) {
            wp_die(json_encode(array('success' => false, 'message' => $result->get_error_message())));
        }

        $response_data = array('success' => true, 'message' => __('Appointment saved successfully', 'clinic-management'));
        if ($appointment_id === 0) {
            $response_data['appointment_id'] = $result;
        }

        wp_die(json_encode($response_data));
    }

    /**
     * AJAX: Get appointments
     */
    public function ajaxGetAppointments()
    {
        check_ajax_referer('cms_ajax_nonce', 'nonce');

        if (!CMS_User_Roles::userCan('cms_manage_appointments')) {
            wp_die(json_encode(array('success' => false, 'message' => __('Permission denied', 'clinic-management'))));
        }

        $filters = $_POST['filters'] ?? array();
        $page = intval($_POST['page'] ?? 1);
        $per_page = 20;
        $offset = ($page - 1) * $per_page;

        $appointments = self::getAppointments($filters, $per_page, $offset);
        $total = self::getAppointmentsCount($filters);

        $response_data = array(
            'success' => true,
            'data' => $appointments,
            'total' => $total,
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($total / $per_page)
        );

        wp_die(json_encode($response_data));
    }

    /**
     * AJAX: Update appointment status
     */
    public function ajaxUpdateAppointmentStatus()
    {
        check_ajax_referer('cms_ajax_nonce', 'nonce');

        if (!CMS_User_Roles::userCan('cms_manage_appointments')) {
            wp_die(json_encode(array('success' => false, 'message' => __('Permission denied', 'clinic-management'))));
        }

        $appointment_id = intval($_POST['appointment_id']);
        $new_status = sanitize_text_field($_POST['status']);

        $allowed_statuses = array('scheduled', 'confirmed', 'in_progress', 'completed', 'cancelled', 'no_show');
        if (!in_array($new_status, $allowed_statuses)) {
            wp_die(json_encode(array('success' => false, 'message' => __('Invalid status', 'clinic-management'))));
        }

        $result = self::updateAppointment($appointment_id, array('status' => $new_status));

        if (is_wp_error($result)) {
            wp_die(json_encode(array('success' => false, 'message' => $result->get_error_message())));
        }

        wp_die(json_encode(array('success' => true, 'message' => __('Appointment status updated', 'clinic-management'))));
    }

    /**
     * AJAX: Get available slots
     */
    public function ajaxGetAvailableSlots()
    {
        check_ajax_referer('cms_ajax_nonce', 'nonce');

        if (!CMS_User_Roles::userCan('cms_manage_appointments')) {
            wp_die(json_encode(array('success' => false, 'message' => __('Permission denied', 'clinic-management'))));
        }

        $doctor_id = intval($_POST['doctor_id']);
        $date = sanitize_text_field($_POST['date']);
        $duration = intval($_POST['duration'] ?? 30);

        $slots = self::getAvailableSlots($doctor_id, $date, $duration);

        wp_die(json_encode(array('success' => true, 'data' => $slots)));
    }

    /**
     * AJAX: Delete appointment
     */
    public function ajaxDeleteAppointment()
    {
        check_ajax_referer('cms_ajax_nonce', 'nonce');

        if (!CMS_User_Roles::userCan('cms_manage_appointments')) {
            wp_die(json_encode(array('success' => false, 'message' => __('Permission denied', 'clinic-management'))));
        }

        $appointment_id = intval($_POST['appointment_id']);

        global $wpdb;
        $result = $wpdb->delete(
            $wpdb->prefix . 'cms_appointments',
            array('id' => $appointment_id),
            array('%d')
        );

        if ($result === false) {
            wp_die(json_encode(array('success' => false, 'message' => __('Failed to delete appointment', 'clinic-management'))));
        }

        wp_die(json_encode(array('success' => true, 'message' => __('Appointment deleted successfully', 'clinic-management'))));
    }

    /**
     * AJAX: Book appointment from patient portal
     */
    public function ajaxBookAppointmentPortal()
    {
        check_ajax_referer('cms_ajax_nonce', 'nonce');

        // This will be implemented when we create the patient portal
        // For now, return not implemented
        wp_die(json_encode(array('success' => false, 'message' => __('Patient portal booking not yet implemented', 'clinic-management'))));
    }

    /**
     * Get appointment statistics
     */
    public static function getAppointmentStats()
    {
        global $wpdb;

        $stats = array();
        $today = current_time('Y-m-d');
        $this_month = current_time('Y-m');

        // Today's appointments
        $stats['today'] = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}cms_appointments 
            WHERE appointment_date = %s AND status != 'cancelled'
        ", $today));

        // This month's appointments
        $stats['this_month'] = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}cms_appointments 
            WHERE appointment_date LIKE %s AND status != 'cancelled'
        ", $this_month . '%'));

        // Appointments by status
        $stats['by_status'] = $wpdb->get_results("
            SELECT status, COUNT(*) as count 
            FROM {$wpdb->prefix}cms_appointments 
            GROUP BY status
        ");

        // Appointments by doctor (this month)
        $stats['by_doctor'] = $wpdb->get_results($wpdb->prepare("
            SELECT u.display_name as doctor_name, COUNT(*) as count
            FROM {$wpdb->prefix}cms_appointments a
            LEFT JOIN {$wpdb->users} u ON a.doctor_id = u.ID
            WHERE a.appointment_date LIKE %s AND a.status != 'cancelled'
            GROUP BY a.doctor_id, u.display_name
            ORDER BY count DESC
        ", $this_month . '%'));

        return $stats;
    }
}