/**
 * Clinic Management System - Admin JavaScript
 */

(function($) {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function() {
        CMS_Admin.init();
    });

    var CMS_Admin = {
        
        init: function() {
            this.bindEvents();
            this.initializeDatePickers();
            this.initializeColorPickers();
            this.initializeModals();
            this.initializeSearch();
        },

        bindEvents: function() {
            // Patient management
            $(document).on('click', '.add-patient-btn', this.showAddPatientModal);
            $(document).on('click', '.edit-patient-btn', this.showEditPatientModal);
            $(document).on('click', '.delete-patient-btn', this.deletePatient);
            $(document).on('submit', '#add-patient-form', this.addPatient);
            $(document).on('submit', '#edit-patient-form', this.updatePatient);

            // Appointment management
            $(document).on('click', '.add-appointment-btn', this.showAddAppointmentModal);
            $(document).on('click', '.edit-appointment-btn', this.showEditAppointmentModal);
            $(document).on('click', '.delete-appointment-btn', this.deleteAppointment);
            $(document).on('submit', '#add-appointment-form', this.addAppointment);
            $(document).on('submit', '#edit-appointment-form', this.updateAppointment);

            // Prescription management
            $(document).on('click', '.add-prescription-btn', this.showAddPrescriptionModal);
            $(document).on('submit', '#add-prescription-form', this.addPrescription);

            // Billing management
            $(document).on('click', '.add-billing-btn', this.showAddBillingModal);
            $(document).on('submit', '#add-billing-form', this.addBilling);
            $(document).on('click', '.record-payment-btn', this.showRecordPaymentModal);

            // Inventory management
            $(document).on('click', '.add-medicine-btn', this.showAddMedicineModal);
            $(document).on('click', '.edit-medicine-btn', this.showEditMedicineModal);
            $(document).on('submit', '#add-medicine-form', this.addMedicine);
            $(document).on('submit', '#edit-medicine-form', this.updateMedicine);

            // General
            $(document).on('click', '.cms-modal-close', this.closeModal);
            $(document).on('click', '.cms-modal', function(e) {
                if (e.target === this) {
                    CMS_Admin.closeModal();
                }
            });
        },

        initializeDatePickers: function() {
            $('.cms-datepicker').datepicker({
                dateFormat: 'yy-mm-dd',
                changeMonth: true,
                changeYear: true,
                yearRange: '-100:+10'
            });
        },

        initializeColorPickers: function() {
            $('.cms-colorpicker').wpColorPicker();
        },

        initializeModals: function() {
            // Modal functionality is handled by showModal method
        },

        initializeSearch: function() {
            // Initialize search functionality
            $('.cms-search-input').on('input', this.debounce(this.performSearch, 300));
        },

        // Utility Functions
        debounce: function(func, wait) {
            var timeout;
            return function executedFunction() {
                var later = function() {
                    timeout = null;
                    func.apply(this, arguments);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },

        showModal: function(content, title) {
            var modal = $('<div class="cms-modal">' +
                '<div class="cms-modal-content">' +
                '<span class="cms-modal-close">&times;</span>' +
                '<h2>' + (title || 'Modal') + '</h2>' +
                content +
                '</div>' +
                '</div>');
            
            $('body').append(modal);
            modal.fadeIn(200);
        },

        closeModal: function() {
            $('.cms-modal').fadeOut(200, function() {
                $(this).remove();
            });
        },

        showNotification: function(message, type) {
            var notification = $('<div class="cms-notification ' + (type || 'info') + '">' +
                '<div class="cms-notification-header">' +
                '<span class="cms-notification-title">' + (type === 'success' ? 'Success' : type === 'error' ? 'Error' : 'Info') + '</span>' +
                '<button class="cms-notification-close">&times;</button>' +
                '</div>' +
                '<div class="cms-notification-message">' + message + '</div>' +
                '</div>');
            
            $('body').append(notification);
            notification.addClass('show');
            
            setTimeout(function() {
                notification.removeClass('show');
                setTimeout(function() {
                    notification.remove();
                }, 300);
            }, 5000);
        },

        // Patient Management
        showAddPatientModal: function() {
            var content = `
                <form id="add-patient-form">
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
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email">
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone *</label>
                            <input type="tel" id="phone" name="phone" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="date_of_birth">Date of Birth</label>
                            <input type="text" id="date_of_birth" name="date_of_birth" class="cms-datepicker">
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
                    <div class="form-row">
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
                        <div class="form-group">
                            <label for="allergies">Allergies</label>
                            <textarea id="allergies" name="allergies" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="medical_history">Medical History</label>
                        <textarea id="medical_history" name="medical_history" rows="4"></textarea>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="cms-btn cms-btn-primary">Add Patient</button>
                        <button type="button" class="cms-btn cms-btn-secondary" onclick="CMS_Admin.closeModal()">Cancel</button>
                    </div>
                </form>
            `;
            
            CMS_Admin.showModal(content, 'Add New Patient');
            CMS_Admin.initializeDatePickers();
        },

        addPatient: function(e) {
            e.preventDefault();
            
            var formData = new FormData(e.target);
            formData.append('action', 'cms_add_patient');
            formData.append('nonce', cms_ajax.nonce);
            
            $.ajax({
                url: cms_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        CMS_Admin.showNotification(response.data.message, 'success');
                        CMS_Admin.closeModal();
                        location.reload();
                    } else {
                        CMS_Admin.showNotification(response.data, 'error');
                    }
                },
                error: function() {
                    CMS_Admin.showNotification('An error occurred. Please try again.', 'error');
                }
            });
        },

        // Appointment Management
        showAddAppointmentModal: function() {
            var content = `
                <form id="add-appointment-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="patient_id">Patient *</label>
                            <select id="patient_id" name="patient_id" required>
                                <option value="">Select Patient</option>
                                <!-- Patient options will be populated via AJAX -->
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="doctor_id">Doctor *</label>
                            <select id="doctor_id" name="doctor_id" required>
                                <option value="">Select Doctor</option>
                                <!-- Doctor options will be populated via AJAX -->
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="appointment_date">Date *</label>
                            <input type="text" id="appointment_date" name="appointment_date" class="cms-datepicker" required>
                        </div>
                        <div class="form-group">
                            <label for="appointment_time">Time *</label>
                            <input type="time" id="appointment_time" name="appointment_time" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="duration">Duration (minutes)</label>
                            <input type="number" id="duration" name="duration" value="30" min="15" max="120">
                        </div>
                        <div class="form-group">
                            <label for="notes">Notes</label>
                            <textarea id="notes" name="notes" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="cms-btn cms-btn-primary">Book Appointment</button>
                        <button type="button" class="cms-btn cms-btn-secondary" onclick="CMS_Admin.closeModal()">Cancel</button>
                    </div>
                </form>
            `;
            
            CMS_Admin.showModal(content, 'Book New Appointment');
            CMS_Admin.initializeDatePickers();
            CMS_Admin.loadPatients();
            CMS_Admin.loadDoctors();
        },

        addAppointment: function(e) {
            e.preventDefault();
            
            var formData = new FormData(e.target);
            formData.append('action', 'cms_add_appointment');
            formData.append('nonce', cms_ajax.nonce);
            
            $.ajax({
                url: cms_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        CMS_Admin.showNotification(response.data.message, 'success');
                        CMS_Admin.closeModal();
                        location.reload();
                    } else {
                        CMS_Admin.showNotification(response.data, 'error');
                    }
                },
                error: function() {
                    CMS_Admin.showNotification('An error occurred. Please try again.', 'error');
                }
            });
        },

        // Prescription Management
        showAddPrescriptionModal: function() {
            var content = `
                <form id="add-prescription-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="patient_id">Patient *</label>
                            <select id="patient_id" name="patient_id" required>
                                <option value="">Select Patient</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="doctor_id">Doctor *</label>
                            <select id="doctor_id" name="doctor_id" required>
                                <option value="">Select Doctor</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="diagnosis">Diagnosis *</label>
                        <textarea id="diagnosis" name="diagnosis" rows="3" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="symptoms">Symptoms</label>
                        <textarea id="symptoms" name="symptoms" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="treatment_plan">Treatment Plan</label>
                        <textarea id="treatment_plan" name="treatment_plan" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="medicines">Medicines</label>
                        <textarea id="medicines" name="medicines" rows="4" placeholder="List medicines with dosages"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="dosage_instructions">Dosage Instructions</label>
                        <textarea id="dosage_instructions" name="dosage_instructions" rows="3"></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="follow_up_date">Follow-up Date</label>
                            <input type="text" id="follow_up_date" name="follow_up_date" class="cms-datepicker">
                        </div>
                        <div class="form-group">
                            <label for="notes">Additional Notes</label>
                            <textarea id="notes" name="notes" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="cms-btn cms-btn-primary">Create Prescription</button>
                        <button type="button" class="cms-btn cms-btn-secondary" onclick="CMS_Admin.closeModal()">Cancel</button>
                    </div>
                </form>
            `;
            
            CMS_Admin.showModal(content, 'Create New Prescription');
            CMS_Admin.initializeDatePickers();
            CMS_Admin.loadPatients();
            CMS_Admin.loadDoctors();
        },

        addPrescription: function(e) {
            e.preventDefault();
            
            var formData = new FormData(e.target);
            formData.append('action', 'cms_add_prescription');
            formData.append('nonce', cms_ajax.nonce);
            
            $.ajax({
                url: cms_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        CMS_Admin.showNotification(response.data.message, 'success');
                        CMS_Admin.closeModal();
                        location.reload();
                    } else {
                        CMS_Admin.showNotification(response.data, 'error');
                    }
                },
                error: function() {
                    CMS_Admin.showNotification('An error occurred. Please try again.', 'error');
                }
            });
        },

        // Billing Management
        showAddBillingModal: function() {
            var content = `
                <form id="add-billing-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="patient_id">Patient *</label>
                            <select id="patient_id" name="patient_id" required>
                                <option value="">Select Patient</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="consultation_fee">Consultation Fee</label>
                            <input type="number" id="consultation_fee" name="consultation_fee" step="0.01" min="0" value="50.00">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="medicine_cost">Medicine Cost</label>
                            <input type="number" id="medicine_cost" name="medicine_cost" step="0.01" min="0" value="0.00">
                        </div>
                        <div class="form-group">
                            <label for="other_charges">Other Charges</label>
                            <input type="number" id="other_charges" name="other_charges" step="0.01" min="0" value="0.00">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="paid_amount">Amount Paid</label>
                            <input type="number" id="paid_amount" name="paid_amount" step="0.01" min="0" value="0.00">
                        </div>
                        <div class="form-group">
                            <label for="payment_method">Payment Method</label>
                            <select id="payment_method" name="payment_method">
                                <option value="">Select Method</option>
                                <option value="cash">Cash</option>
                                <option value="card">Card</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="online">Online</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="due_date">Due Date</label>
                            <input type="text" id="due_date" name="due_date" class="cms-datepicker">
                        </div>
                        <div class="form-group">
                            <label for="notes">Notes</label>
                            <textarea id="notes" name="notes" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="cms-btn cms-btn-primary">Create Invoice</button>
                        <button type="button" class="cms-btn cms-btn-secondary" onclick="CMS_Admin.closeModal()">Cancel</button>
                    </div>
                </form>
            `;
            
            CMS_Admin.showModal(content, 'Create New Invoice');
            CMS_Admin.initializeDatePickers();
            CMS_Admin.loadPatients();
        },

        addBilling: function(e) {
            e.preventDefault();
            
            var formData = new FormData(e.target);
            formData.append('action', 'cms_add_billing');
            formData.append('nonce', cms_ajax.nonce);
            
            $.ajax({
                url: cms_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        CMS_Admin.showNotification(response.data.message, 'success');
                        CMS_Admin.closeModal();
                        location.reload();
                    } else {
                        CMS_Admin.showNotification(response.data, 'error');
                    }
                },
                error: function() {
                    CMS_Admin.showNotification('An error occurred. Please try again.', 'error');
                }
            });
        },

        // Inventory Management
        showAddMedicineModal: function() {
            var content = `
                <form id="add-medicine-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="medicine_name">Medicine Name *</label>
                            <input type="text" id="medicine_name" name="medicine_name" required>
                        </div>
                        <div class="form-group">
                            <label for="generic_name">Generic Name</label>
                            <input type="text" id="generic_name" name="generic_name">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="category">Category</label>
                            <select id="category" name="category">
                                <option value="">Select Category</option>
                                <option value="antibiotics">Antibiotics</option>
                                <option value="painkillers">Painkillers</option>
                                <option value="vitamins">Vitamins</option>
                                <option value="diabetes">Diabetes</option>
                                <option value="hypertension">Hypertension</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="manufacturer">Manufacturer</label>
                            <input type="text" id="manufacturer" name="manufacturer">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="strength">Strength</label>
                            <input type="text" id="strength" name="strength" placeholder="e.g., 500mg">
                        </div>
                        <div class="form-group">
                            <label for="form">Form</label>
                            <select id="form" name="form">
                                <option value="">Select Form</option>
                                <option value="tablet">Tablet</option>
                                <option value="capsule">Capsule</option>
                                <option value="syrup">Syrup</option>
                                <option value="injection">Injection</option>
                                <option value="cream">Cream</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="current_stock">Current Stock *</label>
                            <input type="number" id="current_stock" name="current_stock" min="0" required>
                        </div>
                        <div class="form-group">
                            <label for="minimum_stock">Minimum Stock</label>
                            <input type="number" id="minimum_stock" name="minimum_stock" min="0" value="10">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="unit_price">Unit Price</label>
                            <input type="number" id="unit_price" name="unit_price" step="0.01" min="0" value="0.00">
                        </div>
                        <div class="form-group">
                            <label for="expiry_date">Expiry Date</label>
                            <input type="text" id="expiry_date" name="expiry_date" class="cms-datepicker">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="location">Storage Location</label>
                        <input type="text" id="location" name="location" placeholder="e.g., Shelf A, Drawer 1">
                    </div>
                    <div class="form-group">
                        <label for="notes">Notes</label>
                        <textarea id="notes" name="notes" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="cms-btn cms-btn-primary">Add Medicine</button>
                        <button type="button" class="cms-btn cms-btn-secondary" onclick="CMS_Admin.closeModal()">Cancel</button>
                    </div>
                </form>
            `;
            
            CMS_Admin.showModal(content, 'Add New Medicine');
            CMS_Admin.initializeDatePickers();
        },

        addMedicine: function(e) {
            e.preventDefault();
            
            var formData = new FormData(e.target);
            formData.append('action', 'cms_add_medicine');
            formData.append('nonce', cms_ajax.nonce);
            
            $.ajax({
                url: cms_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        CMS_Admin.showNotification('Medicine added successfully', 'success');
                        CMS_Admin.closeModal();
                        location.reload();
                    } else {
                        CMS_Admin.showNotification(response.data, 'error');
                    }
                },
                error: function() {
                    CMS_Admin.showNotification('An error occurred. Please try again.', 'error');
                }
            });
        },

        // Data Loading Functions
        loadPatients: function() {
            $.ajax({
                url: cms_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'cms_search_patients',
                    nonce: cms_ajax.nonce,
                    search_term: ''
                },
                success: function(response) {
                    if (response.success) {
                        var select = $('#patient_id');
                        select.empty().append('<option value="">Select Patient</option>');
                        
                        response.data.forEach(function(patient) {
                            select.append('<option value="' + patient.patient_id + '">' + 
                                patient.first_name + ' ' + patient.last_name + ' (' + patient.patient_id + ')</option>');
                        });
                    }
                }
            });
        },

        loadDoctors: function() {
            $.ajax({
                url: cms_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'cms_get_doctors',
                    nonce: cms_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var select = $('#doctor_id');
                        select.empty().append('<option value="">Select Doctor</option>');
                        
                        response.data.forEach(function(doctor) {
                            select.append('<option value="' + doctor.ID + '">Dr. ' + doctor.display_name + '</option>');
                        });
                    }
                }
            });
        },

        // Search Functionality
        performSearch: function() {
            var searchTerm = $(this).val();
            var table = $(this).closest('.cms-table-container').find('.cms-table tbody tr');
            
            if (searchTerm.length === 0) {
                table.show();
                return;
            }
            
            table.each(function() {
                var text = $(this).text().toLowerCase();
                if (text.indexOf(searchTerm.toLowerCase()) > -1) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        },

        // Delete Functions
        deletePatient: function() {
            if (!confirm(cms_ajax.strings.confirm_delete)) {
                return;
            }
            
            var patientId = $(this).data('id');
            var row = $(this).closest('tr');
            
            $.ajax({
                url: cms_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'cms_delete_patient',
                    nonce: cms_ajax.nonce,
                    patient_id: patientId
                },
                success: function(response) {
                    if (response.success) {
                        CMS_Admin.showNotification('Patient deleted successfully', 'success');
                        row.fadeOut(300, function() {
                            $(this).remove();
                        });
                    } else {
                        CMS_Admin.showNotification(response.data, 'error');
                    }
                },
                error: function() {
                    CMS_Admin.showNotification('An error occurred. Please try again.', 'error');
                }
            });
        },

        deleteAppointment: function() {
            if (!confirm(cms_ajax.strings.confirm_delete)) {
                return;
            }
            
            var appointmentId = $(this).data('id');
            var row = $(this).closest('tr');
            
            $.ajax({
                url: cms_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'cms_delete_appointment',
                    nonce: cms_ajax.nonce,
                    appointment_id: appointmentId
                },
                success: function(response) {
                    if (response.success) {
                        CMS_Admin.showNotification('Appointment deleted successfully', 'success');
                        row.fadeOut(300, function() {
                            $(this).remove();
                        });
                    } else {
                        CMS_Admin.showNotification(response.data, 'error');
                    }
                },
                error: function() {
                    CMS_Admin.showNotification('An error occurred. Please try again.', 'error');
                }
            });
        }

    };

})(jQuery);