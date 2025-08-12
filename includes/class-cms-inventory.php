<?php
/**
 * Inventory management class
 */
class CMS_Inventory {
    
    private $db;
    
    public function __construct() {
        $this->db = new CMS_Database();
    }
    
    /**
     * Add a new medicine to inventory
     */
    public function add_medicine($data) {
        // Sanitize data
        $sanitized_data = array(
            'medicine_name' => sanitize_text_field($data['medicine_name']),
            'generic_name' => sanitize_text_field($data['generic_name']),
            'category' => sanitize_text_field($data['category']),
            'manufacturer' => sanitize_text_field($data['manufacturer']),
            'strength' => sanitize_text_field($data['strength']),
            'form' => sanitize_text_field($data['form']),
            'current_stock' => intval($data['current_stock']),
            'minimum_stock' => intval($data['minimum_stock']),
            'unit_price' => floatval($data['unit_price']),
            'expiry_date' => sanitize_text_field($data['expiry_date']),
            'location' => sanitize_text_field($data['location']),
            'notes' => sanitize_textarea_field($data['notes'])
        );
        
        $result = $this->db->insert('inventory', $sanitized_data);
        
        if ($result) {
            // Check if stock is low and send alert
            if ($sanitized_data['current_stock'] <= $sanitized_data['minimum_stock']) {
                $this->send_low_stock_alert($sanitized_data);
            }
            
            return $result;
        }
        
        return false;
    }
    
    /**
     * Update medicine information
     */
    public function update_medicine($medicine_id, $data) {
        $sanitized_data = array();
        
        if (isset($data['medicine_name'])) $sanitized_data['medicine_name'] = sanitize_text_field($data['medicine_name']);
        if (isset($data['generic_name'])) $sanitized_data['generic_name'] = sanitize_text_field($data['generic_name']);
        if (isset($data['category'])) $sanitized_data['category'] = sanitize_text_field($data['category']);
        if (isset($data['manufacturer'])) $sanitized_data['manufacturer'] = sanitize_text_field($data['manufacturer']);
        if (isset($data['strength'])) $sanitized_data['strength'] = sanitize_text_field($data['strength']);
        if (isset($data['form'])) $sanitized_data['form'] = sanitize_text_field($data['form']);
        if (isset($data['current_stock'])) $sanitized_data['current_stock'] = intval($data['current_stock']);
        if (isset($data['minimum_stock'])) $sanitized_data['minimum_stock'] = intval($data['minimum_stock']);
        if (isset($data['unit_price'])) $sanitized_data['unit_price'] = floatval($data['unit_price']);
        if (isset($data['expiry_date'])) $sanitized_data['expiry_date'] = sanitize_text_field($data['expiry_date']);
        if (isset($data['location'])) $sanitized_data['location'] = sanitize_text_field($data['location']);
        if (isset($data['notes'])) $sanitized_data['notes'] = sanitize_textarea_field($data['notes']);
        
        $sanitized_data['updated_at'] = current_time('mysql');
        
        $result = $this->db->update('inventory', $sanitized_data, array('id' => $medicine_id));
        
        if ($result && isset($sanitized_data['current_stock'])) {
            // Check if stock is low and send alert
            $medicine = $this->get_medicine($medicine_id);
            if ($medicine && $medicine->current_stock <= $medicine->minimum_stock) {
                $this->send_low_stock_alert((array)$medicine);
            }
        }
        
        return $result;
    }
    
    /**
     * Delete medicine from inventory
     */
    public function delete_medicine($medicine_id) {
        return $this->db->delete('inventory', array('id' => $medicine_id));
    }
    
    /**
     * Get medicine by ID
     */
    public function get_medicine($medicine_id) {
        $query = "SELECT * FROM {$this->db->get_table_name('inventory')} WHERE id = %d";
        return $this->db->get_row($query, array($medicine_id));
    }
    
    /**
     * Get all medicines with pagination
     */
    public function get_medicines($page = 1, $per_page = 20, $category = '', $search = '') {
        $offset = ($page - 1) * $per_page;
        
        $where_clause = "WHERE 1=1";
        $args = array();
        
        if (!empty($category)) {
            $where_clause .= " AND category = %s";
            $args[] = $category;
        }
        
        if (!empty($search)) {
            $where_clause .= " AND (medicine_name LIKE %s OR generic_name LIKE %s OR manufacturer LIKE %s)";
            $search_term = '%' . $search . '%';
            $args[] = $search_term;
            $args[] = $search_term;
            $args[] = $search_term;
        }
        
        $query = "SELECT * FROM {$this->db->get_table_name('inventory')} 
                  $where_clause ORDER BY medicine_name ASC LIMIT %d OFFSET %d";
        
        $args[] = $per_page;
        $args[] = $offset;
        
        return $this->db->get_results($query, $args);
    }
    
    /**
     * Get medicine count
     */
    public function get_medicine_count($category = '', $search = '') {
        $where_clause = "WHERE 1=1";
        $args = array();
        
        if (!empty($category)) {
            $where_clause .= " AND category = %s";
            $args[] = $category;
        }
        
        if (!empty($search)) {
            $where_clause .= " AND (medicine_name LIKE %s OR generic_name LIKE %s OR manufacturer LIKE %s)";
            $search_term = '%' . $search . '%';
            $args[] = $search_term;
            $args[] = $search_term;
            $args[] = $search_term;
        }
        
        $query = "SELECT COUNT(*) FROM {$this->db->get_table_name('inventory')} $where_clause";
        return $this->db->get_var($query, $args);
    }
    
    /**
     * Get low stock medicines
     */
    public function get_low_stock_medicines() {
        $query = "SELECT * FROM {$this->db->get_table_name('inventory')} 
                  WHERE current_stock <= minimum_stock ORDER BY current_stock ASC";
        
        return $this->db->get_results($query);
    }
    
