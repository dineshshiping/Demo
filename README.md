# Clinic Management System - WordPress Plugin

A comprehensive clinic management system built as a WordPress plugin to fully digitize and automate medical clinic operations. This all-in-one solution provides complete patient management, appointment scheduling, billing, inventory management, and more.

## 🏥 Features

### Core System & Security
- **Dedicated Admin Interface**: Access via "Clinic Management" menu in WordPress admin
- **User Role Management**: Custom roles for Doctors, Nurses, Receptionists, Pharmacists, and Patients
- **Secure Access Control**: Leverages WordPress native user system with granular permissions
- **No Separate Login**: Integrates seamlessly with WordPress authentication

### Patient & Consultation Management
- **Comprehensive Patient Profiles**: Personal details, contact info, photo uploads, medical history
- **Multi-Discipline Support**: Documentation for both Ayurveda and Allopathy treatments
- **Unified Medical History**: Complete view of past visits, diagnoses, symptoms, and reports
- **Advanced Patient Search**: Instant search by ID, name, or phone number
- **Medical Report Uploads**: PDF/image uploads with secure storage

### Appointment Scheduling
- **Calendar-Based System**: Interactive appointment booking, rescheduling, and cancellation
- **Slot Management**: Automatic availability checking and conflict prevention
- **Dashboard Widget**: "Today's Appointments" on main WordPress dashboard
- **Doctor Schedule Management**: Flexible scheduling for multiple doctors

### Digital Prescriptions & Billing
- **Professional Prescriptions**: Create, print, and save digital prescriptions
- **Automated Billing**: Auto-generate invoices from consultations and prescriptions
- **Payment Tracking**: Monitor payment status (Paid, Unpaid, Partial)
- **Multiple Payment Methods**: Cash, card, and online payment support

### Pharmacy & Inventory Management
- **Stock Management**: Track medicine inventory with automatic calculations
- **Low Stock Alerts**: Automated notifications when items need reordering
- **Stock Movements**: Detailed logs of received, dispensed, expired, and damaged items
- **Expiry Tracking**: Monitor and alert for medicines nearing expiration

### Secure Patient Portal
- **Frontend Patient Access**: Secure login portal for patients
- **Online Appointment Booking**: Patients can book appointments directly
- **Document Access**: View and download medical reports and prescriptions
- **Bill Management**: View outstanding bills and payment history
- **Responsive Design**: Mobile-friendly interface

### Reporting & Analytics
- **Performance Dashboard**: Graphs and charts for clinic insights
- **Revenue Reports**: Monthly revenue tracking and payment method analysis
- **Patient Analytics**: New patient trends, age groups, and demographics
- **Appointment Statistics**: Daily patterns, doctor performance, and peak hours
- **Inventory Reports**: Stock levels, dispensing patterns, and valuations

### Automated Notifications
- **SMS Integration**: Support for Twilio, Nexmo, and AWS SNS
- **Email Automation**: WordPress native email with custom templates
- **Appointment Reminders**: Automated reminders 24 hours before appointments
- **Payment Reminders**: Overdue bill notifications with customizable schedules
- **Low Stock Alerts**: Staff notifications for inventory management

## 📋 Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher
- SSL certificate (recommended for patient data security)

## 🚀 Installation

### Method 1: Upload Plugin Files

1. Download the plugin files
2. Upload the `clinic-management-system` folder to `/wp-content/plugins/`
3. Go to WordPress Admin > Plugins
4. Activate "Clinic Management System"

### Method 2: WordPress Admin Upload

1. Go to WordPress Admin > Plugins > Add New
2. Click "Upload Plugin"
3. Choose the plugin ZIP file
4. Click "Install Now" and then "Activate"

## ⚙️ Initial Setup

### 1. Plugin Activation
Upon activation, the plugin will:
- Create all necessary database tables
- Set up custom user roles and capabilities
- Create a "Patient Portal" page
- Initialize default settings

### 2. User Roles Setup
The plugin creates these custom roles:
- **Doctor**: Full access to patient records, consultations, prescriptions
- **Nurse**: Patient management, appointment scheduling, basic consultations
- **Receptionist**: Appointment booking, patient registration, billing
- **Pharmacist**: Inventory management, prescription dispensing
- **Patient**: Frontend portal access only

### 3. Assign User Roles
1. Go to **Users > All Users**
2. Edit existing users or create new ones
3. Assign appropriate clinic management roles

### 4. Configure Settings
1. Navigate to **Clinic Management > Settings**
2. Configure:
   - Clinic information (name, address, contact)
   - Email settings
   - SMS provider settings (optional)
   - Notification preferences
   - Default consultation fees

## 🎯 Usage Guide

### Patient Management
1. **Add New Patient**: Navigate to **Clinic Management > Patients > Add New**
2. **Search Patients**: Use the search bar on the patients list page
3. **Upload Reports**: Use the medical reports section in patient profiles
4. **View History**: Access complete medical history from patient details

### Appointment Scheduling
1. **Book Appointment**: Go to **Clinic Management > Appointments > Add New**
2. **Check Availability**: The system automatically shows available slots
3. **Manage Schedule**: View daily, weekly, or monthly appointment calendars
4. **Update Status**: Mark appointments as completed, cancelled, or no-show

### Consultations
1. **Create Consultation**: After completing an appointment
2. **Choose Type**: Select Ayurveda or Allopathy consultation
3. **Record Details**: Add symptoms, diagnosis, treatment plan
4. **Vital Signs**: Record blood pressure, weight, temperature, etc.

### Prescriptions
1. **Add Medicines**: Select from inventory or add custom medicines
2. **Set Dosage**: Specify dosage, frequency, and duration
3. **Add Instructions**: Include special instructions for patient
4. **Print/Save**: Generate printable prescription or save to patient profile

