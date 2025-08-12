<?php
/**
 * Reports and analytics class
 */
class CMS_Reports {
    
    private $db;
    
    public function __construct() {
        $this->db = new CMS_Database();
    }
    
    /**
     * Get dashboard overview statistics
     */
    public function get_dashboard_stats() {
        $patient_stats = $this->get_patient_stats();
        $appointment_stats = $this->get_appointment_stats();
        $billing_stats = $this->get_billing_stats();
        $inventory_stats = $this->get_inventory_stats();
        
        return array(
            'patients' => $patient_stats,
            'appointments' => $appointment_stats,
            'billing' => $billing_stats,
            'inventory' => $inventory_stats
        );
    }
    
    /**
     * Get patient statistics
     */
    private function get_patient_stats() {
        $total_patients = $this->db->get_var(
            "SELECT COUNT(*) FROM {$this->db->get_table_name('patients')}"
        );
        
        $new_this_month = $this->db->get_var(
            "SELECT COUNT(*) FROM {$this->db->get_table_name('patients')} 
             WHERE YEAR(created_at) = %d AND MONTH(created_at) = %d",
            array(date('Y'), date('m'))
        );
        
        $new_this_week = $this->db->get_var(
            "SELECT COUNT(*) FROM {$this->db->get_table_name('patients')} 
             WHERE YEARWEEK(created_at) = YEARWEEK(NOW())",
            array()
        );
        
        $male_count = $this->db->get_var(
            "SELECT COUNT(*) FROM {$this->db->get_table_name('patients')} WHERE gender = 'male'"
        );
        
        $female_count = $this->db->get_var(
            "SELECT COUNT(*) FROM {$this->db->get_table_name('patients')} WHERE gender = 'female'"
        );
        
        return array(
            'total' => $total_patients ?: 0,
            'new_this_month' => $new_this_month ?: 0,
            'new_this_week' => $new_this_week ?: 0,
            'male' => $male_count ?: 0,
            'female' => $female_count ?: 0
        );
    }
    
    /**
     * Get appointment statistics
     */
    private function get_appointment_stats() {
        $today = date('Y-m-d');
        $this_month = date('Y-m');
        
        $today_count = $this->db->get_var(
            "SELECT COUNT(*) FROM {$this->db->get_table_name('appointments')} WHERE appointment_date = %s",
            array($today)
        );
        
        $month_count = $this->db->get_var(
            "SELECT COUNT(*) FROM {$this->db->get_table_name('appointments')} 
             WHERE DATE_FORMAT(appointment_date, '%%Y-%%m') = %s",
            array($this_month)
        );
        
        $pending_count = $this->db->get_var(
            "SELECT COUNT(*) FROM {$this->db->get_table_name('appointments')} 
             WHERE status = 'scheduled' AND appointment_date >= CURDATE()"
        );
        
        $completed_count = $this->db->get_var(
            "SELECT COUNT(*) FROM {$this->db->get_table_name('appointments')} 
             WHERE status = 'completed' AND DATE_FORMAT(appointment_date, '%%Y-%%m') = %s",
            array($this_month)
        );
        
        $cancelled_count = $this->db->get_var(
            "SELECT COUNT(*) FROM {$this->db->get_table_name('appointments')} 
             WHERE status = 'cancelled' AND DATE_FORMAT(appointment_date, '%%Y-%%m') = %s",
            array($this_month)
        );
        
        return array(
            'today' => $today_count ?: 0,
            'this_month' => $month_count ?: 0,
            'pending' => $pending_count ?: 0,
            'completed' => $completed_count ?: 0,
            'cancelled' => $cancelled_count ?: 0
        );
    }
    
