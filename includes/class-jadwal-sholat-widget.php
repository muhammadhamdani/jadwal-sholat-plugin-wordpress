<?php

/**
 * Widget untuk menampilkan jadwal sholat di sidebar
 */

class Jadwal_Sholat_Widget extends WP_Widget
{

    public function __construct()
    {
        parent::__construct(
            'jadwal_sholat_widget',
            'Jadwal Sholat',
            array(
                'description' => 'Menampilkan jadwal sholat hari ini di sidebar'
            )
        );
    }

    public function widget($args, $instance)
    {
        if (!class_exists('Jadwal_Sholat')) {
            return;
        }

        $jadwal_sholat = new Jadwal_Sholat();
        $jadwal_sholat->enqueue_scripts();

        echo wp_kses_post($args['before_widget']);

        $title = !empty($instance['title']) ? apply_filters('widget_title', $instance['title']) : 'Jadwal Sholat';
        $kota  = !empty($instance['kota']) ? $instance['kota'] : '';
        $tema  = !empty($instance['tema']) ? $instance['tema'] : 'modern';

        if (! empty($title)) {
            echo wp_kses_post($args['before_title']);
            echo esc_html($title);
            echo wp_kses_post($args['after_title']);
        }

        echo do_shortcode(
            sprintf(
                '[jadwal_sholat kota="%s" tema="%s"]',
                esc_attr($kota),
                esc_attr($tema)
            )
        );

        echo wp_kses_post($args['after_widget']);
    }


    public function form($instance)
    {
        $title = !empty($instance['title']) ? $instance['title'] : 'Jadwal Sholat';
        $kota = !empty($instance['kota']) ? $instance['kota'] : '';
        $tema = !empty($instance['tema']) ? $instance['tema'] : 'modern';

        // Get kota list from transient or API
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
?>

        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">
                <?php esc_html_e('Judul:', 'jadwal-sholat'); ?>
            </label>
            <input class="widefat"
                id="<?php echo esc_attr($this->get_field_id('title')); ?>"
                name="<?php echo esc_attr($this->get_field_name('title')); ?>"
                type="text"
                value="<?php echo esc_attr($title); ?>">
        </p>

        <p>
            <label for="<?php echo esc_attr($this->get_field_id('kota')); ?>">
                <?php esc_html_e('Kota Default:', 'jadwal-sholat'); ?>
            </label>
            <select class="widefat"
                id="<?php echo esc_attr($this->get_field_id('kota')); ?>"
                name="<?php echo esc_attr($this->get_field_name('kota')); ?>">
                <option value=""><?php esc_html_e('Pilih Kota...', 'jadwal-sholat'); ?></option>
                <?php if ($kota_list) : ?>
                    <?php foreach ($kota_list as $kota_item) : ?>
                        <option value="<?php echo esc_attr($kota_item['id']); ?>"
                            <?php selected($kota, $kota_item['id']); ?>>
                            <?php echo esc_html($kota_item['lokasi']); ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </p>

        <p>
            <label for="<?php echo esc_attr($this->get_field_id('tema')); ?>">
                <?php esc_html_e('Tema Desain:', 'jadwal-sholat'); ?>
            </label>
            <select class="widefat"
                id="<?php echo esc_attr($this->get_field_id('tema')); ?>"
                name="<?php echo esc_attr($this->get_field_name('tema')); ?>">
                <option value="modern" <?php selected($tema, 'modern'); ?>>Modern</option>
                <option value="islamic" <?php selected($tema, 'islamic'); ?>>Islamic</option>
                <option value="minimal" <?php selected($tema, 'minimal'); ?>>Minimal</option>
                <option value="dark" <?php selected($tema, 'dark'); ?>>Dark Mode</option>
            </select>
        </p>


<?php
    }

    public function update($new_instance, $old_instance)
    {
        $instance = array();
        $instance['title'] = ! empty($new_instance['title']) ? wp_strip_all_tags($new_instance['title']) : '';
        $instance['kota']  = ! empty($new_instance['kota'])  ? wp_strip_all_tags($new_instance['kota'])  : '';
        $instance['tema']  = ! empty($new_instance['tema'])  ? wp_strip_all_tags($new_instance['tema'])  : 'modern';

        return $instance;
    }
}
