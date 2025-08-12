# Clinic Management System - WordPress Plugin

A comprehensive, all-in-one WordPress plugin designed to fully digitize and automate medical clinic operations. This plugin provides a complete solution for patient management, appointment scheduling, billing, prescriptions, inventory management, and more.

## 🏥 Features

### Core System & Security
- **Self-contained WordPress plugin** accessible via dedicated "Clinic Management" admin menu
- **Native WordPress user system integration** with custom roles and capabilities
- **Role-based access control** (Administrator, Doctor roles)
- **Secure authentication** and data protection

### Patient & Consultation Management
- **Comprehensive patient profiles** with personal details, contact info, and photos
- **Medical history tracking** for both Ayurveda and Allopathy disciplines
- **Unified patient view** with complete medical records
- **Powerful search functionality** by ID, name, or phone number
- **File upload system** for medical reports and documents

### Appointment Scheduling
- **Calendar-based appointment management** with drag-and-drop functionality
- **Conflict detection** to prevent double-booking
- **Available time slot calculation** based on clinic hours
- **Dashboard widget** for "Today's Appointments"
- **Appointment status tracking** (Scheduled, Confirmed, In Progress, Completed, Cancelled)

### Billing & Prescription Module
- **Digital prescriptions** with printable/savable formats
- **Automated invoice generation** based on consultation fees and medicines
- **Payment status tracking** (Paid, Unpaid, Partial)
- **Professional document generation** (HTML/PDF simulation)

### Pharmacy & Inventory Management
- **Medicine stock management** with current levels and expiry dates
- **Automated low-stock alerts** via email notifications
- **Inventory tracking** with categories and pricing
- **Export functionality** for inventory reports

### Secure Patient Portal
- **Front-end patient login** with secure authentication
- **Online appointment booking** with real-time availability
- **Medical records access** (prescriptions, invoices, reports)
- **Appointment management** (view, cancel, reschedule)
- **Online bill payment** integration

### Reporting & Analytics
- **Dashboard statistics** with visual charts and graphs
- **Revenue analytics** with monthly trends
- **Patient growth tracking** and appointment trends
- **Doctor performance metrics**
- **Comprehensive reporting** with export capabilities

### Automated Notifications
- **Email notifications** for appointments and payments
- **SMS integration** (placeholder for Twilio or similar services)
- **Appointment reminders** sent automatically
- **Payment due notifications**
- **Welcome emails** for new patients

## 🚀 Installation

### Prerequisites
- WordPress 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.7 or higher
- jQuery and jQuery UI (included with WordPress)

### Installation Steps

1. **Download the Plugin**
   ```bash
   # Clone the repository or download the ZIP file
   git clone https://github.com/yourusername/clinic-management-system.git
   ```

2. **Upload to WordPress**
   - Upload the `clinic-management-system` folder to `/wp-content/plugins/`
   - Or upload the ZIP file via WordPress admin → Plugins → Add New → Upload Plugin

3. **Activate the Plugin**
   - Go to WordPress admin → Plugins
   - Find "Clinic Management System" and click "Activate"

4. **Initial Setup**
   - The plugin will automatically create necessary database tables
   - Custom user roles and capabilities will be added
   - Default settings will be configured

5. **Access the System**
   - Navigate to "Clinic Management" in the WordPress admin menu
   - Start by adding your first patient and doctor

## 📁 File Structure

