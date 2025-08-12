<?php
/**
 * Admin Patients Management View
 * 
 * @package Clinic_Management_System
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get current page and search parameters
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$search_term = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$per_page = 20;

// Get patients data
$patients = $this->patient->get_patients($current_page, $per_page, $search_term);
$total_patients = $this->patient->get_patient_count($search_term);
$total_pages = ceil($total_patients / $per_page);
?>

<div class="wrap cms-admin-container">
    <div class="cms-header">
        <h1><i class="fas fa-users"></i> Patient Management</h1>
        <p>Manage your clinic's patient database. Add new patients, update information, and view medical history.</p>
    </div>

    <!-- Search and Actions Bar -->
    <div class="cms-actions-bar">
        <div class="search-section">
            <form method="get" class="cms-search-form">
                <input type="hidden" name="page" value="cms-patients">
                <div class="search-input-group">
                    <input type="text" name="search" value="<?php echo esc_attr($search_term); ?>" 
                           placeholder="Search by Patient ID, Name, or Phone..." class="cms-search-input">
                    <button type="submit" class="cms-btn cms-btn-primary">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
            </form>
        </div>
        
        <div class="actions-section">
            <button class="cms-btn cms-btn-success" onclick="CMS_Admin.showAddPatientModal()">
                <i class="fas fa-user-plus"></i> Add New Patient
            </button>
            <button class="cms-btn cms-btn-info" onclick="CMS_Admin.exportPatients()">
                <i class="fas fa-download"></i> Export
            </button>
        </div>
    </div>

    <!-- Statistics Summary -->
    <div class="cms-stats-summary">
        <div class="stat-item">
            <span class="stat-number"><?php echo esc_html($total_patients); ?></span>
            <span class="stat-label">Total Patients</span>
        </div>
        <div class="stat-item">
            <span class="stat-number"><?php echo esc_html($this->patient->get_patient_count('', 'this_month')); ?></span>
            <span class="stat-label">New This Month</span>
        </div>
        <div class="stat-item">
            <span class="stat-number"><?php echo esc_html($this->patient->get_patient_count('', 'active')); ?></span>
            <span class="stat-label">Active Patients</span>
        </div>
    </div>

    <!-- Patients Table -->
    <div class="cms-table-container">
        <table class="cms-table">
            <thead>
                <tr>
                    <th>Patient ID</th>
                    <th>Photo</th>
                    <th>Name & Details</th>
                    <th>Contact Information</th>
                    <th>Medical Info</th>
                    <th>Registration</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($patients)) : ?>
                    <?php foreach ($patients as $patient) : ?>
                        <tr>
                            <td>
                                <div class="patient-id">
                                    <strong><?php echo esc_html($patient->patient_id); ?></strong>
                                    <small class="patient-type"><?php echo esc_html(ucfirst($patient->patient_type)); ?></small>
                                </div>
                            </td>
                            <td>
                                <div class="patient-photo">
                                    <?php if ($patient->photo) : ?>
                                        <img src="<?php echo esc_url($patient->photo); ?>" alt="Patient Photo" class="patient-avatar">
                                    <?php else : ?>
                                        <div class="patient-avatar-placeholder">
                                            <i class="fas fa-user"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div class="patient-info">
                                    <div class="patient-name">
                                        <strong><?php echo esc_html($patient->first_name . ' ' . $patient->last_name); ?></strong>
                                        <?php if ($patient->age) : ?>
                                            <small><?php echo esc_html($patient->age . ' years'); ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <div class="patient-details">
                                        <span class="gender-badge <?php echo esc_attr($patient->gender); ?>">
                                            <?php echo esc_html(ucfirst($patient->gender)); ?>
                                        </span>
                                        <?php if ($patient->blood_group) : ?>
                                            <span class="blood-group"><?php echo esc_html($patient->blood_group); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="contact-info">
                                    <div class="phone">
                                        <i class="fas fa-phone"></i>
                                        <a href="tel:<?php echo esc_attr($patient->phone); ?>"><?php echo esc_html($patient->phone); ?></a>
                                    </div>
                                    <?php if ($patient->email) : ?>
                                        <div class="email">
                                            <i class="fas fa-envelope"></i>
                                            <a href="mailto:<?php echo esc_attr($patient->email); ?>"><?php echo esc_html($patient->email); ?></a>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($patient->address) : ?>
                                        <div class="address">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <span><?php echo esc_html($patient->address); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div class="medical-info">
                                    <?php if ($patient->allergies) : ?>
                                        <div class="allergies">
                                            <i class="fas fa-exclamation-triangle"></i>
                                            <span class="warning-text"><?php echo esc_html($patient->allergies); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($patient->medical_conditions) : ?>
                                        <div class="conditions">
                                            <i class="fas fa-notes-medical"></i>
                                            <span><?php echo esc_html(wp_trim_words($patient->medical_conditions, 5)); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($patient->emergency_contact) : ?>
                                        <div class="emergency">
                                            <i class="fas fa-ambulance"></i>
                                            <span><?php echo esc_html($patient->emergency_contact); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div class="registration-info">
                                    <div class="reg-date">
                                        <strong><?php echo esc_html(date('M j, Y', strtotime($patient->created_at))); ?></strong>
                                    </div>
                                    <small><?php echo esc_html(human_time_diff(strtotime($patient->created_at)) . ' ago'); ?></small>
                                    <?php if ($patient->last_visit) : ?>
                                        <div class="last-visit">
                                            <small>Last Visit: <?php echo esc_html(date('M j, Y', strtotime($patient->last_visit))); ?></small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="cms-btn cms-btn-sm cms-btn-primary" 
                                            onclick="CMS_Admin.showEditPatientModal(<?php echo esc_attr($patient->id); ?>)"
                                            title="Edit Patient">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="cms-btn cms-btn-sm cms-btn-info" 
                                            onclick="CMS_Admin.viewPatientHistory(<?php echo esc_attr($patient->id); ?>)"
                                            title="View History">
                                        <i class="fas fa-history"></i>
                                    </button>
                                    <button class="cms-btn cms-btn-sm cms-btn-success" 
                                            onclick="CMS_Admin.showAddAppointmentModal(<?php echo esc_attr($patient->id); ?>)"
                                            title="Book Appointment">
                                        <i class="fas fa-calendar-plus"></i>
                                    </button>
                                    <button class="cms-btn cms-btn-sm cms-btn-warning" 
                                            onclick="CMS_Admin.showAddPrescriptionModal(<?php echo esc_attr($patient->id); ?>)"
                                            title="Create Prescription">
                                        <i class="fas fa-prescription"></i>
                                    </button>
                                    <button class="cms-btn cms-btn-sm cms-btn-danger" 
                                            onclick="CMS_Admin.deletePatient(<?php echo esc_attr($patient->id); ?>)"
                                            title="Delete Patient">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="7" class="no-data">
                            <div class="empty-state">
                                <i class="fas fa-users"></i>
                                <h3>No Patients Found</h3>
                                <p><?php echo $search_term ? 'No patients match your search criteria.' : 'Start by adding your first patient.'; ?></p>
                                <?php if (!$search_term) : ?>
                                    <button class="cms-btn cms-btn-primary" onclick="CMS_Admin.showAddPatientModal()">
                                        <i class="fas fa-user-plus"></i> Add First Patient
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1) : ?>
        <div class="cms-pagination">
            <div class="pagination-info">
                Showing <?php echo esc_html((($current_page - 1) * $per_page) + 1); ?> to 
                <?php echo esc_html(min($current_page * $per_page, $total_patients)); ?> of 
                <?php echo esc_html($total_patients); ?> patients
            </div>
            
            <div class="pagination-links">
                <?php if ($current_page > 1) : ?>
                    <a href="<?php echo esc_url(add_query_arg('paged', $current_page - 1)); ?>" class="page-link">
                        <i class="fas fa-chevron-left"></i> Previous
                    </a>
                <?php endif; ?>
                
                <?php
                $start_page = max(1, $current_page - 2);
                $end_page = min($total_pages, $current_page + 2);
                
                if ($start_page > 1) : ?>
                    <a href="<?php echo esc_url(add_query_arg('paged', 1)); ?>" class="page-link">1</a>
                    <?php if ($start_page > 2) : ?>
                        <span class="page-dots">...</span>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php for ($i = $start_page; $i <= $end_page; $i++) : ?>
                    <a href="<?php echo esc_url(add_query_arg('paged', $i)); ?>" 
                       class="page-link <?php echo $i === $current_page ? 'current' : ''; ?>">
                        <?php echo esc_html($i); ?>
                    </a>
                <?php endfor; ?>
                
                <?php if ($end_page < $total_pages) : ?>
                    <?php if ($end_page < $total_pages - 1) : ?>
                        <span class="page-dots">...</span>
                    <?php endif; ?>
                    <a href="<?php echo esc_url(add_query_arg('paged', $total_pages)); ?>" class="page-link">
                        <?php echo esc_html($total_pages); ?>
                    </a>
                <?php endif; ?>
                
                <?php if ($current_page < $total_pages) : ?>
                    <a href="<?php echo esc_url(add_query_arg('paged', $current_page + 1)); ?>" class="page-link">
                        Next <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Bulk Actions -->
    <div class="cms-bulk-actions">
        <div class="bulk-actions-left">
            <select id="bulk-action-selector">
                <option value="">Bulk Actions</option>
                <option value="export">Export Selected</option>
                <option value="delete">Delete Selected</option>
            </select>
            <button class="cms-btn cms-btn-secondary" onclick="CMS_Admin.applyBulkAction()">Apply</button>
        </div>
        
        <div class="bulk-actions-right">
            <label class="checkbox-label">
                <input type="checkbox" id="select-all-patients" onchange="CMS_Admin.toggleSelectAll(this)">
                Select All
            </label>
        </div>
    </div>
</div>

<!-- Patient Modal Templates -->
<div id="patient-modal-templates" style="display: none;">
    <!-- Add/Edit Patient Modal -->
    <div id="patient-modal" class="cms-modal">
        <div class="cms-modal-content">
            <span class="cms-modal-close">&times;</span>
            <h2 id="patient-modal-title">Add New Patient</h2>
            
            <form id="patient-form" class="cms-form">
                <input type="hidden" id="patient_id" name="patient_id">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name *</label>
                        <input type="text" id="first_name" name="first_name" required>
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name *</label>
                        <input type="text" id="last_name" name="last_name" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="date_of_birth">Date of Birth</label>
                        <input type="date" id="date_of_birth" name="date_of_birth">
                    </div>
                    <div class="form-group">
                        <label for="gender">Gender</label>
                        <select id="gender" name="gender">
                            <option value="">Select Gender</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="blood_group">Blood Group</label>
                        <select id="blood_group" name="blood_group">
                            <option value="">Select Blood Group</option>
                            <option value="A+">A+</option>
                            <option value="A-">A-</option>
                            <option value="B+">B+</option>
                            <option value="B-">B-</option>
                            <option value="AB+">AB+</option>
                            <option value="AB-">AB-</option>
                            <option value="O+">O+</option>
                            <option value="O-">O-</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="phone">Phone Number *</label>
                        <input type="tel" id="phone" name="phone" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" rows="3"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="emergency_contact">Emergency Contact</label>
                        <input type="text" id="emergency_contact" name="emergency_contact">
                    </div>
                    <div class="form-group">
                        <label for="emergency_phone">Emergency Phone</label>
                        <input type="tel" id="emergency_phone" name="emergency_phone">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="medical_conditions">Medical Conditions</label>
                    <textarea id="medical_conditions" name="medical_conditions" rows="3" 
                              placeholder="List any existing medical conditions..."></textarea>
                </div>
                
                <div class="form-group">
                    <label for="allergies">Allergies</label>
                    <textarea id="allergies" name="allergies" rows="2" 
                              placeholder="List any known allergies..."></textarea>
                </div>
                
                <div class="form-group">
                    <label for="photo">Patient Photo</label>
                    <input type="file" id="photo" name="photo" accept="image/*" class="cms-file-upload">
                    <div class="file-name"></div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="cms-btn cms-btn-primary">
                        <i class="fas fa-save"></i> Save Patient
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
    // Initialize patient management functionality
    CMS_Admin.initializePatients();
});
</script>