    /**
     * Get billing statistics
     */
    private function get_billing_stats() {
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
        
        $unpaid_invoices = $this->db->get_var(
            "SELECT COUNT(*) FROM {$this->db->get_table_name('billing')} WHERE payment_status != 'paid'"
        );
        
        return array(
            'month_revenue' => $month_revenue ?: 0,
            'month_paid' => $month_paid ?: 0,
            'pending_amount' => $pending_amount ?: 0,
            'total_invoices' => $total_invoices ?: 0,
            'unpaid_invoices' => $unpaid_invoices ?: 0
        );
    }
    
    /**
     * Get inventory statistics
     */
    private function get_inventory_stats() {
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
     * Get monthly revenue data for charts
     */
    public function get_monthly_revenue_data($year = null) {
        if (!$year) {
            $year = date('Y');
        }
        
        $query = "SELECT MONTH(created_at) as month, 
                         SUM(total_amount) as revenue,
                         SUM(paid_amount) as paid
                  FROM {$this->db->get_table_name('billing')} 
                  WHERE YEAR(created_at) = %d 
                  GROUP BY MONTH(created_at) 
                  ORDER BY month ASC";
        
        $results = $this->db->get_results($query, array($year));
        
        $monthly_data = array();
        for ($i = 1; $i <= 12; $i++) {
            $monthly_data[$i] = array(
                'month' => date('F', mktime(0, 0, 0, $i, 1)),
                'revenue' => 0,
                'paid' => 0
            );
        }
        
        foreach ($results as $result) {
            $monthly_data[$result->month]['revenue'] = floatval($result->revenue);
            $monthly_data[$result->month]['paid'] = floatval($result->paid);
        }
        
        return array_values($monthly_data);
    }
    
    /**
     * Get patient growth data
     */
    public function get_patient_growth_data($months = 12) {
        $query = "SELECT DATE_FORMAT(created_at, '%%Y-%%m') as month, 
                         COUNT(*) as new_patients
                  FROM {$this->db->get_table_name('patients')} 
                  WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d MONTH)
                  GROUP BY DATE_FORMAT(created_at, '%%Y-%%m') 
                  ORDER BY month ASC";
        
        $results = $this->db->get_results($query, array($months));
        
        $growth_data = array();
        foreach ($results as $result) {
            $growth_data[] = array(
                'month' => date('M Y', strtotime($result->month . '-01')),
                'new_patients' => intval($result->new_patients)
            );
        }
        
        return $growth_data;
    }
    
    /**
     * Get appointment trends
     */
    public function get_appointment_trends($days = 30) {
        $query = "SELECT DATE(appointment_date) as date, 
                         COUNT(*) as total_appointments,
                         SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                         SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
                  FROM {$this->db->get_table_name('appointments')} 
                  WHERE appointment_date >= DATE_SUB(CURDATE(), INTERVAL %d DAY)
                  GROUP BY DATE(appointment_date) 
                  ORDER BY date ASC";
        
        $results = $this->db->get_results($query, array($days));
        
        $trends_data = array();
        foreach ($results as $result) {
            $trends_data[] = array(
                'date' => date('M j', strtotime($result->date)),
                'total' => intval($result->total_appointments),
                'completed' => intval($result->completed),
                'cancelled' => intval($result->cancelled)
            );
        }
        
        return $trends_data;
    }
    
    /**
     * Get top performing doctors
     */
    public function get_top_doctors($limit = 5) {
        $query = "SELECT u.ID, u.display_name,
                         COUNT(a.id) as total_appointments,
                         COUNT(CASE WHEN a.status = 'completed' THEN 1 END) as completed_appointments,
                         AVG(CASE WHEN a.status = 'completed' THEN 1 ELSE 0 END) * 100 as completion_rate
                  FROM {$GLOBALS['wpdb']->users} u
                  LEFT JOIN {$this->db->get_table_name('appointments')} a ON u.ID = a.doctor_id
                  WHERE u.ID IN (SELECT DISTINCT doctor_id FROM {$this->db->get_table_name('appointments')})
                  GROUP BY u.ID, u.display_name
                  ORDER BY total_appointments DESC
                  LIMIT %d";
        
        return $this->db->get_results($query, array($limit));
    }
    
