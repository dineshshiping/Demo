<?php
/**
 * Prescriptions management module
 */

if (!defined('ABSPATH')) {
    exit;
}

class CMS_Prescriptions
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
        add_action('wp_ajax_cms_save_prescription', array($this, 'ajaxSavePrescription'));
        add_action('wp_ajax_cms_get_prescription', array($this, 'ajaxGetPrescription'));
        add_action('wp_ajax_cms_get_prescriptions', array($this, 'ajaxGetPrescriptions'));
        add_action('wp_ajax_cms_print_prescription', array($this, 'ajaxPrintPrescription'));
        add_action('wp_ajax_cms_search_medicines', array($this, 'ajaxSearchMedicines'));
    }

    /**
     * Create new prescription
     */
    public static function createPrescription($data)
    {
        global $wpdb;

        // Validate required fields
        if (empty($data['consultation_id']) || empty($data['patient_id']) || empty($data['doctor_id']) || empty($data['medicines'])) {
            return new WP_Error('missing_data', __('Consultation, patient, doctor, and medicines are required.', 'clinic-management'));
        }

        // Validate medicines data
        if (!is_array($data['medicines']) || empty($data['medicines'])) {
            return new WP_Error('invalid_medicines', __('At least one medicine must be prescribed.', 'clinic-management'));
        }

        // Prepare medicines data
        $medicines = array();
        foreach ($data['medicines'] as $medicine) {
            if (empty($medicine['medicine_name']) || empty($medicine['dosage'])) {
                continue;
            }

            $medicines[] = array(
                'medicine_id' => !empty($medicine['medicine_id']) ? intval($medicine['medicine_id']) : null,
                'medicine_name' => sanitize_text_field($medicine['medicine_name']),
                'dosage' => sanitize_text_field($medicine['dosage']),
                'frequency' => sanitize_text_field($medicine['frequency'] ?? ''),
                'duration' => sanitize_text_field($medicine['duration'] ?? ''),
                'quantity' => !empty($medicine['quantity']) ? intval($medicine['quantity']) : 1,
                'instructions' => !empty($medicine['instructions']) ? sanitize_textarea_field($medicine['instructions']) : '',
                'price' => !empty($medicine['price']) ? floatval($medicine['price']) : 0,
            );
        }

        if (empty($medicines)) {
            return new WP_Error('no_valid_medicines', __('No valid medicines provided.', 'clinic-management'));
        }

        // Prepare data
        $insert_data = array(
            'consultation_id' => intval($data['consultation_id']),
            'patient_id' => intval($data['patient_id']),
            'doctor_id' => intval($data['doctor_id']),
            'prescription_date' => !empty($data['prescription_date']) ? sanitize_text_field($data['prescription_date']) : current_time('Y-m-d'),
            'medicines' => json_encode($medicines),
            'instructions' => !empty($data['instructions']) ? sanitize_textarea_field($data['instructions']) : null,
            'status' => !empty($data['status']) ? sanitize_text_field($data['status']) : 'active',
            'created_by' => get_current_user_id(),
        );

        $result = $wpdb->insert(
            $wpdb->prefix . 'cms_prescriptions',
            $insert_data,
            array('%d', '%d', '%d', '%s', '%s', '%s', '%s', '%d')
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Failed to create prescription.', 'clinic-management'));
        }

        $prescription_id = $wpdb->insert_id;

        // Update inventory if medicines have IDs
        self::updateInventoryFromPrescription($medicines);

        return $prescription_id;
    }

    /**
     * Update prescription
     */
    public static function updatePrescription($prescription_id, $data)
    {
        global $wpdb;

        // Validate prescription exists
        $prescription = self::getPrescription($prescription_id);
        if (!$prescription) {
            return new WP_Error('prescription_not_found', __('Prescription not found.', 'clinic-management'));
        }

        // Prepare update data
        $update_data = array();
        $update_formats = array();

        // Prepare medicines if provided
        if (!empty($data['medicines']) && is_array($data['medicines'])) {
            $medicines = array();
            foreach ($data['medicines'] as $medicine) {
                if (empty($medicine['medicine_name']) || empty($medicine['dosage'])) {
                    continue;
                }

                $medicines[] = array(
                    'medicine_id' => !empty($medicine['medicine_id']) ? intval($medicine['medicine_id']) : null,
                    'medicine_name' => sanitize_text_field($medicine['medicine_name']),
                    'dosage' => sanitize_text_field($medicine['dosage']),
                    'frequency' => sanitize_text_field($medicine['frequency'] ?? ''),
                    'duration' => sanitize_text_field($medicine['duration'] ?? ''),
                    'quantity' => !empty($medicine['quantity']) ? intval($medicine['quantity']) : 1,
                    'instructions' => !empty($medicine['instructions']) ? sanitize_textarea_field($medicine['instructions']) : '',
                    'price' => !empty($medicine['price']) ? floatval($medicine['price']) : 0,
                );
            }
            $data['medicines'] = json_encode($medicines);
        }

        $fields = array(
            'prescription_date' => '%s',
            'medicines' => '%s',
            'instructions' => '%s',
            'status' => '%s',
        );

        foreach ($fields as $field => $format) {
            if (isset($data[$field])) {
                if ($field === 'instructions') {
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
            $wpdb->prefix . 'cms_prescriptions',
            $update_data,
            array('id' => $prescription_id),
            $update_formats,
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Failed to update prescription.', 'clinic-management'));
        }

        return true;
    }

    /**
     * Get prescription by ID
     */
    public static function getPrescription($prescription_id)
    {
        global $wpdb;

        $prescription = $wpdb->get_row($wpdb->prepare("
            SELECT pr.*, p.first_name, p.last_name, p.patient_id as patient_code, p.date_of_birth, p.gender,
                   p.phone, p.address,
                   u.display_name as doctor_name,
                   c.consultation_date, c.diagnosis, c.consultation_type
            FROM {$wpdb->prefix}cms_prescriptions pr
            LEFT JOIN {$wpdb->prefix}cms_patients p ON pr.patient_id = p.id
            LEFT JOIN {$wpdb->users} u ON pr.doctor_id = u.ID
            LEFT JOIN {$wpdb->prefix}cms_consultations c ON pr.consultation_id = c.id
            WHERE pr.id = %d
        ", $prescription_id));

        if ($prescription && !empty($prescription->medicines)) {
            $prescription->medicines = json_decode($prescription->medicines, true);
        }

        return $prescription;
    }

    /**
     * Get prescriptions with filters
     */
    public static function getPrescriptions($filters = array(), $limit = 20, $offset = 0)
    {
        global $wpdb;

        $where_conditions = array('1=1');
        $prepare_values = array();

        // Date range filter
        if (!empty($filters['date_from'])) {
            $where_conditions[] = "pr.prescription_date >= %s";
            $prepare_values[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where_conditions[] = "pr.prescription_date <= %s";
            $prepare_values[] = $filters['date_to'];
        }

        // Doctor filter
        if (!empty($filters['doctor_id'])) {
            $where_conditions[] = "pr.doctor_id = %d";
            $prepare_values[] = $filters['doctor_id'];
        }

        // Patient filter
        if (!empty($filters['patient_id'])) {
            $where_conditions[] = "pr.patient_id = %d";
            $prepare_values[] = $filters['patient_id'];
        }

        // Status filter
        if (!empty($filters['status'])) {
            $where_conditions[] = "pr.status = %s";
            $prepare_values[] = $filters['status'];
        }

        // Search filter
        if (!empty($filters['search'])) {
            $search_term = '%' . $wpdb->esc_like($filters['search']) . '%';
            $where_conditions[] = "(p.first_name LIKE %s OR p.last_name LIKE %s OR p.patient_id LIKE %s)";
            $prepare_values[] = $search_term;
            $prepare_values[] = $search_term;
            $prepare_values[] = $search_term;
        }

        $where_clause = implode(' AND ', $where_conditions);

        // Add limit and offset
        $prepare_values[] = $limit;
        $prepare_values[] = $offset;

        $sql = "
            SELECT pr.*, p.first_name, p.last_name, p.patient_id as patient_code,
                   u.display_name as doctor_name, c.diagnosis
            FROM {$wpdb->prefix}cms_prescriptions pr
            LEFT JOIN {$wpdb->prefix}cms_patients p ON pr.patient_id = p.id
            LEFT JOIN {$wpdb->users} u ON pr.doctor_id = u.ID
            LEFT JOIN {$wpdb->prefix}cms_consultations c ON pr.consultation_id = c.id
            WHERE {$where_clause}
            ORDER BY pr.prescription_date DESC, pr.created_at DESC
            LIMIT %d OFFSET %d
        ";

        return $wpdb->get_results($wpdb->prepare($sql, $prepare_values));
    }

    /**
     * Get prescriptions count
     */
    public static function getPrescriptionsCount($filters = array())
    {
        global $wpdb;

        $where_conditions = array('1=1');
        $prepare_values = array();

        // Apply same filters as getPrescriptions
        if (!empty($filters['date_from'])) {
            $where_conditions[] = "pr.prescription_date >= %s";
            $prepare_values[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where_conditions[] = "pr.prescription_date <= %s";
            $prepare_values[] = $filters['date_to'];
        }

        if (!empty($filters['doctor_id'])) {
            $where_conditions[] = "pr.doctor_id = %d";
            $prepare_values[] = $filters['doctor_id'];
        }

        if (!empty($filters['patient_id'])) {
            $where_conditions[] = "pr.patient_id = %d";
            $prepare_values[] = $filters['patient_id'];
        }

        if (!empty($filters['status'])) {
            $where_conditions[] = "pr.status = %s";
            $prepare_values[] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $search_term = '%' . $wpdb->esc_like($filters['search']) . '%';
            $where_conditions[] = "(p.first_name LIKE %s OR p.last_name LIKE %s OR p.patient_id LIKE %s)";
            $prepare_values[] = $search_term;
            $prepare_values[] = $search_term;
            $prepare_values[] = $search_term;
        }

        $where_clause = implode(' AND ', $where_conditions);

        $sql = "
            SELECT COUNT(*)
            FROM {$wpdb->prefix}cms_prescriptions pr
            LEFT JOIN {$wpdb->prefix}cms_patients p ON pr.patient_id = p.id
            WHERE {$where_clause}
        ";

        if (empty($prepare_values)) {
            return $wpdb->get_var($sql);
        }

        return $wpdb->get_var($wpdb->prepare($sql, $prepare_values));
    }

    /**
     * Update inventory from prescription
     */
    private static function updateInventoryFromPrescription($medicines)
    {
        global $wpdb;

        foreach ($medicines as $medicine) {
            if (!empty($medicine['medicine_id']) && !empty($medicine['quantity'])) {
                // Update inventory quantity
                $wpdb->query($wpdb->prepare("
                    UPDATE {$wpdb->prefix}cms_inventory 
                    SET quantity_in_stock = quantity_in_stock - %d 
                    WHERE id = %d AND quantity_in_stock >= %d
                ", $medicine['quantity'], $medicine['medicine_id'], $medicine['quantity']));

                // Log stock movement
                $wpdb->insert(
                    $wpdb->prefix . 'cms_stock_movements',
                    array(
                        'medicine_id' => $medicine['medicine_id'],
                        'movement_type' => 'dispensed',
                        'quantity' => -$medicine['quantity'],
                        'reference_type' => 'prescription',
                        'notes' => 'Dispensed via prescription',
                        'created_by' => get_current_user_id(),
                    ),
                    array('%d', '%s', '%d', '%s', '%s', '%d')
                );
            }
        }
    }

    /**
     * Generate prescription HTML for printing
     */
    public static function generatePrescriptionHTML($prescription_id)
    {
        $prescription = self::getPrescription($prescription_id);
        if (!$prescription) {
            return false;
        }

        $clinic_name = CMS_Database::getSetting('clinic_name', 'Your Clinic Name');
        $clinic_address = CMS_Database::getSetting('clinic_address', '');
        $clinic_phone = CMS_Database::getSetting('clinic_phone', '');
        $clinic_email = CMS_Database::getSetting('clinic_email', '');

        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title><?php _e('Prescription', 'clinic-management'); ?> - <?php echo esc_html($prescription->patient_code); ?></title>
            <style>
                body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
                .prescription-header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 15px; margin-bottom: 20px; }
                .clinic-name { font-size: 24px; font-weight: bold; margin-bottom: 5px; }
                .clinic-details { font-size: 14px; color: #666; }
                .prescription-info { display: flex; justify-content: space-between; margin-bottom: 20px; }
                .patient-info, .doctor-info { width: 48%; }
                .info-title { font-weight: bold; font-size: 16px; margin-bottom: 10px; border-bottom: 1px solid #ccc; padding-bottom: 5px; }
                .medicines-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                .medicines-table th, .medicines-table td { border: 1px solid #ccc; padding: 10px; text-align: left; }
                .medicines-table th { background-color: #f5f5f5; font-weight: bold; }
                .instructions { margin-top: 20px; padding: 15px; background-color: #f9f9f9; border-left: 4px solid #007cba; }
                .prescription-footer { margin-top: 30px; text-align: right; }
                .doctor-signature { margin-top: 50px; border-top: 1px solid #000; padding-top: 10px; display: inline-block; min-width: 200px; }
                @media print {
                    body { margin: 0; padding: 15px; }
                    .no-print { display: none; }
                }
            </style>
        </head>
        <body>
            <div class="prescription-header">
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

            <div class="prescription-info">
                <div class="patient-info">
                    <div class="info-title"><?php _e('Patient Information', 'clinic-management'); ?></div>
                    <strong><?php _e('Name:', 'clinic-management'); ?></strong> <?php echo esc_html($prescription->first_name . ' ' . $prescription->last_name); ?><br>
                    <strong><?php _e('Patient ID:', 'clinic-management'); ?></strong> <?php echo esc_html($prescription->patient_code); ?><br>
                    <?php if ($prescription->date_of_birth): ?>
                        <strong><?php _e('Age:', 'clinic-management'); ?></strong> <?php echo esc_html(CMS_Patients::calculateAge($prescription->date_of_birth)); ?><br>
                    <?php endif; ?>
                    <?php if ($prescription->gender): ?>
                        <strong><?php _e('Gender:', 'clinic-management'); ?></strong> <?php echo esc_html(ucfirst($prescription->gender)); ?><br>
                    <?php endif; ?>
                    <?php if ($prescription->phone): ?>
                        <strong><?php _e('Phone:', 'clinic-management'); ?></strong> <?php echo esc_html($prescription->phone); ?><br>
                    <?php endif; ?>
                </div>
                
                <div class="doctor-info">
                    <div class="info-title"><?php _e('Prescription Details', 'clinic-management'); ?></div>
                    <strong><?php _e('Doctor:', 'clinic-management'); ?></strong> <?php echo esc_html($prescription->doctor_name); ?><br>
                    <strong><?php _e('Date:', 'clinic-management'); ?></strong> <?php echo esc_html(date('F j, Y', strtotime($prescription->prescription_date))); ?><br>
                    <?php if ($prescription->diagnosis): ?>
                        <strong><?php _e('Diagnosis:', 'clinic-management'); ?></strong> <?php echo esc_html($prescription->diagnosis); ?><br>
                    <?php endif; ?>
                    <?php if ($prescription->consultation_type): ?>
                        <strong><?php _e('Type:', 'clinic-management'); ?></strong> <?php echo esc_html(ucfirst($prescription->consultation_type)); ?><br>
                    <?php endif; ?>
                </div>
            </div>

            <table class="medicines-table">
                <thead>
                    <tr>
                        <th><?php _e('Medicine', 'clinic-management'); ?></th>
                        <th><?php _e('Dosage', 'clinic-management'); ?></th>
                        <th><?php _e('Frequency', 'clinic-management'); ?></th>
                        <th><?php _e('Duration', 'clinic-management'); ?></th>
                        <th><?php _e('Quantity', 'clinic-management'); ?></th>
                        <th><?php _e('Instructions', 'clinic-management'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($prescription->medicines as $medicine): ?>
                        <tr>
                            <td><?php echo esc_html($medicine['medicine_name']); ?></td>
                            <td><?php echo esc_html($medicine['dosage']); ?></td>
                            <td><?php echo esc_html($medicine['frequency']); ?></td>
                            <td><?php echo esc_html($medicine['duration']); ?></td>
                            <td><?php echo esc_html($medicine['quantity']); ?></td>
                            <td><?php echo esc_html($medicine['instructions']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if ($prescription->instructions): ?>
                <div class="instructions">
                    <strong><?php _e('General Instructions:', 'clinic-management'); ?></strong><br>
                    <?php echo nl2br(esc_html($prescription->instructions)); ?>
                </div>
            <?php endif; ?>

            <div class="prescription-footer">
                <div class="doctor-signature">
                    <?php echo esc_html($prescription->doctor_name); ?><br>
                    <?php _e('Doctor Signature', 'clinic-management'); ?>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * AJAX: Save prescription
     */
    public function ajaxSavePrescription()
    {
        check_ajax_referer('cms_ajax_nonce', 'nonce');

        if (!CMS_User_Roles::userCan('cms_manage_prescriptions')) {
            wp_die(json_encode(array('success' => false, 'message' => __('Permission denied', 'clinic-management'))));
        }

        $prescription_id = !empty($_POST['prescription_id']) ? intval($_POST['prescription_id']) : 0;
        $prescription_data = $_POST['prescription_data'];

        if ($prescription_id > 0) {
            // Update existing prescription
            $result = self::updatePrescription($prescription_id, $prescription_data);
        } else {
            // Create new prescription
            $result = self::createPrescription($prescription_data);
        }

        if (is_wp_error($result)) {
            wp_die(json_encode(array('success' => false, 'message' => $result->get_error_message())));
        }

        $response_data = array('success' => true, 'message' => __('Prescription saved successfully', 'clinic-management'));
        if ($prescription_id === 0) {
            $response_data['prescription_id'] = $result;
        }

        wp_die(json_encode($response_data));
    }

    /**
     * AJAX: Get prescription
     */
    public function ajaxGetPrescription()
    {
        check_ajax_referer('cms_ajax_nonce', 'nonce');

        if (!CMS_User_Roles::userCan('cms_view_prescriptions')) {
            wp_die(json_encode(array('success' => false, 'message' => __('Permission denied', 'clinic-management'))));
        }

        $prescription_id = intval($_POST['prescription_id']);
        $prescription = self::getPrescription($prescription_id);

        if (!$prescription) {
            wp_die(json_encode(array('success' => false, 'message' => __('Prescription not found', 'clinic-management'))));
        }

        wp_die(json_encode(array('success' => true, 'data' => $prescription)));
    }

    /**
     * AJAX: Get prescriptions
     */
    public function ajaxGetPrescriptions()
    {
        check_ajax_referer('cms_ajax_nonce', 'nonce');

        if (!CMS_User_Roles::userCan('cms_view_prescriptions')) {
            wp_die(json_encode(array('success' => false, 'message' => __('Permission denied', 'clinic-management'))));
        }

        $filters = $_POST['filters'] ?? array();
        $page = intval($_POST['page'] ?? 1);
        $per_page = 20;
        $offset = ($page - 1) * $per_page;

        $prescriptions = self::getPrescriptions($filters, $per_page, $offset);
        $total = self::getPrescriptionsCount($filters);

        $response_data = array(
            'success' => true,
            'data' => $prescriptions,
            'total' => $total,
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($total / $per_page)
        );

        wp_die(json_encode($response_data));
    }

    /**
     * AJAX: Print prescription
     */
    public function ajaxPrintPrescription()
    {
        check_ajax_referer('cms_ajax_nonce', 'nonce');

        if (!CMS_User_Roles::userCan('cms_view_prescriptions')) {
            wp_die(__('Permission denied', 'clinic-management'));
        }

        $prescription_id = intval($_POST['prescription_id']);
        $html = self::generatePrescriptionHTML($prescription_id);

        if (!$html) {
            wp_die(__('Prescription not found', 'clinic-management'));
        }

        echo $html;
        wp_die();
    }

    /**
     * AJAX: Search medicines
     */
    public function ajaxSearchMedicines()
    {
        check_ajax_referer('cms_ajax_nonce', 'nonce');

        if (!CMS_User_Roles::userCan('cms_manage_prescriptions')) {
            wp_die(json_encode(array('success' => false, 'message' => __('Permission denied', 'clinic-management'))));
        }

        $search_term = sanitize_text_field($_POST['search_term']);
        
        global $wpdb;
        $medicines = $wpdb->get_results($wpdb->prepare("
            SELECT id, medicine_name, generic_name, brand_name, strength, unit, selling_price, quantity_in_stock
            FROM {$wpdb->prefix}cms_inventory 
            WHERE status = 'active' 
            AND (medicine_name LIKE %s OR generic_name LIKE %s OR brand_name LIKE %s)
            ORDER BY medicine_name
            LIMIT 10
        ", '%' . $wpdb->esc_like($search_term) . '%', '%' . $wpdb->esc_like($search_term) . '%', '%' . $wpdb->esc_like($search_term) . '%'));

        $results = array();
        foreach ($medicines as $medicine) {
            $results[] = array(
                'id' => $medicine->id,
                'name' => $medicine->medicine_name,
                'generic_name' => $medicine->generic_name,
                'brand_name' => $medicine->brand_name,
                'strength' => $medicine->strength,
                'unit' => $medicine->unit,
                'price' => $medicine->selling_price,
                'stock' => $medicine->quantity_in_stock,
            );
        }

        wp_die(json_encode(array('success' => true, 'data' => $results)));
    }

    /**
     * Get prescription statistics
     */
    public static function getPrescriptionStats()
    {
        global $wpdb;

        $stats = array();
        $today = current_time('Y-m-d');
        $this_month = current_time('Y-m');

        // Today's prescriptions
        $stats['today'] = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}cms_prescriptions 
            WHERE prescription_date = %s
        ", $today));

        // This month's prescriptions
        $stats['this_month'] = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}cms_prescriptions 
            WHERE prescription_date LIKE %s
        ", $this_month . '%'));

        // Prescriptions by status
        $stats['by_status'] = $wpdb->get_results("
            SELECT status, COUNT(*) as count 
            FROM {$wpdb->prefix}cms_prescriptions 
            GROUP BY status
        ");

        // Most prescribed medicines
        $stats['top_medicines'] = $wpdb->get_results("
            SELECT medicine_name, COUNT(*) as prescriptions_count
            FROM {$wpdb->prefix}cms_prescriptions pr,
                 JSON_TABLE(pr.medicines, '$[*]' COLUMNS (
                     medicine_name VARCHAR(200) PATH '$.medicine_name'
                 )) jt
            GROUP BY medicine_name
            ORDER BY prescriptions_count DESC
            LIMIT 10
        ");

        return $stats;
    }
}