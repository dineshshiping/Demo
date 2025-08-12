<?php
/**
 * Billing management class
 */
class CMS_Billing {
    
    private $db;
    
    public function __construct() {
        $this->db = new CMS_Database();
    }
    
    /**
     * Add a new billing record
     */
    public function add_billing($data) {
        // Generate invoice number
        $data['invoice_number'] = $this->generate_invoice_number();
        
        // Calculate total amount
        $total = floatval($data['consultation_fee']) + floatval($data['medicine_cost']) + floatval($data['other_charges']);
        
        // Sanitize data
        $sanitized_data = array(
            'patient_id' => sanitize_text_field($data['patient_id']),
            'appointment_id' => !empty($data['appointment_id']) ? intval($data['appointment_id']) : null,
            'invoice_number' => sanitize_text_field($data['invoice_number']),
            'consultation_fee' => floatval($data['consultation_fee']),
            'medicine_cost' => floatval($data['medicine_cost']),
            'other_charges' => floatval($data['other_charges']),
            'total_amount' => $total,
            'paid_amount' => floatval($data['paid_amount']),
            'payment_status' => $total <= floatval($data['paid_amount']) ? 'paid' : 'unpaid',
            'payment_method' => sanitize_text_field($data['payment_method']),
            'payment_date' => !empty($data['payment_date']) ? sanitize_text_field($data['payment_date']) : null,
            'due_date' => !empty($data['due_date']) ? sanitize_text_field($data['due_date']) : null,
            'notes' => sanitize_textarea_field($data['notes'])
        );
        
        $result = $this->db->insert('billing', $sanitized_data);
        
        if ($result) {
            // Generate invoice PDF
            $this->generate_invoice_pdf($result, $sanitized_data);
            
            // Send invoice email if patient has email
            $this->send_invoice_email($data['patient_id'], $sanitized_data);
            
            return $result;
        }
        
        return false;
    }
    
    /**
     * Update billing record
     */
    public function update_billing($billing_id, $data) {
        $sanitized_data = array();
        
        if (isset($data['consultation_fee'])) $sanitized_data['consultation_fee'] = floatval($data['consultation_fee']);
        if (isset($data['medicine_cost'])) $sanitized_data['medicine_cost'] = floatval($data['medicine_cost']);
        if (isset($data['other_charges'])) $sanitized_data['other_charges'] = floatval($data['other_charges']);
        if (isset($data['paid_amount'])) $sanitized_data['paid_amount'] = floatval($data['paid_amount']);
        if (isset($data['payment_status'])) $sanitized_data['payment_status'] = sanitize_text_field($data['payment_status']);
        if (isset($data['payment_method'])) $sanitized_data['payment_method'] = sanitize_text_field($data['payment_method']);
        if (isset($data['payment_date'])) $sanitized_data['payment_date'] = sanitize_text_field($data['payment_date']);
        if (isset($data['due_date'])) $sanitized_data['due_date'] = sanitize_text_field($data['due_date']);
        if (isset($data['notes'])) $sanitized_data['notes'] = sanitize_textarea_field($data['notes']);
        
        // Recalculate total and payment status
        if (isset($sanitized_data['consultation_fee']) || isset($sanitized_data['medicine_cost']) || isset($sanitized_data['other_charges'])) {
            $current = $this->get_billing($billing_id);
            if ($current) {
                $consultation_fee = isset($sanitized_data['consultation_fee']) ? $sanitized_data['consultation_fee'] : $current->consultation_fee;
                $medicine_cost = isset($sanitized_data['medicine_cost']) ? $sanitized_data['medicine_cost'] : $current->medicine_cost;
                $other_charges = isset($sanitized_data['other_charges']) ? $sanitized_data['other_charges'] : $current->other_charges;
                
                $sanitized_data['total_amount'] = $consultation_fee + $medicine_cost + $other_charges;
            }
        }
        
        if (isset($sanitized_data['paid_amount']) && isset($sanitized_data['total_amount'])) {
            $sanitized_data['payment_status'] = $sanitized_data['paid_amount'] >= $sanitized_data['total_amount'] ? 'paid' : 'unpaid';
        }
        
        $sanitized_data['updated_at'] = current_time('mysql');
        
        return $this->db->update('billing', $sanitized_data, array('id' => $billing_id));
    }
    