    /**
     * Get expiring medicines
     */
    public function get_expiring_medicines($days = 30) {
        $query = "SELECT * FROM {$this->db->get_table_name('inventory')} 
                  WHERE expiry_date <= DATE_ADD(CURDATE(), INTERVAL %d DAY) 
                  AND expiry_date >= CURDATE() ORDER BY expiry_date ASC";
        
        return $this->db->get_results($query, array($days));
    }
    
    /**
     * Get medicine categories
     */
    public function get_medicine_categories() {
        $query = "SELECT DISTINCT category FROM {$this->db->get_table_name('inventory')} 
                  WHERE category IS NOT NULL AND category != '' ORDER BY category ASC";
        
        $results = $this->db->get_results($query);
        $categories = array();
        
        foreach ($results as $result) {
            $categories[] = $result->category;
        }
        
        return $categories;
    }
    
    /**
     * Update stock level
     */
    public function update_stock($medicine_id, $quantity, $operation = 'add') {
        $medicine = $this->get_medicine($medicine_id);
        if (!$medicine) {
            return false;
        }
        
        $new_stock = $operation === 'add' ? $medicine->current_stock + $quantity : $medicine->current_stock - $quantity;
        
        if ($new_stock < 0) {
            return false; // Cannot have negative stock
        }
        
        $data = array('current_stock' => $new_stock);
        
        $result = $this->db->update('inventory', $data, array('id' => $medicine_id));
        
        if ($result) {
            // Check if stock is now low
            if ($new_stock <= $medicine->minimum_stock) {
                $this->send_low_stock_alert((array)$medicine);
            }
        }
        
        return $result;
    }
    
    /**
     * Search medicines
     */
    public function search_medicines($search_term) {
        $query = "SELECT * FROM {$this->db->get_table_name('inventory')} 
                  WHERE medicine_name LIKE %s OR generic_name LIKE %s OR manufacturer LIKE %s 
                  ORDER BY medicine_name ASC LIMIT 20";
        
        $search_term = '%' . $search_term . '%';
        return $this->db->get_results($query, array($search_term, $search_term, $search_term));
    }
    
    /**
     * Get inventory statistics
     */
    public function get_inventory_stats() {
        $total_medicines = $this->db->get_var(
            "SELECT COUNT(*) FROM {$this->db->get_table_name('inventory')}"
        );
        
        $low_stock_count = $this->db->get_var(
            "SELECT COUNT(*) FROM {$this->db->get_table_name('inventory')} WHERE current_stock <= minimum_stock"
        );
        
        $expiring_soon = $this->db->get_var(
            "SELECT COUNT(*) FROM {$this->db->get_table_name('inventory')} 
             WHERE expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND expiry_date >= CURDATE()"
        );
        
        $total_value = $this->db->get_var(
            "SELECT SUM(current_stock * unit_price) FROM {$this->db->get_table_name('inventory')}"
        );
        
        return array(
            'total_medicines' => $total_medicines ?: 0,
            'low_stock_count' => $low_stock_count ?: 0,
            'expiring_soon' => $expiring_soon ?: 0,
            'total_value' => $total_value ?: 0
        );
    }
    
    /**
     * Send low stock alert
     */
    private function send_low_stock_alert($medicine_data) {
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
        
        wp_mail($admin_email, $subject, $message);
    }
    
    /**
     * Get medicine by name
     */
    public function get_medicine_by_name($medicine_name) {
        $query = "SELECT * FROM {$this->db->get_table_name('inventory')} 
                  WHERE medicine_name = %s OR generic_name = %s LIMIT 1";
        
        return $this->db->get_row($query, array($medicine_name, $medicine_name));
    }
    
    /**
     * Bulk update stock
     */
    public function bulk_update_stock($updates) {
        $results = array();
        
        foreach ($updates as $update) {
            $medicine_id = intval($update['medicine_id']);
            $quantity = intval($update['quantity']);
            $operation = sanitize_text_field($update['operation']);
            
            $result = $this->update_stock($medicine_id, $quantity, $operation);
            $results[] = array(
                'medicine_id' => $medicine_id,
                'success' => $result,
                'message' => $result ? 'Stock updated successfully' : 'Failed to update stock'
            );
        }
        
        return $results;
    }
    
    /**
     * Export inventory to CSV
     */
    public function export_inventory_csv() {
        $medicines = $this->db->get_results(
            "SELECT * FROM {$this->db->get_table_name('inventory')} ORDER BY category, medicine_name"
        );
        
        $filename = 'inventory_' . date('Y-m-d') . '.csv';
        $filepath = wp_upload_dir()['basedir'] . '/clinic-exports/' . $filename;
        
        if (!file_exists(dirname($filepath))) {
            wp_mkdir_p(dirname($filepath));
        }
        
        $file = fopen($filepath, 'w');
        
        // Add headers
        fputcsv($file, array(
            'Medicine Name', 'Generic Name', 'Category', 'Manufacturer', 'Strength', 'Form',
            'Current Stock', 'Minimum Stock', 'Unit Price', 'Expiry Date', 'Location', 'Notes'
        ));
        
        // Add data
        foreach ($medicines as $medicine) {
            fputcsv($file, array(
                $medicine->medicine_name,
                $medicine->generic_name,
                $medicine->category,
                $medicine->manufacturer,
                $medicine->strength,
                $medicine->form,
                $medicine->current_stock,
                $medicine->minimum_stock,
                $medicine->unit_price,
                $medicine->expiry_date,
                $medicine->location,
                $medicine->notes
            ));
        }
        
        fclose($file);
        
        return $filepath;
    }
}