<?php
/**
 * Dashboard template
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}

// Check permissions
if (!CMS_User_Roles::canAccessCMS()) {
    wp_die(__('Permission denied', 'clinic-management'));
}

// Get statistics
$patient_stats = CMS_Patients::getPatientStats();
$appointment_stats = CMS_Appointments::getAppointmentStats();
$consultation_stats = CMS_Consultations::getConsultationStats();
$billing_stats = CMS_Billing::getBillingStats();
$inventory_stats = CMS_Inventory::getInventoryStats();

$currency_symbol = CMS_Database::getSetting('currency_symbol', '$');
$clinic_name = CMS_Database::getSetting('clinic_name', 'Your Clinic Name');
?>

<div class="cms-dashboard">
    <div class="cms-header mb-4">
        <h1 class="cms-page-title">
            <i class="fas fa-tachometer-alt"></i>
            <?php echo sprintf(__('Welcome to %s Dashboard', 'clinic-management'), esc_html($clinic_name)); ?>
        </h1>
        <p class="cms-subtitle text-muted"><?php _e('Quick overview of your clinic operations', 'clinic-management'); ?></p>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card cms-stat-card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h3 class="card-title"><?php echo number_format($patient_stats['total'] ?? 0); ?></h3>
                            <p class="card-text"><?php _e('Total Patients', 'clinic-management'); ?></p>
                        </div>
                        <div class="cms-stat-icon">
                            <i class="fas fa-users fa-2x"></i>
                        </div>
                    </div>
                    <small><?php echo sprintf(__('%d new this month', 'clinic-management'), $patient_stats['new_this_month'] ?? 0); ?></small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card cms-stat-card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h3 class="card-title"><?php echo number_format($appointment_stats['today'] ?? 0); ?></h3>
                            <p class="card-text"><?php _e("Today's Appointments", 'clinic-management'); ?></p>
                        </div>
                        <div class="cms-stat-icon">
                            <i class="fas fa-calendar-check fa-2x"></i>
                        </div>
                    </div>
                    <small><?php echo sprintf(__('%d this month', 'clinic-management'), $appointment_stats['this_month'] ?? 0); ?></small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card cms-stat-card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h3 class="card-title"><?php echo esc_html($currency_symbol . number_format($billing_stats['monthly_revenue'] ?? 0, 2)); ?></h3>
                            <p class="card-text"><?php _e('Monthly Revenue', 'clinic-management'); ?></p>
                        </div>
                        <div class="cms-stat-icon">
                            <i class="fas fa-dollar-sign fa-2x"></i>
                        </div>
                    </div>
                    <small><?php echo esc_html($currency_symbol . number_format($billing_stats['today_revenue'] ?? 0, 2)); ?> <?php _e('today', 'clinic-management'); ?></small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card cms-stat-card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h3 class="card-title"><?php echo number_format($inventory_stats['low_stock_count'] ?? 0); ?></h3>
                            <p class="card-text"><?php _e('Low Stock Alerts', 'clinic-management'); ?></p>
                        </div>
                        <div class="cms-stat-icon">
                            <i class="fas fa-exclamation-triangle fa-2x"></i>
                        </div>
                    </div>
                    <small><?php echo sprintf(__('%d total medicines', 'clinic-management'), $inventory_stats['total_medicines'] ?? 0); ?></small>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-bolt"></i>
                        <?php _e('Quick Actions', 'clinic-management'); ?>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-2 col-sm-4 col-6 mb-3">
                            <a href="<?php echo CMS_Admin_Menu::getAdminUrl('clinic-management-patients', array('action' => 'add')); ?>" class="btn btn-outline-primary btn-block">
                                <i class="fas fa-user-plus d-block mb-2"></i>
                                <?php _e('Add Patient', 'clinic-management'); ?>
                            </a>
                        </div>
                        <div class="col-md-2 col-sm-4 col-6 mb-3">
                            <a href="<?php echo CMS_Admin_Menu::getAdminUrl('clinic-management-appointments', array('action' => 'add')); ?>" class="btn btn-outline-success btn-block">
                                <i class="fas fa-calendar-plus d-block mb-2"></i>
                                <?php _e('Schedule Appointment', 'clinic-management'); ?>
                            </a>
                        </div>
                        <div class="col-md-2 col-sm-4 col-6 mb-3">
                            <a href="<?php echo CMS_Admin_Menu::getAdminUrl('clinic-management-consultations', array('action' => 'add')); ?>" class="btn btn-outline-info btn-block">
                                <i class="fas fa-stethoscope d-block mb-2"></i>
                                <?php _e('New Consultation', 'clinic-management'); ?>
                            </a>
                        </div>
                        <div class="col-md-2 col-sm-4 col-6 mb-3">
                            <a href="<?php echo CMS_Admin_Menu::getAdminUrl('clinic-management-prescriptions', array('action' => 'add')); ?>" class="btn btn-outline-danger btn-block">
                                <i class="fas fa-prescription-bottle d-block mb-2"></i>
                                <?php _e('Create Prescription', 'clinic-management'); ?>
                            </a>
                        </div>
                        <div class="col-md-2 col-sm-4 col-6 mb-3">
                            <a href="<?php echo CMS_Admin_Menu::getAdminUrl('clinic-management-billing', array('action' => 'add')); ?>" class="btn btn-outline-warning btn-block">
                                <i class="fas fa-file-invoice-dollar d-block mb-2"></i>
                                <?php _e('Generate Bill', 'clinic-management'); ?>
                            </a>
                        </div>
                        <div class="col-md-2 col-sm-4 col-6 mb-3">
                            <a href="<?php echo CMS_Admin_Menu::getAdminUrl('clinic-management-inventory', array('action' => 'add')); ?>" class="btn btn-outline-secondary btn-block">
                                <i class="fas fa-plus-square d-block mb-2"></i>
                                <?php _e('Add Medicine', 'clinic-management'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="row">
        <!-- Recent Appointments -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-calendar-alt"></i>
                        <?php _e("Today's Appointments", 'clinic-management'); ?>
                    </h5>
                    <a href="<?php echo CMS_Admin_Menu::getAdminUrl('clinic-management-appointments'); ?>" class="btn btn-sm btn-outline-primary">
                        <?php _e('View All', 'clinic-management'); ?>
                    </a>
                </div>
                <div class="card-body">
                    <div id="todays-appointments-list">
                        <?php _e('Loading...', 'clinic-management'); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Patients -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-user-injured"></i>
                        <?php _e('Recent Patients', 'clinic-management'); ?>
                    </h5>
                    <a href="<?php echo CMS_Admin_Menu::getAdminUrl('clinic-management-patients'); ?>" class="btn btn-sm btn-outline-primary">
                        <?php _e('View All', 'clinic-management'); ?>
                    </a>
                </div>
                <div class="card-body">
                    <div id="recent-patients-list">
                        <?php _e('Loading...', 'clinic-management'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Widgets Row -->
    <div class="row mt-4">
        <!-- Pending Bills -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php _e('Pending Bills', 'clinic-management'); ?>
                    </h5>
                    <a href="<?php echo CMS_Admin_Menu::getAdminUrl('clinic-management-billing', array('status' => 'unpaid')); ?>" class="btn btn-sm btn-outline-warning">
                        <?php _e('View All', 'clinic-management'); ?>
                    </a>
                </div>
                <div class="card-body">
                    <div id="pending-bills-list">
                        <?php _e('Loading...', 'clinic-management'); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Low Stock Items -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-box"></i>
                        <?php _e('Low Stock Items', 'clinic-management'); ?>
                    </h5>
                    <a href="<?php echo CMS_Admin_Menu::getAdminUrl('clinic-management-inventory', array('filter' => 'low_stock')); ?>" class="btn btn-sm btn-outline-danger">
                        <?php _e('View All', 'clinic-management'); ?>
                    </a>
                </div>
                <div class="card-body">
                    <div id="low-stock-list">
                        <?php _e('Loading...', 'clinic-management'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Load dashboard widgets
    loadTodaysAppointments();
    loadRecentPatients();
    loadPendingBills();
    loadLowStockItems();

    function loadTodaysAppointments() {
        // Implementation for loading today's appointments via AJAX
        $('#todays-appointments-list').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> <?php _e("Loading appointments...", "clinic-management"); ?></div>');
    }

    function loadRecentPatients() {
        // Implementation for loading recent patients via AJAX
        $('#recent-patients-list').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> <?php _e("Loading patients...", "clinic-management"); ?></div>');
    }

    function loadPendingBills() {
        // Implementation for loading pending bills via AJAX
        $('#pending-bills-list').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> <?php _e("Loading bills...", "clinic-management"); ?></div>');
    }

    function loadLowStockItems() {
        // Implementation for loading low stock items via AJAX
        $('#low-stock-list').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> <?php _e("Loading inventory...", "clinic-management"); ?></div>');
    }
});
</script>