    /**
     * Delete billing record
     */
    public function delete_billing($billing_id) {
        return $this->db->delete('billing', array('id' => $billing_id));
    }
    
    /**
     * Get billing by ID
     */
    public function get_billing($billing_id) {
        $query = "SELECT b.*, p.first_name, p.last_name, p.patient_id as patient_code,
                         p.email, p.phone
                  FROM {$this->db->get_table_name('billing')} b
                  JOIN {$this->db->get_table_name('patients')} p ON b.patient_id = p.patient_id
                  WHERE b.id = %d";
        return $this->db->get_row($query, array($billing_id));
    }
    
    /**
     * Get billing records for a patient
     */
    public function get_patient_billing($patient_id) {
        $query = "SELECT b.* FROM {$this->db->get_table_name('billing')} b
                  WHERE b.patient_id = %s ORDER BY b.created_at DESC";
        
        return $this->db->get_results($query, array($patient_id));
    }
    
    /**
     * Get all billing records with pagination
     */
    public function get_billing_records($page = 1, $per_page = 20, $status = '', $search = '') {
        $offset = ($page - 1) * $per_page;
        
        $where_clause = "WHERE 1=1";
        $args = array();
        
        if (!empty($status)) {
            $where_clause .= " AND b.payment_status = %s";
            $args[] = $status;
        }
        
        if (!empty($search)) {
            $where_clause .= " AND (p.first_name LIKE %s OR p.last_name LIKE %s OR p.patient_id LIKE %s OR b.invoice_number LIKE %s)";
            $search_term = '%' . $search . '%';
            $args[] = $search_term;
            $args[] = $search_term;
            $args[] = $search_term;
            $args[] = $search_term;
        }
        
        $query = "SELECT b.*, p.first_name, p.last_name, p.patient_id as patient_code
                  FROM {$this->db->get_table_name('billing')} b
                  JOIN {$this->db->get_table_name('patients')} p ON b.patient_id = p.patient_id
                  $where_clause ORDER BY b.created_at DESC LIMIT %d OFFSET %d";
        
        $args[] = $per_page;
        $args[] = $offset;
        
        return $this->db->get_results($query, $args);
    }
    
    /**
     * Get billing count
     */
    public function get_billing_count($status = '', $search = '') {
        $where_clause = "WHERE 1=1";
        $args = array();
        
        if (!empty($status)) {
            $where_clause .= " AND b.payment_status = %s";
            $args[] = $status;
        }
        
        if (!empty($search)) {
            $where_clause .= " AND (p.first_name LIKE %s OR p.last_name LIKE %s OR p.patient_id LIKE %s OR b.invoice_number LIKE %s)";
            $search_term = '%' . $search . '%';
            $args[] = $search_term;
            $args[] = $search_term;
            $args[] = $search_term;
            $args[] = $search_term;
        }
        
        $query = "SELECT COUNT(*) FROM {$this->db->get_table_name('billing')} b
                  JOIN {$this->db->get_table_name('patients')} p ON b.patient_id = p.patient_id
                  $where_clause";
        
        return $this->db->get_var($query, $args);
    }
    
    /**
     * Record payment
     */
    public function record_payment($billing_id, $amount, $method = 'cash') {
        $billing = $this->get_billing($billing_id);
        if (!$billing) {
            return false;
        }
        
        $new_paid_amount = $billing->paid_amount + floatval($amount);
        $payment_status = $new_paid_amount >= $billing->total_amount ? 'paid' : 'partial';
        
        $data = array(
            'paid_amount' => $new_paid_amount,
            'payment_status' => $payment_status,
            'payment_method' => $method,
            'payment_date' => current_time('mysql')
        );
        
        $result = $this->update_billing($billing_id, $data);
        
        if ($result && $payment_status === 'paid') {
            // Send payment confirmation
            $this->send_payment_confirmation($billing_id);
        }
        
        return $result;
    }
    
