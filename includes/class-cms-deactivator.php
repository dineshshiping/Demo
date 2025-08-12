<?php
/**
 * Plugin deactivation class
 */
class CMS_Deactivator {
    
    public static function deactivate() {
        // Clear scheduled cron jobs
        wp_clear_scheduled_hook('cms_daily_notifications');
        
        // Remove custom capabilities from administrator role
        $role = get_role('administrator');
        if ($role) {
            $role->remove_cap('manage_clinic');
            $role->remove_cap('view_patients');
            $role->remove_cap('edit_patients');
            $role->remove_cap('delete_patients');
            $role->remove_cap('manage_appointments');
            $role->remove_cap('manage_billing');
            $role->remove_cap('manage_prescriptions');
            $role->remove_cap('manage_inventory');
            $role->remove_cap('view_reports');
        }
        
        // Remove custom Doctor role
        remove_role('doctor');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}