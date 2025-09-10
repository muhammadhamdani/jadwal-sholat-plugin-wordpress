<?php
class Jadwal_Sholat_MyQuran
{

    private $default_kota;
    private $desain_tema;

    public function __construct()
    {
        $options = get_option('jsm_settings');
        $this->default_kota = isset($options['jsm_default_kota']) ? $options['jsm_default_kota'] : '1101';
        $this->desain_tema = isset($options['jsm_desain_tema']) ? $options['jsm_desain_tema'] : 'modern';
    }

    public function init()
    {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('jadwal_sholat', array($this, 'jadwal_sholat_shortcode'));
        add_action('wp_ajax_get_jadwal_sholat', array($this, 'get_jadwal_sholat_ajax'));
        add_action('wp_ajax_nopriv_get_jadwal_sholat', array($this, 'get_jadwal_sholat_ajax'));
        add_action('wp_ajax_get_kota_list', array($this, 'get_kota_list_ajax'));
        add_action('wp_ajax_nopriv_get_kota_list', array($this, 'get_kota_list_ajax'));

        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
    }

    public static function activate()
    {
        // Set default options
        if (!get_option('jsm_settings')) {
            add_option('jsm_settings', array(
                'jsm_default_kota' => '1101',
                'jsm_desain_tema' => 'modern'
            ));
        }
    }

    public static function deactivate()
    {
        // Clean up if needed
    }

