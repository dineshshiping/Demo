/**
 * Clinic Management System - Frontend JavaScript
 */

jQuery(document).ready(function($) {
    'use strict';

    // Global variables
    window.CMS_Frontend = {
        ajax_url: cms_ajax.ajax_url,
        nonce: cms_ajax.nonce,
        text_domain: cms_ajax.text_domain
    };

    // Patient login form handling
    $('#patient-login-form').on('submit', function(e) {
        e.preventDefault();
        patientLogin();
    });

    function patientLogin() {
        var formData = $('#patient-login-form').serialize();
        formData += '&action=cms_patient_login&nonce=' + CMS_Frontend.nonce;

        $.ajax({
            url: CMS_Frontend.ajax_url,
            type: 'POST',
            data: formData,
            beforeSend: function() {
                $('#login-btn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Logging in...');
            },
            success: function(response) {
                if (response.success) {
                    showFrontendAlert('success', 'Login successful! Redirecting...');
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showFrontendAlert('danger', response.message || 'Login failed');
                    $('#login-btn').prop('disabled', false).html('Login');
                }
            },
            error: function() {
                showFrontendAlert('danger', 'Network error occurred');
                $('#login-btn').prop('disabled', false).html('Login');
            }
        });
    }

    // Patient registration form handling
    $('#patient-register-form').on('submit', function(e) {
        e.preventDefault();
        patientRegister();
    });

    function patientRegister() {
        var formData = $('#patient-register-form').serialize();
        formData += '&action=cms_patient_register&nonce=' + CMS_Frontend.nonce;

        $.ajax({
            url: CMS_Frontend.ajax_url,
            type: 'POST',
            data: formData,
            beforeSend: function() {
                $('#register-btn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Registering...');
            },
            success: function(response) {
                if (response.success) {
                    showFrontendAlert('success', 'Registration successful! Please login with your credentials.');
                    $('#patient-register-form')[0].reset();
                    $('#register-tab').removeClass('active');
                    $('#login-tab').addClass('active');
                    $('#register').removeClass('show active');
                    $('#login').addClass('show active');
                } else {
                    showFrontendAlert('danger', response.message || 'Registration failed');
                }
                $('#register-btn').prop('disabled', false).html('Register');
            },
            error: function() {
                showFrontendAlert('danger', 'Network error occurred');
                $('#register-btn').prop('disabled', false).html('Register');
            }
        });
    }

    // Book appointment form handling
    $('#book-appointment-form').on('submit', function(e) {
        e.preventDefault();
        bookAppointment();
    });

    function bookAppointment() {
        var formData = $('#book-appointment-form').serialize();
        formData += '&action=cms_book_appointment_portal&nonce=' + CMS_Frontend.nonce;

        $.ajax({
            url: CMS_Frontend.ajax_url,
            type: 'POST',
            data: formData,
            beforeSend: function() {
                $('#book-appointment-btn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Booking...');
            },
            success: function(response) {
                if (response.success) {
                    showFrontendAlert('success', 'Appointment booked successfully!');
                    $('#book-appointment-form')[0].reset();
                    loadPatientAppointments();
                } else {
                    showFrontendAlert('danger', response.message || 'Failed to book appointment');
                }
                $('#book-appointment-btn').prop('disabled', false).html('Book Appointment');
            },
            error: function() {
                showFrontendAlert('danger', 'Network error occurred');
                $('#book-appointment-btn').prop('disabled', false).html('Book Appointment');
            }
        });
    }

    // Load available slots when doctor or date changes
    $('#appointment-doctor, #appointment-date').on('change', function() {
        loadAvailableSlots();
    });

    function loadAvailableSlots() {
        var doctorId = $('#appointment-doctor').val();
        var date = $('#appointment-date').val();

        if (doctorId && date) {
            $.ajax({
                url: CMS_Frontend.ajax_url,
                type: 'POST',
                data: {
                    action: 'cms_get_available_slots',
                    doctor_id: doctorId,
                    date: date,
                    nonce: CMS_Frontend.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var timeSelect = $('#appointment-time');
                        timeSelect.empty().append('<option value="">Select Time</option>');
                        
                        if (response.data && response.data.length > 0) {
                            $.each(response.data, function(index, slot) {
                                timeSelect.append('<option value="' + slot.time + '">' + slot.time + '</option>');
                            });
                        } else {
                            timeSelect.append('<option value="">No slots available</option>');
                        }
                    }
                }
            });
        }
    }

    // Load patient appointments
    function loadPatientAppointments() {
        $.ajax({
            url: CMS_Frontend.ajax_url,
            type: 'POST',
            data: {
                action: 'cms_get_patient_appointments',
                nonce: CMS_Frontend.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#appointments-list').html(response.data);
                } else {
                    $('#appointments-list').html('<div class="alert alert-info">No appointments found</div>');
                }
            }
        });
    }

    // Load patient reports
    function loadPatientReports() {
        $.ajax({
            url: CMS_Frontend.ajax_url,
            type: 'POST',
            data: {
                action: 'cms_get_patient_reports',
                nonce: CMS_Frontend.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#reports-list').html(response.data);
                } else {
                    $('#reports-list').html('<div class="alert alert-info">No reports found</div>');
                }
            }
        });
    }

    // Load patient bills
    function loadPatientBills() {
        $.ajax({
            url: CMS_Frontend.ajax_url,
            type: 'POST',
            data: {
                action: 'cms_get_patient_bills',
                nonce: CMS_Frontend.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#bills-list').html(response.data);
                } else {
                    $('#bills-list').html('<div class="alert alert-info">No bills found</div>');
                }
            }
        });
    }

    // Load patient prescriptions
    function loadPatientPrescriptions() {
        $.ajax({
            url: CMS_Frontend.ajax_url,
            type: 'POST',
            data: {
                action: 'cms_get_patient_prescriptions',
                nonce: CMS_Frontend.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#prescriptions-list').html(response.data);
                } else {
                    $('#prescriptions-list').html('<div class="alert alert-info">No prescriptions found</div>');
                }
            }
        });
    }

    // Load patient data when dashboard is active
    if ($('.patient-dashboard').length) {
        loadPatientAppointments();
        loadPatientReports();
        loadPatientBills();
        loadPatientPrescriptions();
    }

    // Cancel appointment
    $(document).on('click', '.cancel-appointment', function() {
        var appointmentId = $(this).data('appointment-id');
        if (confirm('Are you sure you want to cancel this appointment?')) {
            cancelAppointment(appointmentId);
        }
    });

    function cancelAppointment(appointmentId) {
        $.ajax({
            url: CMS_Frontend.ajax_url,
            type: 'POST',
            data: {
                action: 'cms_cancel_appointment',
                appointment_id: appointmentId,
                nonce: CMS_Frontend.nonce
            },
            success: function(response) {
                if (response.success) {
                    showFrontendAlert('success', 'Appointment cancelled successfully');
                    loadPatientAppointments();
                } else {
                    showFrontendAlert('danger', response.message || 'Failed to cancel appointment');
                }
            }
        });
    }

    // Download report
    $(document).on('click', '.download-report', function() {
        var reportUrl = $(this).data('report-url');
        window.open(reportUrl, '_blank');
    });

    // View prescription
    $(document).on('click', '.view-prescription', function() {
        var prescriptionId = $(this).data('prescription-id');
        window.open('?cms_action=view_prescription&id=' + prescriptionId, '_blank');
    });

    // Pay bill
    $(document).on('click', '.pay-bill', function() {
        var billId = $(this).data('bill-id');
        var amount = $(this).data('amount');
        
        if (confirm('Pay $' + amount + ' for this bill?')) {
            // In a real implementation, this would integrate with a payment gateway
            showFrontendAlert('info', 'Payment gateway integration would be implemented here');
        }
    });

    // Tab switching
    $('.nav-tabs a').on('click', function(e) {
        e.preventDefault();
        $(this).tab('show');
        
        // Load data when tab is activated
        var target = $(this).attr('href');
        if (target === '#appointments') {
            loadPatientAppointments();
        } else if (target === '#reports') {
            loadPatientReports();
        } else if (target === '#bills') {
            loadPatientBills();
        } else if (target === '#prescriptions') {
            loadPatientPrescriptions();
        }
    });

    // Form validation
    $('form').on('submit', function() {
        var isValid = true;
        $(this).find('input[required], select[required], textarea[required]').each(function() {
            if (!$(this).val()) {
                $(this).addClass('is-invalid');
                isValid = false;
            } else {
                $(this).removeClass('is-invalid');
            }
        });
        return isValid;
    });

    // Clear validation on input
    $('input, select, textarea').on('input change', function() {
        $(this).removeClass('is-invalid');
    });

    // Date picker for appointment booking
    if ($('#appointment-date').length) {
        $('#appointment-date').attr('min', new Date().toISOString().split('T')[0]);
    }

    // Password visibility toggle
    $('.toggle-password').on('click', function() {
        var target = $(this).data('target');
        var input = $(target);
        var icon = $(this).find('i');
        
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            input.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });

    // Patient logout
    $('#patient-logout').on('click', function(e) {
        e.preventDefault();
        if (confirm('Are you sure you want to logout?')) {
            $.ajax({
                url: CMS_Frontend.ajax_url,
                type: 'POST',
                data: {
                    action: 'cms_patient_logout',
                    nonce: CMS_Frontend.nonce
                },
                success: function() {
                    location.reload();
                }
            });
        }
    });

    // Utility functions
    function showFrontendAlert(type, message) {
        var alertHtml = '<div class="alert alert-' + type + ' alert-dismissible fade show" role="alert">' +
                       message +
                       '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                       '</div>';
        
        $('.cms-frontend-alerts').html(alertHtml);
        setTimeout(function() {
            $('.cms-frontend-alerts .alert').fadeOut();
        }, 5000);
    }

    // Initialize tooltips
    if ($.fn.tooltip) {
        $('[data-bs-toggle="tooltip"]').tooltip();
    }

    // Auto-hide alerts
    setTimeout(function() {
        $('.alert').fadeOut();
    }, 5000);
});