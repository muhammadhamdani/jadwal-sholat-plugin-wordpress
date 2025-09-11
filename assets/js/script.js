jQuery(document).ready(function ($) {
  // Initialize each jadwal sholat container
  $('.jadwal-sholat-container').each(function (index) {
    initJadwalSholat($(this), index);
  });

  function initJadwalSholat($container, index) {
    var defaultKota = $container.data('kota') || jsm_ajax.default_kota;
    var $selectKota = $container.find('.jsm-select-kota');
    var $loading = $container.find('.jsm-loading');
    var $result = $container.find('.jsm-result');
    var $error = $container.find('.jsm-error');

    // Generate unique IDs for this instance
    var uniqueId = 'jsm-' + index + '-';

    // Load kota list
    loadKotaList($selectKota, defaultKota, function () {
      // Set default kota and load jadwal
      if (defaultKota) {
        $selectKota.val(defaultKota);
        loadJadwalSholat($container, defaultKota, uniqueId);
      }
    });

    // Handle change events
    $selectKota.on('change', function () {
      loadJadwalSholat($container, $(this).val(), uniqueId);
    });
  }

  function loadKotaList($selectElement, defaultKota, callback) {
    $.ajax({
      url: jsm_ajax.ajax_url,
      type: 'POST',
      data: {
        action: 'get_kota_list',
        nonce: jsm_ajax.nonce,
      },
      beforeSend: function () {
        $selectElement.prop('disabled', true);
      },
      success: function (response) {
        if (response.success) {
          var options = '<option value="">Pilih Kota...</option>';
          $.each(response.data, function (index, kota) {
            var selected = kota.id == defaultKota ? 'selected' : '';
            options +=
              '<option value="' +
              kota.id +
              '" ' +
              selected +
              '>' +
              kota.lokasi +
              '</option>';
          });
          $selectElement.html(options);

          if (typeof callback === 'function') {
            callback();
          }
        } else {
          showError(
            $selectElement
              .closest('.jadwal-sholat-container')
              .find('.jsm-error'),
            'Gagal memuat daftar kota'
          );
        }
      },
      error: function () {
        showError(
          $selectElement.closest('.jadwal-sholat-container').find('.jsm-error'),
          'Terjadi kesalahan saat memuat daftar kota'
        );
      },
      complete: function () {
        $selectElement.prop('disabled', false);
      },
    });
  }

  function loadJadwalSholat($container, kotaId, uniqueId) {
    var $loading = $container.find('.jsm-loading');
    var $result = $container.find('.jsm-result');
    var $error = $container.find('.jsm-error');

    if (!kotaId) {
      $result.html('');
      return;
    }

    $.ajax({
      url: jsm_ajax.ajax_url,
      type: 'POST',
      data: {
        action: 'get_jadwal_sholat',
        nonce: jsm_ajax.nonce,
        kota_id: kotaId,
        unique_id: uniqueId, // Kirim unique ID ke server
      },
      beforeSend: function () {
        $loading.show();
        $result.html('');
        $error.hide();
      },
      success: function (response) {
        if (response.success) {
          $result.html(response.data);
          // Initialize countdown for this instance
          initCountdown(uniqueId);
        } else {
          showError($error, response.data);
        }
      },
      error: function () {
        showError($error, 'Terjadi kesalahan saat memuat jadwal sholat');
      },
      complete: function () {
        $loading.hide();
      },
    });
  }

  function showError($errorElement, message) {
    $errorElement.html(message).show();
  }

  function initCountdown(uniqueId) {
    // Gunakan unique ID untuk selector
    var $hours = $('#' + uniqueId + 'countdown-hours');
    var $minutes = $('#' + uniqueId + 'countdown-minutes');
    var $seconds = $('#' + uniqueId + 'countdown-seconds');

    // Cek jika element countdown ada
    if ($hours.length && $minutes.length && $seconds.length) {
      var waktuSholat = $hours.closest('.jsm-countdown').data('waktu');

      if (waktuSholat) {
        updateCountdown(uniqueId, waktuSholat);

        // Set interval untuk countdown ini saja
        setInterval(function () {
          updateCountdown(uniqueId, waktuSholat);
        }, 1000);
      }
    }
  }

  function updateCountdown(uniqueId, waktuSholat) {
    var $hours = $('#' + uniqueId + 'countdown-hours');
    var $minutes = $('#' + uniqueId + 'countdown-minutes');
    var $seconds = $('#' + uniqueId + 'countdown-seconds');

    if (!$hours.length || !$minutes.length || !$seconds.length) return;

    var now = new Date();
    var targetTime = new Date();
    var timeParts = waktuSholat.split(':');

    targetTime.setHours(parseInt(timeParts[0]), parseInt(timeParts[1]), 0, 0);

    if (targetTime < now) {
      targetTime.setDate(targetTime.getDate() + 1);
    }

    var diff = targetTime - now;
    var hours = Math.floor(diff / (1000 * 60 * 60));
    var minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
    var seconds = Math.floor((diff % (1000 * 60)) / 1000);

    $hours.text(hours.toString().padStart(2, '0'));
    $minutes.text(minutes.toString().padStart(2, '0'));
    $seconds.text(seconds.toString().padStart(2, '0'));
  }

  // Auto-refresh jadwal every minute
  setInterval(function () {
    $('.jadwal-sholat-container').each(function (index) {
      var $container = $(this);
      var kotaId = $container.find('.jsm-select-kota').val();
      var uniqueId = 'jsm-' + index + '-';
      if (kotaId) {
        loadJadwalSholat($container, kotaId, uniqueId);
      }
    });
  }, 60000); // 60 seconds
});
