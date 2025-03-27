<?php

/**
 * Plugin Name: Jadwal Sholat
 * Plugin URI: https://wordpress.org
 * Description: Plugin untuk menampilkan jadwal sholat dengan pilihan kota dinamis menggunakan API myquran.com v2. [shortcode] = [sholat_schedule]
 * Version: 1.0
 * Author: Muhammad Chamdani Sukron
 * Author URI: https://www.instagram.com/lapakdhani?utm_source=ig_web_button_share_sheet&igsh=ZDNlZDc0MzIxNw==
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function sholat_schedule_enqueue_scripts()
{
    // Tambahkan Bootstrap 5 dari CDN
    wp_enqueue_style('bootstrap-css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css');

    // Tambahkan script utama
    wp_enqueue_script('sholat-schedule-script', plugin_dir_url(__FILE__) . 'sholat-schedule.js', array('jquery'), null, true);
    wp_localize_script('sholat-schedule-script', 'sholatAjax', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('sholat_nonce'),
        'defaultCityId' => '1638'
    ));
}
add_action('wp_enqueue_scripts', 'sholat_schedule_enqueue_scripts');

function sholat_schedule_shortcode()
{
    ob_start();
?>
    <div class="container mt-4">
        <div class="w-100 w-md-50 mx-auto card shadow-sm">
            <div class="card-header bg-primary text-white text-center">
                <h5 class="mb-0">Jadwal Sholat</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="sholat-city" class="form-label">Pilih Kota:</label>
                    <select id="sholat-city" class="form-select">
                        <option value="">Loading...</option>
                    </select>
                </div>
                <div id="sholat-result" class="mt-3"></div>
            </div>
        </div>
    </div>
<?php
    return ob_get_clean();
}
add_shortcode('sholat_schedule', 'sholat_schedule_shortcode');

function get_kota_list()
{
    $cached_data = get_transient('sholat_kota_list');
    if ($cached_data) {
        wp_send_json_success($cached_data);
    }

    $response = wp_remote_get('https://api.myquran.com/v2/sholat/kota/semua');
    if (is_wp_error($response)) {
        wp_send_json_error('Gagal mengambil data kota');
    }
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    if (!isset($data['data'])) {
        wp_send_json_error('Format data tidak sesuai');
    }

    set_transient('sholat_kota_list', $data['data'], HOUR_IN_SECONDS);
    wp_send_json_success($data['data']);
}
add_action('wp_ajax_get_kota_list', 'get_kota_list');
add_action('wp_ajax_nopriv_get_kota_list', 'get_kota_list');

function get_sholat_schedule()
{
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'sholat_nonce')) {
        wp_send_json_error('Nonce tidak valid');
    }

    $city_id = isset($_POST['city_id']) ? sanitize_text_field($_POST['city_id']) : '';
    if (!$city_id) {
        wp_send_json_error('ID kota tidak valid');
    }

    $today = date('Y-m-d');
    $api_url = "https://api.myquran.com/v2/sholat/jadwal/{$city_id}/{$today}";

    $response = wp_remote_get($api_url);
    if (is_wp_error($response)) {
        wp_send_json_error('Gagal mengambil data jadwal sholat');
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (!isset($data['data']['jadwal'])) {
        wp_send_json_error('Format data tidak sesuai.');
    }

    wp_send_json_success($data['data']['jadwal']);
}
add_action('wp_ajax_get_sholat_schedule', 'get_sholat_schedule');
add_action('wp_ajax_nopriv_get_sholat_schedule', 'get_sholat_schedule');
