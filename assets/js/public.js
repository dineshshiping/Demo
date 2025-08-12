/**
 * Clinic Management System - Public JavaScript
 */

(function($) {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function() {
        CMS_Public.init();
    });

    var CMS_Public = {
        
        init: function() {
            this.bindEvents();
            this.initializePatientLogin();
            this.initializeAppointmentBooking();
            this.initializeCalendar();
        },

        bindEvents: function() {
            // Patient login
            $(document).on('submit', '#cms-patient-login-form', this.handlePatientLogin);
            $(document).on('click', '.cms-forgot-password', this.showForgotPasswordModal);
            
            // Appointment booking
            $(document).on('submit', '#cms-appointment-booking-form', this.handleAppointmentBooking);
            $(document).on('change', '#doctor_id', this.loadAvailableSlots);
            $(document).on('change', '#appointment_date', this.loadAvailableSlots);
            
            // Patient portal actions
            $(document).on('click', '.cancel-appointment-btn', this.cancelAppointment);
            $(document).on('click', '.download-prescription-btn', this.downloadPrescription);
            $(document).on('click', '.download-invoice-btn', this.downloadInvoice);
            $(document).on('click', '.logout-btn', this.logout);
            
            // General
            $(document).on('click', '.cms-notification-close', this.closeNotification);
        },

        initializePatientLogin: function() {
            // Check if there's a login form on the page
            if ($('#cms-patient-login-form').length) {
                // Initialize any login-specific functionality
            }
        },

        initializeAppointmentBooking: function() {
            // Check if there's an appointment booking form on the page
            if ($('#cms-appointment-booking-form').length) {
                // Initialize date pickers and time selectors
                $('.cms-datepicker').datepicker({
                    dateFormat: 'yy-mm-dd',
                    minDate: 0,
                    changeMonth: true,
                    changeYear: true
                });
            }
        },

        initializeCalendar: function() {
            // Initialize FullCalendar if it exists
            if ($.fn.fullCalendar) {
                $('.cms-calendar').fullCalendar({
                    header: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'month,agendaWeek,agendaDay'
                    },
                    defaultView: 'month',
                    editable: false,
                    eventLimit: true,
                    events: function(start, end, timezone, callback) {
                        // Load events via AJAX
                        CMS_Public.loadCalendarEvents(start, end, callback);
                    },
                    eventClick: function(event) {
                        CMS_Public.showEventDetails(event);
                    }
                });
            }
        },

        // Patient Login
        handlePatientLogin: function(e) {
            e.preventDefault();
            
            var form = $(this);
            var submitBtn = form.find('button[type="submit"]');
            var originalText = submitBtn.text();
            
            // Show loading state
            submitBtn.text('Logging in...').prop('disabled', true);
            
            var formData = new FormData(this);
            formData.append('action', 'cms_patient_login');
            formData.append('nonce', cms_public_ajax.nonce);
            
            $.ajax({
                url: cms_public_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        CMS_Public.showNotification(response.data.message, 'success');
                        setTimeout(function() {
                            window.location.href = response.data.redirect_url;
                        }, 1000);
                    } else {
                        CMS_Public.showNotification(response.data, 'error');
                        submitBtn.text(originalText).prop('disabled', false);
                    }
                },
                error: function() {
                    CMS_Public.showNotification('An error occurred. Please try again.', 'error');
                    submitBtn.text(originalText).prop('disabled', false);
                }
            });
        },

        showForgotPasswordModal: function(e) {
            e.preventDefault();
            
            var content = `
                <div class="cms-forgot-password-modal">
                    <h3>Reset Password</h3>
                    <p>Enter your email address and we'll send you a new password.</p>
                    <form id="cms-forgot-password-form">
                        <div class="form-group">
                            <label for="reset_email">Email Address</label>
                            <input type="email" id="reset_email" name="email" required>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="cms-btn cms-btn-primary">Reset Password</button>
                            <button type="button" class="cms-btn cms-btn-secondary" onclick="CMS_Public.closeModal()">Cancel</button>
                        </div>
                    </form>
                </div>
            `;
            
            CMS_Public.showModal(content, 'Forgot Password');
            
            // Bind form submission
            $('#cms-forgot-password-form').on('submit', CMS_Public.handleForgotPassword);
        },

        handleForgotPassword: function(e) {
            e.preventDefault();
            
            var form = $(this);
            var submitBtn = form.find('button[type="submit"]');
            var originalText = submitBtn.text();
            
            submitBtn.text('Sending...').prop('disabled', true);
            
            var formData = new FormData(this);
            formData.append('action', 'cms_forgot_password');
            formData.append('nonce', cms_public_ajax.nonce);
            
            $.ajax({
                url: cms_public_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        CMS_Public.showNotification('Password reset email sent successfully. Please check your email.', 'success');
                        CMS_Public.closeModal();
                    } else {
                        CMS_Public.showNotification(response.data, 'error');
                    }
                    submitBtn.text(originalText).prop('disabled', false);
                },
                error: function() {
                    CMS_Public.showNotification('An error occurred. Please try again.', 'error');
                    submitBtn.text(originalText).prop('disabled', false);
                }
            });
        },

        // Appointment Booking
        handleAppointmentBooking: function(e) {
            e.preventDefault();
            
            var form = $(this);
            var submitBtn = form.find('button[type="submit"]');
            var originalText = submitBtn.text();
            
            submitBtn.text('Booking...').prop('disabled', true);
            
            var formData = new FormData(this);
            formData.append('action', 'cms_book_appointment');
            formData.append('nonce', cms_public_ajax.nonce);
            
            $.ajax({
                url: cms_public_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        CMS_Public.showNotification(response.data.message, 'success');
                        form[0].reset();
                        // Refresh available slots
                        CMS_Public.loadAvailableSlots();
                    } else {
                        CMS_Public.showNotification(response.data, 'error');
                    }
                    submitBtn.text(originalText).prop('disabled', false);
                },
                error: function() {
                    CMS_Public.showNotification('An error occurred. Please try again.', 'error');
                    submitBtn.text(originalText).prop('disabled', false);
                }
            });
        },

        loadAvailableSlots: function() {
            var doctorId = $('#doctor_id').val();
            var date = $('#appointment_date').val();
            var timeSelect = $('#appointment_time');
            
            if (!doctorId || !date) {
                return;
            }
            
            $.ajax({
                url: cms_public_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'cms_get_available_slots',
                    nonce: cms_public_ajax.nonce,
                    doctor_id: doctorId,
                    date: date
                },
                success: function(response) {
                    if (response.success) {
                        timeSelect.empty().append('<option value="">Select Time</option>');
                        
                        response.data.forEach(function(slot) {
                            var time = new Date('2000-01-01T' + slot);
                            var timeString = time.toLocaleTimeString('en-US', {
                                hour: 'numeric',
                                minute: '2-digit',
                                hour12: true
                            });
                            timeSelect.append('<option value="' + slot + '">' + timeString + '</option>');
                        });
                    }
                }
            });
        },

        // Calendar Functions
        loadCalendarEvents: function(start, end, callback) {
            // This would load events for the logged-in patient
            var patientId = $('.cms-patient-portal').data('patient-id');
            
            if (!patientId) {
                callback([]);
                return;
            }
            
            $.ajax({
                url: cms_public_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'cms_get_patient_calendar',
                    nonce: cms_public_ajax.nonce,
                    patient_id: patientId,
                    start_date: start.format('YYYY-MM-DD'),
                    end_date: end.format('YYYY-MM-DD')
                },
                success: function(response) {
                    if (response.success) {
                        callback(response.data);
                    } else {
                        callback([]);
                    }
                },
                error: function() {
                    callback([]);
                }
            });
        },

        showEventDetails: function(event) {
            var content = `
                <div class="cms-event-details">
                    <h3>${event.title}</h3>
                    <p><strong>Date:</strong> ${moment(event.start).format('MMMM Do YYYY')}</p>
                    <p><strong>Time:</strong> ${moment(event.start).format('h:mm A')} - ${moment(event.end).format('h:mm A')}</p>
                    <p><strong>Status:</strong> <span class="status-badge ${event.status}">${event.status}</span></p>
                    <div class="event-actions">
                        <button class="cms-btn cms-btn-primary" onclick="CMS_Public.rescheduleAppointment(${event.id})">Reschedule</button>
                        <button class="cms-btn cms-btn-danger" onclick="CMS_Public.cancelAppointment(${event.id})">Cancel</button>
                    </div>
                </div>
            `;
            
            CMS_Public.showModal(content, 'Appointment Details');
        },

        // Patient Portal Actions
        cancelAppointment: function() {
            var appointmentId = $(this).data('id');
            
            if (!confirm(cms_public_ajax.strings.confirm_cancel)) {
                return;
            }
            
            $.ajax({
                url: cms_public_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'cms_cancel_appointment',
                    nonce: cms_public_ajax.nonce,
                    appointment_id: appointmentId
                },
                success: function(response) {
                    if (response.success) {
                        CMS_Public.showNotification('Appointment cancelled successfully', 'success');
                        location.reload();
                    } else {
                        CMS_Public.showNotification(response.data, 'error');
                    }
                },
                error: function() {
                    CMS_Public.showNotification('An error occurred. Please try again.', 'error');
                }
            });
        },

        downloadPrescription: function() {
            var prescriptionId = $(this).data('id');
            var url = $(this).data('url');
            
            if (url) {
                // Open in new tab for download
                window.open(url, '_blank');
            } else {
                CMS_Public.showNotification('Download link not available', 'error');
            }
        },

        downloadInvoice: function() {
            var invoiceId = $(this).data('id');
            var url = $(this).data('url');
            
            if (url) {
                // Open in new tab for download
                window.open(url, '_blank');
            } else {
                CMS_Public.showNotification('Download link not available', 'error');
            }
        },

        logout: function() {
            if (confirm('Are you sure you want to logout?')) {
                $.ajax({
                    url: cms_public_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'cms_patient_logout',
                        nonce: cms_public_ajax.nonce
                    },
                    success: function() {
                        location.reload();
                    }
                });
            }
        },

        // Utility Functions
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

        closeNotification: function() {
            $(this).closest('.cms-notification').remove();
        },

        // Form Validation
        validateForm: function(form) {
            var isValid = true;
            var requiredFields = form.find('[required]');
            
            requiredFields.each(function() {
                var field = $(this);
                var value = field.val().trim();
                
                if (!value) {
                    field.addClass('error');
                    isValid = false;
                } else {
                    field.removeClass('error');
                }
            });
            
            return isValid;
        },

        // Date and Time Utilities
        formatDate: function(dateString) {
            return moment(dateString).format('MMMM Do YYYY');
        },

        formatTime: function(timeString) {
            var time = new Date('2000-01-01T' + timeString);
            return time.toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
        },

        // Search and Filter
        initializeSearch: function() {
            $('.cms-search-input').on('input', this.debounce(function() {
                var searchTerm = $(this).val();
                CMS_Public.performSearch(searchTerm);
            }, 300));
        },

        performSearch: function(searchTerm) {
            var container = $('.cms-search-container');
            var items = container.find('.searchable-item');
            
            if (searchTerm.length === 0) {
                items.show();
                return;
            }
            
            items.each(function() {
                var text = $(this).text().toLowerCase();
                if (text.indexOf(searchTerm.toLowerCase()) > -1) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        },

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

        // File Upload
        initializeFileUpload: function() {
            $('.cms-file-upload').on('change', function() {
                var file = this.files[0];
                var fileName = file ? file.name : 'No file selected';
                $(this).next('.file-name').text(fileName);
            });
        },

        // Print Functionality
        printElement: function(element) {
            var printWindow = window.open('', '_blank');
            var elementToPrint = $(element).clone();
            
            // Remove print buttons and other non-printable elements
            elementToPrint.find('.no-print').remove();
            
            printWindow.document.write(`
                <html>
                    <head>
                        <title>Print</title>
                        <style>
                            body { font-family: Arial, sans-serif; margin: 20px; }
                            .cms-btn, .no-print { display: none; }
                            @media print { body { margin: 0; } }
                        </style>
                    </head>
                    <body>
                        ${elementToPrint.html()}
                    </body>
                </html>
            `);
            
            printWindow.document.close();
            printWindow.focus();
            printWindow.print();
            printWindow.close();
        }

    };

})(jQuery);