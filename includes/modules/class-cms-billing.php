<?php
/**
 * Billing management module
 */

if (!defined('ABSPATH')) {
    exit;
}

class CMS_Billing
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
        add_action('wp_ajax_cms_save_bill', array($this, 'ajaxSaveBill'));
        add_action('wp_ajax_cms_get_bill', array($this, 'ajaxGetBill'));
        add_action('wp_ajax_cms_get_bills', array($this, 'ajaxGetBills'));
        add_action('wp_ajax_cms_record_payment', array($this, 'ajaxRecordPayment'));
        add_action('wp_ajax_cms_generate_auto_bill', array($this, 'ajaxGenerateAutoBill'));
        add_action('wp_ajax_cms_print_bill', array($this, 'ajaxPrintBill'));
        
        // Auto-generate bills for consultations
        add_action('cms_consultation_created', array($this, 'autoGenerateBill'), 10, 2);
    }

    /**
     * Generate unique bill number
     */
    public static function generateBillNumber()
    {
        global $wpdb;
        
        $prefix = CMS_Database::getSetting('bill_number_prefix', 'BILL');
        $year = date('Y');
        
        // Get the last bill number for current year
        $last_number = $wpdb->get_var($wpdb->prepare("
            SELECT MAX(CAST(SUBSTRING(bill_number, %d) AS UNSIGNED)) 
            FROM {$wpdb->prefix}cms_billing 
            WHERE bill_number LIKE %s
        ", strlen($prefix . $year) + 1, $prefix . $year . '%'));
        
        $next_number = ($last_number ? $last_number + 1 : 1);
        
        return $prefix . $year . str_pad($next_number, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Create new bill
     */
    public static function createBill($data)
    {
        global $wpdb;

        // Validate required fields
        if (empty($data['patient_id']) || empty($data['bill_date'])) {
            return new WP_Error('missing_data', __('Patient and bill date are required.', 'clinic-management'));
        }

        // Generate bill number
        $bill_number = self::generateBillNumber();

        // Calculate total amount
        $consultation_fee = !empty($data['consultation_fee']) ? floatval($data['consultation_fee']) : 0;
        $medicine_fee = !empty($data['medicine_fee']) ? floatval($data['medicine_fee']) : 0;
        $other_charges = !empty($data['other_charges']) ? floatval($data['other_charges']) : 0;
        $discount = !empty($data['discount']) ? floatval($data['discount']) : 0;
        
        $total_amount = $consultation_fee + $medicine_fee + $other_charges - $discount;

        // Prepare data
        $insert_data = array(
            'bill_number' => $bill_number,
            'patient_id' => intval($data['patient_id']),
            'consultation_id' => !empty($data['consultation_id']) ? intval($data['consultation_id']) : null,
            'bill_date' => sanitize_text_field($data['bill_date']),
            'consultation_fee' => $consultation_fee,
            'medicine_fee' => $medicine_fee,
            'other_charges' => $other_charges,
            'discount' => $discount,
            'total_amount' => $total_amount,
            'paid_amount' => !empty($data['paid_amount']) ? floatval($data['paid_amount']) : 0,
            'payment_status' => !empty($data['payment_status']) ? sanitize_text_field($data['payment_status']) : 'unpaid',
            'payment_method' => !empty($data['payment_method']) ? sanitize_text_field($data['payment_method']) : null,
            'payment_date' => !empty($data['payment_date']) ? sanitize_text_field($data['payment_date']) : null,
            'notes' => !empty($data['notes']) ? sanitize_textarea_field($data['notes']) : null,
            'created_by' => get_current_user_id(),
        );

        // Update payment status based on paid amount
        if ($insert_data['paid_amount'] >= $total_amount) {
            $insert_data['payment_status'] = 'paid';
            if (empty($insert_data['payment_date'])) {
                $insert_data['payment_date'] = current_time('mysql');
            }
        } elseif ($insert_data['paid_amount'] > 0) {
            $insert_data['payment_status'] = 'partial';
        }

        $result = $wpdb->insert(
            $wpdb->prefix . 'cms_billing',
            $insert_data,
            array('%s', '%d', '%d', '%s', '%f', '%f', '%f', '%f', '%f', '%f', '%s', '%s', '%s', '%s', '%d')
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Failed to create bill.', 'clinic-management'));
        }

        $bill_id = $wpdb->insert_id;

        // Schedule payment reminder if unpaid
        if ($insert_data['payment_status'] === 'unpaid') {
            self::schedulePaymentReminder($bill_id);
        }

        return $bill_id;
    }

    /**
     * Update bill
     */
    public static function updateBill($bill_id, $data)
    {
        global $wpdb;

        // Validate bill exists
        $bill = self::getBill($bill_id);
        if (!$bill) {
            return new WP_Error('bill_not_found', __('Bill not found.', 'clinic-management'));
        }

        // Prepare update data
        $update_data = array();
        $update_formats = array();

        $fields = array(
            'consultation_fee' => '%f',
            'medicine_fee' => '%f',
            'other_charges' => '%f',
            'discount' => '%f',
            'paid_amount' => '%f',
            'payment_status' => '%s',
            'payment_method' => '%s',
            'payment_date' => '%s',
            'notes' => '%s',
        );

        foreach ($fields as $field => $format) {
            if (isset($data[$field])) {
                if (strpos($format, 'f') !== false) {
                    $update_data[$field] = floatval($data[$field]);
                } elseif ($field === 'notes') {
                    $update_data[$field] = sanitize_textarea_field($data[$field]);
                } else {
                    $update_data[$field] = sanitize_text_field($data[$field]);
                }
                $update_formats[] = $format;
            }
        }

        // Recalculate total if fees changed
        if (isset($data['consultation_fee']) || isset($data['medicine_fee']) || isset($data['other_charges']) || isset($data['discount'])) {
            $consultation_fee = isset($data['consultation_fee']) ? floatval($data['consultation_fee']) : $bill->consultation_fee;
            $medicine_fee = isset($data['medicine_fee']) ? floatval($data['medicine_fee']) : $bill->medicine_fee;
            $other_charges = isset($data['other_charges']) ? floatval($data['other_charges']) : $bill->other_charges;
            $discount = isset($data['discount']) ? floatval($data['discount']) : $bill->discount;
            
            $update_data['total_amount'] = $consultation_fee + $medicine_fee + $other_charges - $discount;
            $update_formats[] = '%f';
        }

        // Update payment status based on paid amount
        if (isset($data['paid_amount'])) {
            $total_amount = isset($update_data['total_amount']) ? $update_data['total_amount'] : $bill->total_amount;
            $paid_amount = floatval($data['paid_amount']);
            
            if ($paid_amount >= $total_amount) {
                $update_data['payment_status'] = 'paid';
                if (empty($update_data['payment_date'])) {
                    $update_data['payment_date'] = current_time('mysql');
                    $update_formats[] = '%s';
                }
            } elseif ($paid_amount > 0) {
                $update_data['payment_status'] = 'partial';
            } else {
                $update_data['payment_status'] = 'unpaid';
            }
        }

        if (empty($update_data)) {
            return new WP_Error('no_data', __('No data to update.', 'clinic-management'));
        }

        $result = $wpdb->update(
            $wpdb->prefix . 'cms_billing',
            $update_data,
            array('id' => $bill_id),
            $update_formats,
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Failed to update bill.', 'clinic-management'));
        }

        return true;
    }

    /**
     * Get bill by ID
     */
    public static function getBill($bill_id)
    {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare("
            SELECT b.*, p.first_name, p.last_name, p.patient_id as patient_code, p.phone,
                   c.consultation_date, c.consultation_type, c.diagnosis,
                   u.display_name as doctor_name
            FROM {$wpdb->prefix}cms_billing b
            LEFT JOIN {$wpdb->prefix}cms_patients p ON b.patient_id = p.id
            LEFT JOIN {$wpdb->prefix}cms_consultations c ON b.consultation_id = c.id
            LEFT JOIN {$wpdb->users} u ON c.doctor_id = u.ID
            WHERE b.id = %d
        ", $bill_id));
    }

    /**
     * Get bills with filters
     */
    public static function getBills($filters = array(), $limit = 20, $offset = 0)
    {
        global $wpdb;

        $where_conditions = array('1=1');
        $prepare_values = array();

        // Date range filter
        if (!empty($filters['date_from'])) {
            $where_conditions[] = "b.bill_date >= %s";
            $prepare_values[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where_conditions[] = "b.bill_date <= %s";
            $prepare_values[] = $filters['date_to'];
        }

        // Patient filter
        if (!empty($filters['patient_id'])) {
            $where_conditions[] = "b.patient_id = %d";
            $prepare_values[] = $filters['patient_id'];
        }

        // Payment status filter
        if (!empty($filters['payment_status'])) {
            $where_conditions[] = "b.payment_status = %s";
            $prepare_values[] = $filters['payment_status'];
        }

        // Search filter
        if (!empty($filters['search'])) {
            $search_term = '%' . $wpdb->esc_like($filters['search']) . '%';
            $where_conditions[] = "(b.bill_number LIKE %s OR p.first_name LIKE %s OR p.last_name LIKE %s OR p.patient_id LIKE %s)";
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
            SELECT b.*, p.first_name, p.last_name, p.patient_id as patient_code,
                   c.consultation_date, u.display_name as doctor_name
            FROM {$wpdb->prefix}cms_billing b
            LEFT JOIN {$wpdb->prefix}cms_patients p ON b.patient_id = p.id
            LEFT JOIN {$wpdb->prefix}cms_consultations c ON b.consultation_id = c.id
            LEFT JOIN {$wpdb->users} u ON c.doctor_id = u.ID
            WHERE {$where_clause}
            ORDER BY b.bill_date DESC, b.created_at DESC
            LIMIT %d OFFSET %d
        ";

        return $wpdb->get_results($wpdb->prepare($sql, $prepare_values));
    }

    /**
     * Get bills count
     */
    public static function getBillsCount($filters = array())
    {
        global $wpdb;

        $where_conditions = array('1=1');
        $prepare_values = array();

        // Apply same filters as getBills
        if (!empty($filters['date_from'])) {
            $where_conditions[] = "b.bill_date >= %s";
            $prepare_values[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where_conditions[] = "b.bill_date <= %s";
            $prepare_values[] = $filters['date_to'];
        }

        if (!empty($filters['patient_id'])) {
            $where_conditions[] = "b.patient_id = %d";
            $prepare_values[] = $filters['patient_id'];
        }

        if (!empty($filters['payment_status'])) {
            $where_conditions[] = "b.payment_status = %s";
            $prepare_values[] = $filters['payment_status'];
        }

        if (!empty($filters['search'])) {
            $search_term = '%' . $wpdb->esc_like($filters['search']) . '%';
            $where_conditions[] = "(b.bill_number LIKE %s OR p.first_name LIKE %s OR p.last_name LIKE %s OR p.patient_id LIKE %s)";
            $prepare_values[] = $search_term;
            $prepare_values[] = $search_term;
            $prepare_values[] = $search_term;
            $prepare_values[] = $search_term;
        }

        $where_clause = implode(' AND ', $where_conditions);

        $sql = "
            SELECT COUNT(*)
            FROM {$wpdb->prefix}cms_billing b
            LEFT JOIN {$wpdb->prefix}cms_patients p ON b.patient_id = p.id
            WHERE {$where_clause}
        ";

        if (empty($prepare_values)) {
            return $wpdb->get_var($sql);
        }

        return $wpdb->get_var($wpdb->prepare($sql, $prepare_values));
    }

    /**
     * Auto-generate bill for consultation
     */
    public static function autoGenerateBillForConsultation($consultation_id)
    {
        global $wpdb;

        // Get consultation details
        $consultation = $wpdb->get_row($wpdb->prepare("
            SELECT c.*, p.id as patient_id
            FROM {$wpdb->prefix}cms_consultations c
            LEFT JOIN {$wpdb->prefix}cms_patients p ON c.patient_id = p.id
            WHERE c.id = %d
        ", $consultation_id));

        if (!$consultation) {
            return false;
        }

        // Check if bill already exists
        $existing_bill = $wpdb->get_var($wpdb->prepare("
            SELECT id FROM {$wpdb->prefix}cms_billing 
            WHERE consultation_id = %d
        ", $consultation_id));

        if ($existing_bill) {
            return $existing_bill;
        }

        // Calculate medicine fee from prescription
        $medicine_fee = 0;
        $prescription = $wpdb->get_row($wpdb->prepare("
            SELECT medicines FROM {$wpdb->prefix}cms_prescriptions 
            WHERE consultation_id = %d
        ", $consultation_id));

        if ($prescription && !empty($prescription->medicines)) {
            $medicines = json_decode($prescription->medicines, true);
            foreach ($medicines as $medicine) {
                $medicine_fee += floatval($medicine['price'] ?? 0) * intval($medicine['quantity'] ?? 1);
            }
        }

        // Prepare bill data
        $bill_data = array(
            'patient_id' => $consultation->patient_id,
            'consultation_id' => $consultation_id,
            'bill_date' => $consultation->consultation_date,
            'consultation_fee' => $consultation->consultation_fee,
            'medicine_fee' => $medicine_fee,
            'other_charges' => 0,
            'discount' => 0,
            'payment_status' => 'unpaid',
        );

        return self::createBill($bill_data);
    }

    /**
     * Record payment
     */
    public static function recordPayment($bill_id, $amount, $method = '', $notes = '')
    {
        global $wpdb;

        $bill = self::getBill($bill_id);
        if (!$bill) {
            return new WP_Error('bill_not_found', __('Bill not found.', 'clinic-management'));
        }

        $new_paid_amount = $bill->paid_amount + floatval($amount);
        
        $update_data = array(
            'paid_amount' => $new_paid_amount,
            'payment_method' => sanitize_text_field($method),
            'payment_date' => current_time('mysql'),
        );

        // Update payment status
        if ($new_paid_amount >= $bill->total_amount) {
            $update_data['payment_status'] = 'paid';
        } elseif ($new_paid_amount > 0) {
            $update_data['payment_status'] = 'partial';
        }

        // Add notes if provided
        if (!empty($notes)) {
            $existing_notes = $bill->notes ? $bill->notes . "\n" : '';
            $update_data['notes'] = $existing_notes . date('Y-m-d H:i:s') . ': ' . sanitize_textarea_field($notes);
        }

        $result = $wpdb->update(
            $wpdb->prefix . 'cms_billing',
            $update_data,
            array('id' => $bill_id),
            array('%f', '%s', '%s', '%s', '%s'),
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Failed to record payment.', 'clinic-management'));
        }

        return true;
    }

    /**
     * Schedule payment reminder
     */
    public static function schedulePaymentReminder($bill_id)
    {
        global $wpdb;

        $bill = self::getBill($bill_id);
        if (!$bill) {
            return false;
        }

        // Schedule reminder for 7 days after bill date
        $reminder_date = date('Y-m-d H:i:s', strtotime($bill->bill_date . ' +7 days'));

        $wpdb->insert(
            $wpdb->prefix . 'cms_notifications',
            array(
                'patient_id' => $bill->patient_id,
                'notification_type' => 'payment_reminder',
                'recipient_phone' => $bill->phone,
                'message' => sprintf(
                    __('Payment reminder: Your bill %s of %s%s is pending. Please make payment at your earliest convenience.', 'clinic-management'),
                    $bill->bill_number,
                    CMS_Database::getSetting('currency_symbol', '$'),
                    number_format($bill->total_amount - $bill->paid_amount, 2)
                ),
                'scheduled_time' => $reminder_date,
                'created_by' => get_current_user_id(),
            ),
            array('%d', '%s', '%s', '%s', '%s', '%d')
        );

        return true;
    }

    /**
     * Generate bill HTML for printing
     */
    public static function generateBillHTML($bill_id)
    {
        $bill = self::getBill($bill_id);
        if (!$bill) {
            return false;
        }

        $clinic_name = CMS_Database::getSetting('clinic_name', 'Your Clinic Name');
        $clinic_address = CMS_Database::getSetting('clinic_address', '');
        $clinic_phone = CMS_Database::getSetting('clinic_phone', '');
        $clinic_email = CMS_Database::getSetting('clinic_email', '');
        $currency_symbol = CMS_Database::getSetting('currency_symbol', '$');

        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title><?php _e('Bill', 'clinic-management'); ?> - <?php echo esc_html($bill->bill_number); ?></title>
            <style>
                body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
                .bill-header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 15px; margin-bottom: 20px; }
                .clinic-name { font-size: 24px; font-weight: bold; margin-bottom: 5px; }
                .clinic-details { font-size: 14px; color: #666; }
                .bill-info { display: flex; justify-content: space-between; margin-bottom: 20px; }
                .patient-info, .bill-details { width: 48%; }
                .info-title { font-weight: bold; font-size: 16px; margin-bottom: 10px; border-bottom: 1px solid #ccc; padding-bottom: 5px; }
                .charges-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                .charges-table th, .charges-table td { border: 1px solid #ccc; padding: 10px; text-align: left; }
                .charges-table th { background-color: #f5f5f5; font-weight: bold; }
                .charges-table .amount { text-align: right; }
                .total-row { background-color: #f0f0f0; font-weight: bold; }
                .payment-info { margin-top: 20px; padding: 15px; background-color: #f9f9f9; border-left: 4px solid #007cba; }
                .bill-footer { margin-top: 30px; text-align: center; font-size: 12px; color: #666; }
                @media print {
                    body { margin: 0; padding: 15px; }
                    .no-print { display: none; }
                }
            </style>
        </head>
        <body>
            <div class="bill-header">
                <div class="clinic-name"><?php echo esc_html($clinic_name); ?></div>
                <div class="clinic-details">
                    <?php if ($clinic_address): ?>
                        <?php echo esc_html($clinic_address); ?><br>
                    <?php endif; ?>
                    <?php if ($clinic_phone): ?>
                        <?php _e('Phone:', 'clinic-management'); ?> <?php echo esc_html($clinic_phone); ?>
                    <?php endif; ?>
                    <?php if ($clinic_email): ?>
                        | <?php _e('Email:', 'clinic-management'); ?> <?php echo esc_html($clinic_email); ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="bill-info">
                <div class="patient-info">
                    <div class="info-title"><?php _e('Patient Information', 'clinic-management'); ?></div>
                    <strong><?php _e('Name:', 'clinic-management'); ?></strong> <?php echo esc_html($bill->first_name . ' ' . $bill->last_name); ?><br>
                    <strong><?php _e('Patient ID:', 'clinic-management'); ?></strong> <?php echo esc_html($bill->patient_code); ?><br>
                    <?php if ($bill->phone): ?>
                        <strong><?php _e('Phone:', 'clinic-management'); ?></strong> <?php echo esc_html($bill->phone); ?><br>
                    <?php endif; ?>
                </div>
                
                <div class="bill-details">
                    <div class="info-title"><?php _e('Bill Details', 'clinic-management'); ?></div>
                    <strong><?php _e('Bill Number:', 'clinic-management'); ?></strong> <?php echo esc_html($bill->bill_number); ?><br>
                    <strong><?php _e('Bill Date:', 'clinic-management'); ?></strong> <?php echo esc_html(date('F j, Y', strtotime($bill->bill_date))); ?><br>
                    <?php if ($bill->consultation_date): ?>
                        <strong><?php _e('Consultation Date:', 'clinic-management'); ?></strong> <?php echo esc_html(date('F j, Y', strtotime($bill->consultation_date))); ?><br>
                    <?php endif; ?>
                    <?php if ($bill->doctor_name): ?>
                        <strong><?php _e('Doctor:', 'clinic-management'); ?></strong> <?php echo esc_html($bill->doctor_name); ?><br>
                    <?php endif; ?>
                </div>
            </div>

            <table class="charges-table">
                <thead>
                    <tr>
                        <th><?php _e('Description', 'clinic-management'); ?></th>
                        <th class="amount"><?php _e('Amount', 'clinic-management'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($bill->consultation_fee > 0): ?>
                        <tr>
                            <td><?php _e('Consultation Fee', 'clinic-management'); ?></td>
                            <td class="amount"><?php echo esc_html($currency_symbol . number_format($bill->consultation_fee, 2)); ?></td>
                        </tr>
                    <?php endif; ?>
                    
                    <?php if ($bill->medicine_fee > 0): ?>
                        <tr>
                            <td><?php _e('Medicine Charges', 'clinic-management'); ?></td>
                            <td class="amount"><?php echo esc_html($currency_symbol . number_format($bill->medicine_fee, 2)); ?></td>
                        </tr>
                    <?php endif; ?>
                    
                    <?php if ($bill->other_charges > 0): ?>
                        <tr>
                            <td><?php _e('Other Charges', 'clinic-management'); ?></td>
                            <td class="amount"><?php echo esc_html($currency_symbol . number_format($bill->other_charges, 2)); ?></td>
                        </tr>
                    <?php endif; ?>
                    
                    <?php if ($bill->discount > 0): ?>
                        <tr>
                            <td><?php _e('Discount', 'clinic-management'); ?></td>
                            <td class="amount">-<?php echo esc_html($currency_symbol . number_format($bill->discount, 2)); ?></td>
                        </tr>
                    <?php endif; ?>
                    
                    <tr class="total-row">
                        <td><?php _e('Total Amount', 'clinic-management'); ?></td>
                        <td class="amount"><?php echo esc_html($currency_symbol . number_format($bill->total_amount, 2)); ?></td>
                    </tr>
                    
                    <?php if ($bill->paid_amount > 0): ?>
                        <tr>
                            <td><?php _e('Paid Amount', 'clinic-management'); ?></td>
                            <td class="amount"><?php echo esc_html($currency_symbol . number_format($bill->paid_amount, 2)); ?></td>
                        </tr>
                        
                        <tr class="total-row">
                            <td><?php _e('Balance Due', 'clinic-management'); ?></td>
                            <td class="amount"><?php echo esc_html($currency_symbol . number_format($bill->total_amount - $bill->paid_amount, 2)); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="payment-info">
                <strong><?php _e('Payment Status:', 'clinic-management'); ?></strong> <?php echo esc_html(ucfirst($bill->payment_status)); ?><br>
                <?php if ($bill->payment_method): ?>
                    <strong><?php _e('Payment Method:', 'clinic-management'); ?></strong> <?php echo esc_html($bill->payment_method); ?><br>
                <?php endif; ?>
                <?php if ($bill->payment_date): ?>
                    <strong><?php _e('Payment Date:', 'clinic-management'); ?></strong> <?php echo esc_html(date('F j, Y', strtotime($bill->payment_date))); ?><br>
                <?php endif; ?>
            </div>

            <?php if ($bill->notes): ?>
                <div class="payment-info">
                    <strong><?php _e('Notes:', 'clinic-management'); ?></strong><br>
                    <?php echo nl2br(esc_html($bill->notes)); ?>
                </div>
            <?php endif; ?>

            <div class="bill-footer">
                <?php _e('Thank you for choosing our clinic. For any queries, please contact us.', 'clinic-management'); ?>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * AJAX: Save bill
     */
    public function ajaxSaveBill()
    {
        check_ajax_referer('cms_ajax_nonce', 'nonce');

        if (!CMS_User_Roles::userCan('cms_manage_billing')) {
            wp_die(json_encode(array('success' => false, 'message' => __('Permission denied', 'clinic-management'))));
        }

        $bill_id = !empty($_POST['bill_id']) ? intval($_POST['bill_id']) : 0;
        $bill_data = $_POST['bill_data'];

        if ($bill_id > 0) {
            // Update existing bill
            $result = self::updateBill($bill_id, $bill_data);
        } else {
            // Create new bill
            $result = self::createBill($bill_data);
        }

        if (is_wp_error($result)) {
            wp_die(json_encode(array('success' => false, 'message' => $result->get_error_message())));
        }

        $response_data = array('success' => true, 'message' => __('Bill saved successfully', 'clinic-management'));
        if ($bill_id === 0) {
            $response_data['bill_id'] = $result;
        }

        wp_die(json_encode($response_data));
    }

    /**
     * AJAX: Get bill
     */
    public function ajaxGetBill()
    {
        check_ajax_referer('cms_ajax_nonce', 'nonce');

        if (!CMS_User_Roles::userCan('cms_view_billing')) {
            wp_die(json_encode(array('success' => false, 'message' => __('Permission denied', 'clinic-management'))));
        }

        $bill_id = intval($_POST['bill_id']);
        $bill = self::getBill($bill_id);

        if (!$bill) {
            wp_die(json_encode(array('success' => false, 'message' => __('Bill not found', 'clinic-management'))));
        }

        wp_die(json_encode(array('success' => true, 'data' => $bill)));
    }

    /**
     * AJAX: Get bills
     */
    public function ajaxGetBills()
    {
        check_ajax_referer('cms_ajax_nonce', 'nonce');

        if (!CMS_User_Roles::userCan('cms_view_billing')) {
            wp_die(json_encode(array('success' => false, 'message' => __('Permission denied', 'clinic-management'))));
        }

        $filters = $_POST['filters'] ?? array();
        $page = intval($_POST['page'] ?? 1);
        $per_page = 20;
        $offset = ($page - 1) * $per_page;

        $bills = self::getBills($filters, $per_page, $offset);
        $total = self::getBillsCount($filters);

        $response_data = array(
            'success' => true,
            'data' => $bills,
            'total' => $total,
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($total / $per_page)
        );

        wp_die(json_encode($response_data));
    }

    /**
     * AJAX: Record payment
     */
    public function ajaxRecordPayment()
    {
        check_ajax_referer('cms_ajax_nonce', 'nonce');

        if (!CMS_User_Roles::userCan('cms_manage_billing')) {
            wp_die(json_encode(array('success' => false, 'message' => __('Permission denied', 'clinic-management'))));
        }

        $bill_id = intval($_POST['bill_id']);
        $amount = floatval($_POST['amount']);
        $method = sanitize_text_field($_POST['method'] ?? '');
        $notes = sanitize_textarea_field($_POST['notes'] ?? '');

        $result = self::recordPayment($bill_id, $amount, $method, $notes);

        if (is_wp_error($result)) {
            wp_die(json_encode(array('success' => false, 'message' => $result->get_error_message())));
        }

        wp_die(json_encode(array('success' => true, 'message' => __('Payment recorded successfully', 'clinic-management'))));
    }

    /**
     * AJAX: Generate auto bill
     */
    public function ajaxGenerateAutoBill()
    {
        check_ajax_referer('cms_ajax_nonce', 'nonce');

        if (!CMS_User_Roles::userCan('cms_manage_billing')) {
            wp_die(json_encode(array('success' => false, 'message' => __('Permission denied', 'clinic-management'))));
        }

        $consultation_id = intval($_POST['consultation_id']);
        $result = self::autoGenerateBillForConsultation($consultation_id);

        if (is_wp_error($result)) {
            wp_die(json_encode(array('success' => false, 'message' => $result->get_error_message())));
        }

        wp_die(json_encode(array('success' => true, 'message' => __('Bill generated successfully', 'clinic-management'), 'bill_id' => $result)));
    }

    /**
     * AJAX: Print bill
     */
    public function ajaxPrintBill()
    {
        check_ajax_referer('cms_ajax_nonce', 'nonce');

        if (!CMS_User_Roles::userCan('cms_view_billing')) {
            wp_die(__('Permission denied', 'clinic-management'));
        }

        $bill_id = intval($_POST['bill_id']);
        $html = self::generateBillHTML($bill_id);

        if (!$html) {
            wp_die(__('Bill not found', 'clinic-management'));
        }

        echo $html;
        wp_die();
    }

    /**
     * Auto-generate bill for consultation (hook)
     */
    public function autoGenerateBill($consultation_id, $consultation_data)
    {
        self::autoGenerateBillForConsultation($consultation_id);
    }

    /**
     * Get billing statistics
     */
    public static function getBillingStats()
    {
        global $wpdb;

        $stats = array();
        $today = current_time('Y-m-d');
        $this_month = current_time('Y-m');
        $currency_symbol = CMS_Database::getSetting('currency_symbol', '$');

        // Today's revenue
        $stats['today_revenue'] = $wpdb->get_var($wpdb->prepare("
            SELECT SUM(paid_amount) FROM {$wpdb->prefix}cms_billing 
            WHERE bill_date = %s
        ", $today));

        // This month's revenue
        $stats['monthly_revenue'] = $wpdb->get_var($wpdb->prepare("
            SELECT SUM(paid_amount) FROM {$wpdb->prefix}cms_billing 
            WHERE bill_date LIKE %s
        ", $this_month . '%'));

        // Pending amount
        $stats['pending_amount'] = $wpdb->get_var("
            SELECT SUM(total_amount - paid_amount) FROM {$wpdb->prefix}cms_billing 
            WHERE payment_status IN ('unpaid', 'partial')
        ");

        // Bills by status
        $stats['by_status'] = $wpdb->get_results("
            SELECT payment_status, COUNT(*) as count, SUM(total_amount) as total_amount, SUM(paid_amount) as paid_amount
            FROM {$wpdb->prefix}cms_billing 
            GROUP BY payment_status
        ");

        // Monthly revenue trend (last 6 months)
        $stats['monthly_trend'] = $wpdb->get_results("
            SELECT DATE_FORMAT(bill_date, '%Y-%m') as month, 
                   SUM(paid_amount) as revenue,
                   COUNT(*) as bills_count
            FROM {$wpdb->prefix}cms_billing 
            WHERE bill_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(bill_date, '%Y-%m')
            ORDER BY month ASC
        ");

        return $stats;
    }
}