<?php
/**
 * Admin Dashboard View
 * 
 * @package Clinic_Management_System
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get dashboard statistics
$stats = $this->reports->get_dashboard_stats();
?>

<div class="wrap cms-admin-container">
    <div class="cms-header">
        <h1><i class="fas fa-tachometer-alt"></i> Clinic Dashboard</h1>
        <p>Welcome to your clinic management system. Here's an overview of your clinic's performance.</p>
    </div>

    <!-- Statistics Cards -->
    <div class="cms-stats-grid">
        <div class="cms-stat-card">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo esc_html($stats['total_patients']); ?></h3>
                <p>Total Patients</p>
                <small><?php echo esc_html($stats['new_patients_this_month']); ?> new this month</small>
            </div>
        </div>

        <div class="cms-stat-card">
            <div class="stat-icon">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo esc_html($stats['total_appointments']); ?></h3>
                <p>Total Appointments</p>
                <small><?php echo esc_html($stats['appointments_today']); ?> today</small>
            </div>
        </div>

        <div class="cms-stat-card">
            <div class="stat-icon">
                <i class="fas fa-file-medical"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo esc_html($stats['total_prescriptions']); ?></h3>
                <p>Prescriptions</p>
                <small><?php echo esc_html($stats['prescriptions_this_month']); ?> this month</small>
            </div>
        </div>

        <div class="cms-stat-card">
            <div class="stat-icon">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo esc_html($stats['total_revenue']); ?></h3>
                <p>Total Revenue</p>
                <small><?php echo esc_html($stats['revenue_this_month']); ?> this month</small>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="cms-quick-actions">
        <h2>Quick Actions</h2>
        <div class="action-buttons">
            <button class="cms-btn cms-btn-primary" onclick="CMS_Admin.showAddPatientModal()">
                <i class="fas fa-user-plus"></i> Add New Patient
            </button>
            <button class="cms-btn cms-btn-success" onclick="CMS_Admin.showAddAppointmentModal()">
                <i class="fas fa-calendar-plus"></i> Book Appointment
            </button>
            <button class="cms-btn cms-btn-info" onclick="CMS_Admin.showAddPrescriptionModal()">
                <i class="fas fa-prescription"></i> Create Prescription
            </button>
            <button class="cms-btn cms-btn-warning" onclick="CMS_Admin.showAddBillingModal()">
                <i class="fas fa-file-invoice"></i> Generate Invoice
            </button>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="cms-recent-activity">
        <h2>Recent Activity</h2>
        
        <div class="activity-tabs">
            <button class="tab-button active" data-tab="appointments">Recent Appointments</button>
            <button class="tab-button" data-tab="patients">New Patients</button>
            <button class="tab-button" data-tab="prescriptions">Recent Prescriptions</button>
            <button class="tab-button" data-tab="billing">Recent Billing</button>
        </div>

        <div class="tab-content active" id="appointments-tab">
            <div class="cms-table-container">
                <table class="cms-table">
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Doctor</th>
                            <th>Date & Time</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $recent_appointments = $this->appointment->get_recent_appointments(5);
                        if (!empty($recent_appointments)) :
                            foreach ($recent_appointments as $appointment) :
                                $patient = $this->patient->get_patient($appointment->patient_id);
                                $doctor = get_userdata($appointment->doctor_id);
                        ?>
                        <tr>
                            <td>
                                <div class="patient-info">
                                    <strong><?php echo esc_html($patient->first_name . ' ' . $patient->last_name); ?></strong>
                                    <small><?php echo esc_html($patient->phone); ?></small>
                                </div>
                            </td>
                            <td><?php echo esc_html($doctor ? $doctor->display_name : 'N/A'); ?></td>
                            <td>
                                <div class="datetime-info">
                                    <strong><?php echo esc_html(date('M j, Y', strtotime($appointment->appointment_date))); ?></strong>
                                    <small><?php echo esc_html(date('g:i A', strtotime($appointment->appointment_time))); ?></small>
                                </div>
                            </td>
                            <td>
                                <span class="status-badge <?php echo esc_attr($appointment->status); ?>">
                                    <?php echo esc_html(ucfirst($appointment->status)); ?>
                                </span>
                            </td>
                            <td>
                                <button class="cms-btn cms-btn-sm cms-btn-primary" onclick="CMS_Admin.showEditAppointmentModal(<?php echo esc_attr($appointment->id); ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="cms-btn cms-btn-sm cms-btn-danger" onclick="CMS_Admin.deleteAppointment(<?php echo esc_attr($appointment->id); ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php
                            endforeach;
                        else :
                        ?>
                        <tr>
                            <td colspan="5" class="no-data">No recent appointments found.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="tab-content" id="patients-tab">
            <div class="cms-table-container">
                <table class="cms-table">
                    <thead>
                        <tr>
                            <th>Patient ID</th>
                            <th>Name</th>
                            <th>Contact</th>
                            <th>Registration Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $recent_patients = $this->patient->get_patients(1, 5, '');
                        if (!empty($recent_patients)) :
                            foreach ($recent_patients as $patient) :
                        ?>
                        <tr>
                            <td><strong><?php echo esc_html($patient->patient_id); ?></strong></td>
                            <td>
                                <div class="patient-info">
                                    <strong><?php echo esc_html($patient->first_name . ' ' . $patient->last_name); ?></strong>
                                    <small><?php echo esc_html($patient->age . ' years, ' . ucfirst($patient->gender)); ?></small>
                                </div>
                            </td>
                            <td>
                                <div class="contact-info">
                                    <div><?php echo esc_html($patient->phone); ?></div>
                                    <small><?php echo esc_html($patient->email); ?></small>
                                </div>
                            </td>
                            <td><?php echo esc_html(date('M j, Y', strtotime($patient->created_at))); ?></td>
                            <td>
                                <button class="cms-btn cms-btn-sm cms-btn-primary" onclick="CMS_Admin.showEditPatientModal(<?php echo esc_attr($patient->id); ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="cms-btn cms-btn-sm cms-btn-info" onclick="CMS_Admin.viewPatientHistory(<?php echo esc_attr($patient->id); ?>)">
                                    <i class="fas fa-history"></i>
                                </button>
                            </td>
                        </tr>
                        <?php
                            endforeach;
                        else :
                        ?>
                        <tr>
                            <td colspan="5" class="no-data">No recent patients found.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="tab-content" id="prescriptions-tab">
            <div class="cms-table-container">
                <table class="cms-table">
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Doctor</th>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $recent_prescriptions = $this->prescription->get_recent_prescriptions(5);
                        if (!empty($recent_prescriptions)) :
                            foreach ($recent_prescriptions as $prescription) :
                                $patient = $this->patient->get_patient($prescription->patient_id);
                                $doctor = get_userdata($prescription->doctor_id);
                        ?>
                        <tr>
                            <td><?php echo esc_html($patient->first_name . ' ' . $patient->last_name); ?></td>
                            <td><?php echo esc_html($doctor ? $doctor->display_name : 'N/A'); ?></td>
                            <td><?php echo esc_html(date('M j, Y', strtotime($prescription->created_at))); ?></td>
                            <td>
                                <span class="type-badge <?php echo esc_attr($prescription->treatment_type); ?>">
                                    <?php echo esc_html(ucfirst($prescription->treatment_type)); ?>
                                </span>
                            </td>
                            <td>
                                <button class="cms-btn cms-btn-sm cms-btn-primary" onclick="CMS_Admin.showEditPrescriptionModal(<?php echo esc_attr($prescription->id); ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="cms-btn cms-btn-sm cms-btn-info" onclick="CMS_Admin.viewPrescription(<?php echo esc_attr($prescription->id); ?>)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                        <?php
                            endforeach;
                        else :
                        ?>
                        <tr>
                            <td colspan="5" class="no-data">No recent prescriptions found.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="tab-content" id="billing-tab">
            <div class="cms-table-container">
                <table class="cms-table">
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Patient</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $recent_billing = $this->billing->get_billing_records(1, 5, '', '');
                        if (!empty($recent_billing)) :
                            foreach ($recent_billing as $bill) :
                                $patient = $this->patient->get_patient($bill->patient_id);
                        ?>
                        <tr>
                            <td><strong><?php echo esc_html($bill->invoice_number); ?></strong></td>
                            <td><?php echo esc_html($patient->first_name . ' ' . $patient->last_name); ?></td>
                            <td><strong><?php echo esc_html('$' . number_format($bill->total_amount, 2)); ?></strong></td>
                            <td>
                                <span class="status-badge <?php echo esc_attr($bill->payment_status); ?>">
                                    <?php echo esc_html(ucfirst($bill->payment_status)); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html(date('M j, Y', strtotime($bill->created_at))); ?></td>
                            <td>
                                <button class="cms-btn cms-btn-sm cms-btn-primary" onclick="CMS_Admin.showEditBillingModal(<?php echo esc_attr($bill->id); ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="cms-btn cms-btn-sm cms-btn-info" onclick="CMS_Admin.viewInvoice(<?php echo esc_attr($bill->id); ?>)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                        <?php
                            endforeach;
                        else :
                        ?>
                        <tr>
                            <td colspan="6" class="no-data">No recent billing records found.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- System Status -->
    <div class="cms-system-status">
        <h2>System Status</h2>
        <div class="status-grid">
            <div class="status-item">
                <i class="fas fa-database"></i>
                <span>Database: <strong>Connected</strong></span>
            </div>
            <div class="status-item">
                <i class="fas fa-envelope"></i>
                <span>Email: <strong><?php echo wp_mail('test@example.com', 'Test', 'Test message') ? 'Working' : 'Not Working'; ?></strong></span>
            </div>
            <div class="status-item">
                <i class="fas fa-upload"></i>
                <span>File Uploads: <strong>Enabled</strong></span>
            </div>
            <div class="status-item">
                <i class="fas fa-clock"></i>
                <span>Cron Jobs: <strong>Active</strong></span>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Tab functionality
    $('.tab-button').on('click', function() {
        var tabId = $(this).data('tab');
        
        // Remove active class from all tabs and content
        $('.tab-button').removeClass('active');
        $('.tab-content').removeClass('active');
        
        // Add active class to clicked tab and corresponding content
        $(this).addClass('active');
        $('#' + tabId + '-tab').addClass('active');
    });
});
</script>