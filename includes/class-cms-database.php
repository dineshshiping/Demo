<?php
/**
 * Database management class
 */

if (!defined('ABSPATH')) {
    exit;
}

class CMS_Database
{
    /**
     * Create all required tables
     */
    public static function createTables()
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Patients table
        $patients_table = $wpdb->prefix . 'cms_patients';
        $patients_sql = "CREATE TABLE $patients_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            patient_id varchar(20) NOT NULL UNIQUE,
            first_name varchar(100) NOT NULL,
            last_name varchar(100) NOT NULL,
            date_of_birth date,
            gender varchar(10),
            phone varchar(20),
            email varchar(100),
            address text,
            emergency_contact varchar(100),
            emergency_phone varchar(20),
            blood_group varchar(5),
            allergies text,
            medical_history text,
            photo_url varchar(255),
            created_by bigint(20),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY patient_id (patient_id),
            KEY phone (phone),
            KEY email (email)
        ) $charset_collate;";

        // Appointments table
        $appointments_table = $wpdb->prefix . 'cms_appointments';
        $appointments_sql = "CREATE TABLE $appointments_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            patient_id mediumint(9) NOT NULL,
            doctor_id bigint(20) NOT NULL,
            appointment_date date NOT NULL,
            appointment_time time NOT NULL,
            duration int(11) DEFAULT 30,
            status varchar(20) DEFAULT 'scheduled',
            appointment_type varchar(50),
            notes text,
            created_by bigint(20),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY patient_id (patient_id),
            KEY doctor_id (doctor_id),
            KEY appointment_date (appointment_date),
            KEY status (status)
        ) $charset_collate;";

        // Consultations table
        $consultations_table = $wpdb->prefix . 'cms_consultations';
        $consultations_sql = "CREATE TABLE $consultations_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            patient_id mediumint(9) NOT NULL,
            appointment_id mediumint(9),
            doctor_id bigint(20) NOT NULL,
            consultation_date date NOT NULL,
            consultation_type varchar(20) DEFAULT 'allopathy',
            chief_complaint text,
            symptoms text,
            examination_findings text,
            diagnosis text,
            treatment_plan text,
            vital_signs json,
            follow_up_date date,
            notes text,
            consultation_fee decimal(10,2),
            created_by bigint(20),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY patient_id (patient_id),
            KEY appointment_id (appointment_id),
            KEY doctor_id (doctor_id),
            KEY consultation_date (consultation_date),
            KEY consultation_type (consultation_type)
        ) $charset_collate;";

        // Prescriptions table
        $prescriptions_table = $wpdb->prefix . 'cms_prescriptions';
        $prescriptions_sql = "CREATE TABLE $prescriptions_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            consultation_id mediumint(9) NOT NULL,
            patient_id mediumint(9) NOT NULL,
            doctor_id bigint(20) NOT NULL,
            prescription_date date NOT NULL,
            medicines json NOT NULL,
            instructions text,
            status varchar(20) DEFAULT 'active',
            created_by bigint(20),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY consultation_id (consultation_id),
            KEY patient_id (patient_id),
            KEY doctor_id (doctor_id),
            KEY prescription_date (prescription_date),
            KEY status (status)
        ) $charset_collate;";

        // Billing table
        $billing_table = $wpdb->prefix . 'cms_billing';
        $billing_sql = "CREATE TABLE $billing_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            bill_number varchar(50) NOT NULL UNIQUE,
            patient_id mediumint(9) NOT NULL,
            consultation_id mediumint(9),
            bill_date date NOT NULL,
            consultation_fee decimal(10,2) DEFAULT 0,
            medicine_fee decimal(10,2) DEFAULT 0,
            other_charges decimal(10,2) DEFAULT 0,
            discount decimal(10,2) DEFAULT 0,
            total_amount decimal(10,2) NOT NULL,
            paid_amount decimal(10,2) DEFAULT 0,
            payment_status varchar(20) DEFAULT 'unpaid',
            payment_method varchar(20),
            payment_date datetime,
            notes text,
            created_by bigint(20),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY bill_number (bill_number),
            KEY patient_id (patient_id),
            KEY consultation_id (consultation_id),
            KEY bill_date (bill_date),
            KEY payment_status (payment_status)
        ) $charset_collate;";

        // Inventory table
        $inventory_table = $wpdb->prefix . 'cms_inventory';
        $inventory_sql = "CREATE TABLE $inventory_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            medicine_name varchar(200) NOT NULL,
            generic_name varchar(200),
            brand_name varchar(200),
            category varchar(100),
            dosage_form varchar(50),
            strength varchar(50),
            unit varchar(20),
            batch_number varchar(50),
            expiry_date date,
            quantity_in_stock int(11) DEFAULT 0,
            minimum_stock_level int(11) DEFAULT 10,
            unit_cost decimal(10,2),
            selling_price decimal(10,2),
            supplier varchar(200),
            supplier_contact varchar(20),
            status varchar(20) DEFAULT 'active',
            created_by bigint(20),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY medicine_name (medicine_name),
            KEY category (category),
            KEY batch_number (batch_number),
            KEY expiry_date (expiry_date),
            KEY status (status)
        ) $charset_collate;";

        // Stock movements table
        $stock_movements_table = $wpdb->prefix . 'cms_stock_movements';
        $stock_movements_sql = "CREATE TABLE $stock_movements_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            medicine_id mediumint(9) NOT NULL,
            movement_type varchar(20) NOT NULL,
            quantity int(11) NOT NULL,
            unit_cost decimal(10,2),
            reference_id mediumint(9),
            reference_type varchar(50),
            notes text,
            movement_date datetime DEFAULT CURRENT_TIMESTAMP,
            created_by bigint(20),
            PRIMARY KEY (id),
            KEY medicine_id (medicine_id),
            KEY movement_type (movement_type),
            KEY movement_date (movement_date),
            KEY reference_id (reference_id)
        ) $charset_collate;";

        // Medical reports table
        $medical_reports_table = $wpdb->prefix . 'cms_medical_reports';
        $medical_reports_sql = "CREATE TABLE $medical_reports_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            patient_id mediumint(9) NOT NULL,
            consultation_id mediumint(9),
            report_type varchar(50),
            report_name varchar(200) NOT NULL,
            file_url varchar(255),
            file_type varchar(10),
            report_date date,
            notes text,
            uploaded_by bigint(20),
            uploaded_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY patient_id (patient_id),
            KEY consultation_id (consultation_id),
            KEY report_type (report_type),
            KEY report_date (report_date)
        ) $charset_collate;";

        // Patient portal access table
        $portal_access_table = $wpdb->prefix . 'cms_patient_portal_access';
        $portal_access_sql = "CREATE TABLE $portal_access_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            patient_id mediumint(9) NOT NULL,
            access_token varchar(100) NOT NULL,
            phone_verified tinyint(1) DEFAULT 0,
            email_verified tinyint(1) DEFAULT 0,
            is_active tinyint(1) DEFAULT 1,
            last_login datetime,
            token_expires datetime,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY patient_id (patient_id),
            KEY access_token (access_token),
            KEY token_expires (token_expires)
        ) $charset_collate;";

        // Notifications table
        $notifications_table = $wpdb->prefix . 'cms_notifications';
        $notifications_sql = "CREATE TABLE $notifications_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            patient_id mediumint(9),
            appointment_id mediumint(9),
            notification_type varchar(50) NOT NULL,
            recipient_phone varchar(20),
            recipient_email varchar(100),
            message text NOT NULL,
            status varchar(20) DEFAULT 'pending',
            scheduled_time datetime,
            sent_time datetime,
            delivery_status varchar(20),
            created_by bigint(20),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY patient_id (patient_id),
            KEY appointment_id (appointment_id),
            KEY notification_type (notification_type),
            KEY status (status),
            KEY scheduled_time (scheduled_time)
        ) $charset_collate;";

        // Settings table
        $settings_table = $wpdb->prefix . 'cms_settings';
        $settings_sql = "CREATE TABLE $settings_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            setting_key varchar(100) NOT NULL UNIQUE,
            setting_value longtext,
            setting_type varchar(20) DEFAULT 'text',
            updated_by bigint(20),
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY setting_key (setting_key)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Create tables
        dbDelta($patients_sql);
        dbDelta($appointments_sql);
        dbDelta($consultations_sql);
        dbDelta($prescriptions_sql);
        dbDelta($billing_sql);
        dbDelta($inventory_sql);
        dbDelta($stock_movements_sql);
        dbDelta($medical_reports_sql);
        dbDelta($portal_access_sql);
        dbDelta($notifications_sql);
        dbDelta($settings_sql);

        // Add foreign key constraints
        self::addForeignKeys();

        // Insert default settings
        self::insertDefaultSettings();

        // Update database version
        update_option('cms_database_version', CMS_PLUGIN_VERSION);
    }

    /**
     * Add foreign key constraints
     */
    private static function addForeignKeys()
    {
        global $wpdb;

        $sql_commands = array(
            "ALTER TABLE {$wpdb->prefix}cms_appointments ADD CONSTRAINT fk_appointment_patient FOREIGN KEY (patient_id) REFERENCES {$wpdb->prefix}cms_patients(id) ON DELETE CASCADE",
            "ALTER TABLE {$wpdb->prefix}cms_consultations ADD CONSTRAINT fk_consultation_patient FOREIGN KEY (patient_id) REFERENCES {$wpdb->prefix}cms_patients(id) ON DELETE CASCADE",
            "ALTER TABLE {$wpdb->prefix}cms_consultations ADD CONSTRAINT fk_consultation_appointment FOREIGN KEY (appointment_id) REFERENCES {$wpdb->prefix}cms_appointments(id) ON DELETE SET NULL",
            "ALTER TABLE {$wpdb->prefix}cms_prescriptions ADD CONSTRAINT fk_prescription_consultation FOREIGN KEY (consultation_id) REFERENCES {$wpdb->prefix}cms_consultations(id) ON DELETE CASCADE",
            "ALTER TABLE {$wpdb->prefix}cms_prescriptions ADD CONSTRAINT fk_prescription_patient FOREIGN KEY (patient_id) REFERENCES {$wpdb->prefix}cms_patients(id) ON DELETE CASCADE",
            "ALTER TABLE {$wpdb->prefix}cms_billing ADD CONSTRAINT fk_billing_patient FOREIGN KEY (patient_id) REFERENCES {$wpdb->prefix}cms_patients(id) ON DELETE CASCADE",
            "ALTER TABLE {$wpdb->prefix}cms_billing ADD CONSTRAINT fk_billing_consultation FOREIGN KEY (consultation_id) REFERENCES {$wpdb->prefix}cms_consultations(id) ON DELETE SET NULL",
            "ALTER TABLE {$wpdb->prefix}cms_stock_movements ADD CONSTRAINT fk_stock_medicine FOREIGN KEY (medicine_id) REFERENCES {$wpdb->prefix}cms_inventory(id) ON DELETE CASCADE",
            "ALTER TABLE {$wpdb->prefix}cms_medical_reports ADD CONSTRAINT fk_report_patient FOREIGN KEY (patient_id) REFERENCES {$wpdb->prefix}cms_patients(id) ON DELETE CASCADE",
            "ALTER TABLE {$wpdb->prefix}cms_medical_reports ADD CONSTRAINT fk_report_consultation FOREIGN KEY (consultation_id) REFERENCES {$wpdb->prefix}cms_consultations(id) ON DELETE SET NULL",
            "ALTER TABLE {$wpdb->prefix}cms_patient_portal_access ADD CONSTRAINT fk_portal_patient FOREIGN KEY (patient_id) REFERENCES {$wpdb->prefix}cms_patients(id) ON DELETE CASCADE",
            "ALTER TABLE {$wpdb->prefix}cms_notifications ADD CONSTRAINT fk_notification_patient FOREIGN KEY (patient_id) REFERENCES {$wpdb->prefix}cms_patients(id) ON DELETE SET NULL",
            "ALTER TABLE {$wpdb->prefix}cms_notifications ADD CONSTRAINT fk_notification_appointment FOREIGN KEY (appointment_id) REFERENCES {$wpdb->prefix}cms_appointments(id) ON DELETE SET NULL"
        );

        foreach ($sql_commands as $sql) {
            $wpdb->query($sql);
        }
    }

    /**
     * Insert default settings
     */
    private static function insertDefaultSettings()
    {
        global $wpdb;

        $default_settings = array(
            array('clinic_name', 'Your Clinic Name', 'text'),
            array('clinic_address', '', 'textarea'),
            array('clinic_phone', '', 'text'),
            array('clinic_email', '', 'email'),
            array('consultation_fee_allopathy', '500', 'number'),
            array('consultation_fee_ayurveda', '400', 'number'),
            array('currency_symbol', '$', 'text'),
            array('appointment_duration', '30', 'number'),
            array('working_hours_start', '09:00', 'time'),
            array('working_hours_end', '18:00', 'time'),
            array('working_days', 'monday,tuesday,wednesday,thursday,friday,saturday', 'text'),
            array('sms_api_key', '', 'text'),
            array('sms_api_url', '', 'text'),
            array('email_notifications_enabled', '1', 'boolean'),
            array('sms_notifications_enabled', '0', 'boolean'),
            array('appointment_reminder_hours', '24', 'number'),
            array('low_stock_threshold', '10', 'number'),
            array('patient_id_prefix', 'PAT', 'text'),
            array('bill_number_prefix', 'BILL', 'text'),
            array('prescription_template', '', 'textarea'),
            array('prescription_footer', '', 'textarea')
        );

        foreach ($default_settings as $setting) {
            $wpdb->replace(
                $wpdb->prefix . 'cms_settings',
                array(
                    'setting_key' => $setting[0],
                    'setting_value' => $setting[1],
                    'setting_type' => $setting[2]
                ),
                array('%s', '%s', '%s')
            );
        }
    }

    /**
     * Get setting value
     */
    public static function getSetting($key, $default = '')
    {
        global $wpdb;

        $value = $wpdb->get_var($wpdb->prepare(
            "SELECT setting_value FROM {$wpdb->prefix}cms_settings WHERE setting_key = %s",
            $key
        ));

        return $value !== null ? $value : $default;
    }

    /**
     * Update setting value
     */
    public static function updateSetting($key, $value)
    {
        global $wpdb;

        return $wpdb->replace(
            $wpdb->prefix . 'cms_settings',
            array(
                'setting_key' => $key,
                'setting_value' => $value,
                'updated_by' => get_current_user_id()
            ),
            array('%s', '%s', '%d')
        );
    }

    /**
     * Drop all tables (for uninstall)
     */
    public static function dropTables()
    {
        global $wpdb;

        $tables = array(
            $wpdb->prefix . 'cms_notifications',
            $wpdb->prefix . 'cms_patient_portal_access',
            $wpdb->prefix . 'cms_medical_reports',
            $wpdb->prefix . 'cms_stock_movements',
            $wpdb->prefix . 'cms_billing',
            $wpdb->prefix . 'cms_prescriptions',
            $wpdb->prefix . 'cms_consultations',
            $wpdb->prefix . 'cms_appointments',
            $wpdb->prefix . 'cms_inventory',
            $wpdb->prefix . 'cms_patients',
            $wpdb->prefix . 'cms_settings'
        );

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }

        delete_option('cms_database_version');
    }
}