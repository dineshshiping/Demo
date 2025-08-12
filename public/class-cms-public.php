<?php
/**
 * Public interface management class
 */
class CMS_Public {
    
    private $plugin_name;
    private $version;
    
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }
    
    /**
     * Enqueue public styles
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name . '-public',
            CMS_PLUGIN_URL . 'assets/css/public.css',
            array(),
            $this->version,
            'all'
        );
        
        // Enqueue Font Awesome for icons
        wp_enqueue_style(
            'font-awesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css',
            array(),
            '5.15.4'
        );
    }
    
    /**
     * Enqueue public scripts
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            $this->plugin_name . '-public',
            CMS_PLUGIN_URL . 'assets/js/public.js',
            array('jquery'),
            $this->version,
            false
        );
        
        wp_localize_script($this->plugin_name . '-public', 'cms_public_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cms_nonce'),
            'strings' => array(
                'loading' => 'Loading...',
                'success' => 'Operation completed successfully!',
                'error' => 'An error occurred. Please try again.',
                'confirm_cancel' => 'Are you sure you want to cancel this appointment?',
                'confirm_delete' => 'Are you sure you want to delete this item?'
            )
        ));
    }
}