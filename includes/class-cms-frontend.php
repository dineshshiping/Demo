<?php
/**
 * Frontend functionality for patient portal
 */

if (!defined('ABSPATH')) {
    exit;
}

class CMS_Frontend
{
    private static $instance = null;

    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        add_shortcode('cms_patient_portal', array($this, 'patientPortalShortcode'));
        add_action('wp_ajax_cms_patient_login', array($this, 'patientLogin'));
        add_action('wp_ajax_nopriv_cms_patient_login', array($this, 'patientLogin'));
        add_action('wp_ajax_cms_patient_register', array($this, 'patientRegister'));
        add_action('wp_ajax_nopriv_cms_patient_register', array($this, 'patientRegister'));
    }

    /**
     * Patient portal shortcode
     */
    public function patientPortalShortcode($atts)
    {
        $atts = shortcode_atts(array(
            'show_login' => 'true',
            'show_register' => 'true',
        ), $atts, 'cms_patient_portal');

        ob_start();
        $this->renderPatientPortal($atts);
        return ob_get_clean();
    }

    /**
     * Render patient portal
     */
    private function renderPatientPortal($atts)
    {
        // Check if patient is logged in (basic implementation)
        $patient_id = $this->getCurrentPatientId();

        if ($patient_id) {
            $this->renderPatientDashboard($patient_id);
        } else {
            $this->renderPatientLogin($atts);
        }
    }

    /**
     * Render patient login form
     */
    private function renderPatientLogin($atts)
    {
        ?>
        <div class="cms-patient-portal">
            <div class="cms-portal-container">
                <div class="row justify-content-center">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header text-center">
                                <h4><?php _e('Patient Portal Login', 'clinic-management'); ?></h4>
                            </div>
                            <div class="card-body">
                                <form id="cms-patient-login-form">
                                    <div class="form-group">
                                        <label for="patient_id"><?php _e('Patient ID', 'clinic-management'); ?></label>
                                        <input type="text" class="form-control" id="patient_id" name="patient_id" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="phone"><?php _e('Phone Number', 'clinic-management'); ?></label>
                                        <input type="tel" class="form-control" id="phone" name="phone" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="date_of_birth"><?php _e('Date of Birth', 'clinic-management'); ?></label>
                                        <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-block">
                                        <?php _e('Login', 'clinic-management'); ?>
                                    </button>
                                </form>
                                
                                <div id="cms-login-message" class="mt-3"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('#cms-patient-login-form').on('submit', function(e) {
                e.preventDefault();
                
                var formData = {
                    action: 'cms_patient_login',
                    nonce: cms_ajax.nonce,
                    patient_id: $('#patient_id').val(),
                    phone: $('#phone').val(),
                    date_of_birth: $('#date_of_birth').val()
                };

                $.post(cms_ajax.ajax_url, formData, function(response) {
                    var result = JSON.parse(response);
                    if (result.success) {
                        $('#cms-login-message').html('<div class="alert alert-success">' + result.message + '</div>');
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        $('#cms-login-message').html('<div class="alert alert-danger">' + result.message + '</div>');
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Render patient dashboard
     */
    private function renderPatientDashboard($patient_id)
    {
        global $wpdb;
        
        // Get patient data
        $patient = CMS_Patients::getPatient($patient_id);
        if (!$patient) {
            echo '<div class="alert alert-danger">' . __('Patient not found', 'clinic-management') . '</div>';
            return;
        }

        // Get recent appointments
        $appointments = $wpdb->get_results($wpdb->prepare("
            SELECT a.*, u.display_name as doctor_name
            FROM {$wpdb->prefix}cms_appointments a
            LEFT JOIN {$wpdb->users} u ON a.doctor_id = u.ID
            WHERE a.patient_id = %d
            ORDER BY a.appointment_date DESC, a.appointment_time DESC
            LIMIT 5
        ", $patient_id));

        // Get medical reports
        $reports = CMS_Patients::getPatientMedicalReports($patient_id);

        // Get pending bills
        $bills = $wpdb->get_results($wpdb->prepare("
            SELECT b.*, (b.total_amount - b.paid_amount) as due_amount
            FROM {$wpdb->prefix}cms_billing b
            WHERE b.patient_id = %d AND b.payment_status IN ('unpaid', 'partial')
            ORDER BY b.bill_date DESC
        ", $patient_id));

        ?>
        <div class="cms-patient-dashboard">
            <div class="cms-dashboard-header mb-4">
                <div class="row">
                    <div class="col-md-8">
                        <h2><?php echo sprintf(__('Welcome, %s', 'clinic-management'), esc_html($patient->first_name . ' ' . $patient->last_name)); ?></h2>
                        <p class="text-muted"><?php echo sprintf(__('Patient ID: %s', 'clinic-management'), esc_html($patient->patient_id)); ?></p>
                    </div>
                    <div class="col-md-4 text-right">
                        <a href="#" class="btn btn-outline-secondary" onclick="cmsPatientLogout()">
                            <?php _e('Logout', 'clinic-management'); ?>
                        </a>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Recent Appointments -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5><?php _e('Recent Appointments', 'clinic-management'); ?></h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($appointments)): ?>
                                <p class="text-muted"><?php _e('No appointments found.', 'clinic-management'); ?></p>
                            <?php else: ?>
                                <?php foreach ($appointments as $appointment): ?>
                                    <div class="appointment-item border-bottom py-2">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <strong><?php echo esc_html(date('F j, Y', strtotime($appointment->appointment_date))); ?></strong>
                                                <br>
                                                <small class="text-muted">
                                                    <?php echo esc_html(date('g:i A', strtotime($appointment->appointment_time))); ?> - 
                                                    Dr. <?php echo esc_html($appointment->doctor_name); ?>
                                                </small>
                                            </div>
                                            <span class="badge badge-<?php echo $appointment->status === 'completed' ? 'success' : 'primary'; ?>">
                                                <?php echo esc_html(ucfirst($appointment->status)); ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Medical Reports -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5><?php _e('Medical Reports', 'clinic-management'); ?></h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($reports)): ?>
                                <p class="text-muted"><?php _e('No reports available.', 'clinic-management'); ?></p>
                            <?php else: ?>
                                <?php foreach (array_slice($reports, 0, 5) as $report): ?>
                                    <div class="report-item border-bottom py-2">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <strong><?php echo esc_html($report->report_name); ?></strong>
                                                <br>
                                                <small class="text-muted">
                                                    <?php echo esc_html(date('F j, Y', strtotime($report->report_date))); ?>
                                                </small>
                                            </div>
                                            <a href="<?php echo esc_url($report->file_url); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                <?php _e('View', 'clinic-management'); ?>
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!empty($bills)): ?>
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5><?php _e('Pending Bills', 'clinic-management'); ?></h5>
                        </div>
                        <div class="card-body">
                            <?php 
                            $currency_symbol = CMS_Database::getSetting('currency_symbol', '$');
                            foreach ($bills as $bill): 
                            ?>
                                <div class="bill-item border-bottom py-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?php echo esc_html($bill->bill_number); ?></strong>
                                            <br>
                                            <small class="text-muted">
                                                <?php echo esc_html(date('F j, Y', strtotime($bill->bill_date))); ?>
                                            </small>
                                        </div>
                                        <div class="text-right">
                                            <span class="badge badge-warning">
                                                <?php echo esc_html($currency_symbol . number_format($bill->due_amount, 2)); ?>
                                            </span>
                                            <br>
                                            <small class="text-muted"><?php _e('Due', 'clinic-management'); ?></small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <script>
        function cmsPatientLogout() {
            if (confirm('<?php _e("Are you sure you want to logout?", "clinic-management"); ?>')) {
                // Clear session storage
                sessionStorage.removeItem('cms_patient_id');
                location.reload();
            }
        }
        </script>
        <?php
    }

    /**
     * AJAX: Patient login
     */
    public function patientLogin()
    {
        check_ajax_referer('cms_ajax_nonce', 'nonce');

        $patient_id = sanitize_text_field($_POST['patient_id']);
        $phone = sanitize_text_field($_POST['phone']);
        $date_of_birth = sanitize_text_field($_POST['date_of_birth']);

        // Validate patient credentials
        global $wpdb;
        $patient = $wpdb->get_row($wpdb->prepare("
            SELECT id, first_name, last_name 
            FROM {$wpdb->prefix}cms_patients 
            WHERE patient_id = %s AND phone = %s AND date_of_birth = %s
        ", $patient_id, $phone, $date_of_birth));

        if (!$patient) {
            wp_die(json_encode(array('success' => false, 'message' => __('Invalid credentials', 'clinic-management'))));
        }

        // Store patient session (basic implementation)
        // In production, you should use proper session management
        wp_die(json_encode(array(
            'success' => true, 
            'message' => __('Login successful', 'clinic-management'),
            'patient_id' => $patient->id
        )));
    }

    /**
     * AJAX: Patient registration (placeholder)
     */
    public function patientRegister()
    {
        check_ajax_referer('cms_ajax_nonce', 'nonce');

        // Basic patient registration implementation
        // This would be expanded in a real implementation
        wp_die(json_encode(array('success' => false, 'message' => __('Registration not yet implemented', 'clinic-management'))));
    }

    /**
     * Get current patient ID (basic implementation)
     */
    private function getCurrentPatientId()
    {
        // In a real implementation, you would use proper session management
        // For now, we'll use a simple check
        return isset($_SESSION['cms_patient_id']) ? intval($_SESSION['cms_patient_id']) : null;
    }
}