    public function enqueue_scripts()
    {
        wp_enqueue_style('jadwal-sholat-style', JSM_PLUGIN_URL . 'assets/css/style.css', array(), JSM_VERSION);

        // Load theme-specific CSS hanya jika bukan tema modern default
        if ($this->desain_tema != 'modern') {
            wp_enqueue_style('jadwal-sholat-theme', JSM_PLUGIN_URL . 'assets/css/themes/' . $this->desain_tema . '.css', array('jadwal-sholat-style'), JSM_VERSION);
        } else {
            // Untuk tema modern, load CSS modern secara langsung
            wp_enqueue_style('jadwal-sholat-modern', JSM_PLUGIN_URL . 'assets/css/themes/modern.css', array('jadwal-sholat-style'), JSM_VERSION);
        }

        wp_enqueue_script('jadwal-sholat-script', JSM_PLUGIN_URL . 'assets/js/script.js', array('jquery'), JSM_VERSION, true);

        wp_localize_script('jadwal-sholat-script', 'jsm_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('jsm_nonce'),
            'default_kota' => $this->default_kota,
            'today' => date('Y-m-d'),
            'desain_tema' => $this->desain_tema
        ));
    }
    
    public function jadwal_sholat_shortcode($atts)
    {
        $atts = shortcode_atts(array(
            'kota' => $this->default_kota,
            'tema' => $this->desain_tema
        ), $atts, 'jadwal_sholat');

        // Override theme if specified in shortcode
        $tema = $atts['tema'];

        ob_start();
?>
        <div class="jadwal-sholat-container jsm-tema-<?php echo esc_attr($tema); ?>">
            <div class="jsm-header">
                <h3>Jadwal Sholat Hari Ini</h3>
                <div class="jsm-date-display"><?php echo date_i18n('l, j F Y'); ?></div>
                <div class="jsm-controls">
                    <select id="jsm-select-kota" class="jsm-select">
                        <option value="">Pilih Kota...</option>
                    </select>
                </div>
            </div>
            <div id="jsm-loading" class="jsm-loading" style="display: none;">
                <div class="jsm-spinner"></div>
                <p>Memuat data...</p>
            </div>
            <div id="jsm-result" class="jsm-result"></div>
            <div id="jsm-error" class="jsm-error" style="display: none;"></div>
        </div>
    <?php
        return ob_get_clean();
    }

    public function get_kota_list_ajax()
    {
        check_ajax_referer('jsm_nonce', 'nonce');

        $cached_data = get_transient('jsm_kota_list');

        if ($cached_data) {
            wp_send_json_success($cached_data);
            return;
        }

        $response = wp_remote_get('https://api.myquran.com/v2/sholat/kota/semua');

        if (is_wp_error($response)) {
            wp_send_json_error('Gagal mengambil data kota: ' . $response->get_error_message());
            return;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($data && isset($data['data'])) {
            set_transient('jsm_kota_list', $data['data'], DAY_IN_SECONDS);
            wp_send_json_success($data['data']);
        } else {
            wp_send_json_error('Data kota tidak ditemukan');
        }
    }

    public function get_jadwal_sholat_ajax()
    {
        check_ajax_referer('jsm_nonce', 'nonce');

        $kota_id = isset($_POST['kota_id']) ? intval($_POST['kota_id']) : 0;
        $tanggal = date('Y-m-d'); // Selalu gunakan tanggal hari ini

        if (!$kota_id) {
            wp_send_json_error('Pilih kota terlebih dahulu');
            return;
        }

        $response = wp_remote_get("https://api.myquran.com/v2/sholat/jadwal/{$kota_id}/{$tanggal}");

        if (is_wp_error($response)) {
            wp_send_json_error('Gagal mengambil jadwal sholat: ' . $response->get_error_message());
            return;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($data && isset($data['data'])) {
            ob_start();
            $this->display_jadwal($data['data']);
            $html = ob_get_clean();
            wp_send_json_success($html);
        } else {
            wp_send_json_error('Jadwal sholat tidak ditemukan');
        }
    }

    private function display_jadwal($data)
    {
        if (!isset($data['jadwal'])) {
            echo '<p>Data jadwal tidak tersedia</p>';
            return;
        }

        $jadwal = $data['jadwal'];
        $waktu_sekarang = current_time('H:i');
        $waktu_sholat = [
            'subuh' => $jadwal['subuh'],
            'dzuhur' => $jadwal['dzuhur'],
            'ashar' => $jadwal['ashar'],
            'maghrib' => $jadwal['maghrib'],
            'isya' => $jadwal['isya']
        ];

        // Tentukan sholat berikutnya
        $sholat_berikutnya = '';
        $waktu_berikutnya = '';
        foreach ($waktu_sholat as $nama => $waktu) {
            if ($waktu > $waktu_sekarang) {
                $sholat_berikutnya = $nama;
                $waktu_berikutnya = $waktu;
                break;
            }
        }

        if (empty($sholat_berikutnya)) {
            $sholat_berikutnya = 'subuh'; // Jika sudah lewat isya, maka subuh esok hari
            $waktu_berikutnya = $waktu_sholat['subuh'];
        }

        // Hitung countdown jika sholat berikutnya adalah hari ini
        $countdown_html = '';
        if ($sholat_berikutnya && $waktu_berikutnya) {
            $countdown_html = $this->generate_countdown_html($waktu_berikutnya);
        }
    ?>
        <div class="jsm-jadwal">
            <div class="jsm-lokasi">
                <h4><?php echo esc_html($data['lokasi']); ?></h4>
                <p><?php echo esc_html($data['daerah']); ?></p>
            </div>

            <?php if ($sholat_berikutnya && $waktu_berikutnya) : ?>
                <div class="jsm-next-prayer">
                    <p>Sholat berikutnya: <strong><?php echo ucfirst($sholat_berikutnya); ?></strong> pukul <strong><?php echo $waktu_berikutnya; ?></strong></p>
                    <?php echo $countdown_html; ?>
                </div>
            <?php endif; ?>

            <div class="jsm-waktu-list">
                <div class="jsm-waktu-item <?php echo $sholat_berikutnya === 'subuh' ? 'jsm-next' : ''; ?>">
                    <span class="jsm-waktu-label">Subuh</span>
                    <span class="jsm-waktu-value"><?php echo esc_html($jadwal['subuh']); ?></span>
                </div>
                <div class="jsm-waktu-item">
                    <span class="jsm-waktu-label">Terbit</span>
                    <span class="jsm-waktu-value"><?php echo esc_html($jadwal['terbit']); ?></span>
                </div>
                <div class="jsm-waktu-item">
                    <span class="jsm-waktu-label">Dhuha</span>
                    <span class="jsm-waktu-value"><?php echo esc_html($jadwal['dhuha']); ?></span>
                </div>
                <div class="jsm-waktu-item <?php echo $sholat_berikutnya === 'dzuhur' ? 'jsm-next' : ''; ?>">
                    <span class="jsm-waktu-label">Dzuhur</span>
                    <span class="jsm-waktu-value"><?php echo esc_html($jadwal['dzuhur']); ?></span>
                </div>
                <div class="jsm-waktu-item <?php echo $sholat_berikutnya === 'ashar' ? 'jsm-next' : ''; ?>">
                    <span class="jsm-waktu-label">Ashar</span>
                    <span class="jsm-waktu-value"><?php echo esc_html($jadwal['ashar']); ?></span>
                </div>
                <div class="jsm-waktu-item <?php echo $sholat_berikutnya === 'maghrib' ? 'jsm-next' : ''; ?>">
                    <span class="jsm-waktu-label">Maghrib</span>
                    <span class="jsm-waktu-value"><?php echo esc_html($jadwal['maghrib']); ?></span>
                </div>
                <div class="jsm-waktu-item <?php echo $sholat_berikutnya === 'isya' ? 'jsm-next' : ''; ?>">
                    <span class="jsm-waktu-label">Isya</span>
                    <span class="jsm-waktu-value"><?php echo esc_html($jadwal['isya']); ?></span>
                </div>
            </div>

            <div class="jsm-footer">
                <p>Terakhir diperbarui: <?php echo date('H:i:s'); ?></p>
            </div>
        </div>
    <?php
    }

    private function generate_countdown_html($waktu_sholat)
    {
        return '
        <div class="jsm-countdown">
            <div class="jsm-countdown-text">Menuju sholat:</div>
            <div class="jsm-countdown-timer">
                <span id="jsm-countdown-hours">00</span>:
                <span id="jsm-countdown-minutes">00</span>:
                <span id="jsm-countdown-seconds">00</span>
            </div>
        </div>
        <script>
            jQuery(document).ready(function($) {
                function updateCountdown() {
                    var now = new Date();
                    var targetTime = new Date();
                    var timeParts = "' . esc_js($waktu_sholat) . '".split(":");
                    
                    targetTime.setHours(parseInt(timeParts[0]), parseInt(timeParts[1]), 0, 0);
                    
                    if (targetTime < now) {
                        targetTime.setDate(targetTime.getDate() + 1);
                    }
                    
                    var diff = targetTime - now;
                    var hours = Math.floor(diff / (1000 * 60 * 60));
                    var minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                    var seconds = Math.floor((diff % (1000 * 60)) / 1000);
                    
                    $("#jsm-countdown-hours").text(hours.toString().padStart(2, "0"));
                    $("#jsm-countdown-minutes").text(minutes.toString().padStart(2, "0"));
                    $("#jsm-countdown-seconds").text(seconds.toString().padStart(2, "0"));
                }
                
                updateCountdown();
                setInterval(updateCountdown, 1000);
            });
        </script>';
    }

    public function add_admin_menu()
    {
        add_options_page(
            'Jadwal Sholat Settings',
            'Jadwal Sholat',
            'manage_options',
            'jadwal-sholat',
            array($this, 'options_page')
        );
    }

    public function settings_init()
    {
        register_setting('jsm_pluginPage', 'jsm_settings');

        add_settings_section(
            'jsm_pluginPage_section',
            'Pengaturan Jadwal Sholat',
            array($this, 'settings_section_callback'),
            'jsm_pluginPage'
        );

        add_settings_field(
            'jsm_default_kota',
            'Kota Default',
            array($this, 'default_kota_render'),
            'jsm_pluginPage',
            'jsm_pluginPage_section'
        );

        add_settings_field(
            'jsm_desain_tema',
            'Tema Desain',
            array($this, 'desain_tema_render'),
            'jsm_pluginPage',
            'jsm_pluginPage_section'
        );
    }

    public function default_kota_render()
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

    public function desain_tema_render()
    {
        $options = get_option('jsm_settings');
        $desain_tema = isset($options['jsm_desain_tema']) ? $options['jsm_desain_tema'] : 'modern';
    ?>
        <select name='jsm_settings[jsm_desain_tema]' id='jsm_desain_tema'>
            <option value="modern" <?php selected($desain_tema, 'modern'); ?>>Modern</option>
            <option value="islamic" <?php selected($desain_tema, 'islamic'); ?>>Islamic</option>
            <option value="minimal" <?php selected($desain_tema, 'minimal'); ?>>Minimal</option>
            <option value="dark" <?php selected($desain_tema, 'dark'); ?>>Dark Mode</option>
        </select>
        <p class="description">Pilih tema desain untuk tampilan jadwal sholat.</p>
    <?php
    }

    public function settings_section_callback()
    {
        echo '<p>Atur pengaturan default untuk plugin Jadwal Sholat</p>';
    }

    public function options_page()
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

            <div class="jsm-theme-previews">
                <h3>Pratinjau Tema</h3>
                <div class="jsm-theme-preview">
                    <h4>Modern</h4>
                    <div class="jsm-preview-image modern-preview"></div>
                </div>
                <div class="jsm-theme-preview">
                    <h4>Islamic</h4>
                    <div class="jsm-preview-image islamic-preview"></div>
                </div>
                <div class="jsm-theme-preview">
                    <h4>Minimal</h4>
                    <div class="jsm-preview-image minimal-preview"></div>
                </div>
                <div class="jsm-theme-preview">
                    <h4>Dark Mode</h4>
                    <div class="jsm-preview-image dark-preview"></div>
                </div>
            </div>
        </div>

        <style>
            .jsm-theme-previews {
                display: flex;
                flex-wrap: wrap;
                gap: 20px;
                margin-top: 30px;
            }

            .jsm-theme-preview {
                flex: 1;
                min-width: 200px;
                border: 1px solid #ddd;
                padding: 15px;
                border-radius: 5px;
            }

            .jsm-preview-image {
                height: 120px;
                border: 1px solid #eee;
                border-radius: 4px;
                background-size: cover;
                background-position: center;
            }

            .modern-preview {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            }

            .islamic-preview {
                background: linear-gradient(135deg, #4a8c5f 0%, #1e5631 100%);
            }

            .minimal-preview {
                background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            }

            .dark-preview {
                background: linear-gradient(135deg, #2c3e50 0%, #1a1a2e 100%);
            }
        </style>
<?php
    }
}
