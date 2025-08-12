<?php
/**
 * User roles and capabilities management
 */

if (!defined('ABSPATH')) {
    exit;
}

class CMS_User_Roles
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
        add_action('init', array($this, 'addCapabilitiesToExistingRoles'));
    }

    /**
     * Create custom user roles
     */
    public static function createRoles()
    {
        // Doctor role
        add_role(
            'cms_doctor',
            __('Doctor', 'clinic-management'),
            array(
                'read' => true,
                'cms_access' => true,
                'cms_manage_patients' => true,
                'cms_manage_appointments' => true,
                'cms_manage_consultations' => true,
                'cms_manage_prescriptions' => true,
                'cms_view_billing' => true,
                'cms_manage_inventory' => true,
                'cms_view_reports' => true,
                'cms_send_notifications' => true,
            )
        );

        // Nurse role
        add_role(
            'cms_nurse',
            __('Nurse', 'clinic-management'),
            array(
                'read' => true,
                'cms_access' => true,
                'cms_manage_patients' => true,
                'cms_manage_appointments' => true,
                'cms_view_consultations' => true,
                'cms_view_prescriptions' => true,
                'cms_view_billing' => true,
                'cms_manage_inventory' => true,
                'cms_send_notifications' => true,
            )
        );

        // Receptionist role
        add_role(
            'cms_receptionist',
            __('Receptionist', 'clinic-management'),
            array(
                'read' => true,
                'cms_access' => true,
                'cms_manage_patients' => true,
                'cms_manage_appointments' => true,
                'cms_view_consultations' => true,
                'cms_manage_billing' => true,
                'cms_view_inventory' => true,
                'cms_send_notifications' => true,
            )
        );

        // Pharmacist role
        add_role(
            'cms_pharmacist',
            __('Pharmacist', 'clinic-management'),
            array(
                'read' => true,
                'cms_access' => true,
                'cms_view_patients' => true,
                'cms_view_prescriptions' => true,
                'cms_manage_inventory' => true,
                'cms_view_billing' => true,
            )
        );

        // Patient role (for portal access)
        add_role(
            'cms_patient',
            __('Patient', 'clinic-management'),
            array(
                'read' => true,
                'cms_patient_portal' => true,
            )
        );
    }

    /**
     * Add capabilities to existing administrator role
     */
    public function addCapabilitiesToExistingRoles()
    {
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $admin_capabilities = array(
                'cms_access',
                'cms_manage_patients',
                'cms_manage_appointments',
                'cms_manage_consultations',
                'cms_manage_prescriptions',
                'cms_manage_billing',
                'cms_manage_inventory',
                'cms_manage_reports',
                'cms_manage_settings',
                'cms_send_notifications',
                'cms_manage_users',
                'cms_view_all_data',
            );

            foreach ($admin_capabilities as $capability) {
                $admin_role->add_cap($capability);
            }
        }
    }

    /**
     * Remove custom roles (for uninstall)
     */
    public static function removeRoles()
    {
        remove_role('cms_doctor');
        remove_role('cms_nurse');
        remove_role('cms_receptionist');
        remove_role('cms_pharmacist');
        remove_role('cms_patient');

        // Remove capabilities from administrator
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $admin_capabilities = array(
                'cms_access',
                'cms_manage_patients',
                'cms_manage_appointments',
                'cms_manage_consultations',
                'cms_manage_prescriptions',
                'cms_manage_billing',
                'cms_manage_inventory',
                'cms_manage_reports',
                'cms_manage_settings',
                'cms_send_notifications',
                'cms_manage_users',
                'cms_view_all_data',
            );

            foreach ($admin_capabilities as $capability) {
                $admin_role->remove_cap($capability);
            }
        }
    }

    /**
     * Check if current user has specific capability
     */
    public static function userCan($capability)
    {
        return current_user_can($capability) || current_user_can('manage_options');
    }

    /**
     * Get user role display name
     */
    public static function getUserRoleDisplayName($user_id = null)
    {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        $user = get_userdata($user_id);
        if (!$user) {
            return '';
        }

        $roles = $user->roles;
        if (empty($roles)) {
            return '';
        }

        $role = $roles[0];
        $role_names = array(
            'administrator' => __('Administrator', 'clinic-management'),
            'cms_doctor' => __('Doctor', 'clinic-management'),
            'cms_nurse' => __('Nurse', 'clinic-management'),
            'cms_receptionist' => __('Receptionist', 'clinic-management'),
            'cms_pharmacist' => __('Pharmacist', 'clinic-management'),
            'cms_patient' => __('Patient', 'clinic-management'),
        );

        return isset($role_names[$role]) ? $role_names[$role] : ucfirst($role);
    }

    /**
     * Get doctors list
     */
    public static function getDoctors()
    {
        $doctors = get_users(array(
            'role__in' => array('administrator', 'cms_doctor'),
            'fields' => array('ID', 'display_name', 'user_email'),
        ));

        return $doctors;
    }

    /**
     * Get staff members (excluding patients)
     */
    public static function getStaffMembers()
    {
        $staff = get_users(array(
            'role__in' => array('administrator', 'cms_doctor', 'cms_nurse', 'cms_receptionist', 'cms_pharmacist'),
            'fields' => array('ID', 'display_name', 'user_email'),
        ));

        return $staff;
    }

    /**
     * Check if user is doctor
     */
    public static function isDoctor($user_id = null)
    {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }

        return in_array('cms_doctor', $user->roles) || in_array('administrator', $user->roles);
    }

    /**
     * Check if user is staff member
     */
    public static function isStaffMember($user_id = null)
    {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }

        $staff_roles = array('administrator', 'cms_doctor', 'cms_nurse', 'cms_receptionist', 'cms_pharmacist');
        return !empty(array_intersect($staff_roles, $user->roles));
    }

    /**
     * Check if user can access clinic management
     */
    public static function canAccessCMS($user_id = null)
    {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        return current_user_can('cms_access') || current_user_can('manage_options');
    }

    /**
     * Get capability requirements for different modules
     */
    public static function getModuleCapabilities()
    {
        return array(
            'patients' => array(
                'view' => 'cms_access',
                'manage' => 'cms_manage_patients',
            ),
            'appointments' => array(
                'view' => 'cms_access',
                'manage' => 'cms_manage_appointments',
            ),
            'consultations' => array(
                'view' => 'cms_view_consultations',
                'manage' => 'cms_manage_consultations',
            ),
            'prescriptions' => array(
                'view' => 'cms_view_prescriptions',
                'manage' => 'cms_manage_prescriptions',
            ),
            'billing' => array(
                'view' => 'cms_view_billing',
                'manage' => 'cms_manage_billing',
            ),
            'inventory' => array(
                'view' => 'cms_view_inventory',
                'manage' => 'cms_manage_inventory',
            ),
            'reports' => array(
                'view' => 'cms_view_reports',
                'manage' => 'cms_manage_reports',
            ),
            'settings' => array(
                'view' => 'cms_access',
                'manage' => 'cms_manage_settings',
            ),
        );
    }
}