    /**
     * Get popular appointment times
     */
    public function get_popular_appointment_times() {
        $query = "SELECT HOUR(appointment_time) as hour,
                         COUNT(*) as appointment_count
                  FROM {$this->db->get_table_name('appointments')} 
                  WHERE appointment_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                  GROUP BY HOUR(appointment_time) 
                  ORDER BY appointment_count DESC";
        
        $results = $this->db->get_results($query);
        
        $time_data = array();
        foreach ($results as $result) {
            $time_data[] = array(
                'hour' => date('g A', mktime($result->hour, 0, 0)),
                'count' => intval($result->appointment_count)
            );
        }
        
        return $time_data;
    }
    
    /**
     * Get inventory alerts
     */
    public function get_inventory_alerts() {
        $low_stock = $this->db->get_results(
            "SELECT * FROM {$this->db->get_table_name('inventory')} 
             WHERE current_stock <= minimum_stock ORDER BY current_stock ASC LIMIT 10"
        );
        
        $expiring_soon = $this->db->get_results(
            "SELECT * FROM {$this->db->get_table_name('inventory')} 
             WHERE expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) 
             AND expiry_date >= CURDATE() ORDER BY expiry_date ASC LIMIT 10"
        );
        
        return array(
            'low_stock' => $low_stock,
            'expiring_soon' => $expiring_soon
        );
    }
    
    /**
     * Generate comprehensive report
     */
    public function generate_comprehensive_report($start_date = null, $end_date = null) {
        if (!$start_date) {
            $start_date = date('Y-m-01'); // First day of current month
        }
        if (!$end_date) {
            $end_date = date('Y-m-t'); // Last day of current month
        }
        
        $report = array(
            'period' => array(
                'start_date' => $start_date,
                'end_date' => $end_date
            ),
            'summary' => $this->get_period_summary($start_date, $end_date),
            'patients' => $this->get_period_patient_data($start_date, $end_date),
            'appointments' => $this->get_period_appointment_data($start_date, $end_date),
            'revenue' => $this->get_period_revenue_data($start_date, $end_date),
            'inventory' => $this->get_period_inventory_data($start_date, $end_date)
        );
        
        return $report;
    }
    
    /**
     * Get period summary
     */
    private function get_period_summary($start_date, $end_date) {
        $new_patients = $this->db->get_var(
            "SELECT COUNT(*) FROM {$this->db->get_table_name('patients')} 
             WHERE DATE(created_at) BETWEEN %s AND %s",
            array($start_date, $end_date)
        );
        
        $total_appointments = $this->db->get_var(
            "SELECT COUNT(*) FROM {$this->db->get_table_name('appointments')} 
             WHERE DATE(appointment_date) BETWEEN %s AND %s",
            array($start_date, $end_date)
        );
        
        $total_revenue = $this->db->get_var(
            "SELECT SUM(total_amount) FROM {$this->db->get_table_name('billing')} 
             WHERE DATE(created_at) BETWEEN %s AND %s",
            array($start_date, $end_date)
        );
        
        return array(
            'new_patients' => $new_patients ?: 0,
            'total_appointments' => $total_appointments ?: 0,
            'total_revenue' => $total_revenue ?: 0
        );
    }
    
    /**
     * Get period patient data
     */
    private function get_period_patient_data($start_date, $end_date) {
        $query = "SELECT DATE(created_at) as date, COUNT(*) as new_patients
                  FROM {$this->db->get_table_name('patients')} 
                  WHERE DATE(created_at) BETWEEN %s AND %s
                  GROUP BY DATE(created_at) ORDER BY date ASC";
        
        return $this->db->get_results($query, array($start_date, $end_date));
    }
    
    /**
     * Get period appointment data
     */
    private function get_period_appointment_data($start_date, $end_date) {
        $query = "SELECT DATE(appointment_date) as date, 
                         COUNT(*) as total_appointments,
                         SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
                  FROM {$this->db->get_table_name('appointments')} 
                  WHERE DATE(appointment_date) BETWEEN %s AND %s
                  GROUP BY DATE(appointment_date) ORDER BY date ASC";
        
        return $this->db->get_results($query, array($start_date, $end_date));
    }
    
    /**
     * Get period revenue data
     */
    private function get_period_revenue_data($start_date, $end_date) {
        $query = "SELECT DATE(created_at) as date, 
                         SUM(total_amount) as revenue,
                         SUM(paid_amount) as paid
                  FROM {$this->db->get_table_name('billing')} 
                  WHERE DATE(created_at) BETWEEN %s AND %s
                  GROUP BY DATE(created_at) ORDER BY date ASC";
        
        return $this->db->get_results($query, array($start_date, $end_date));
    }
    
    /**
     * Get period inventory data
     */
    private function get_period_inventory_data($start_date, $end_date) {
        // For inventory, we'll get current status since inventory changes over time
        $low_stock = $this->db->get_var(
            "SELECT COUNT(*) FROM {$this->db->get_table_name('inventory')} WHERE current_stock <= minimum_stock"
        );
        
        $expiring_soon = $this->db->get_var(
            "SELECT COUNT(*) FROM {$this->db->get_table_name('inventory')} 
             WHERE expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)"
        );
        
        return array(
            'low_stock_count' => $low_stock ?: 0,
            'expiring_soon_count' => $expiring_soon ?: 0
        );
    }
    
    /**
     * Export report to CSV
     */
    public function export_report_csv($report_data, $filename = null) {
        if (!$filename) {
            $filename = 'clinic_report_' . date('Y-m-d') . '.csv';
        }
        
        $filepath = wp_upload_dir()['basedir'] . '/clinic-reports/' . $filename;
        
        if (!file_exists(dirname($filepath))) {
            wp_mkdir_p(dirname($filepath));
        }
        
        $file = fopen($filepath, 'w');
        
        // Write summary
        fputcsv($file, array('CLINIC MANAGEMENT SYSTEM REPORT'));
        fputcsv($file, array('Period:', $report_data['period']['start_date'], 'to', $report_data['period']['end_date']));
        fputcsv($file, array(''));
        
        // Write summary data
        fputcsv($file, array('SUMMARY'));
        fputcsv($file, array('New Patients', $report_data['summary']['new_patients']));
        fputcsv($file, array('Total Appointments', $report_data['summary']['total_appointments']));
        fputcsv($file, array('Total Revenue', '$' . number_format($report_data['summary']['total_revenue'], 2)));
        fputcsv($file, array(''));
        
        // Write patient data
        fputcsv($file, array('DAILY PATIENT REGISTRATIONS'));
        fputcsv($file, array('Date', 'New Patients'));
        foreach ($report_data['patients'] as $patient) {
            fputcsv($file, array($patient->date, $patient->new_patients));
        }
        fputcsv($file, array(''));
        
        // Write appointment data
        fputcsv($file, array('DAILY APPOINTMENTS'));
        fputcsv($file, array('Date', 'Total Appointments', 'Completed'));
        foreach ($report_data['appointments'] as $appointment) {
            fputcsv($file, array($appointment->date, $appointment->total_appointments, $appointment->completed));
        }
        fputcsv($file, array(''));
        
        // Write revenue data
        fputcsv($file, array('DAILY REVENUE'));
        fputcsv($file, array('Date', 'Revenue', 'Paid'));
        foreach ($report_data['revenue'] as $revenue) {
            fputcsv($file, array($revenue->date, '$' . number_format($revenue->revenue, 2), '$' . number_format($revenue->paid, 2)));
        }
        
        fclose($file);
        
        return $filepath;
    }
}