    /**
     * Generate invoice number
     */
    private function generate_invoice_number() {
        $prefix = 'INV';
        $year = date('Y');
        $month = date('m');
        
        // Get count of invoices for this month
        $query = "SELECT COUNT(*) FROM {$this->db->get_table_name('billing')} 
                  WHERE YEAR(created_at) = %d AND MONTH(created_at) = %d";
        $count = $this->db->get_var($query, array($year, $month));
        
        $count++;
        return $prefix . $year . $month . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * Generate invoice PDF
     */
    private function generate_invoice_pdf($billing_id, $data) {
        $billing = $this->get_billing($billing_id);
        if (!$billing) {
            return false;
        }
        
        $html = $this->generate_invoice_html($billing);
        
        // Save HTML to file
        $upload_dir = wp_upload_dir();
        $invoice_dir = $upload_dir['basedir'] . '/clinic-files/' . $data['patient_id'] . '/invoices/';
        
        if (!file_exists($invoice_dir)) {
            wp_mkdir_p($invoice_dir);
        }
        
        $filename = 'invoice_' . $data['invoice_number'] . '.html';
        $filepath = $invoice_dir . $filename;
        
        if (file_put_contents($filepath, $html)) {
            // Save file record to database
            $file_url = $upload_dir['baseurl'] . '/clinic-files/' . $data['patient_id'] . '/invoices/' . $filename;
            
            $this->db->insert('patient_files', array(
                'patient_id' => $data['patient_id'],
                'file_name' => $filename,
                'file_url' => $file_url,
                'file_type' => 'invoice'
            ));
            
            return $file_url;
        }
        
        return false;
    }
    
    /**
     * Generate invoice HTML
     */
    private function generate_invoice_html($billing) {
        $clinic_name = get_option('cms_clinic_name', 'Your Clinic Name');
        $clinic_address = get_option('cms_clinic_address', 'Your Clinic Address');
        $clinic_phone = get_option('cms_clinic_phone', 'Your Clinic Phone');
        $clinic_email = get_option('cms_clinic_email', 'your@clinic.com');
        
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Invoice - ' . $billing->invoice_number . '</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 30px; }
        .invoice-info { display: flex; justify-content: space-between; margin-bottom: 30px; }
        .patient-info { margin-bottom: 30px; }
        .invoice-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .invoice-table th, .invoice-table td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        .invoice-table th { background-color: #f2f2f2; }
        .total-row { font-weight: bold; background-color: #f9f9f9; }
        .footer { margin-top: 50px; text-align: center; }
        @media print { body { margin: 0; } .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="header">
        <h1>' . $clinic_name . '</h1>
        <p>' . $clinic_address . '</p>
        <p>Phone: ' . $clinic_phone . ' | Email: ' . $clinic_email . '</p>
    </div>
    
    <div class="invoice-info">
        <div>
            <h3>Invoice To:</h3>
            <p>' . $billing->first_name . ' ' . $billing->last_name . '</p>
            <p>Patient ID: ' . $billing->patient_code . '</p>
            <p>Email: ' . $billing->email . '</p>
            <p>Phone: ' . $billing->phone . '</p>
        </div>
        <div>
            <h3>Invoice Details:</h3>
            <p><strong>Invoice #:</strong> ' . $billing->invoice_number . '</p>
            <p><strong>Date:</strong> ' . date('F j, Y', strtotime($billing->created_at)) . '</p>
            <p><strong>Due Date:</strong> ' . (!empty($billing->due_date) ? date('F j, Y', strtotime($billing->due_date)) : 'N/A') . '</p>
        </div>
    </div>
    
    <table class="invoice-table">
        <thead>
            <tr>
                <th>Description</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Consultation Fee</td>
                <td>$' . number_format($billing->consultation_fee, 2) . '</td>
            </tr>';
        
        if ($billing->medicine_cost > 0) {
            $html .= '<tr>
                <td>Medicine Cost</td>
                <td>$' . number_format($billing->medicine_cost, 2) . '</td>
            </tr>';
        }
        
        if ($billing->other_charges > 0) {
            $html .= '<tr>
                <td>Other Charges</td>
                <td>$' . number_format($billing->other_charges, 2) . '</td>
            </tr>';
        }
        
        $html .= '<tr class="total-row">
                <td><strong>Total Amount</strong></td>
                <td><strong>$' . number_format($billing->total_amount, 2) . '</strong></td>
            </tr>
            <tr>
                <td><strong>Paid Amount</strong></td>
                <td>$' . number_format($billing->paid_amount, 2) . '</td>
            </tr>
            <tr class="total-row">
                <td><strong>Balance Due</strong></td>
                <td><strong>$' . number_format($billing->total_amount - $billing->paid_amount, 2) . '</strong></td>
            </tr>
        </tbody>
    </table>
    
    <div class="footer">
        <p><strong>Payment Status:</strong> ' . ucfirst($billing->payment_status) . '</p>';
        
        if (!empty($billing->notes)) {
            $html .= '<p><strong>Notes:</strong> ' . nl2br(esc_html($billing->notes)) . '</p>';
        }
        
        $html .= '<p class="no-print">This invoice was generated electronically on ' . date('F j, Y \a\t g:i A') . '</p>
    </div>
</body>
</html>';
        
        return $html;
    }
    
    /**
     * Send invoice email
     */
    private function send_invoice_email($patient_id, $billing_data) {
        $patient = $this->db->get_row(
            "SELECT * FROM {$this->db->get_table_name('patients')} WHERE patient_id = %s",
            array($patient_id)
        );
        
        if (!$patient || !$patient->email) {
            return;
        }
        
        $subject = 'Invoice #' . $billing_data['invoice_number'] . ' - ' . get_option('cms_clinic_name', 'Our Clinic');
        $message = "Dear {$patient->first_name} {$patient->last_name},\n\n";
        $message .= "Please find attached your invoice for the recent consultation.\n\n";
        $message .= "Invoice Details:\n";
        $message .= "Invoice #: {$billing_data['invoice_number']}\n";
        $message .= "Total Amount: $" . number_format($billing_data['total_amount'], 2) . "\n";
        $message .= "Due Date: " . (!empty($billing_data['due_date']) ? date('F j, Y', strtotime($billing_data['due_date'])) : 'N/A' . "\n\n";
        $message .= "Please contact us if you have any questions about this invoice.\n\n";
        $message .= "Best regards,\n" . get_option('cms_clinic_name', 'Clinic Team');
        
        wp_mail($patient->email, $subject, $message);
    }
    
    /**
     * Send payment confirmation
     */
    private function send_payment_confirmation($billing_id) {
        $billing = $this->get_billing($billing_id);
        if (!$billing || !$billing->email) {
            return;
        }
        
        $subject = 'Payment Confirmation - Invoice #' . $billing->invoice_number;
        $message = "Dear {$billing->first_name} {$billing->last_name},\n\n";
        $message .= "Thank you for your payment!\n\n";
        $message .= "Payment Details:\n";
        $message .= "Invoice #: {$billing->invoice_number}\n";
        $message .= "Amount Paid: $" . number_format($billing->paid_amount, 2) . "\n";
        $message .= "Payment Date: " . date('F j, Y', strtotime($billing->payment_date)) . "\n";
        $message .= "Payment Method: {$billing->payment_method}\n\n";
        $message .= "Your invoice has been marked as paid. Thank you for choosing our clinic!\n\n";
        $message .= "Best regards,\n" . get_option('cms_clinic_name', 'Clinic Team');
        
        wp_mail($billing->email, $subject, $message);
    }
    
    /**
     * Get billing statistics
     */
    public function get_billing_stats() {
        $this_month = date('Y-m');
        
        $month_revenue = $this->db->get_var(
            "SELECT SUM(total_amount) FROM {$this->db->get_table_name('billing')} 
             WHERE DATE_FORMAT(created_at, '%%Y-%%m') = %s",
            array($this_month)
        );
        
        $month_paid = $this->db->get_var(
            "SELECT SUM(paid_amount) FROM {$this->db->get_table_name('billing')} 
             WHERE DATE_FORMAT(created_at, '%%Y-%%m') = %s",
            array($this_month)
        );
        
        $pending_amount = $this->db->get_var(
            "SELECT SUM(total_amount - paid_amount) FROM {$this->db->get_table_name('billing')} 
             WHERE payment_status != 'paid'"
        );
        
        $total_invoices = $this->db->get_var(
            "SELECT COUNT(*) FROM {$this->db->get_table_name('billing')}"
        );
        
        return array(
            'month_revenue' => $month_revenue ?: 0,
            'month_paid' => $month_paid ?: 0,
            'pending_amount' => $pending_amount ?: 0,
            'total_invoices' => $total_invoices ?: 0
        );
    }
}