<?php
/**
 * Reports and Analytics Module
 */

if (!defined('ABSPATH')) {
    exit;
}

class CMS_Reports
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
        add_action('wp_ajax_cms_get_reports_data', array($this, 'ajaxGetReportsData'));
        add_action('wp_ajax_cms_generate_report', array($this, 'ajaxGenerateReport'));
        add_action('wp_ajax_cms_export_report', array($this, 'ajaxExportReport'));
    }

    /**
     * Get revenue statistics
     */
    public static function getRevenueStats($start_date = null, $end_date = null)
    {
        global $wpdb;
        
        if (!$start_date) {
            $start_date = date('Y-m-01'); // First day of current month
        }
        if (!$end_date) {
            $end_date = date('Y-m-d'); // Today
        }

        // Total revenue
        $total_revenue = $wpdb->get_var($wpdb->prepare("
            SELECT SUM(total_amount) 
            FROM {$wpdb->prefix}cms_billing 
            WHERE payment_status = 'paid' 
            AND bill_date BETWEEN %s AND %s
        ", $start_date, $end_date));

        // Revenue by month (last 12 months)
        $monthly_revenue = $wpdb->get_results("
            SELECT 
                DATE_FORMAT(bill_date, '%Y-%m') as month,
                SUM(total_amount) as revenue,
                COUNT(*) as bills_count
            FROM {$wpdb->prefix}cms_billing 
            WHERE payment_status = 'paid' 
            AND bill_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(bill_date, '%Y-%m')
            ORDER BY month ASC
        ");

        // Revenue by payment method
        $payment_methods = $wpdb->get_results("
            SELECT 
                payment_method,
                SUM(amount_paid) as total,
                COUNT(*) as count
            FROM {$wpdb->prefix}cms_billing 
            WHERE payment_status IN ('paid', 'partial') 
            AND bill_date BETWEEN %s AND %s
            GROUP BY payment_method
        ", $start_date, $end_date);

        return array(
            'total_revenue' => floatval($total_revenue),
            'monthly_revenue' => $monthly_revenue,
            'payment_methods' => $payment_methods,
            'period' => array('start' => $start_date, 'end' => $end_date)
        );
    }

    /**
     * Get patient statistics
     */
    public static function getPatientStats($start_date = null, $end_date = null)
    {
        global $wpdb;
        
        if (!$start_date) {
            $start_date = date('Y-m-01');
        }
        if (!$end_date) {
            $end_date = date('Y-m-d');
        }

        // Total patients
        $total_patients = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}cms_patients");

        // New patients in period
        $new_patients = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM {$wpdb->prefix}cms_patients 
            WHERE DATE(created_at) BETWEEN %s AND %s
        ", $start_date, $end_date));

        // Patients by age group
        $age_groups = $wpdb->get_results("
            SELECT 
                CASE 
                    WHEN YEAR(CURDATE()) - YEAR(date_of_birth) < 18 THEN 'Under 18'
                    WHEN YEAR(CURDATE()) - YEAR(date_of_birth) BETWEEN 18 AND 30 THEN '18-30'
                    WHEN YEAR(CURDATE()) - YEAR(date_of_birth) BETWEEN 31 AND 50 THEN '31-50'
                    WHEN YEAR(CURDATE()) - YEAR(date_of_birth) BETWEEN 51 AND 70 THEN '51-70'
                    ELSE 'Over 70'
                END as age_group,
                COUNT(*) as count
            FROM {$wpdb->prefix}cms_patients 
            WHERE date_of_birth IS NOT NULL
            GROUP BY age_group
        ");

        // Patients by gender
        $gender_stats = $wpdb->get_results("
            SELECT gender, COUNT(*) as count 
            FROM {$wpdb->prefix}cms_patients 
            WHERE gender IS NOT NULL AND gender != ''
            GROUP BY gender
        ");

        // New patients by month (last 12 months)
        $monthly_new_patients = $wpdb->get_results("
            SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as new_patients
            FROM {$wpdb->prefix}cms_patients 
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month ASC
        ");

        return array(
            'total_patients' => intval($total_patients),
            'new_patients' => intval($new_patients),
            'age_groups' => $age_groups,
            'gender_stats' => $gender_stats,
            'monthly_new_patients' => $monthly_new_patients,
            'period' => array('start' => $start_date, 'end' => $end_date)
        );
    }

    /**
     * Get appointment statistics
     */
    public static function getAppointmentStats($start_date = null, $end_date = null)
    {
        global $wpdb;
        
        if (!$start_date) {
            $start_date = date('Y-m-01');
        }
        if (!$end_date) {
            $end_date = date('Y-m-d');
        }

        // Total appointments
        $total_appointments = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM {$wpdb->prefix}cms_appointments 
            WHERE appointment_date BETWEEN %s AND %s
        ", $start_date, $end_date));

        // Appointments by status
        $status_stats = $wpdb->get_results($wpdb->prepare("
            SELECT status, COUNT(*) as count 
            FROM {$wpdb->prefix}cms_appointments 
            WHERE appointment_date BETWEEN %s AND %s
            GROUP BY status
        ", $start_date, $end_date));

        // Appointments by doctor
        $doctor_stats = $wpdb->get_results($wpdb->prepare("
            SELECT 
                u.display_name as doctor_name,
                COUNT(*) as appointments_count,
                SUM(CASE WHEN a.status = 'completed' THEN 1 ELSE 0 END) as completed_count
            FROM {$wpdb->prefix}cms_appointments a
            LEFT JOIN {$wpdb->users} u ON a.doctor_id = u.ID
            WHERE a.appointment_date BETWEEN %s AND %s
            GROUP BY a.doctor_id, u.display_name
            ORDER BY appointments_count DESC
        ", $start_date, $end_date));

        // Daily appointments (last 30 days)
        $daily_appointments = $wpdb->get_results("
            SELECT 
                appointment_date as date,
                COUNT(*) as appointments_count,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count
            FROM {$wpdb->prefix}cms_appointments 
            WHERE appointment_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY appointment_date
            ORDER BY appointment_date ASC
        ");

        // Peak hours
        $peak_hours = $wpdb->get_results("
            SELECT 
                HOUR(appointment_time) as hour,
                COUNT(*) as appointments_count
            FROM {$wpdb->prefix}cms_appointments 
            WHERE appointment_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY HOUR(appointment_time)
            ORDER BY appointments_count DESC
            LIMIT 5
        ");

        return array(
            'total_appointments' => intval($total_appointments),
            'status_stats' => $status_stats,
            'doctor_stats' => $doctor_stats,
            'daily_appointments' => $daily_appointments,
            'peak_hours' => $peak_hours,
            'period' => array('start' => $start_date, 'end' => $end_date)
        );
    }

    /**
     * Get consultation statistics
     */
    public static function getConsultationStats($start_date = null, $end_date = null)
    {
        global $wpdb;
        
        if (!$start_date) {
            $start_date = date('Y-m-01');
        }
        if (!$end_date) {
            $end_date = date('Y-m-d');
        }

        // Total consultations
        $total_consultations = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM {$wpdb->prefix}cms_consultations 
            WHERE DATE(consultation_date) BETWEEN %s AND %s
        ", $start_date, $end_date));

        // Consultations by type
        $type_stats = $wpdb->get_results($wpdb->prepare("
            SELECT consultation_type, COUNT(*) as count 
            FROM {$wpdb->prefix}cms_consultations 
            WHERE DATE(consultation_date) BETWEEN %s AND %s
            GROUP BY consultation_type
        ", $start_date, $end_date));

        // Common diagnoses
        $common_diagnoses = $wpdb->get_results($wpdb->prepare("
            SELECT 
                diagnosis,
                COUNT(*) as count
            FROM {$wpdb->prefix}cms_consultations 
            WHERE DATE(consultation_date) BETWEEN %s AND %s
            AND diagnosis IS NOT NULL AND diagnosis != ''
            GROUP BY diagnosis
            ORDER BY count DESC
            LIMIT 10
        ", $start_date, $end_date));

        // Average consultation fee
        $avg_consultation_fee = $wpdb->get_var($wpdb->prepare("
            SELECT AVG(consultation_fee) 
            FROM {$wpdb->prefix}cms_consultations 
            WHERE DATE(consultation_date) BETWEEN %s AND %s
            AND consultation_fee > 0
        ", $start_date, $end_date));

        return array(
            'total_consultations' => intval($total_consultations),
            'type_stats' => $type_stats,
            'common_diagnoses' => $common_diagnoses,
            'avg_consultation_fee' => floatval($avg_consultation_fee),
            'period' => array('start' => $start_date, 'end' => $end_date)
        );
    }

    /**
     * Get inventory statistics
     */
    public static function getInventoryStats()
    {
        global $wpdb;

        // Total medicines
        $total_medicines = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}cms_inventory");

        // Low stock items
        $low_stock_items = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->prefix}cms_inventory 
            WHERE quantity_in_stock <= reorder_level
        ");

        // Expiring medicines (next 30 days)
        $expiring_medicines = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->prefix}cms_inventory 
            WHERE expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
            AND expiry_date > CURDATE()
        ");

        // Most dispensed medicines (last 30 days)
        $most_dispensed = $wpdb->get_results("
            SELECT 
                i.medicine_name,
                SUM(CASE WHEN sm.movement_type = 'dispensed' THEN ABS(sm.quantity_change) ELSE 0 END) as dispensed_quantity
            FROM {$wpdb->prefix}cms_inventory i
            LEFT JOIN {$wpdb->prefix}cms_stock_movements sm ON i.id = sm.medicine_id
            WHERE sm.movement_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY i.id, i.medicine_name
            HAVING dispensed_quantity > 0
            ORDER BY dispensed_quantity DESC
            LIMIT 10
        ");

        // Stock value
        $total_stock_value = $wpdb->get_var("
            SELECT SUM(quantity_in_stock * unit_cost) 
            FROM {$wpdb->prefix}cms_inventory 
            WHERE unit_cost > 0
        ");

        return array(
            'total_medicines' => intval($total_medicines),
            'low_stock_items' => intval($low_stock_items),
            'expiring_medicines' => intval($expiring_medicines),
            'most_dispensed' => $most_dispensed,
            'total_stock_value' => floatval($total_stock_value)
        );
    }

    /**
     * Generate comprehensive clinic report
     */
    public static function generateClinicReport($start_date = null, $end_date = null)
    {
        $revenue_stats = self::getRevenueStats($start_date, $end_date);
        $patient_stats = self::getPatientStats($start_date, $end_date);
        $appointment_stats = self::getAppointmentStats($start_date, $end_date);
        $consultation_stats = self::getConsultationStats($start_date, $end_date);
        $inventory_stats = self::getInventoryStats();

        return array(
            'period' => array('start' => $start_date, 'end' => $end_date),
            'revenue' => $revenue_stats,
            'patients' => $patient_stats,
            'appointments' => $appointment_stats,
            'consultations' => $consultation_stats,
            'inventory' => $inventory_stats,
            'generated_at' => current_time('mysql')
        );
    }

    /**
     * AJAX handler for getting reports data
     */
    public function ajaxGetReportsData()
    {
        check_ajax_referer('cms_ajax_nonce', 'nonce');

        if (!CMS_User_Roles::canAccessCMS()) {
            wp_die(json_encode(array('success' => false, 'message' => __('Permission denied', 'clinic-management'))));
        }

        $report_type = sanitize_text_field($_POST['report_type'] ?? '');
        $start_date = sanitize_text_field($_POST['start_date'] ?? '');
        $end_date = sanitize_text_field($_POST['end_date'] ?? '');

        $data = array();

        switch ($report_type) {
            case 'revenue':
                $data = self::getRevenueStats($start_date, $end_date);
                break;
            case 'patients':
                $data = self::getPatientStats($start_date, $end_date);
                break;
            case 'appointments':
                $data = self::getAppointmentStats($start_date, $end_date);
                break;
            case 'consultations':
                $data = self::getConsultationStats($start_date, $end_date);
                break;
            case 'inventory':
                $data = self::getInventoryStats();
                break;
            case 'comprehensive':
                $data = self::generateClinicReport($start_date, $end_date);
                break;
            default:
                wp_die(json_encode(array('success' => false, 'message' => __('Invalid report type', 'clinic-management'))));
        }

        wp_die(json_encode(array('success' => true, 'data' => $data)));
    }

    /**
     * AJAX handler for generating report
     */
    public function ajaxGenerateReport()
    {
        check_ajax_referer('cms_ajax_nonce', 'nonce');

        if (!CMS_User_Roles::canAccessCMS()) {
            wp_die(json_encode(array('success' => false, 'message' => __('Permission denied', 'clinic-management'))));
        }

        $report_type = sanitize_text_field($_POST['report_type'] ?? '');
        $start_date = sanitize_text_field($_POST['start_date'] ?? '');
        $end_date = sanitize_text_field($_POST['end_date'] ?? '');
        $format = sanitize_text_field($_POST['format'] ?? 'html');

        $report_data = self::generateClinicReport($start_date, $end_date);

        if ($format === 'pdf') {
            // In a real implementation, you would use a PDF library like TCPDF or FPDF
            $html = self::generateReportHTML($report_data);
            // Convert HTML to PDF here
            wp_die(json_encode(array('success' => true, 'message' => __('PDF generation not implemented yet', 'clinic-management'))));
        } else {
            $html = self::generateReportHTML($report_data);
            wp_die(json_encode(array('success' => true, 'html' => $html)));
        }
    }

    /**
     * Generate HTML report
     */
    private static function generateReportHTML($report_data)
    {
        ob_start();
        ?>
        <div class="cms-report-document">
            <div class="report-header">
                <h1><?php _e('Clinic Performance Report', 'clinic-management'); ?></h1>
                <p class="report-period">
                    <?php 
                    echo sprintf(
                        __('Period: %s to %s', 'clinic-management'),
                        date('F j, Y', strtotime($report_data['period']['start'])),
                        date('F j, Y', strtotime($report_data['period']['end']))
                    );
                    ?>
                </p>
                <p class="report-generated">
                    <?php echo sprintf(__('Generated on: %s', 'clinic-management'), date('F j, Y g:i A', strtotime($report_data['generated_at']))); ?>
                </p>
            </div>

            <div class="report-section">
                <h2><?php _e('Revenue Summary', 'clinic-management'); ?></h2>
                <div class="summary-stats">
                    <div class="stat-item">
                        <strong><?php _e('Total Revenue:', 'clinic-management'); ?></strong>
                        $<?php echo number_format($report_data['revenue']['total_revenue'], 2); ?>
                    </div>
                </div>
            </div>

            <div class="report-section">
                <h2><?php _e('Patient Statistics', 'clinic-management'); ?></h2>
                <div class="summary-stats">
                    <div class="stat-item">
                        <strong><?php _e('Total Patients:', 'clinic-management'); ?></strong>
                        <?php echo number_format($report_data['patients']['total_patients']); ?>
                    </div>
                    <div class="stat-item">
                        <strong><?php _e('New Patients:', 'clinic-management'); ?></strong>
                        <?php echo number_format($report_data['patients']['new_patients']); ?>
                    </div>
                </div>
            </div>

            <div class="report-section">
                <h2><?php _e('Appointment Statistics', 'clinic-management'); ?></h2>
                <div class="summary-stats">
                    <div class="stat-item">
                        <strong><?php _e('Total Appointments:', 'clinic-management'); ?></strong>
                        <?php echo number_format($report_data['appointments']['total_appointments']); ?>
                    </div>
                </div>
            </div>

            <div class="report-section">
                <h2><?php _e('Consultation Statistics', 'clinic-management'); ?></h2>
                <div class="summary-stats">
                    <div class="stat-item">
                        <strong><?php _e('Total Consultations:', 'clinic-management'); ?></strong>
                        <?php echo number_format($report_data['consultations']['total_consultations']); ?>
                    </div>
                    <div class="stat-item">
                        <strong><?php _e('Average Consultation Fee:', 'clinic-management'); ?></strong>
                        $<?php echo number_format($report_data['consultations']['avg_consultation_fee'], 2); ?>
                    </div>
                </div>
            </div>

            <div class="report-section">
                <h2><?php _e('Inventory Summary', 'clinic-management'); ?></h2>
                <div class="summary-stats">
                    <div class="stat-item">
                        <strong><?php _e('Total Medicines:', 'clinic-management'); ?></strong>
                        <?php echo number_format($report_data['inventory']['total_medicines']); ?>
                    </div>
                    <div class="stat-item">
                        <strong><?php _e('Low Stock Items:', 'clinic-management'); ?></strong>
                        <?php echo number_format($report_data['inventory']['low_stock_items']); ?>
                    </div>
                    <div class="stat-item">
                        <strong><?php _e('Total Stock Value:', 'clinic-management'); ?></strong>
                        $<?php echo number_format($report_data['inventory']['total_stock_value'], 2); ?>
                    </div>
                </div>
            </div>
        </div>

        <style>
        .cms-report-document {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px;
            font-family: Arial, sans-serif;
            line-height: 1.6;
        }
        .report-header {
            text-align: center;
            margin-bottom: 40px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        .report-header h1 {
            margin: 0 0 10px 0;
            color: #333;
        }
        .report-period, .report-generated {
            margin: 5px 0;
            color: #666;
        }
        .report-section {
            margin-bottom: 30px;
        }
        .report-section h2 {
            color: #333;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
        }
        .summary-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        .stat-item {
            padding: 15px;
            background: #f9f9f9;
            border-radius: 5px;
        }
        </style>
        <?php
        return ob_get_clean();
    }

    /**
     * AJAX handler for exporting report
     */
    public function ajaxExportReport()
    {
        check_ajax_referer('cms_ajax_nonce', 'nonce');

        if (!CMS_User_Roles::canAccessCMS()) {
            wp_die(json_encode(array('success' => false, 'message' => __('Permission denied', 'clinic-management'))));
        }

        // Implementation for CSV/Excel export would go here
        wp_die(json_encode(array('success' => false, 'message' => __('Export functionality not implemented yet', 'clinic-management'))));
    }
}