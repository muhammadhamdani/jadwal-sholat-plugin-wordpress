<?php
// Add admin menu
add_action('admin_menu', 'jsm_add_admin_menu');
add_action('admin_init', 'jsm_settings_init');

function jsm_add_admin_menu()
{
    add_options_page(
        'Jadwal Sholat Settings',
        'Jadwal Sholat',
        'manage_options',
        'jadwal-sholat',
        'jsm_options_page'
    );
}

function jsm_settings_init()
{
    register_setting('jsm_pluginPage', 'jsm_settings', array(
        'sanitize_callback' => 'jsm_sanitize_settings'
    ));

    add_settings_section(
        'jsm_pluginPage_section',
        'Pengaturan Default Jadwal Sholat',
        'jsm_settings_section_callback',
        'jsm_pluginPage'
    );

    add_settings_field(
        'jsm_default_kota',
        'Kota Default',
        'jsm_default_kota_render',
        'jsm_pluginPage',
        'jsm_pluginPage_section'
    );
}

function jsm_sanitize_settings($input)
{
    $output = array();

    // Sanitasi kota default
    if (isset($input['jsm_default_kota'])) {
        $output['jsm_default_kota'] = sanitize_text_field($input['jsm_default_kota']);
    }

    return $output;
}

function jsm_default_kota_render()
{
    $options = get_option('jsm_settings');
    $default_kota = isset($options['jsm_default_kota']) ? $options['jsm_default_kota'] : '1101';
?>
    <select name='jsm_settings[jsm_default_kota]' id='jsm_default_kota'>
        <option value="">Pilih Kota Default...</option>
        <?php
        $kota_list = get_transient('jsm_kota_list');
        if (!$kota_list) {
            $response = wp_remote_get('https://api.myquran.com/v2/sholat/kota/semua');
            if (!is_wp_error($response)) {
                $body = wp_remote_retrieve_body($response);
                $data = json_decode($body, true);
                if ($data && isset($data['data'])) {
                    $kota_list = $data['data'];
                    set_transient('jsm_kota_list', $kota_list, DAY_IN_SECONDS);
                }
            }
        }

        if ($kota_list) {
            foreach ($kota_list as $kota) {
                echo '<option value="' . esc_attr($kota['id']) . '" ' . selected($default_kota, $kota['id'], false) . '>' . esc_html($kota['lokasi']) . '</option>';
            }
        }
        ?>
    </select>
    <p class="description">Pilih kota default yang akan ditampilkan ketika plugin pertama kali dimuat.</p>
<?php
}

function jsm_settings_section_callback()
{
    echo '<p>Atur pengaturan default untuk plugin Jadwal Sholat</p>';
}

function jsm_options_page()
{
?>
    <div class="wrap">
        <h1>Pengaturan Jadwal Sholat</h1>
        <form action='options.php' method='post'>
            <?php
            settings_fields('jsm_pluginPage');
            do_settings_sections('jsm_pluginPage');
            submit_button();
            ?>
        </form>
    </div>
<?php
}
