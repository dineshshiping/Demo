<?php
/**
 * Public Patient Portal View
 * 
 * @package Clinic_Management_System
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check if patient is logged in
$is_logged_in = $this->is_patient_logged_in();
$patient_data = null;

if ($is_logged_in) {
    $patient_data = $this->get_logged_in_patient();
}
?>

<div class="cms-public-container">
    <?php if (!$is_logged_in) : ?>
        <!-- Patient Login Form -->
        <div class="cms-patient-portal">
            <div class="cms-portal-header">
                <h1><i class="fas fa-user-md"></i> Patient Portal</h1>
                <p>Access your medical records, book appointments, and manage your health information.</p>
            </div>
            
            <div class="cms-login-section">
                <div class="login-container">
                    <div class="login-form-wrapper">
                        <h2>Patient Login</h2>
                        <p>Please enter your credentials to access your patient portal.</p>
                        
                        <form id="cms-patient-login-form" class="cms-login-form">
                            <div class="form-group">
                                <label for="username">Username or Email</label>
                                <input type="text" id="username" name="username" required 
                                       placeholder="Enter your username or email">
                            </div>
                            
                            <div class="form-group">
                                <label for="password">Password</label>
                                <input type="password" id="password" name="password" required 
                                       placeholder="Enter your password">
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="cms-btn cms-btn-primary">
                                    <i class="fas fa-sign-in-alt"></i> Login
                                </button>
                                <button type="button" class="cms-btn cms-btn-link cms-forgot-password">
                                    Forgot Password?
                                </button>
                            </div>
                        </form>
                        
                        <div class="login-help">
                            <p><strong>Need help?</strong></p>
                            <ul>
                                <li>Contact the clinic reception for login credentials</li>
                                <li>Call: <a href="tel:+1234567890">+1 (234) 567-890</a></li>
                                <li>Email: <a href="mailto:support@clinic.com">support@clinic.com</a></li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="login-info">
                        <div class="info-card">
                            <i class="fas fa-calendar-check"></i>
                            <h3>Book Appointments</h3>
                            <p>Schedule your next visit with our doctors online. Choose your preferred time and date.</p>
                        </div>
                        
                        <div class="info-card">
                            <i class="fas fa-file-medical"></i>
                            <h3>View Medical Records</h3>
                            <p>Access your complete medical history, prescriptions, and test results anytime.</p>
                        </div>
                        
                        <div class="info-card">
                            <i class="fas fa-credit-card"></i>
                            <h3>Manage Billing</h3>
                            <p>View and pay your medical bills online securely.</p>
                        </div>
                        
                        <div class="info-card">
                            <i class="fas fa-bell"></i>
                            <h3>Stay Updated</h3>
                            <p>Receive appointment reminders and important health updates.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
    <?php else : ?>
        <!-- Patient Dashboard -->
        <div class="cms-patient-portal logged-in">
            <div class="cms-portal-header">
                <div class="patient-welcome">
                    <h1>Welcome back, <?php echo esc_html($patient_data->first_name); ?>!</h1>
                    <p>Here's your health overview and recent activity.</p>
                </div>
                
                <div class="patient-actions">
                    <button class="cms-btn cms-btn-primary" onclick="CMS_Public.showAppointmentBookingModal()">
                        <i class="fas fa-calendar-plus"></i> Book Appointment
                    </button>
                    <button class="cms-btn cms-btn-secondary" onclick="CMS_Public.logout()">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </button>
                </div>
            </div>
            
            <!-- Patient Overview -->
            <div class="cms-patient-overview">
                <div class="overview-card patient-info">
                    <div class="card-header">
                        <h3><i class="fas fa-user"></i> Personal Information</h3>
                        <button class="cms-btn cms-btn-sm cms-btn-primary" onclick="CMS_Public.editProfile()">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                    </div>
                    <div class="card-content">
                        <div class="info-grid">
                            <div class="info-item">
                                <label>Patient ID:</label>
                                <span><?php echo esc_html($patient_data->patient_id); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Full Name:</label>
                                <span><?php echo esc_html($patient_data->first_name . ' ' . $patient_data->last_name); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Age:</label>
                                <span><?php echo esc_html($patient_data->age . ' years'); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Gender:</label>
                                <span><?php echo esc_html(ucfirst($patient_data->gender)); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Blood Group:</label>
                                <span><?php echo esc_html($patient_data->blood_group ?: 'Not specified'); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Phone:</label>
                                <span><?php echo esc_html($patient_data->phone); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Email:</label>
                                <span><?php echo esc_html($patient_data->email ?: 'Not specified'); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Address:</label>
                                <span><?php echo esc_html($patient_data->address ?: 'Not specified'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="overview-card medical-summary">
                    <div class="card-header">
                        <h3><i class="fas fa-notes-medical"></i> Medical Summary</h3>
                    </div>
                    <div class="card-content">
                        <?php if ($patient_data->allergies) : ?>
                            <div class="medical-item allergies">
                                <i class="fas fa-exclamation-triangle"></i>
                                <div class="item-content">
                                    <label>Allergies:</label>
                                    <span class="warning-text"><?php echo esc_html($patient_data->allergies); ?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($patient_data->medical_conditions) : ?>
                            <div class="medical-item conditions">
                                <i class="fas fa-notes-medical"></i>
                                <div class="item-content">
                                    <label>Medical Conditions:</label>
                                    <span><?php echo esc_html($patient_data->medical_conditions); ?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($patient_data->emergency_contact) : ?>
                            <div class="medical-item emergency">
                                <i class="fas fa-ambulance"></i>
                                <div class="item-content">
                                    <label>Emergency Contact:</label>
                                    <span><?php echo esc_html($patient_data->emergency_contact); ?></span>
                                    <?php if ($patient_data->emergency_phone) : ?>
                                        <small><?php echo esc_html($patient_data->emergency_phone); ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!$patient_data->allergies && !$patient_data->medical_conditions && !$patient_data->emergency_contact) : ?>
                            <p class="no-medical-info">No medical information recorded yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Dashboard Tabs -->
            <div class="cms-dashboard-tabs">
                <div class="tab-navigation">
                    <button class="tab-button active" data-tab="appointments">
                        <i class="fas fa-calendar"></i> Appointments
                    </button>
                    <button class="tab-button" data-tab="prescriptions">
                        <i class="fas fa-prescription"></i> Prescriptions
                    </button>
                    <button class="tab-button" data-tab="billing">
                        <i class="fas fa-file-invoice"></i> Billing
                    </button>
                    <button class="tab-button" data-tab="medical-history">
                        <i class="fas fa-history"></i> Medical History
                    </button>
                </div>
                
                <!-- Appointments Tab -->
                <div class="tab-content active" id="appointments-tab">
                    <div class="tab-header">
                        <h3>Your Appointments</h3>
                        <button class="cms-btn cms-btn-primary" onclick="CMS_Public.showAppointmentBookingModal()">
                            <i class="fas fa-calendar-plus"></i> Book New Appointment
                        </button>
                    </div>
                    
                    <div class="appointments-calendar">
                        <div id="patient-calendar" class="cms-calendar"></div>
                    </div>
                    
                    <div class="appointments-list">
                        <h4>Upcoming Appointments</h4>
                        <div class="appointments-grid">
                            <?php
                            $upcoming_appointments = $this->get_patient_appointments($patient_data->id, 'upcoming');
                            if (!empty($upcoming_appointments)) :
                                foreach ($upcoming_appointments as $appointment) :
                                    $doctor = get_userdata($appointment->doctor_id);
                            ?>
                            <div class="appointment-card">
                                <div class="appointment-header">
                                    <span class="appointment-date">
                                        <?php echo esc_html(date('M j, Y', strtotime($appointment->appointment_date))); ?>
                                    </span>
                                    <span class="appointment-time">
                                        <?php echo esc_html(date('g:i A', strtotime($appointment->appointment_time))); ?>
                                    </span>
                                </div>
                                <div class="appointment-details">
                                    <div class="doctor-name">
                                        <i class="fas fa-user-md"></i>
                                        Dr. <?php echo esc_html($doctor ? $doctor->display_name : 'N/A'); ?>
                                    </div>
                                    <div class="appointment-type">
                                        <span class="type-badge <?php echo esc_attr($appointment->appointment_type); ?>">
                                            <?php echo esc_html(ucfirst($appointment->appointment_type)); ?>
                                        </span>
                                    </div>
                                    <div class="appointment-status">
                                        <span class="status-badge <?php echo esc_attr($appointment->status); ?>">
                                            <?php echo esc_html(ucfirst(str_replace('_', ' ', $appointment->status))); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="appointment-actions">
                                    <?php if (in_array($appointment->status, array('scheduled', 'confirmed'))) : ?>
                                        <button class="cms-btn cms-btn-sm cms-btn-warning" 
                                                onclick="CMS_Public.rescheduleAppointment(<?php echo esc_attr($appointment->id); ?>)">
                                            <i class="fas fa-calendar-alt"></i> Reschedule
                                        </button>
                                        <button class="cms-btn cms-btn-sm cms-btn-danger" 
                                                onclick="CMS_Public.cancelAppointment(<?php echo esc_attr($appointment->id); ?>)">
                                            <i class="fas fa-times"></i> Cancel
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php
                                endforeach;
                            else :
                            ?>
                            <div class="no-appointments">
                                <i class="fas fa-calendar-times"></i>
                                <p>No upcoming appointments scheduled.</p>
                                <button class="cms-btn cms-btn-primary" onclick="CMS_Public.showAppointmentBookingModal()">
                                    <i class="fas fa-calendar-plus"></i> Book Your First Appointment
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Prescriptions Tab -->
                <div class="tab-content" id="prescriptions-tab">
                    <div class="tab-header">
                        <h3>Your Prescriptions</h3>
                    </div>
                    
                    <div class="prescriptions-list">
                        <?php
                        $prescriptions = $this->get_patient_prescriptions($patient_data->id);
                        if (!empty($prescriptions)) :
                            foreach ($prescriptions as $prescription) :
                                $doctor = get_userdata($prescription->doctor_id);
                        ?>
                        <div class="prescription-card">
                            <div class="prescription-header">
                                <div class="prescription-date">
                                    <i class="fas fa-calendar"></i>
                                    <?php echo esc_html(date('M j, Y', strtotime($prescription->created_at))); ?>
                                </div>
                                <div class="prescription-doctor">
                                    <i class="fas fa-user-md"></i>
                                    Dr. <?php echo esc_html($doctor ? $doctor->display_name : 'N/A'); ?>
                                </div>
                            </div>
                            
                            <div class="prescription-content">
                                <div class="treatment-type">
                                    <span class="type-badge <?php echo esc_attr($prescription->treatment_type); ?>">
                                        <?php echo esc_html(ucfirst($prescription->treatment_type)); ?>
                                    </span>
                                </div>
                                
                                <?php if ($prescription->diagnosis) : ?>
                                    <div class="diagnosis">
                                        <label>Diagnosis:</label>
                                        <span><?php echo esc_html($prescription->diagnosis); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($prescription->medications) : ?>
                                    <div class="medications">
                                        <label>Medications:</label>
                                        <div class="medication-list">
                                            <?php echo wp_kses_post(nl2br($prescription->medications)); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($prescription->instructions) : ?>
                                    <div class="instructions">
                                        <label>Instructions:</label>
                                        <span><?php echo esc_html($prescription->instructions); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="prescription-actions">
                                <button class="cms-btn cms-btn-sm cms-btn-primary" 
                                        onclick="CMS_Public.downloadPrescription(<?php echo esc_attr($prescription->id); ?>)">
                                    <i class="fas fa-download"></i> Download
                                </button>
                                <button class="cms-btn cms-btn-sm cms-btn-info" 
                                        onclick="CMS_Public.printPrescription(<?php echo esc_attr($prescription->id); ?>)">
                                    <i class="fas fa-print"></i> Print
                                </button>
                            </div>
                        </div>
                        <?php
                            endforeach;
                        else :
                        ?>
                        <div class="no-prescriptions">
                            <i class="fas fa-prescription"></i>
                            <p>No prescriptions found.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Billing Tab -->
                <div class="tab-content" id="billing-tab">
                    <div class="tab-header">
                        <h3>Your Billing Information</h3>
                    </div>
                    
                    <div class="billing-summary">
                        <div class="billing-stats">
                            <div class="stat-item">
                                <span class="stat-number">$<?php echo esc_html(number_format($this->get_total_billed($patient_data->id), 2)); ?></span>
                                <span class="stat-label">Total Billed</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number">$<?php echo esc_html(number_format($this->get_total_paid($patient_data->id), 2)); ?></span>
                                <span class="stat-label">Total Paid</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number">$<?php echo esc_html(number_format($this->get_outstanding_balance($patient_data->id), 2)); ?></span>
                                <span class="stat-label">Outstanding Balance</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="billing-list">
                        <?php
                        $billing_records = $this->get_patient_billing($patient_data->id);
                        if (!empty($billing_records)) :
                            foreach ($billing_records as $bill) :
                        ?>
                        <div class="billing-card">
                            <div class="billing-header">
                                <div class="invoice-number">
                                    <strong>Invoice #<?php echo esc_html($bill->invoice_number); ?></strong>
                                </div>
                                <div class="billing-date">
                                    <?php echo esc_html(date('M j, Y', strtotime($bill->created_at))); ?>
                                </div>
                            </div>
                            
                            <div class="billing-details">
                                <div class="billing-amount">
                                    <span class="amount">$<?php echo esc_html(number_format($bill->total_amount, 2)); ?></span>
                                    <span class="status-badge <?php echo esc_attr($bill->payment_status); ?>">
                                        <?php echo esc_html(ucfirst($bill->payment_status)); ?>
                                    </span>
                                </div>
                                
                                <div class="billing-breakdown">
                                    <div class="breakdown-item">
                                        <label>Consultation Fee:</label>
                                        <span>$<?php echo esc_html(number_format($bill->consultation_fee, 2)); ?></span>
                                    </div>
                                    <?php if ($bill->medicine_cost > 0) : ?>
                                        <div class="breakdown-item">
                                            <label>Medicines:</label>
                                            <span>$<?php echo esc_html(number_format($bill->medicine_cost, 2)); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($bill->other_charges > 0) : ?>
                                        <div class="breakdown-item">
                                            <label>Other Charges:</label>
                                            <span>$<?php echo esc_html(number_format($bill->other_charges, 2)); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="billing-actions">
                                <button class="cms-btn cms-btn-sm cms-btn-primary" 
                                        onclick="CMS_Public.downloadInvoice(<?php echo esc_attr($bill->id); ?>)">
                                    <i class="fas fa-download"></i> Download Invoice
                                </button>
                                <?php if ($bill->payment_status === 'unpaid') : ?>
                                    <button class="cms-btn cms-btn-sm cms-btn-success" 
                                            onclick="CMS_Public.payBill(<?php echo esc_attr($bill->id); ?>)">
                                        <i class="fas fa-credit-card"></i> Pay Now
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php
                            endforeach;
                        else :
                        ?>
                        <div class="no-billing">
                            <i class="fas fa-file-invoice"></i>
                            <p>No billing records found.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Medical History Tab -->
                <div class="tab-content" id="medical-history-tab">
                    <div class="tab-header">
                        <h3>Your Medical History</h3>
                    </div>
                    
                    <div class="medical-history-timeline">
                        <?php
                        $medical_history = $this->get_patient_medical_history($patient_data->id);
                        if (!empty($medical_history)) :
                            foreach ($medical_history as $record) :
                        ?>
                        <div class="timeline-item">
                            <div class="timeline-date">
                                <?php echo esc_html(date('M j, Y', strtotime($record->created_at))); ?>
                            </div>
                            <div class="timeline-content">
                                <div class="timeline-header">
                                    <h4><?php echo esc_html($record->title); ?></h4>
                                    <span class="record-type"><?php echo esc_html(ucfirst($record->type)); ?></span>
                                </div>
                                <div class="timeline-body">
                                    <?php if ($record->description) : ?>
                                        <p><?php echo esc_html($record->description); ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="timeline-actions">
                                    <?php if ($record->file_url) : ?>
                                        <button class="cms-btn cms-btn-sm cms-btn-primary" 
                                                onclick="CMS_Public.downloadFile('<?php echo esc_url($record->file_url); ?>')">
                                            <i class="fas fa-download"></i> Download
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php
                            endforeach;
                        else :
                        ?>
                        <div class="no-medical-history">
                            <i class="fas fa-history"></i>
                            <p>No medical history records found.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    // Initialize patient portal functionality
    CMS_Public.initializePatientPortal();
    
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