```
clinic-management-system/
├── clinic-management-system.php          # Main plugin file
├── includes/
│   ├── class-cms-loader.php             # Core loader class
│   ├── class-cms-activator.php          # Activation handler
│   ├── class-cms-deactivator.php        # Deactivation handler
│   ├── class-cms-database.php           # Database abstraction layer
│   ├── class-cms-patient.php            # Patient management
│   ├── class-cms-appointment.php        # Appointment management
│   ├── class-cms-prescription.php       # Prescription management
│   ├── class-cms-billing.php            # Billing and invoicing
│   ├── class-cms-inventory.php          # Inventory management
│   ├── class-cms-notifications.php      # Email/SMS notifications
│   ├── class-cms-reports.php            # Reporting and analytics
│   └── views/                           # Admin view templates
│       ├── admin-dashboard.php          # Admin dashboard
│       ├── admin-patients.php           # Patient management
│       └── admin-appointments.php       # Appointment management
├── admin/
│   ├── class-cms-admin.php              # Admin interface controller
│   └── class-cms-dashboard-widget.php   # Dashboard widget
├── public/
│   ├── class-cms-public.php             # Public assets controller
│   ├── class-cms-patient-portal.php     # Patient portal
│   └── views/                           # Public view templates
│       ├── public-patient-portal.php    # Patient portal
│       └── public-appointment-booking.php # Appointment booking
├── assets/
│   ├── css/
│   │   ├── admin.css                    # Admin styles
│   │   └── public.css                   # Public styles
│   └── js/
│       ├── admin.js                     # Admin JavaScript
│       └── public.js                    # Public JavaScript
└── languages/                            # Translation files
```

## ⚙️ Configuration

### User Roles and Capabilities

The plugin creates custom user roles with specific capabilities:

- **Administrator**: Full access to all features
- **Doctor**: Access to patients, appointments, prescriptions, and reports

### Database Tables

The plugin creates the following custom tables:

- `wp_cms_patients` - Patient information and medical history
- `wp_cms_appointments` - Appointment scheduling and status
- `wp_cms_prescriptions` - Digital prescriptions and treatment records
- `wp_cms_billing` - Invoicing and payment tracking
- `wp_cms_inventory` - Medicine and supply inventory
- `wp_cms_patient_files` - Uploaded medical documents
- `wp_cms_portal_users` - Patient portal authentication

### Settings

Configure the plugin via **Clinic Management → Settings**:

- **Clinic Information**: Name, address, contact details
- **Working Hours**: Daily schedule for appointments
- **Notification Settings**: Email and SMS configuration
- **File Upload Settings**: Allowed file types and sizes
- **Billing Configuration**: Default fees and tax rates

## 🎯 Usage

### For Administrators

1. **Dashboard Overview**
   - View clinic statistics and recent activity
   - Quick access to common actions
   - System status monitoring

2. **Patient Management**
   - Add new patients with complete profiles
   - Search and filter patient database
   - View complete medical history

3. **Appointment Scheduling**
   - Calendar-based appointment booking
   - Conflict detection and resolution
   - Bulk appointment management

4. **Billing and Invoicing**
   - Generate invoices automatically
   - Track payment status
   - Financial reporting

### For Doctors

1. **Patient Records**
   - Access patient medical history
   - View previous prescriptions
   - Update patient information

2. **Appointment Management**
   - View daily schedule
   - Update appointment status
   - Add consultation notes

3. **Prescription Creation**
   - Create digital prescriptions
   - Specify medications and dosages
   - Add treatment instructions

### For Patients

1. **Patient Portal**
   - Secure login with credentials
   - View medical records and history
   - Access prescriptions and invoices

2. **Appointment Booking**
   - Online appointment scheduling
   - Choose preferred doctor and time
   - Receive confirmation notifications

3. **Document Access**
   - Download prescriptions and reports
   - View billing information
   - Track appointment status

## 🔧 Customization

### Styling

Customize the appearance by modifying CSS files:

- `assets/css/admin.css` - Admin interface styling
- `assets/css/public.css` - Public-facing elements

### Functionality

Extend the plugin by:

- Adding custom fields to patient profiles
- Creating additional appointment types
- Implementing custom billing rules
- Adding new notification triggers

### Hooks and Filters

The plugin provides various WordPress hooks for customization:

```php
// Add custom patient fields
add_filter('cms_patient_fields', 'my_custom_patient_fields');

// Customize appointment validation
add_filter('cms_appointment_validation', 'my_custom_validation');

// Modify notification content
add_filter('cms_notification_content', 'my_custom_content');
```

