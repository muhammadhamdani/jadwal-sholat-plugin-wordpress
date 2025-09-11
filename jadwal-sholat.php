<?php

/**
 * Plugin Name: Jadwal Sholat
 * Plugin URI: https://github.com/muhammadhamdani/jadwal-sholat-plugin-wordpress
 * Description: Plugin untuk menampilkan jadwal sholat
 * Version: 1.0
 * Author: Muhammad Chamdani Sukron
 * Author URI: https://github.com/muhammadhamdani
 * License: GPL v2 or later
 * Text Domain: jadwal-sholat
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('JSM_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('JSM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('JSM_VERSION', '1.4.0');

// Include the main class
require_once JSM_PLUGIN_PATH . 'includes/class-jadwal-sholat.php';
require_once JSM_PLUGIN_PATH . 'includes/class-jadwal-sholat-widget.php';

// Initialize the plugin
function jsm_init_plugin()
{
    $jadwal_sholat = new Jadwal_Sholat();
    $jadwal_sholat->init();
}
add_action('plugins_loaded', 'jsm_init_plugin');

// Register activation hook
register_activation_hook(__FILE__, array('Jadwal_Sholat', 'activate'));

// Register deactivation hook
register_deactivation_hook(__FILE__, array('Jadwal_Sholat', 'deactivate'));