### Billing
1. **Auto-Generate**: Bills are automatically created from consultations
2. **Manual Billing**: Create custom bills for additional services
3. **Record Payments**: Track payments with different methods
4. **Print Invoices**: Generate professional invoices for patients

### Inventory Management
1. **Add Medicines**: Go to **Clinic Management > Inventory > Add New**
2. **Update Stock**: Record received, dispensed, or expired quantities
3. **Set Reorder Levels**: Configure automatic low stock alerts
4. **Monitor Expiry**: Track medicines approaching expiration dates

### Patient Portal
1. **Page Setup**: A "Patient Portal" page is automatically created
2. **Patient Access**: Patients can register and login on the frontend
3. **Shortcode**: Use `[cms_patient_portal]` to display the portal anywhere
4. **Customization**: Style the portal to match your website theme

## 🔧 Configuration

### SMS Notifications (Optional)
To enable SMS notifications:

1. **Twilio Setup**:
   - Get Account SID, Auth Token, and Phone Number from Twilio
   - Add credentials in **Settings > Notifications**

2. **Other Providers**:
   - Similar setup for Nexmo/Vonage or AWS SNS
   - Update provider settings in the admin panel

### Email Notifications
- Uses WordPress native `wp_mail()` function
- Configure SMTP settings using plugins like WP Mail SMTP
- Customize email templates in **Settings > Notifications**

### Security Considerations
- **Data Encryption**: Patient data is stored securely in WordPress database
- **Access Control**: Role-based permissions for all features
- **Audit Logs**: All actions are logged for compliance
- **File Uploads**: Medical reports are stored in secure upload directories

## 🎨 Customization

### Styling
- Admin styles: `/assets/css/admin.css`
- Frontend styles: `/assets/css/frontend.css`
- Override styles in your theme's CSS

### Templates
- Dashboard template: `/templates/admin/dashboard.php`
- Add custom templates in `/templates/` directory
- Use WordPress template hierarchy for overrides

### Hooks and Filters
The plugin provides various hooks for customization:

```php
// Patient created hook
do_action('cms_patient_created', $patient_id);

// Appointment status changed
do_action('cms_appointment_status_changed', $appointment_id, $old_status, $new_status);

// Bill generated hook
do_action('cms_bill_created', $bill_id);

// Consultation completed
do_action('cms_consultation_created', $consultation_id);
```

## 📊 Reporting Features

### Built-in Reports
- **Revenue Reports**: Monthly revenue trends and payment analysis
- **Patient Reports**: Demographics, new patient trends, age distribution
- **Appointment Reports**: Daily patterns, doctor performance, peak hours
- **Inventory Reports**: Stock levels, dispensing patterns, valuations

### Export Options
- Generate HTML reports for viewing
- PDF export (requires additional setup)
- CSV export for data analysis

## 🔌 Extending the Plugin

### Custom Modules
Create additional modules by:
1. Adding new class files in `/includes/modules/`
2. Following the existing module structure
3. Registering AJAX handlers and hooks

### Database Extensions
- Add custom tables using WordPress `dbDelta()`
- Follow WordPress database naming conventions
- Include foreign key relationships where appropriate

## 🐛 Troubleshooting

### Common Issues

**1. Plugin Activation Errors**
- Check PHP version (7.4+ required)
- Verify database permissions
- Ensure WordPress version compatibility

**2. Permission Denied Errors**
- Verify user roles are properly assigned
- Check if custom capabilities are active
- Re-activate plugin if necessary

**3. AJAX Not Working**
- Check for JavaScript errors in browser console
- Verify nonce validation
- Ensure jQuery is loaded

**4. Database Errors**
- Check database table creation
- Verify MySQL version (5.6+ required)
- Check database user permissions

### Debug Mode
Enable WordPress debug mode to troubleshoot:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## 📝 Development

### File Structure
```
clinic-management-system/
├── clinic-management-system.php (Main plugin file)
├── includes/
│   ├── class-cms-database.php
│   ├── class-cms-user-roles.php
│   ├── class-cms-admin-menu.php
│   ├── class-cms-dashboard-widgets.php
│   ├── class-cms-ajax.php
│   ├── class-cms-frontend.php
│   └── modules/
│       ├── class-cms-patients.php
│       ├── class-cms-appointments.php
│       ├── class-cms-consultations.php
│       ├── class-cms-prescriptions.php
│       ├── class-cms-billing.php
│       ├── class-cms-inventory.php
│       ├── class-cms-patient-portal.php
│       ├── class-cms-reports.php
│       └── class-cms-notifications.php
├── templates/
│   └── admin/
│       └── dashboard.php
├── assets/
│   ├── css/
│   │   ├── admin.css
│   │   └── frontend.css
│   └── js/
│       ├── admin.js
│       └── frontend.js
└── README.md
```

### Coding Standards
- Follow WordPress Coding Standards
- Use proper sanitization and validation
- Implement nonce verification for security
- Use prepared statements for database queries

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## 📄 License

This plugin is licensed under the GPL v2 or later.

## 🆘 Support

For support and documentation:
- Check the troubleshooting section above
- Review WordPress and PHP error logs
- Ensure all requirements are met
- Test with default WordPress theme

## 🔮 Future Enhancements

Potential future features:
- **Multi-location Support**: Manage multiple clinic branches
- **Telemedicine Integration**: Video consultation capabilities
- **Insurance Management**: Insurance claim processing
- **Lab Integration**: Connect with laboratory systems
- **Mobile App**: Native mobile applications
- **API Integration**: RESTful API for third-party integrations
- **Advanced Reporting**: More detailed analytics and reports
- **Backup & Sync**: Cloud backup and synchronization

---

**Clinic Management System** - Digitizing healthcare, one clinic at a time. 🏥✨
