<?php
/**
 * Patient Portal module (placeholder)
 */

if (!defined('ABSPATH')) {
    exit;
}

class CMS_Patient_Portal
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
        // Patient portal functionality is handled by CMS_Frontend class
        // This class serves as a placeholder for additional portal features
    }
}