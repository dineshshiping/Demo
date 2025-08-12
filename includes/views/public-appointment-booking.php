<?php
/**
 * Public Appointment Booking Form View
 * 
 * @package Clinic_Management_System
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get available doctors
$doctors = get_users(array('role' => 'doctor', 'orderby' => 'display_name'));

// Get clinic working hours
$working_hours = get_option('cms_working_hours', array(
    'monday' => array('start' => '09:00', 'end' => '17:00'),
    'tuesday' => array('start' => '09:00', 'end' => '17:00'),
    'wednesday' => array('start' => '09:00', 'end' => '17:00'),
    'thursday' => array('start' => '09:00', 'end' => '17:00'),
    'friday' => array('start' => '09:00', 'end' => '17:00'),
    'saturday' => array('start' => '09:00', 'end' => '13:00'),
    'sunday' => array('start' => '', 'end' => '')
));
?>

<div class="cms-public-container">
    <div class="cms-appointment-booking">
        <div class="booking-header">
            <h1><i class="fas fa-calendar-plus"></i> Book Your Appointment</h1>
            <p>Schedule your visit with our healthcare professionals. Choose your preferred doctor, date, and time.</p>
        </div>
        
        <!-- Booking Form -->
        <div class="booking-form-container">
            <form id="cms-appointment-booking-form" class="cms-booking-form">
                <div class="form-section">
                    <h3><i class="fas fa-user"></i> Patient Information</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name">First Name *</label>
                            <input type="text" id="first_name" name="first_name" required 
                                   placeholder="Enter your first name">
                        </div>
                        <div class="form-group">
                            <label for="last_name">Last Name *</label>
                            <input type="text" id="last_name" name="last_name" required 
                                   placeholder="Enter your last name">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone">Phone Number *</label>
                            <input type="tel" id="phone" name="phone" required 
                                   placeholder="Enter your phone number">
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" 
                                   placeholder="Enter your email address">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="date_of_birth">Date of Birth</label>
                        <input type="date" id="date_of_birth" name="date_of_birth">
                    </div>
                    
                    <div class="form-row">
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
                </div>
                
                <div class="form-section">
                    <h3><i class="fas fa-calendar-alt"></i> Appointment Details</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="doctor_id">Select Doctor *</label>
                            <select id="doctor_id" name="doctor_id" required>
                                <option value="">Choose a Doctor</option>
                                <?php foreach ($doctors as $doctor) : ?>
                                    <option value="<?php echo esc_attr($doctor->ID); ?>">
                                        Dr. <?php echo esc_html($doctor->display_name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="appointment_type">Appointment Type *</label>
                            <select id="appointment_type" name="appointment_type" required>
                                <option value="">Select Type</option>
                                <option value="consultation">General Consultation</option>
                                <option value="follow_up">Follow-up Visit</option>
                                <option value="checkup">Regular Checkup</option>
                                <option value="emergency">Emergency</option>
                                <option value="procedure">Medical Procedure</option>
                                <option value="vaccination">Vaccination</option>
                                <option value="lab_test">Lab Test</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="appointment_date">Preferred Date *</label>
                            <input type="date" id="appointment_date" name="appointment_date" required 
                                   min="<?php echo esc_attr(date('Y-m-d')); ?>"
                                   class="cms-datepicker">
                        </div>
                        <div class="form-group">
                            <label for="appointment_time">Preferred Time *</label>
                            <select id="appointment_time" name="appointment_time" required>
                                <option value="">Select Time</option>
                                <!-- Available time slots will be loaded dynamically -->
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="duration">Duration</label>
                            <select id="duration" name="duration">
                                <option value="15">15 minutes</option>
                                <option value="30" selected>30 minutes</option>
                                <option value="45">45 minutes</option>
                                <option value="60">1 hour</option>
                                <option value="90">1.5 hours</option>
                                <option value="120">2 hours</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="appointment_notes">Special Notes or Symptoms</label>
                        <textarea id="appointment_notes" name="appointment_notes" rows="4" 
                                  placeholder="Please describe your symptoms, concerns, or any special requirements..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="is_emergency" name="is_emergency">
                            This is an emergency appointment
                        </label>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3><i class="fas fa-notes-medical"></i> Medical Information</h3>
                    
                    <div class="form-group">
                        <label for="medical_conditions">Existing Medical Conditions</label>
                        <textarea id="medical_conditions" name="medical_conditions" rows="3" 
                                  placeholder="List any existing medical conditions, if any..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="allergies">Known Allergies</label>
                        <textarea id="allergies" name="allergies" rows="2" 
                                  placeholder="List any known allergies to medications, foods, etc..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="current_medications">Current Medications</label>
                        <textarea id="current_medications" name="current_medications" rows="2" 
                                  placeholder="List any medications you are currently taking..."></textarea>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3><i class="fas fa-shield-alt"></i> Privacy & Consent</h3>
                    
                    <div class="form-group">
                        <label class="checkbox-label required">
                            <input type="checkbox" id="privacy_consent" name="privacy_consent" required>
                            I consent to the collection, use, and disclosure of my personal health information for the purpose of providing healthcare services.
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-label required">
                            <input type="checkbox" id="terms_consent" name="terms_consent" required>
                            I agree to the clinic's terms of service and appointment policies.
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="marketing_consent" name="marketing_consent">
                            I would like to receive appointment reminders and health updates via SMS/Email.
                        </label>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="cms-btn cms-btn-primary cms-btn-large">
                        <i class="fas fa-calendar-check"></i> Book Appointment
                    </button>
                    <button type="reset" class="cms-btn cms-btn-secondary cms-btn-large">
                        <i class="fas fa-undo"></i> Reset Form
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Booking Information -->
        <div class="booking-info">
            <div class="info-section">
                <h3><i class="fas fa-info-circle"></i> Important Information</h3>
                <ul>
                    <li>Please arrive 10-15 minutes before your scheduled appointment time</li>
                    <li>Bring a valid ID and your insurance card (if applicable)</li>
                    <li>Bring a list of current medications and medical history</li>
                    <li>If you need to cancel or reschedule, please do so at least 24 hours in advance</li>
                    <li>Emergency appointments are subject to availability and may incur additional charges</li>
                </ul>
            </div>
            
            <div class="info-section">
                <h3><i class="fas fa-clock"></i> Clinic Hours</h3>
                <div class="clinic-hours">
                    <?php foreach ($working_hours as $day => $hours) : ?>
                        <?php if (!empty($hours['start']) && !empty($hours['end'])) : ?>
                            <div class="hour-item">
                                <span class="day"><?php echo esc_html(ucfirst($day)); ?></span>
                                <span class="time"><?php echo esc_html($hours['start']); ?> - <?php echo esc_html($hours['end']); ?></span>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="info-section">
                <h3><i class="fas fa-phone"></i> Need Help?</h3>
                <div class="contact-info">
                    <p>If you have any questions or need assistance with booking:</p>
                    <div class="contact-item">
                        <i class="fas fa-phone"></i>
                        <span>Call: <a href="tel:+1234567890">+1 (234) 567-890</a></span>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <span>Email: <a href="mailto:appointments@clinic.com">appointments@clinic.com</a></span>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>Visit: 123 Medical Center Dr, City, State 12345</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Appointment Status Checker -->
        <div class="appointment-checker">
            <h3><i class="fas fa-search"></i> Check Appointment Status</h3>
            <p>Already have an appointment? Check its status using your phone number or appointment ID.</p>
            
            <form class="status-check-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="check_phone">Phone Number</label>
                        <input type="tel" id="check_phone" name="check_phone" 
                               placeholder="Enter your phone number">
                    </div>
                    <div class="form-group">
                        <label for="check_appointment_id">Appointment ID</label>
                        <input type="text" id="check_appointment_id" name="check_appointment_id" 
                               placeholder="Enter appointment ID (optional)">
                    </div>
                    <div class="form-group">
                        <button type="button" class="cms-btn cms-btn-info" onclick="CMS_Public.checkAppointmentStatus()">
                            <i class="fas fa-search"></i> Check Status
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Appointment Confirmation Modal -->
<div id="appointment-confirmation-modal" class="cms-modal" style="display: none;">
    <div class="cms-modal-content">
        <span class="cms-modal-close">&times;</span>
        <h2>Appointment Confirmation</h2>
        
        <div class="confirmation-details">
            <div class="confirmation-success">
                <i class="fas fa-check-circle"></i>
                <h3>Appointment Booked Successfully!</h3>
                <p>Your appointment has been scheduled. You will receive a confirmation email and SMS shortly.</p>
            </div>
            
            <div class="appointment-summary">
                <h4>Appointment Details:</h4>
                <div class="summary-item">
                    <label>Appointment ID:</label>
                    <span id="confirmation-id"></span>
                </div>
                <div class="summary-item">
                    <label>Patient:</label>
                    <span id="confirmation-patient"></span>
                </div>
                <div class="summary-item">
                    <label>Doctor:</label>
                    <span id="confirmation-doctor"></span>
                </div>
                <div class="summary-item">
                    <label>Date & Time:</label>
                    <span id="confirmation-datetime"></span>
                </div>
                <div class="summary-item">
                    <label>Type:</label>
                    <span id="confirmation-type"></span>
                </div>
            </div>
            
            <div class="next-steps">
                <h4>Next Steps:</h4>
                <ul>
                    <li>Check your email for appointment confirmation</li>
                    <li>Save the appointment details</li>
                    <li>Set a reminder for your appointment</li>
                    <li>Prepare any required documents or information</li>
                </ul>
            </div>
            
            <div class="confirmation-actions">
                <button class="cms-btn cms-btn-primary" onclick="CMS_Public.printConfirmation()">
                    <i class="fas fa-print"></i> Print Confirmation
                </button>
                <button class="cms-btn cms-btn-secondary" onclick="CMS_Public.closeModal()">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Initialize appointment booking functionality
    CMS_Public.initializeAppointmentBooking();
    
    // Initialize date picker
    $('.cms-datepicker').datepicker({
        dateFormat: 'yy-mm-dd',
        minDate: 0,
        changeMonth: true,
        changeYear: true,
        beforeShowDay: function(date) {
            // Disable weekends and holidays
            var day = date.getDay();
            var isWeekend = (day === 0 || day === 6);
            return [!isWeekend, '', isWeekend ? 'Weekend' : ''];
        }
    });
    
    // Load available time slots when doctor or date changes
    $('#doctor_id, #appointment_date').on('change', function() {
        CMS_Public.loadAvailableSlots();
    });
});
</script>