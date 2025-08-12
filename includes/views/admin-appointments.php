<?php
/**
 * Admin Appointments Management View
 * 
 * @package Clinic_Management_System
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get current page and filters
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$date_filter = isset($_GET['date']) ? sanitize_text_field($_GET['date']) : date('Y-m-d');
$doctor_filter = isset($_GET['doctor']) ? intval($_GET['doctor']) : 0;
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$per_page = 20;

// Get appointments data
$appointments = $this->appointment->get_appointments_by_date($date_filter, $doctor_filter);
$total_appointments = count($appointments);
$total_pages = ceil($total_appointments / $per_page);

// Get doctors for filter
$doctors = get_users(array('role' => 'doctor', 'orderby' => 'display_name'));
?>

<div class="wrap cms-admin-container">
    <div class="cms-header">
        <h1><i class="fas fa-calendar-check"></i> Appointment Management</h1>
        <p>Schedule and manage patient appointments. View calendar, book new appointments, and track appointment status.</p>
    </div>

    <!-- Quick Actions -->
    <div class="cms-quick-actions">
        <button class="cms-btn cms-btn-success" onclick="CMS_Admin.showAddAppointmentModal()">
            <i class="fas fa-calendar-plus"></i> Book New Appointment
        </button>
        <button class="cms-btn cms-btn-info" onclick="CMS_Admin.showBulkBookingModal()">
            <i class="fas fa-calendar-week"></i> Bulk Booking
        </button>
        <button class="cms-btn cms-btn-warning" onclick="CMS_Admin.exportAppointments()">
            <i class="fas fa-download"></i> Export Schedule
        </button>
    </div>

    <!-- Calendar View -->
    <div class="cms-calendar-section">
        <div class="calendar-header">
            <h2>Appointment Calendar</h2>
            <div class="calendar-controls">
                <button class="cms-btn cms-btn-secondary" onclick="CMS_Admin.previousMonth()">
                    <i class="fas fa-chevron-left"></i> Previous
                </button>
                <span class="current-month"><?php echo esc_html(date('F Y', strtotime($date_filter))); ?></span>
                <button class="cms-btn cms-btn-secondary" onclick="CMS_Admin.nextMonth()">
                    Next <i class="fas fa-chevron-right"></i>
                </button>
                <button class="cms-btn cms-btn-primary" onclick="CMS_Admin.today()">Today</button>
            </div>
        </div>
        
        <div id="appointment-calendar" class="cms-calendar"></div>
    </div>

    <!-- Filters and Appointments List -->
    <div class="cms-appointments-section">
        <div class="filters-bar">
            <h2>Appointments List</h2>
            
            <form method="get" class="cms-filters-form">
                <input type="hidden" name="page" value="cms-appointments">
                
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="date_filter">Date:</label>
                        <input type="date" id="date_filter" name="date" value="<?php echo esc_attr($date_filter); ?>" 
                               onchange="this.form.submit()">
                    </div>
                    
                    <div class="filter-group">
                        <label for="doctor_filter">Doctor:</label>
                        <select id="doctor_filter" name="doctor" onchange="this.form.submit()">
                            <option value="">All Doctors</option>
                            <?php foreach ($doctors as $doctor) : ?>
                                <option value="<?php echo esc_attr($doctor->ID); ?>" 
                                        <?php selected($doctor_filter, $doctor->ID); ?>>
                                    <?php echo esc_html($doctor->display_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="status_filter">Status:</label>
                        <select id="status_filter" name="status" onchange="this.form.submit()">
                            <option value="">All Statuses</option>
                            <option value="scheduled" <?php selected($status_filter, 'scheduled'); ?>>Scheduled</option>
                            <option value="confirmed" <?php selected($status_filter, 'confirmed'); ?>>Confirmed</option>
                            <option value="in_progress" <?php selected($status_filter, 'in_progress'); ?>>In Progress</option>
                            <option value="completed" <?php selected($status_filter, 'completed'); ?>>Completed</option>
                            <option value="cancelled" <?php selected($status_filter, 'cancelled'); ?>>Cancelled</option>
                            <option value="no_show" <?php selected($status_filter, 'no_show'); ?>>No Show</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <button type="submit" class="cms-btn cms-btn-primary">
                            <i class="fas fa-filter"></i> Apply Filters
                        </button>
                        <a href="?page=cms-appointments" class="cms-btn cms-btn-secondary">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Appointments Table -->
        <div class="cms-table-container">
            <table class="cms-table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Patient</th>
                        <th>Doctor</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Notes</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($appointments)) : ?>
                        <?php foreach ($appointments as $appointment) : ?>
                            <?php 
                            $patient = $this->patient->get_patient($appointment->patient_id);
                            $doctor = get_userdata($appointment->doctor_id);
                            ?>
                            <tr class="appointment-row status-<?php echo esc_attr($appointment->status); ?>">
                                <td>
                                    <div class="time-info">
                                        <strong><?php echo esc_html(date('g:i A', strtotime($appointment->appointment_time))); ?></strong>
                                        <small><?php echo esc_html(date('M j, Y', strtotime($appointment->appointment_date))); ?></small>
                                        <div class="duration"><?php echo esc_html($appointment->duration); ?> min</div>
                                    </div>
                                </td>
                                <td>
                                    <div class="patient-info">
                                        <div class="patient-name">
                                            <strong><?php echo esc_html($patient->first_name . ' ' . $patient->last_name); ?></strong>
                                            <small><?php echo esc_html($patient->patient_id); ?></small>
                                        </div>
                                        <div class="patient-contact">
                                            <span><?php echo esc_html($patient->phone); ?></span>
                                            <?php if ($patient->email) : ?>
                                                <small><?php echo esc_html($patient->email); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="doctor-info">
                                        <strong><?php echo esc_html($doctor ? $doctor->display_name : 'N/A'); ?></strong>
                                        <?php if ($appointment->room_number) : ?>
                                            <small>Room <?php echo esc_html($appointment->room_number); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="appointment-type">
                                        <span class="type-badge <?php echo esc_attr($appointment->appointment_type); ?>">
                                            <?php echo esc_html(ucfirst($appointment->appointment_type)); ?>
                                        </span>
                                        <?php if ($appointment->is_emergency) : ?>
                                            <span class="emergency-badge">Emergency</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="status-section">
                                        <span class="status-badge <?php echo esc_attr($appointment->status); ?>">
                                            <?php echo esc_html(ucfirst(str_replace('_', ' ', $appointment->status))); ?>
                                        </span>
                                        <?php if ($appointment->status === 'scheduled') : ?>
                                            <small class="time-until">
                                                <?php echo esc_html($this->getTimeUntil($appointment->appointment_date, $appointment->appointment_time)); ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="appointment-notes">
                                        <?php if ($appointment->notes) : ?>
                                            <span class="notes-text" title="<?php echo esc_attr($appointment->notes); ?>">
                                                <?php echo esc_html(wp_trim_words($appointment->notes, 10)); ?>
                                            </span>
                                        <?php else : ?>
                                            <span class="no-notes">No notes</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <?php if ($appointment->status === 'scheduled') : ?>
                                            <button class="cms-btn cms-btn-sm cms-btn-success" 
                                                    onclick="CMS_Admin.confirmAppointment(<?php echo esc_attr($appointment->id); ?>)"
                                                    title="Confirm Appointment">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        <?php endif; ?>
                                        
                                        <?php if (in_array($appointment->status, array('scheduled', 'confirmed'))) : ?>
                                            <button class="cms-btn cms-btn-sm cms-btn-primary" 
                                                    onclick="CMS_Admin.startAppointment(<?php echo esc_attr($appointment->id); ?>)"
                                                    title="Start Appointment">
                                                <i class="fas fa-play"></i>
                                            </button>
                                        <?php endif; ?>
                                        
                                        <?php if ($appointment->status === 'in_progress') : ?>
                                            <button class="cms-btn cms-btn-sm cms-btn-success" 
                                                    onclick="CMS_Admin.completeAppointment(<?php echo esc_attr($appointment->id); ?>)"
                                                    title="Complete Appointment">
                                                <i class="fas fa-flag-checkered"></i>
                                            </button>
                                        <?php endif; ?>
                                        
                                        <button class="cms-btn cms-btn-sm cms-btn-info" 
                                                onclick="CMS_Admin.showEditAppointmentModal(<?php echo esc_attr($appointment->id); ?>)"
                                                title="Edit Appointment">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        
                                        <button class="cms-btn cms-btn-sm cms-btn-warning" 
                                                onclick="CMS_Admin.rescheduleAppointment(<?php echo esc_attr($appointment->id); ?>)"
                                                title="Reschedule">
                                            <i class="fas fa-calendar-alt"></i>
                                        </button>
                                        
                                        <button class="cms-btn cms-btn-sm cms-btn-danger" 
                                                onclick="CMS_Admin.cancelAppointment(<?php echo esc_attr($appointment->id); ?>)"
                                                title="Cancel Appointment">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="7" class="no-data">
                                <div class="empty-state">
                                    <i class="fas fa-calendar-times"></i>
                                    <h3>No Appointments Found</h3>
                                    <p>No appointments match your current filters.</p>
                                    <button class="cms-btn cms-btn-primary" onclick="CMS_Admin.showAddAppointmentModal()">
                                        <i class="fas fa-calendar-plus"></i> Book First Appointment
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Today's Summary -->
        <div class="cms-today-summary">
            <h3>Today's Summary (<?php echo esc_html(date('l, F j, Y', strtotime($date_filter))); ?>)</h3>
            <div class="summary-stats">
                <div class="summary-stat">
                    <span class="stat-number"><?php echo esc_html($this->appointment->get_appointments_by_date($date_filter, null, 'scheduled')); ?></span>
                    <span class="stat-label">Scheduled</span>
                </div>
                <div class="summary-stat">
                    <span class="stat-number"><?php echo esc_html($this->appointment->get_appointments_by_date($date_filter, null, 'confirmed')); ?></span>
                    <span class="stat-label">Confirmed</span>
                </div>
                <div class="summary-stat">
                    <span class="stat-number"><?php echo esc_html($this->appointment->get_appointments_by_date($date_filter, null, 'in_progress')); ?></span>
                    <span class="stat-label">In Progress</span>
                </div>
                <div class="summary-stat">
                    <span class="stat-number"><?php echo esc_html($this->appointment->get_appointments_by_date($date_filter, null, 'completed')); ?></span>
                    <span class="stat-label">Completed</span>
                </div>
                <div class="summary-stat">
                    <span class="stat-number"><?php echo esc_html($this->appointment->get_appointments_by_date($date_filter, null, 'cancelled')); ?></span>
                    <span class="stat-label">Cancelled</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Appointment Modal Templates -->
<div id="appointment-modal-templates" style="display: none;">
    <!-- Add/Edit Appointment Modal -->
    <div id="appointment-modal" class="cms-modal">
        <div class="cms-modal-content">
            <span class="cms-modal-close">&times;</span>
            <h2 id="appointment-modal-title">Book New Appointment</h2>
            
            <form id="appointment-form" class="cms-form">
                <input type="hidden" id="appointment_id" name="appointment_id">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="patient_id">Patient *</label>
                        <select id="patient_id" name="patient_id" required>
                            <option value="">Select Patient</option>
                        </select>
                        <button type="button" class="cms-btn cms-btn-sm cms-btn-secondary" onclick="CMS_Admin.showAddPatientModal()">
                            <i class="fas fa-user-plus"></i> Add New Patient
                        </button>
                    </div>
                    <div class="form-group">
                        <label for="doctor_id">Doctor *</label>
                        <select id="doctor_id" name="doctor_id" required>
                            <option value="">Select Doctor</option>
                            <?php foreach ($doctors as $doctor) : ?>
                                <option value="<?php echo esc_attr($doctor->ID); ?>">
                                    <?php echo esc_html($doctor->display_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="appointment_date">Date *</label>
                        <input type="date" id="appointment_date" name="appointment_date" required 
                               min="<?php echo esc_attr(date('Y-m-d')); ?>">
                    </div>
                    <div class="form-group">
                        <label for="appointment_time">Time *</label>
                        <select id="appointment_time" name="appointment_time" required>
                            <option value="">Select Time</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="duration">Duration (minutes) *</label>
                        <select id="duration" name="duration" required>
                            <option value="15">15 minutes</option>
                            <option value="30" selected>30 minutes</option>
                            <option value="45">45 minutes</option>
                            <option value="60">1 hour</option>
                            <option value="90">1.5 hours</option>
                            <option value="120">2 hours</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="appointment_type">Appointment Type</label>
                        <select id="appointment_type" name="appointment_type">
                            <option value="consultation">Consultation</option>
                            <option value="follow_up">Follow-up</option>
                            <option value="emergency">Emergency</option>
                            <option value="procedure">Procedure</option>
                            <option value="checkup">Checkup</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="room_number">Room Number</label>
                        <input type="text" id="room_number" name="room_number" placeholder="e.g., 101">
                    </div>
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="is_emergency" name="is_emergency">
                            Emergency Appointment
                        </label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="notes">Notes</label>
                    <textarea id="notes" name="notes" rows="3" 
                              placeholder="Any special notes or instructions..."></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="cms-btn cms-btn-primary">
                        <i class="fas fa-save"></i> Save Appointment
                    </button>
                    <button type="button" class="cms-btn cms-btn-secondary" onclick="CMS_Admin.closeModal()">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Initialize appointment management functionality
    CMS_Admin.initializeAppointments();
    
    // Initialize calendar
    CMS_Admin.initializeCalendar();
});
</script>