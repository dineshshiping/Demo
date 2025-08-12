<?php
/**
 * Inventory management module
 */

if (!defined('ABSPATH')) {
    exit;
}

class CMS_Inventory
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
        add_action('wp_ajax_cms_save_medicine', array($this, 'ajaxSaveMedicine'));
        add_action('wp_ajax_cms_get_medicine', array($this, 'ajaxGetMedicine'));
        add_action('wp_ajax_cms_get_medicines', array($this, 'ajaxGetMedicines'));
        add_action('wp_ajax_cms_update_stock', array($this, 'ajaxUpdateStock'));
        add_action('wp_ajax_cms_get_stock_movements', array($this, 'ajaxGetStockMovements'));
        add_action('wp_ajax_cms_get_low_stock_alerts', array($this, 'ajaxGetLowStockAlerts'));
    }

    /**
     * Create new medicine
     */
    public static function createMedicine($data)
    {
        global $wpdb;

        // Validate required fields
        if (empty($data['medicine_name'])) {
            return new WP_Error('missing_data', __('Medicine name is required.', 'clinic-management'));
        }

        // Check if medicine already exists
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}cms_inventory WHERE medicine_name = %s AND batch_number = %s",
            $data['medicine_name'],
            $data['batch_number'] ?? ''
        ));

        if ($existing) {
            return new WP_Error('medicine_exists', __('Medicine with this name and batch number already exists.', 'clinic-management'));
        }

        // Prepare data
        $insert_data = array(
            'medicine_name' => sanitize_text_field($data['medicine_name']),
            'generic_name' => !empty($data['generic_name']) ? sanitize_text_field($data['generic_name']) : null,
            'brand_name' => !empty($data['brand_name']) ? sanitize_text_field($data['brand_name']) : null,
            'category' => !empty($data['category']) ? sanitize_text_field($data['category']) : null,
            'dosage_form' => !empty($data['dosage_form']) ? sanitize_text_field($data['dosage_form']) : null,
            'strength' => !empty($data['strength']) ? sanitize_text_field($data['strength']) : null,
            'unit' => !empty($data['unit']) ? sanitize_text_field($data['unit']) : null,
            'batch_number' => !empty($data['batch_number']) ? sanitize_text_field($data['batch_number']) : null,
            'expiry_date' => !empty($data['expiry_date']) ? sanitize_text_field($data['expiry_date']) : null,
            'quantity_in_stock' => !empty($data['quantity_in_stock']) ? intval($data['quantity_in_stock']) : 0,
            'minimum_stock_level' => !empty($data['minimum_stock_level']) ? intval($data['minimum_stock_level']) : 10,
            'unit_cost' => !empty($data['unit_cost']) ? floatval($data['unit_cost']) : 0,
            'selling_price' => !empty($data['selling_price']) ? floatval($data['selling_price']) : 0,
            'supplier' => !empty($data['supplier']) ? sanitize_text_field($data['supplier']) : null,
            'supplier_contact' => !empty($data['supplier_contact']) ? sanitize_text_field($data['supplier_contact']) : null,
            'status' => !empty($data['status']) ? sanitize_text_field($data['status']) : 'active',
            'created_by' => get_current_user_id(),
        );

        $result = $wpdb->insert(
            $wpdb->prefix . 'cms_inventory',
            $insert_data,
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%f', '%f', '%s', '%s', '%s', '%d')
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Failed to create medicine record.', 'clinic-management'));
        }

        $medicine_id = $wpdb->insert_id;

        // Log initial stock movement
        if ($insert_data['quantity_in_stock'] > 0) {
            self::logStockMovement($medicine_id, 'received', $insert_data['quantity_in_stock'], $insert_data['unit_cost'], null, 'purchase', 'Initial stock');
        }

        return $medicine_id;
    }

    /**
     * Update medicine
     */
    public static function updateMedicine($medicine_id, $data)
    {
        global $wpdb;

        // Validate medicine exists
        $medicine = self::getMedicine($medicine_id);
        if (!$medicine) {
            return new WP_Error('medicine_not_found', __('Medicine not found.', 'clinic-management'));
        }

        // Prepare update data
        $update_data = array();
        $update_formats = array();

        $fields = array(
            'medicine_name' => '%s',
            'generic_name' => '%s',
            'brand_name' => '%s',
            'category' => '%s',
            'dosage_form' => '%s',
            'strength' => '%s',
            'unit' => '%s',
            'batch_number' => '%s',
            'expiry_date' => '%s',
            'minimum_stock_level' => '%d',
            'unit_cost' => '%f',
            'selling_price' => '%f',
            'supplier' => '%s',
            'supplier_contact' => '%s',
            'status' => '%s',
        );

        foreach ($fields as $field => $format) {
            if (isset($data[$field])) {
                if (in_array($field, array('minimum_stock_level'))) {
                    $update_data[$field] = intval($data[$field]);
                } elseif (in_array($field, array('unit_cost', 'selling_price'))) {
                    $update_data[$field] = floatval($data[$field]);
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
            $wpdb->prefix . 'cms_inventory',
            $update_data,
            array('id' => $medicine_id),
            $update_formats,
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Failed to update medicine record.', 'clinic-management'));
        }

        return true;
    }

    /**
     * Get medicine by ID
     */
    public static function getMedicine($medicine_id)
    {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}cms_inventory WHERE id = %d",
            $medicine_id
        ));
    }

    /**
     * Get medicines with filters
     */
    public static function getMedicines($filters = array(), $limit = 20, $offset = 0)
    {
        global $wpdb;

        $where_conditions = array('1=1');
        $prepare_values = array();

        // Category filter
        if (!empty($filters['category'])) {
            $where_conditions[] = "category = %s";
            $prepare_values[] = $filters['category'];
        }

        // Status filter
        if (!empty($filters['status'])) {
            $where_conditions[] = "status = %s";
            $prepare_values[] = $filters['status'];
        }

        // Low stock filter
        if (!empty($filters['low_stock'])) {
            $where_conditions[] = "quantity_in_stock <= minimum_stock_level";
        }

        // Expiry filter
        if (!empty($filters['expiry_days'])) {
            $days = intval($filters['expiry_days']);
            $where_conditions[] = "expiry_date <= DATE_ADD(CURDATE(), INTERVAL %d DAY)";
            $prepare_values[] = $days;
        }

        // Search filter
        if (!empty($filters['search'])) {
            $search_term = '%' . $wpdb->esc_like($filters['search']) . '%';
            $where_conditions[] = "(medicine_name LIKE %s OR generic_name LIKE %s OR brand_name LIKE %s OR batch_number LIKE %s)";
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
            SELECT *
            FROM {$wpdb->prefix}cms_inventory
            WHERE {$where_clause}
            ORDER BY medicine_name ASC
            LIMIT %d OFFSET %d
        ";

        return $wpdb->get_results($wpdb->prepare($sql, $prepare_values));
    }

    /**
     * Get medicines count
     */
    public static function getMedicinesCount($filters = array())
    {
        global $wpdb;

        $where_conditions = array('1=1');
        $prepare_values = array();

        // Apply same filters as getMedicines
        if (!empty($filters['category'])) {
            $where_conditions[] = "category = %s";
            $prepare_values[] = $filters['category'];
        }

        if (!empty($filters['status'])) {
            $where_conditions[] = "status = %s";
            $prepare_values[] = $filters['status'];
        }

        if (!empty($filters['low_stock'])) {
            $where_conditions[] = "quantity_in_stock <= minimum_stock_level";
        }

        if (!empty($filters['expiry_days'])) {
            $days = intval($filters['expiry_days']);
            $where_conditions[] = "expiry_date <= DATE_ADD(CURDATE(), INTERVAL %d DAY)";
            $prepare_values[] = $days;
        }

        if (!empty($filters['search'])) {
            $search_term = '%' . $wpdb->esc_like($filters['search']) . '%';
            $where_conditions[] = "(medicine_name LIKE %s OR generic_name LIKE %s OR brand_name LIKE %s OR batch_number LIKE %s)";
            $prepare_values[] = $search_term;
            $prepare_values[] = $search_term;
            $prepare_values[] = $search_term;
            $prepare_values[] = $search_term;
        }

        $where_clause = implode(' AND ', $where_conditions);

        $sql = "
            SELECT COUNT(*)
            FROM {$wpdb->prefix}cms_inventory
            WHERE {$where_clause}
        ";

        if (empty($prepare_values)) {
            return $wpdb->get_var($sql);
        }

        return $wpdb->get_var($wpdb->prepare($sql, $prepare_values));
    }

    /**
     * Update stock quantity
     */
    public static function updateStock($medicine_id, $movement_type, $quantity, $unit_cost = null, $reference_id = null, $reference_type = null, $notes = '')
    {
        global $wpdb;

        $medicine = self::getMedicine($medicine_id);
        if (!$medicine) {
            return new WP_Error('medicine_not_found', __('Medicine not found.', 'clinic-management'));
        }

        // Calculate new stock based on movement type
        $stock_change = 0;
        switch ($movement_type) {
            case 'received':
            case 'purchased':
            case 'returned':
                $stock_change = intval($quantity);
                break;
            case 'dispensed':
            case 'sold':
            case 'expired':
            case 'damaged':
                $stock_change = -intval($quantity);
                break;
            default:
                return new WP_Error('invalid_movement_type', __('Invalid movement type.', 'clinic-management'));
        }

        $new_quantity = $medicine->quantity_in_stock + $stock_change;

        // Prevent negative stock
        if ($new_quantity < 0) {
            return new WP_Error('insufficient_stock', __('Insufficient stock available.', 'clinic-management'));
        }

        // Update stock in inventory
        $result = $wpdb->update(
            $wpdb->prefix . 'cms_inventory',
            array('quantity_in_stock' => $new_quantity),
            array('id' => $medicine_id),
            array('%d'),
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Failed to update stock.', 'clinic-management'));
        }

        // Log stock movement
        self::logStockMovement($medicine_id, $movement_type, $stock_change, $unit_cost, $reference_id, $reference_type, $notes);

        return $new_quantity;
    }

    /**
     * Log stock movement
     */
    public static function logStockMovement($medicine_id, $movement_type, $quantity, $unit_cost = null, $reference_id = null, $reference_type = null, $notes = '')
    {
        global $wpdb;

        $wpdb->insert(
            $wpdb->prefix . 'cms_stock_movements',
            array(
                'medicine_id' => $medicine_id,
                'movement_type' => $movement_type,
                'quantity' => $quantity,
                'unit_cost' => $unit_cost,
                'reference_id' => $reference_id,
                'reference_type' => $reference_type,
                'notes' => $notes,
                'created_by' => get_current_user_id(),
            ),
            array('%d', '%s', '%d', '%f', '%d', '%s', '%s', '%d')
        );
    }

    /**
     * Get stock movements
     */
    public static function getStockMovements($medicine_id = null, $limit = 50, $offset = 0)
    {
        global $wpdb;

        $where_clause = '1=1';
        $prepare_values = array();

        if ($medicine_id) {
            $where_clause = 'sm.medicine_id = %d';
            $prepare_values[] = $medicine_id;
        }

        $prepare_values[] = $limit;
        $prepare_values[] = $offset;

        return $wpdb->get_results($wpdb->prepare("
            SELECT sm.*, i.medicine_name, u.display_name as created_by_name
            FROM {$wpdb->prefix}cms_stock_movements sm
            LEFT JOIN {$wpdb->prefix}cms_inventory i ON sm.medicine_id = i.id
            LEFT JOIN {$wpdb->users} u ON sm.created_by = u.ID
            WHERE {$where_clause}
            ORDER BY sm.movement_date DESC
            LIMIT %d OFFSET %d
        ", $prepare_values));
    }

    /**
     * Get low stock medicines
     */
    public static function getLowStockMedicines()
    {
        global $wpdb;

        return $wpdb->get_results("
            SELECT *
            FROM {$wpdb->prefix}cms_inventory
            WHERE status = 'active' AND quantity_in_stock <= minimum_stock_level
            ORDER BY quantity_in_stock ASC
        ");
    }

    /**
     * Get medicines expiring soon
     */
    public static function getExpiringMedicines($days = 30)
    {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare("
            SELECT *
            FROM {$wpdb->prefix}cms_inventory
            WHERE status = 'active' 
            AND expiry_date IS NOT NULL 
            AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL %d DAY)
            ORDER BY expiry_date ASC
        ", $days));
    }

    /**
     * Get medicine categories
     */
    public static function getCategories()
    {
        global $wpdb;

        return $wpdb->get_col("
            SELECT DISTINCT category
            FROM {$wpdb->prefix}cms_inventory
            WHERE category IS NOT NULL AND category != ''
            ORDER BY category ASC
        ");
    }

    /**
     * AJAX: Save medicine
     */
    public function ajaxSaveMedicine()
    {
        check_ajax_referer('cms_ajax_nonce', 'nonce');

        if (!CMS_User_Roles::userCan('cms_manage_inventory')) {
            wp_die(json_encode(array('success' => false, 'message' => __('Permission denied', 'clinic-management'))));
        }

        $medicine_id = !empty($_POST['medicine_id']) ? intval($_POST['medicine_id']) : 0;
        $medicine_data = $_POST['medicine_data'];

        if ($medicine_id > 0) {
            // Update existing medicine
            $result = self::updateMedicine($medicine_id, $medicine_data);
        } else {
            // Create new medicine
            $result = self::createMedicine($medicine_data);
        }

        if (is_wp_error($result)) {
            wp_die(json_encode(array('success' => false, 'message' => $result->get_error_message())));
        }

        $response_data = array('success' => true, 'message' => __('Medicine saved successfully', 'clinic-management'));
        if ($medicine_id === 0) {
            $response_data['medicine_id'] = $result;
        }

        wp_die(json_encode($response_data));
    }

    /**
     * AJAX: Get medicine
     */
    public function ajaxGetMedicine()
    {
        check_ajax_referer('cms_ajax_nonce', 'nonce');

        if (!CMS_User_Roles::userCan('cms_view_inventory')) {
            wp_die(json_encode(array('success' => false, 'message' => __('Permission denied', 'clinic-management'))));
        }

        $medicine_id = intval($_POST['medicine_id']);
        $medicine = self::getMedicine($medicine_id);

        if (!$medicine) {
            wp_die(json_encode(array('success' => false, 'message' => __('Medicine not found', 'clinic-management'))));
        }

        wp_die(json_encode(array('success' => true, 'data' => $medicine)));
    }

    /**
     * AJAX: Get medicines
     */
    public function ajaxGetMedicines()
    {
        check_ajax_referer('cms_ajax_nonce', 'nonce');

        if (!CMS_User_Roles::userCan('cms_view_inventory')) {
            wp_die(json_encode(array('success' => false, 'message' => __('Permission denied', 'clinic-management'))));
        }

        $filters = $_POST['filters'] ?? array();
        $page = intval($_POST['page'] ?? 1);
        $per_page = 20;
        $offset = ($page - 1) * $per_page;

        $medicines = self::getMedicines($filters, $per_page, $offset);
        $total = self::getMedicinesCount($filters);

        $response_data = array(
            'success' => true,
            'data' => $medicines,
            'total' => $total,
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($total / $per_page)
        );

        wp_die(json_encode($response_data));
    }

    /**
     * AJAX: Update stock
     */
    public function ajaxUpdateStock()
    {
        check_ajax_referer('cms_ajax_nonce', 'nonce');

        if (!CMS_User_Roles::userCan('cms_manage_inventory')) {
            wp_die(json_encode(array('success' => false, 'message' => __('Permission denied', 'clinic-management'))));
        }

        $medicine_id = intval($_POST['medicine_id']);
        $movement_type = sanitize_text_field($_POST['movement_type']);
        $quantity = intval($_POST['quantity']);
        $unit_cost = !empty($_POST['unit_cost']) ? floatval($_POST['unit_cost']) : null;
        $notes = sanitize_textarea_field($_POST['notes'] ?? '');

        $result = self::updateStock($medicine_id, $movement_type, $quantity, $unit_cost, null, 'manual', $notes);

        if (is_wp_error($result)) {
            wp_die(json_encode(array('success' => false, 'message' => $result->get_error_message())));
        }

        wp_die(json_encode(array('success' => true, 'message' => __('Stock updated successfully', 'clinic-management'), 'new_quantity' => $result)));
    }

    /**
     * AJAX: Get stock movements
     */
    public function ajaxGetStockMovements()
    {
        check_ajax_referer('cms_ajax_nonce', 'nonce');

        if (!CMS_User_Roles::userCan('cms_view_inventory')) {
            wp_die(json_encode(array('success' => false, 'message' => __('Permission denied', 'clinic-management'))));
        }

        $medicine_id = !empty($_POST['medicine_id']) ? intval($_POST['medicine_id']) : null;
        $page = intval($_POST['page'] ?? 1);
        $per_page = 20;
        $offset = ($page - 1) * $per_page;

        $movements = self::getStockMovements($medicine_id, $per_page, $offset);

        wp_die(json_encode(array('success' => true, 'data' => $movements)));
    }

    /**
     * AJAX: Get low stock alerts
     */
    public function ajaxGetLowStockAlerts()
    {
        check_ajax_referer('cms_ajax_nonce', 'nonce');

        if (!CMS_User_Roles::userCan('cms_view_inventory')) {
            wp_die(json_encode(array('success' => false, 'message' => __('Permission denied', 'clinic-management'))));
        }

        $low_stock = self::getLowStockMedicines();
        $expiring = self::getExpiringMedicines();

        wp_die(json_encode(array(
            'success' => true, 
            'data' => array(
                'low_stock' => $low_stock,
                'expiring' => $expiring
            )
        )));
    }

    /**
     * Get inventory statistics
     */
    public static function getInventoryStats()
    {
        global $wpdb;

        $stats = array();

        // Total medicines
        $stats['total_medicines'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}cms_inventory WHERE status = 'active'");

        // Low stock count
        $stats['low_stock_count'] = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->prefix}cms_inventory 
            WHERE status = 'active' AND quantity_in_stock <= minimum_stock_level
        ");

        // Expiring soon count (30 days)
        $stats['expiring_soon_count'] = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->prefix}cms_inventory 
            WHERE status = 'active' 
            AND expiry_date IS NOT NULL 
            AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        ");

        // Total inventory value
        $stats['total_value'] = $wpdb->get_var("
            SELECT SUM(quantity_in_stock * unit_cost) FROM {$wpdb->prefix}cms_inventory 
            WHERE status = 'active'
        ");

        // Top categories by count
        $stats['top_categories'] = $wpdb->get_results("
            SELECT category, COUNT(*) as count, SUM(quantity_in_stock) as total_quantity
            FROM {$wpdb->prefix}cms_inventory 
            WHERE status = 'active' AND category IS NOT NULL
            GROUP BY category
            ORDER BY count DESC
            LIMIT 5
        ");

        // Recent stock movements (last 7 days)
        $stats['recent_movements'] = $wpdb->get_results("
            SELECT movement_type, COUNT(*) as count
            FROM {$wpdb->prefix}cms_stock_movements 
            WHERE movement_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY movement_type
        ");

        return $stats;
    }
}