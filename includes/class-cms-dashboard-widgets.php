<?php
/**
 * Dashboard widgets for WordPress admin dashboard
 */

if (!defined('ABSPATH')) {
    exit;
}

class CMS_Dashboard_Widgets
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
        add_action('wp_dashboard_setup', array($this, 'addDashboardWidgets'));
    }

    /**
     * Add dashboard widgets
     */
    public function addDashboardWidgets()
    {
        // Only show widgets to users with CMS access
        if (!CMS_User_Roles::canAccessCMS()) {
            return;
        }

        // Today's Appointments widget
        wp_add_dashboard_widget(
            'cms_todays_appointments',
            __("Today's Appointments", 'clinic-management'),
            array($this, 'displayTodaysAppointments')
        );

        // Clinic Statistics widget
        wp_add_dashboard_widget(
            'cms_clinic_stats',
            __('Clinic Statistics', 'clinic-management'),
            array($this, 'displayClinicStats')
        );

        // Recent Patients widget
        wp_add_dashboard_widget(
            'cms_recent_patients',
            __('Recent Patients', 'clinic-management'),
            array($this, 'displayRecentPatients')
        );

        // Low Stock Alerts widget
        if (CMS_User_Roles::userCan('cms_view_inventory')) {
            wp_add_dashboard_widget(
                'cms_low_stock_alerts',
                __('Low Stock Alerts', 'clinic-management'),
                array($this, 'displayLowStockAlerts')
            );
        }
    }

    /**
     * Display today's appointments widget
     */
    public function displayTodaysAppointments()
    {
        global $wpdb;
        
        $today = current_time('Y-m-d');
        
        $appointments = $wpdb->get_results($wpdb->prepare("
            SELECT a.*, p.first_name, p.last_name, p.phone, u.display_name as doctor_name
            FROM {$wpdb->prefix}cms_appointments a
            LEFT JOIN {$wpdb->prefix}cms_patients p ON a.patient_id = p.id
            LEFT JOIN {$wpdb->users} u ON a.doctor_id = u.ID
            WHERE a.appointment_date = %s AND a.status != 'cancelled'
            ORDER BY a.appointment_time ASC
        ", $today));

        ?>
        <div class="cms-dashboard-widget">
            <?php if (empty($appointments)): ?>
                <p><?php _e('No appointments scheduled for today.', 'clinic-management'); ?></p>
            <?php else: ?>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th><?php _e('Time', 'clinic-management'); ?></th>
                            <th><?php _e('Patient', 'clinic-management'); ?></th>
                            <th><?php _e('Doctor', 'clinic-management'); ?></th>
                            <th><?php _e('Status', 'clinic-management'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $appointment): ?>
                            <tr>
                                <td><?php echo esc_html(date('H:i', strtotime($appointment->appointment_time))); ?></td>
                                <td>
                                    <strong><?php echo esc_html($appointment->first_name . ' ' . $appointment->last_name); ?></strong>
                                    <br><small><?php echo esc_html($appointment->phone); ?></small>
                                </td>
                                <td><?php echo esc_html($appointment->doctor_name); ?></td>
                                <td>
                                    <span class="cms-status-badge cms-status-<?php echo esc_attr($appointment->status); ?>">
                                        <?php echo esc_html(ucfirst($appointment->status)); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            
            <div class="cms-widget-footer">
                <a href="<?php echo admin_url('admin.php?page=clinic-management-appointments'); ?>" class="button">
                    <?php _e('View All Appointments', 'clinic-management'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=clinic-management-appointments&action=add'); ?>" class="button button-primary">
                    <?php _e('Schedule New', 'clinic-management'); ?>
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Display clinic statistics widget
     */
    public function displayClinicStats()
    {
        global $wpdb;
        
        $today = current_time('Y-m-d');
        $this_month = current_time('Y-m');
        
        // Get statistics
        $stats = array();
        
        // Total patients
        $stats['total_patients'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}cms_patients");
        
        // Today's appointments
        $stats['todays_appointments'] = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}cms_appointments 
            WHERE appointment_date = %s AND status != 'cancelled'
        ", $today));
        
        // This month's revenue
        $stats['monthly_revenue'] = $wpdb->get_var($wpdb->prepare("
            SELECT SUM(paid_amount) FROM {$wpdb->prefix}cms_billing 
            WHERE bill_date LIKE %s AND payment_status = 'paid'
        ", $this_month . '%'));
        
        // Pending bills
        $stats['pending_bills'] = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->prefix}cms_billing 
            WHERE payment_status = 'unpaid'
        ");
        
        $currency_symbol = CMS_Database::getSetting('currency_symbol', '$');
        
        ?>
        <div class="cms-dashboard-widget">
            <div class="cms-stats-grid">
                <div class="cms-stat-item">
                    <div class="cms-stat-number"><?php echo number_format($stats['total_patients']); ?></div>
                    <div class="cms-stat-label"><?php _e('Total Patients', 'clinic-management'); ?></div>
                </div>
                
                <div class="cms-stat-item">
                    <div class="cms-stat-number"><?php echo number_format($stats['todays_appointments']); ?></div>
                    <div class="cms-stat-label"><?php _e("Today's Appointments", 'clinic-management'); ?></div>
                </div>
                
                <div class="cms-stat-item">
                    <div class="cms-stat-number"><?php echo esc_html($currency_symbol . number_format($stats['monthly_revenue'], 2)); ?></div>
                    <div class="cms-stat-label"><?php _e('Monthly Revenue', 'clinic-management'); ?></div>
                </div>
                
                <div class="cms-stat-item">
                    <div class="cms-stat-number"><?php echo number_format($stats['pending_bills']); ?></div>
                    <div class="cms-stat-label"><?php _e('Pending Bills', 'clinic-management'); ?></div>
                </div>
            </div>
            
            <div class="cms-widget-footer">
                <a href="<?php echo admin_url('admin.php?page=clinic-management'); ?>" class="button">
                    <?php _e('View Dashboard', 'clinic-management'); ?>
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Display recent patients widget
     */
    public function displayRecentPatients()
    {
        global $wpdb;
        
        $recent_patients = $wpdb->get_results("
            SELECT patient_id, first_name, last_name, phone, created_at
            FROM {$wpdb->prefix}cms_patients
            ORDER BY created_at DESC
            LIMIT 5
        ");

        ?>
        <div class="cms-dashboard-widget">
            <?php if (empty($recent_patients)): ?>
                <p><?php _e('No patients registered yet.', 'clinic-management'); ?></p>
            <?php else: ?>
                <ul class="cms-patient-list">
                    <?php foreach ($recent_patients as $patient): ?>
                        <li>
                            <strong><?php echo esc_html($patient->first_name . ' ' . $patient->last_name); ?></strong>
                            <span class="cms-patient-id"><?php echo esc_html($patient->patient_id); ?></span>
                            <br>
                            <small>
                                <?php echo esc_html($patient->phone); ?> • 
                                <?php echo esc_html(human_time_diff(strtotime($patient->created_at), current_time('timestamp')) . ' ago'); ?>
                            </small>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            
            <div class="cms-widget-footer">
                <a href="<?php echo admin_url('admin.php?page=clinic-management-patients'); ?>" class="button">
                    <?php _e('View All Patients', 'clinic-management'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=clinic-management-patients&action=add'); ?>" class="button button-primary">
                    <?php _e('Add Patient', 'clinic-management'); ?>
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Display low stock alerts widget
     */
    public function displayLowStockAlerts()
    {
        global $wpdb;
        
        $low_stock_threshold = CMS_Database::getSetting('low_stock_threshold', 10);
        
        $low_stock_medicines = $wpdb->get_results($wpdb->prepare("
            SELECT medicine_name, quantity_in_stock, minimum_stock_level
            FROM {$wpdb->prefix}cms_inventory
            WHERE status = 'active' AND quantity_in_stock <= %d
            ORDER BY quantity_in_stock ASC
            LIMIT 5
        ", $low_stock_threshold));

        ?>
        <div class="cms-dashboard-widget">
            <?php if (empty($low_stock_medicines)): ?>
                <p style="color: green;">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <?php _e('All medicines are in stock.', 'clinic-management'); ?>
                </p>
            <?php else: ?>
                <ul class="cms-stock-alerts">
                    <?php foreach ($low_stock_medicines as $medicine): ?>
                        <li class="cms-low-stock-item">
                            <strong><?php echo esc_html($medicine->medicine_name); ?></strong>
                            <span class="cms-stock-level">
                                <?php echo sprintf(__('%d units left', 'clinic-management'), $medicine->quantity_in_stock); ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            
            <div class="cms-widget-footer">
                <a href="<?php echo admin_url('admin.php?page=clinic-management-inventory'); ?>" class="button">
                    <?php _e('Manage Inventory', 'clinic-management'); ?>
                </a>
            </div>
        </div>
        <?php
    }
}