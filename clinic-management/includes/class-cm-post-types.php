<?php
/**
 * Registers custom post types and associated meta.
 *
 * @package ClinicManagement\PostTypes
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CM_Post_Types {

    private static $instance;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'init', array( $this, 'register_post_types' ) );
        add_action( 'add_meta_boxes', array( $this, 'register_meta_boxes' ) );
        add_action( 'save_post', array( $this, 'save_meta_boxes' ) );
    }

    public function register_post_types() {
        // Patients
        register_post_type( 'cm_patient', array(
            'labels' => array(
                'name'          => __( 'Patients', 'clinic-management' ),
                'singular_name' => __( 'Patient', 'clinic-management' ),
            ),
            'public'              => false,
            'show_ui'             => true,
            'capability_type'     => 'post',
            'capability'          => 'cm_access',
            'map_meta_cap'        => true,
            'menu_icon'           => 'dashicons-id',
            'supports'            => array( 'title', 'editor', 'thumbnail' ),
        ) );

        // Appointments
        register_post_type( 'cm_appointment', array(
            'labels' => array(
                'name'          => __( 'Appointments', 'clinic-management' ),
                'singular_name' => __( 'Appointment', 'clinic-management' ),
            ),
            'public'          => false,
            'show_ui'         => true,
            'capability'      => 'cm_access',
            'map_meta_cap'    => true,
            'menu_icon'       => 'dashicons-calendar',
            'supports'        => array( 'title' ),
        ) );

        // Prescriptions
        register_post_type( 'cm_prescription', array(
            'labels' => array(
                'name'          => __( 'Prescriptions', 'clinic-management' ),
                'singular_name' => __( 'Prescription', 'clinic-management' ),
            ),
            'public'          => false,
            'show_ui'         => true,
            'capability'      => 'cm_access',
            'map_meta_cap'    => true,
            'menu_icon'       => 'dashicons-editor-paste-text',
            'supports'        => array( 'title', 'editor' ),
        ) );

        // Bills
        register_post_type( 'cm_bill', array(
            'labels' => array(
                'name'          => __( 'Bills', 'clinic-management' ),
                'singular_name' => __( 'Bill', 'clinic-management' ),
            ),
            'public'          => false,
            'show_ui'         => true,
            'capability'      => 'cm_access',
            'map_meta_cap'    => true,
            'menu_icon'       => 'dashicons-media-spreadsheet',
            'supports'        => array( 'title' ),
        ) );

        // Medicines (Inventory)
        register_post_type( 'cm_medicine', array(
            'labels' => array(
                'name'          => __( 'Medicines', 'clinic-management' ),
                'singular_name' => __( 'Medicine', 'clinic-management' ),
            ),
            'public'          => false,
            'show_ui'         => true,
            'capability'      => 'cm_access',
            'map_meta_cap'    => true,
            'menu_icon'       => 'dashicons-pressthis',
            'supports'        => array( 'title', 'editor' ),
        ) );
    }

    /**
     * Register meta boxes for patient information.
     */
    public function register_meta_boxes() {
        add_meta_box( 'cm_patient_details', __( 'Patient Details', 'clinic-management' ), array( $this, 'render_patient_meta_box' ), 'cm_patient', 'normal', 'default' );
    }

    /**
     * Render patient meta box.
     */
    public function render_patient_meta_box( $post ) {
        wp_nonce_field( 'cm_save_patient', 'cm_patient_nonce' );

        $phone   = get_post_meta( $post->ID, '_cm_phone', true );
        $dob     = get_post_meta( $post->ID, '_cm_dob', true );
        $history = get_post_meta( $post->ID, '_cm_medical_history', true );

        echo '<p><label>' . __( 'Phone Number', 'clinic-management' ) . '</label><br/><input type="text" name="cm_phone" value="' . esc_attr( $phone ) . '" class="widefat" /></p>';
        echo '<p><label>' . __( 'Date of Birth', 'clinic-management' ) . '</label><br/><input type="date" name="cm_dob" value="' . esc_attr( $dob ) . '" class="widefat" /></p>';
        echo '<p><label>' . __( 'Medical History', 'clinic-management' ) . '</label><br/><textarea name="cm_medical_history" class="widefat" rows="5">' . esc_textarea( $history ) . '</textarea></p>';
    }

    /**
     * Save patient meta box.
     */
    public function save_meta_boxes( $post_id ) {
        if ( ! isset( $_POST['cm_patient_nonce'] ) || ! wp_verify_nonce( $_POST['cm_patient_nonce'], 'cm_save_patient' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'cm_access' ) ) {
            return;
        }

        if ( isset( $_POST['cm_phone'] ) ) {
            update_post_meta( $post_id, '_cm_phone', sanitize_text_field( $_POST['cm_phone'] ) );
        }
        if ( isset( $_POST['cm_dob'] ) ) {
            update_post_meta( $post_id, '_cm_dob', sanitize_text_field( $_POST['cm_dob'] ) );
        }
        if ( isset( $_POST['cm_medical_history'] ) ) {
            update_post_meta( $post_id, '_cm_medical_history', sanitize_textarea_field( $_POST['cm_medical_history'] ) );
        }
    }
}