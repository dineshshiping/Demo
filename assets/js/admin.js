/**
 * Clinic Management System - Admin JavaScript
 */

jQuery(document).ready(function($) {
    'use strict';

    // Global variables
    window.CMS = {
        ajax_url: cms_ajax.ajax_url,
        nonce: cms_ajax.nonce,
        text_domain: cms_ajax.text_domain
    };

    // Dashboard functions
    if ($('.cms-dashboard').length) {
        loadDashboardWidgets();
    }

    // Load dashboard widgets
    function loadDashboardWidgets() {
        loadTodaysAppointments();
        loadRecentPatients();
        loadPendingBills();
        loadLowStockItems();
    }

    function loadTodaysAppointments() {
        $('#todays-appointments-list').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading appointments...</div>');
        
        $.ajax({
            url: CMS.ajax_url,
            type: 'POST',
            data: {
                action: 'cms_dashboard_data',
                data_type: 'todays_appointments',
                nonce: CMS.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#todays-appointments-list').html(response.data);
                } else {
                    $('#todays-appointments-list').html('<div class="alert alert-warning">No appointments today</div>');
                }
            },
            error: function() {
                $('#todays-appointments-list').html('<div class="alert alert-danger">Error loading appointments</div>');
            }
        });
    }

    function loadRecentPatients() {
        $('#recent-patients-list').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading patients...</div>');
        
        $.ajax({
            url: CMS.ajax_url,
            type: 'POST',
            data: {
                action: 'cms_dashboard_data',
                data_type: 'recent_patients',
                nonce: CMS.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#recent-patients-list').html(response.data);
                } else {
                    $('#recent-patients-list').html('<div class="alert alert-warning">No recent patients</div>');
                }
            },
            error: function() {
                $('#recent-patients-list').html('<div class="alert alert-danger">Error loading patients</div>');
            }
        });
    }

    function loadPendingBills() {
        $('#pending-bills-list').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading bills...</div>');
        
        $.ajax({
            url: CMS.ajax_url,
            type: 'POST',
            data: {
                action: 'cms_dashboard_data',
                data_type: 'pending_bills',
                nonce: CMS.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#pending-bills-list').html(response.data);
                } else {
                    $('#pending-bills-list').html('<div class="alert alert-warning">No pending bills</div>');
                }
            },
            error: function() {
                $('#pending-bills-list').html('<div class="alert alert-danger">Error loading bills</div>');
            }
        });
    }

    function loadLowStockItems() {
        $('#low-stock-list').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading stock alerts...</div>');
        
        $.ajax({
            url: CMS.ajax_url,
            type: 'POST',
            data: {
                action: 'cms_dashboard_data',
                data_type: 'low_stock',
                nonce: CMS.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#low-stock-list').html(response.data);
                } else {
                    $('#low-stock-list').html('<div class="alert alert-warning">No low stock items</div>');
                }
            },
            error: function() {
                $('#low-stock-list').html('<div class="alert alert-danger">Error loading stock alerts</div>');
            }
        });
    }

    // Patient search functionality
    $('#patient-search').on('input', function() {
        var searchTerm = $(this).val();
        if (searchTerm.length >= 2) {
            searchPatients(searchTerm);
        } else if (searchTerm.length === 0) {
            loadAllPatients();
        }
    });

    function searchPatients(searchTerm) {
        $.ajax({
            url: CMS.ajax_url,
            type: 'POST',
            data: {
                action: 'cms_search_patients',
                search_term: searchTerm,
                nonce: CMS.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#patients-list').html(response.data);
                } else {
                    $('#patients-list').html('<div class="alert alert-warning">No patients found</div>');
                }
            }
        });
    }

    // Patient form handling
    $('#patient-form').on('submit', function(e) {
        e.preventDefault();
        savePatient();
    });

    function savePatient() {
        var formData = new FormData($('#patient-form')[0]);
        formData.append('action', 'cms_save_patient');
        formData.append('nonce', CMS.nonce);

        $.ajax({
            url: CMS.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showAlert('success', 'Patient saved successfully');
                    $('#patient-modal').modal('hide');
                    loadAllPatients();
                } else {
                    showAlert('danger', response.message || 'Error saving patient');
                }
            },
            error: function() {
                showAlert('danger', 'Network error occurred');
            }
        });
    }

    // Appointment form handling
    $('#appointment-form').on('submit', function(e) {
        e.preventDefault();
        saveAppointment();
    });

    function saveAppointment() {
        var formData = $('#appointment-form').serialize();
        formData += '&action=cms_save_appointment&nonce=' + CMS.nonce;

        $.ajax({
            url: CMS.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    showAlert('success', 'Appointment saved successfully');
                    $('#appointment-modal').modal('hide');
                    loadAppointments();
                } else {
                    showAlert('danger', response.message || 'Error saving appointment');
                }
            }
        });
    }

    // Consultation form handling
    $('#consultation-form').on('submit', function(e) {
        e.preventDefault();
        saveConsultation();
    });

    function saveConsultation() {
        var formData = $('#consultation-form').serialize();
        formData += '&action=cms_save_consultation&nonce=' + CMS.nonce;

        $.ajax({
            url: CMS.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    showAlert('success', 'Consultation saved successfully');
                    if (response.data && response.data.consultation_id) {
                        window.location.href = '?page=clinic-management-prescriptions&consultation_id=' + response.data.consultation_id;
                    }
                } else {
                    showAlert('danger', response.message || 'Error saving consultation');
                }
            }
        });
    }

    // Prescription form handling
    $('#prescription-form').on('submit', function(e) {
        e.preventDefault();
        savePrescription();
    });

    function savePrescription() {
        var medicines = [];
        $('.medicine-row').each(function() {
            var medicine = {
                name: $(this).find('.medicine-name').val(),
                dosage: $(this).find('.medicine-dosage').val(),
                frequency: $(this).find('.medicine-frequency').val(),
                duration: $(this).find('.medicine-duration').val(),
                instructions: $(this).find('.medicine-instructions').val(),
                quantity: $(this).find('.medicine-quantity').val(),
                price: $(this).find('.medicine-price').val()
            };
            if (medicine.name) {
                medicines.push(medicine);
            }
        });

        var formData = $('#prescription-form').serialize();
        formData += '&medicines=' + JSON.stringify(medicines);
        formData += '&action=cms_save_prescription&nonce=' + CMS.nonce;

        $.ajax({
            url: CMS.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    showAlert('success', 'Prescription saved successfully');
                    if (confirm('Generate bill automatically?')) {
                        generateAutoBill(response.data.consultation_id);
                    }
                } else {
                    showAlert('danger', response.message || 'Error saving prescription');
                }
            }
        });
    }

    // Add medicine row
    $(document).on('click', '.add-medicine-row', function() {
        var newRow = $('.medicine-row:first').clone();
        newRow.find('input, select').val('');
        newRow.appendTo('#medicines-container');
    });

    // Remove medicine row
    $(document).on('click', '.remove-medicine-row', function() {
        if ($('.medicine-row').length > 1) {
            $(this).closest('.medicine-row').remove();
        }
    });

    // Billing functions
    function generateAutoBill(consultationId) {
        $.ajax({
            url: CMS.ajax_url,
            type: 'POST',
            data: {
                action: 'cms_generate_auto_bill',
                consultation_id: consultationId,
                nonce: CMS.nonce
            },
            success: function(response) {
                if (response.success) {
                    showAlert('success', 'Bill generated successfully');
                    window.location.href = '?page=clinic-management-billing&bill_id=' + response.data.bill_id;
                } else {
                    showAlert('warning', response.message || 'Could not generate bill automatically');
                }
            }
        });
    }

    // Record payment
    $(document).on('click', '.record-payment', function() {
        var billId = $(this).data('bill-id');
        var amount = prompt('Enter payment amount:');
        if (amount && parseFloat(amount) > 0) {
            recordPayment(billId, amount);
        }
    });

    function recordPayment(billId, amount) {
        $.ajax({
            url: CMS.ajax_url,
            type: 'POST',
            data: {
                action: 'cms_record_payment',
                bill_id: billId,
                amount: amount,
                payment_method: 'cash',
                nonce: CMS.nonce
            },
            success: function(response) {
                if (response.success) {
                    showAlert('success', 'Payment recorded successfully');
                    location.reload();
                } else {
                    showAlert('danger', response.message || 'Error recording payment');
                }
            }
        });
    }

    // Inventory functions
    $('#medicine-form').on('submit', function(e) {
        e.preventDefault();
        saveMedicine();
    });

    function saveMedicine() {
        var formData = $('#medicine-form').serialize();
        formData += '&action=cms_save_medicine&nonce=' + CMS.nonce;

        $.ajax({
            url: CMS.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    showAlert('success', 'Medicine saved successfully');
                    $('#medicine-modal').modal('hide');
                    loadMedicines();
                } else {
                    showAlert('danger', response.message || 'Error saving medicine');
                }
            }
        });
    }

    // Update stock
    $(document).on('click', '.update-stock', function() {
        var medicineId = $(this).data('medicine-id');
        var currentStock = $(this).data('current-stock');
        var movementType = prompt('Movement type (received/dispensed/expired/damaged):');
        var quantity = prompt('Quantity:');
        
        if (movementType && quantity) {
            updateStock(medicineId, movementType, quantity);
        }
    });

    function updateStock(medicineId, movementType, quantity) {
        $.ajax({
            url: CMS.ajax_url,
            type: 'POST',
            data: {
                action: 'cms_update_stock',
                medicine_id: medicineId,
                movement_type: movementType,
                quantity: quantity,
                nonce: CMS.nonce
            },
            success: function(response) {
                if (response.success) {
                    showAlert('success', 'Stock updated successfully');
                    loadMedicines();
                } else {
                    showAlert('danger', response.message || 'Error updating stock');
                }
            }
        });
    }

    // File upload handling
    $('.upload-btn').on('click', function() {
        var inputId = $(this).data('input');
        $('#' + inputId).click();
    });

    $('input[type="file"]').on('change', function() {
        var file = this.files[0];
        if (file) {
            var formData = new FormData();
            formData.append('file', file);
            formData.append('action', 'cms_upload_file');
            formData.append('nonce', CMS.nonce);

            $.ajax({
                url: CMS.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        var previewId = $(this).data('preview');
                        if (previewId) {
                            $('#' + previewId).attr('src', response.data.url).show();
                        }
                        showAlert('success', 'File uploaded successfully');
                    } else {
                        showAlert('danger', response.message || 'Error uploading file');
                    }
                }.bind(this)
            });
        }
    });

    // Print prescription
    $(document).on('click', '.print-prescription', function() {
        var prescriptionId = $(this).data('prescription-id');
        window.open('?page=clinic-management-prescriptions&action=print&id=' + prescriptionId, '_blank');
    });

    // Print bill
    $(document).on('click', '.print-bill', function() {
        var billId = $(this).data('bill-id');
        window.open('?page=clinic-management-billing&action=print&id=' + billId, '_blank');
    });

    // Modal handling
    $('.modal').on('hidden.bs.modal', function() {
        $(this).find('form')[0].reset();
        $(this).find('.alert').remove();
    });

    // Date picker initialization
    if ($.fn.datepicker) {
        $('.datepicker').datepicker({
            format: 'yyyy-mm-dd',
            autoclose: true,
            todayHighlight: true
        });
    }

    // Time picker initialization
    if ($.fn.timepicker) {
        $('.timepicker').timepicker({
            timeFormat: 'HH:mm',
            interval: 15,
            minTime: '09:00',
            maxTime: '18:00',
            defaultTime: '09:00',
            startTime: '09:00',
            dynamic: false,
            dropdown: true,
            scrollbar: true
        });
    }

    // Utility functions
    function showAlert(type, message) {
        var alertHtml = '<div class="alert alert-' + type + ' alert-dismissible fade show" role="alert">' +
                       message +
                       '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                       '</div>';
        
        $('.cms-alerts').html(alertHtml);
        setTimeout(function() {
            $('.cms-alerts .alert').fadeOut();
        }, 5000);
    }

    function loadAllPatients() {
        // Implementation for loading all patients
        $('#patients-list').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading patients...</div>');
    }

    function loadAppointments() {
        // Implementation for loading appointments
        $('#appointments-list').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading appointments...</div>');
    }

    function loadMedicines() {
        // Implementation for loading medicines
        $('#medicines-list').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading medicines...</div>');
    }

    // Initialize tooltips
    if ($.fn.tooltip) {
        $('[data-bs-toggle="tooltip"]').tooltip();
    }

    // Initialize popovers
    if ($.fn.popover) {
        $('[data-bs-toggle="popover"]').popover();
    }

    // Auto-save functionality for forms
    $('.auto-save').on('input', function() {
        clearTimeout(window.autoSaveTimeout);
        window.autoSaveTimeout = setTimeout(function() {
            // Auto-save implementation
        }, 2000);
    });

    // Confirmation dialogs
    $(document).on('click', '.confirm-delete', function(e) {
        if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
            e.preventDefault();
            return false;
        }
    });

    // Dynamic form validation
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
});