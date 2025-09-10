jQuery(document).ready(function ($) {
  // Load kota list on document ready
  loadKotaList();

  // Handle change events
  $('#jsm-select-kota').on('change', function () {
    loadJadwalSholat();
  });

  // Set default kota if available
  function setDefaultKota() {
    if (jsm_ajax.default_kota) {
      // Set nilai dropdown ke kota default
      $('#jsm-select-kota').val(jsm_ajax.default_kota);

      // Load jadwal untuk kota default
      loadJadwalSholat();
    }
  }

  function loadKotaList() {
    $.ajax({
      url: jsm_ajax.ajax_url,
      type: 'POST',
      data: {
        action: 'get_kota_list',
        nonce: jsm_ajax.nonce,
      },
      beforeSend: function () {
        $('#jsm-select-kota').prop('disabled', true);
        $('#jsm-loading').show();
      },
      success: function (response) {
        if (response.success) {
          var options = '<option value="">Pilih Kota...</option>';
          $.each(response.data, function (index, kota) {
            var selected = kota.id == jsm_ajax.default_kota ? 'selected' : '';
            options +=
              '<option value="' +
              kota.id +
              '" ' +
              selected +
              '>' +
              kota.lokasi +
              '</option>';
          });
          $('#jsm-select-kota').html(options);

          // Jika ada kota default, langsung load jadwal
          if (jsm_ajax.default_kota) {
            loadJadwalSholat();
          }
        } else {
          showError('Gagal memuat daftar kota');
        }
      },
      error: function () {
        showError('Terjadi kesalahan saat memuat daftar kota');
      },
      complete: function () {
        $('#jsm-select-kota').prop('disabled', false);
        $('#jsm-loading').hide();
      },
    });
  }

  function loadJadwalSholat() {
    var kotaId = $('#jsm-select-kota').val();

    if (!kotaId) {
      $('#jsm-result').html('');
      return;
    }

    $.ajax({
      url: jsm_ajax.ajax_url,
      type: 'POST',
      data: {
        action: 'get_jadwal_sholat',
        nonce: jsm_ajax.nonce,
        kota_id: kotaId,
      },
      beforeSend: function () {
        $('#jsm-loading').show();
        $('#jsm-result').html('');
        $('#jsm-error').hide();
      },
      success: function (response) {
        if (response.success) {
          $('#jsm-result').html(response.data);
        } else {
          showError(response.data);
        }
      },
      error: function () {
        showError('Terjadi kesalahan saat memuat jadwal sholat');
      },
      complete: function () {
        $('#jsm-loading').hide();
      },
    });
  }

  function showError(message) {
    $('#jsm-error').html(message).show();
  }

  // Auto-refresh jadwal every minute
  setInterval(function () {
    if ($('#jsm-select-kota').val()) {
      loadJadwalSholat();
    }
  }, 60000); // 60 seconds
});
