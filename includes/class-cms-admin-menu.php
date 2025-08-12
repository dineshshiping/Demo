<?php
/**
 * Admin menu management
 */

if (!defined('ABSPATH')) {
    exit;
}

class CMS_Admin_Menu
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
        add_action('admin_menu', array($this, 'addAdminMenu'));
        add_action('admin_init', array($this, 'handleActions'));
    }

    /**
     * Add admin menu and submenus
     */
    public function addAdminMenu()
    {
        // Check if user has access
        if (!CMS_User_Roles::canAccessCMS()) {
            return;
        }

        // Main menu
        add_menu_page(
            __('Clinic Management', 'clinic-management'),
            __('Clinic Management', 'clinic-management'),
            'cms_access',
            'clinic-management',
            array($this, 'displayDashboard'),
            'dashicons-heart',
            30
        );

        // Dashboard submenu
        add_submenu_page(
            'clinic-management',
            __('Dashboard', 'clinic-management'),
            __('Dashboard', 'clinic-management'),
            'cms_access',
            'clinic-management',
            array($this, 'displayDashboard')
        );

        // Patients submenu
        add_submenu_page(
            'clinic-management',
            __('Patients', 'clinic-management'),
            __('Patients', 'clinic-management'),
            'cms_manage_patients',
            'clinic-management-patients',
            array($this, 'displayPatients')
        );

        // Appointments submenu
        add_submenu_page(
            'clinic-management',
            __('Appointments', 'clinic-management'),
            __('Appointments', 'clinic-management'),
            'cms_manage_appointments',
            'clinic-management-appointments',
            array($this, 'displayAppointments')
        );

        // Consultations submenu
        if (CMS_User_Roles::userCan('cms_manage_consultations') || CMS_User_Roles::userCan('cms_view_consultations')) {
            add_submenu_page(
                'clinic-management',
                __('Consultations', 'clinic-management'),
                __('Consultations', 'clinic-management'),
                'cms_view_consultations',
                'clinic-management-consultations',
                array($this, 'displayConsultations')
            );
        }

        // Prescriptions submenu
        if (CMS_User_Roles::userCan('cms_manage_prescriptions') || CMS_User_Roles::userCan('cms_view_prescriptions')) {
            add_submenu_page(
                'clinic-management',
                __('Prescriptions', 'clinic-management'),
                __('Prescriptions', 'clinic-management'),
                'cms_view_prescriptions',
                'clinic-management-prescriptions',
                array($this, 'displayPrescriptions')
            );
        }

        // Billing submenu
        if (CMS_User_Roles::userCan('cms_manage_billing') || CMS_User_Roles::userCan('cms_view_billing')) {
            add_submenu_page(
                'clinic-management',
                __('Billing', 'clinic-management'),
                __('Billing', 'clinic-management'),
                'cms_view_billing',
                'clinic-management-billing',
                array($this, 'displayBilling')
            );
        }

        // Inventory submenu
        if (CMS_User_Roles::userCan('cms_manage_inventory') || CMS_User_Roles::userCan('cms_view_inventory')) {
            add_submenu_page(
                'clinic-management',
                __('Inventory', 'clinic-management'),
                __('Inventory', 'clinic-management'),
                'cms_view_inventory',
                'clinic-management-inventory',
                array($this, 'displayInventory')
            );
        }

        // Reports submenu
        if (CMS_User_Roles::userCan('cms_view_reports')) {
            add_submenu_page(
                'clinic-management',
                __('Reports', 'clinic-management'),
                __('Reports', 'clinic-management'),
                'cms_view_reports',
                'clinic-management-reports',
                array($this, 'displayReports')
            );
        }

        // Settings submenu
        if (CMS_User_Roles::userCan('cms_manage_settings')) {
            add_submenu_page(
                'clinic-management',
                __('Settings', 'clinic-management'),
                __('Settings', 'clinic-management'),
                'cms_manage_settings',
                'clinic-management-settings',
                array($this, 'displaySettings')
            );
        }
    }

    /**
     * Handle admin actions
     */
    public function handleActions()
    {
        if (!isset($_GET['page']) || strpos($_GET['page'], 'clinic-management') === false) {
            return;
        }

        // Handle nonce verification
        if (isset($_POST['cms_nonce']) && !wp_verify_nonce($_POST['cms_nonce'], 'cms_admin_action')) {
            wp_die(__('Security check failed', 'clinic-management'));
        }
    }

    /**
     * Display dashboard
     */
    public function displayDashboard()
    {
        $this->renderHeader();
        include CMS_PLUGIN_PATH . 'templates/admin/dashboard.php';
        $this->renderFooter();
    }

    /**
     * Display patients page
     */
    public function displayPatients()
    {
        $this->renderHeader();
        include CMS_PLUGIN_PATH . 'templates/admin/patients.php';
        $this->renderFooter();
    }

    /**
     * Display appointments page
     */
    public function displayAppointments()
    {
        $this->renderHeader();
        include CMS_PLUGIN_PATH . 'templates/admin/appointments.php';
        $this->renderFooter();
    }

    /**
     * Display consultations page
     */
    public function displayConsultations()
    {
        $this->renderHeader();
        include CMS_PLUGIN_PATH . 'templates/admin/consultations.php';
        $this->renderFooter();
    }

    /**
     * Display prescriptions page
     */
    public function displayPrescriptions()
    {
        $this->renderHeader();
        include CMS_PLUGIN_PATH . 'templates/admin/prescriptions.php';
        $this->renderFooter();
    }

    /**
     * Display billing page
     */
    public function displayBilling()
    {
        $this->renderHeader();
        include CMS_PLUGIN_PATH . 'templates/admin/billing.php';
        $this->renderFooter();
    }

    /**
     * Display inventory page
     */
    public function displayInventory()
    {
        $this->renderHeader();
        include CMS_PLUGIN_PATH . 'templates/admin/inventory.php';
        $this->renderFooter();
    }

    /**
     * Display reports page
     */
    public function displayReports()
    {
        $this->renderHeader();
        include CMS_PLUGIN_PATH . 'templates/admin/reports.php';
        $this->renderFooter();
    }

    /**
     * Display settings page
     */
    public function displaySettings()
    {
        $this->renderHeader();
        include CMS_PLUGIN_PATH . 'templates/admin/settings.php';
        $this->renderFooter();
    }

    /**
     * Render common header
     */
    private function renderHeader()
    {
        ?>
        <div class="wrap">
            <div class="cms-admin-container">
                <?php
                // Display notifications
                $this->displayNotifications();
                ?>
        <?php
    }

    /**
     * Render common footer
     */
    private function renderFooter()
    {
        ?>
            </div>
        </div>
        <?php
    }

    /**
     * Display admin notifications
     */
    private function displayNotifications()
    {
        // Success messages
        if (isset($_GET['success'])) {
            $messages = array(
                'patient_added' => __('Patient added successfully', 'clinic-management'),
                'patient_updated' => __('Patient updated successfully', 'clinic-management'),
                'appointment_scheduled' => __('Appointment scheduled successfully', 'clinic-management'),
                'consultation_saved' => __('Consultation saved successfully', 'clinic-management'),
                'prescription_created' => __('Prescription created successfully', 'clinic-management'),
                'bill_generated' => __('Bill generated successfully', 'clinic-management'),
                'payment_recorded' => __('Payment recorded successfully', 'clinic-management'),
                'medicine_added' => __('Medicine added to inventory', 'clinic-management'),
                'settings_saved' => __('Settings saved successfully', 'clinic-management'),
            );

            $message_key = sanitize_text_field($_GET['success']);
            if (isset($messages[$message_key])) {
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($messages[$message_key]) . '</p></div>';
            }
        }

        // Error messages
        if (isset($_GET['error'])) {
            $errors = array(
                'invalid_data' => __('Invalid data provided', 'clinic-management'),
                'permission_denied' => __('Permission denied', 'clinic-management'),
                'patient_exists' => __('Patient already exists', 'clinic-management'),
                'appointment_conflict' => __('Appointment time slot not available', 'clinic-management'),
                'insufficient_stock' => __('Insufficient stock for prescribed medicines', 'clinic-management'),
                'upload_failed' => __('File upload failed', 'clinic-management'),
                'database_error' => __('Database error occurred', 'clinic-management'),
            );

            $error_key = sanitize_text_field($_GET['error']);
            if (isset($errors[$error_key])) {
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($errors[$error_key]) . '</p></div>';
            }
        }

        // Low stock alerts
        $this->displayLowStockAlerts();
    }

    /**
     * Display low stock alerts
     */
    private function displayLowStockAlerts()
    {
        if (!CMS_User_Roles::userCan('cms_view_inventory')) {
            return;
        }

        global $wpdb;
        $low_stock_threshold = CMS_Database::getSetting('low_stock_threshold', 10);
        
        $low_stock_medicines = $wpdb->get_results($wpdb->prepare(
            "SELECT medicine_name, quantity_in_stock, minimum_stock_level 
             FROM {$wpdb->prefix}cms_inventory 
             WHERE status = 'active' 
             AND quantity_in_stock <= %d 
             ORDER BY quantity_in_stock ASC 
             LIMIT 5",
            $low_stock_threshold
        ));

        if (!empty($low_stock_medicines)) {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p><strong>' . __('Low Stock Alert:', 'clinic-management') . '</strong></p>';
            echo '<ul>';
            foreach ($low_stock_medicines as $medicine) {
                echo '<li>' . esc_html($medicine->medicine_name) . ' - ' . 
                     sprintf(__('Only %d units remaining', 'clinic-management'), $medicine->quantity_in_stock) . 
                     '</li>';
            }
            echo '</ul>';
            echo '<p><a href="' . admin_url('admin.php?page=clinic-management-inventory') . '">' . 
                 __('Manage Inventory', 'clinic-management') . '</a></p>';
            echo '</div>';
        }
    }

    /**
     * Get current page title
     */
    public static function getCurrentPageTitle()
    {
        if (!isset($_GET['page'])) {
            return '';
        }

        $page_titles = array(
            'clinic-management' => __('Dashboard', 'clinic-management'),
            'clinic-management-patients' => __('Patients', 'clinic-management'),
            'clinic-management-appointments' => __('Appointments', 'clinic-management'),
            'clinic-management-consultations' => __('Consultations', 'clinic-management'),
            'clinic-management-prescriptions' => __('Prescriptions', 'clinic-management'),
            'clinic-management-billing' => __('Billing', 'clinic-management'),
            'clinic-management-inventory' => __('Inventory', 'clinic-management'),
            'clinic-management-reports' => __('Reports', 'clinic-management'),
            'clinic-management-settings' => __('Settings', 'clinic-management'),
        );

        $current_page = sanitize_text_field($_GET['page']);
        return isset($page_titles[$current_page]) ? $page_titles[$current_page] : '';
    }

    /**
     * Generate nonce field
     */
    public static function getNonceField($action = 'cms_admin_action')
    {
        return wp_nonce_field($action, 'cms_nonce', true, false);
    }

    /**
     * Generate admin URL
     */
    public static function getAdminUrl($page, $params = array())
    {
        $url = admin_url('admin.php?page=' . $page);
        
        if (!empty($params)) {
            $url .= '&' . http_build_query($params);
        }
        
        return $url;
    }
}