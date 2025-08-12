<?php
/**
 * Plugin activation class
 */
class CMS_Activator {
    
    public static function activate() {
        // Create database tables
        self::create_tables();
        
        // Set default options
        self::set_default_options();
        
        // Create upload directories
        self::create_upload_directories();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    private static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Patients table
        $table_patients = $wpdb->prefix . 'cms_patients';
        $sql_patients = "CREATE TABLE $table_patients (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            patient_id varchar(20) NOT NULL,
            first_name varchar(50) NOT NULL,
            last_name varchar(50) NOT NULL,
            email varchar(100),
            phone varchar(20),
            date_of_birth date,
            gender enum('male', 'female', 'other'),
            address text,
            emergency_contact varchar(100),
            emergency_phone varchar(20),
            blood_group varchar(5),
            allergies text,
            medical_history text,
            photo_url varchar(255),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY patient_id (patient_id),
            KEY email (email),
            KEY phone (phone)
        ) $charset_collate;";
        
        // Appointments table
        $table_appointments = $wpdb->prefix . 'cms_appointments';
        $sql_appointments = "CREATE TABLE $table_appointments (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            patient_id mediumint(9) NOT NULL,
            doctor_id bigint(20) NOT NULL,
            appointment_date date NOT NULL,
            appointment_time time NOT NULL,
            duration int(11) DEFAULT 30,
            status enum('scheduled', 'confirmed', 'completed', 'cancelled', 'no_show') DEFAULT 'scheduled',
            notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY patient_id (patient_id),
            KEY doctor_id (doctor_id),
            KEY appointment_date (appointment_date),
            KEY status (status)
        ) $charset_collate;";
        
        // Prescriptions table
        $table_prescriptions = $wpdb->prefix . 'cms_prescriptions';
        $sql_prescriptions = "CREATE TABLE $table_prescriptions (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            patient_id mediumint(9) NOT NULL,
            doctor_id bigint(20) NOT NULL,
            appointment_id mediumint(9),
            diagnosis text,
            symptoms text,
            treatment_plan text,
            medicines text,
            dosage_instructions text,
            follow_up_date date,
            notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY patient_id (patient_id),
            KEY doctor_id (doctor_id),
            KEY appointment_id (appointment_id)
        ) $charset_collate;";
        
        // Billing table
        $table_billing = $wpdb->prefix . 'cms_billing';
        $sql_billing = "CREATE TABLE $table_billing (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            patient_id mediumint(9) NOT NULL,
            appointment_id mediumint(9),
            invoice_number varchar(20) NOT NULL,
            consultation_fee decimal(10,2) DEFAULT 0.00,
            medicine_cost decimal(10,2) DEFAULT 0.00,
            other_charges decimal(10,2) DEFAULT 0.00,
            total_amount decimal(10,2) NOT NULL,
            paid_amount decimal(10,2) DEFAULT 0.00,
            payment_status enum('paid', 'unpaid', 'partial') DEFAULT 'unpaid',
            payment_method varchar(50),
            payment_date datetime,
            due_date date,
            notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY invoice_number (invoice_number),
            KEY patient_id (patient_id),
            KEY appointment_id (appointment_id),
            KEY payment_status (payment_status)
        ) $charset_collate;";
        
        // Inventory table
        $table_inventory = $wpdb->prefix . 'cms_inventory';
        $sql_inventory = "CREATE TABLE $table_inventory (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            medicine_name varchar(100) NOT NULL,
            generic_name varchar(100),
            category varchar(50),
            manufacturer varchar(100),
            strength varchar(50),
            form varchar(50),
            current_stock int(11) DEFAULT 0,
            minimum_stock int(11) DEFAULT 10,
            unit_price decimal(10,2) DEFAULT 0.00,
            expiry_date date,
            location varchar(100),
            notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY medicine_name (medicine_name),
            KEY category (category),
            KEY current_stock (current_stock)
        ) $charset_collate;";
        
        // Patient files table
        $table_files = $wpdb->prefix . 'cms_patient_files';
        $sql_files = "CREATE TABLE $table_files (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            patient_id mediumint(9) NOT NULL,
            file_name varchar(255) NOT NULL,
            file_url varchar(500) NOT NULL,
            file_type varchar(50),
            file_size int(11),
            uploaded_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY patient_id (patient_id),
            KEY file_type (file_type)
        ) $charset_collate;";
        
        // Patient portal users table
        $table_portal_users = $wpdb->prefix . 'cms_portal_users';
        $sql_portal_users = "CREATE TABLE $table_portal_users (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            patient_id mediumint(9) NOT NULL,
            username varchar(50) NOT NULL,
            password_hash varchar(255) NOT NULL,
            email varchar(100) NOT NULL,
            is_active tinyint(1) DEFAULT 1,
            last_login datetime,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY username (username),
            UNIQUE KEY email (email),
            KEY patient_id (patient_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($sql_patients);
        dbDelta($sql_appointments);
        dbDelta($sql_prescriptions);
        dbDelta($sql_billing);
        dbDelta($sql_inventory);
        dbDelta($sql_files);
        dbDelta($sql_portal_users);
    }
    
    private static function set_default_options() {
        add_option('cms_consultation_fee', '50.00');
        add_option('cms_clinic_name', 'Your Clinic Name');
        add_option('cms_clinic_address', 'Your Clinic Address');
        add_option('cms_clinic_phone', 'Your Clinic Phone');
        add_option('cms_clinic_email', 'your@clinic.com');
        add_option('cms_appointment_duration', '30');
        add_option('cms_working_hours', '09:00-17:00');
        add_option('cms_sms_enabled', '0');
        add_option('cms_email_enabled', '1');
        add_option('cms_currency', 'USD');
        add_option('cms_timezone', 'UTC');
    }
    
    private static function create_upload_directories() {
        $upload_dir = wp_upload_dir();
        $clinic_dir = $upload_dir['basedir'] . '/clinic-files/';
        
        if (!file_exists($clinic_dir)) {
            wp_mkdir_p($clinic_dir);
        }
        
        // Create .htaccess to protect files
        $htaccess_content = "Order Deny,Allow\nDeny from all\n";
        file_put_contents($clinic_dir . '.htaccess', $htaccess_content);
    }
}