## 📊 Database Schema

### Patients Table
```sql
CREATE TABLE wp_cms_patients (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    patient_id varchar(20) NOT NULL,
    first_name varchar(50) NOT NULL,
    last_name varchar(50) NOT NULL,
    date_of_birth date DEFAULT NULL,
    gender enum('male','female','other') DEFAULT NULL,
    blood_group varchar(5) DEFAULT NULL,
    phone varchar(20) NOT NULL,
    email varchar(100) DEFAULT NULL,
    address text DEFAULT NULL,
    medical_conditions text DEFAULT NULL,
    allergies text DEFAULT NULL,
    emergency_contact varchar(100) DEFAULT NULL,
    emergency_phone varchar(20) DEFAULT NULL,
    photo varchar(255) DEFAULT NULL,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY patient_id (patient_id),
    KEY phone (phone),
    KEY email (email)
);
```

### Appointments Table
```sql
CREATE TABLE wp_cms_appointments (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    patient_id bigint(20) NOT NULL,
    doctor_id bigint(20) NOT NULL,
    appointment_date date NOT NULL,
    appointment_time time NOT NULL,
    duration int(11) DEFAULT 30,
    appointment_type varchar(50) DEFAULT 'consultation',
    status enum('scheduled','confirmed','in_progress','completed','cancelled','no_show') DEFAULT 'scheduled',
    notes text DEFAULT NULL,
    room_number varchar(20) DEFAULT NULL,
    is_emergency tinyint(1) DEFAULT 0,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY patient_id (patient_id),
    KEY doctor_id (doctor_id),
    KEY appointment_date (appointment_date),
    KEY status (status)
);
```

## 🚨 Troubleshooting

### Common Issues

1. **Plugin Not Activating**
   - Check PHP version compatibility
   - Verify WordPress version
   - Check for plugin conflicts

2. **Database Tables Not Created**
   - Deactivate and reactivate the plugin
   - Check database permissions
   - Review error logs

3. **Appointments Not Saving**
   - Verify AJAX is working
   - Check nonce verification
   - Review form validation

4. **File Uploads Failing**
   - Check upload directory permissions
   - Verify file size limits
   - Review allowed file types

### Debug Mode

Enable WordPress debug mode for detailed error information:

```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### Support

For technical support:

1. Check the WordPress error logs
2. Review browser console for JavaScript errors
3. Verify database connectivity
4. Test with default WordPress theme

## 🔒 Security Features

- **Nonce verification** for all AJAX requests
- **Input sanitization** and validation
- **SQL injection prevention** with prepared statements
- **File upload security** with type and size restrictions
- **Role-based access control**
- **Session management** for patient portal

## 📈 Performance Optimization

- **Database indexing** on frequently queried fields
- **Efficient queries** with proper JOINs
- **Caching** for static data
- **Lazy loading** for large datasets
- **Optimized file handling**

## 🌐 Internationalization

The plugin supports multiple languages:

- Text domain: `clinic-management-system`
- Translation files in `/languages/` directory
- RTL language support
- Date and number formatting localization

## 📝 Changelog

### Version 1.0.0
- Initial release
- Core patient management system
- Appointment scheduling
- Prescription management
- Billing and invoicing
- Inventory management
- Patient portal
- Reporting and analytics

## 🤝 Contributing

We welcome contributions! Please:

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## 📄 License

This plugin is licensed under the GPL v2 or later.

## 🙏 Acknowledgments

- WordPress community for the excellent platform
- Contributors and beta testers
- Medical professionals for domain expertise
- Open source libraries and tools

## 📞 Contact

- **Plugin Author**: Your Name
- **Website**: https://example.com
- **Email**: support@example.com
- **Support Forum**: https://example.com/support

---

**Note**: This plugin is designed for educational and demonstration purposes. For production use in medical environments, please ensure compliance with local healthcare regulations and data protection laws.
