<?php
/**
 * Dashboard widget class
 */
class CMS_Dashboard_Widget {
    
    public function add_dashboard_widget() {
        wp_add_dashboard_widget(
            'cms_todays_appointments',
            'Today\'s Appointments',
            array($this, 'render_dashboard_widget')
        );
    }
    
    public function render_dashboard_widget() {
        // Check if user has permission to view appointments
        if (!current_user_can('manage_appointments')) {
            echo '<p>You do not have permission to view appointments.</p>';
            return;
        }
        
        $appointment = new CMS_Appointment();
        $todays_appointments = $appointment->get_todays_appointments();
        
        if (empty($todays_appointments)) {
            echo '<p>No appointments scheduled for today.</p>';
            return;
        }
        
        echo '<div class="cms-dashboard-widget">';
        echo '<table class="widefat">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>Time</th>';
        echo '<th>Patient</th>';
        echo '<th>Doctor</th>';
        echo '<th>Status</th>';
        echo '<th>Actions</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        foreach ($todays_appointments as $appointment) {
            $status_class = $this->get_status_class($appointment->status);
            $time_formatted = date('g:i A', strtotime($appointment->appointment_time));
            
            echo '<tr>';
            echo '<td><strong>' . esc_html($time_formatted) . '</strong></td>';
            echo '<td>' . esc_html($appointment->first_name . ' ' . $appointment->last_name) . '<br><small>ID: ' . esc_html($appointment->patient_code) . '</small></td>';
            echo '<td>' . esc_html($appointment->doctor_name) . '</td>';
            echo '<td><span class="status-badge ' . esc_attr($status_class) . '">' . esc_html(ucfirst($appointment->status)) . '</span></td>';
            echo '<td>';
            echo '<a href="' . admin_url('admin.php?page=cms-appointments&date=' . date('Y-m-d')) . '" class="button button-small">View</a>';
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
        
        echo '<div class="cms-dashboard-footer">';
        echo '<a href="' . admin_url('admin.php?page=cms-appointments') . '" class="button button-primary">View All Appointments</a>';
        echo '<a href="' . admin_url('admin.php?page=cms-appointments&action=add') . '" class="button">Add New Appointment</a>';
        echo '</div>';
        
        echo '</div>';
        
        // Add inline styles
        echo '<style>
            .cms-dashboard-widget table {
                margin-top: 10px;
            }
            .cms-dashboard-widget th {
                font-weight: 600;
                background: #f1f1f1;
            }
            .cms-dashboard-widget td {
                vertical-align: middle;
            }
            .status-badge {
                padding: 2px 8px;
                border-radius: 3px;
                font-size: 11px;
                font-weight: 600;
                text-transform: uppercase;
            }
            .status-badge.scheduled {
                background: #e7f3ff;
                color: #0073aa;
            }
            .status-badge.confirmed {
                background: #d4edda;
                color: #155724;
            }
            .status-badge.completed {
                background: #d1ecf1;
                color: #0c5460;
            }
            .status-badge.cancelled {
                background: #f8d7da;
                color: #721c24;
            }
            .status-badge.no_show {
                background: #fff3cd;
                color: #856404;
            }
            .cms-dashboard-footer {
                margin-top: 15px;
                padding-top: 15px;
                border-top: 1px solid #ddd;
            }
            .cms-dashboard-footer .button {
                margin-right: 10px;
            }
        </style>';
    }
    
    private function get_status_class($status) {
        switch ($status) {
            case 'scheduled':
                return 'scheduled';
            case 'confirmed':
                return 'confirmed';
            case 'completed':
                return 'completed';
            case 'cancelled':
                return 'cancelled';
            case 'no_show':
                return 'no_show';
            default:
                return 'scheduled';
        }
    }
}