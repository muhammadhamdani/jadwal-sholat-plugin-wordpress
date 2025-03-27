jQuery(document).ready(function ($) {
    let defaultCityId = sholatAjax.defaultCityId;

    $('#sholat-city').html('<option value="">Memuat daftar kota...</option>');

    $.post(sholatAjax.ajaxurl, { action: 'get_kota_list' }, function (response) {
        if (response.success) {
            let options = '';
            response.data.forEach(city => {
                let selected = city.id === defaultCityId ? 'selected' : '';
                options += `<option value="${city.id}" ${selected}>${city.lokasi}</option>`;
            });
            $('#sholat-city').html(options);
            loadSholatSchedule(defaultCityId);
        } else {
            $('#sholat-city').html('<option value="">Gagal memuat data kota</option>');
        }
    });

    $('#sholat-city').on('change', function () {
        let cityId = $(this).val();
        if (cityId) {
            loadSholatSchedule(cityId);
        }
    });

    function loadSholatSchedule(cityId) {
        $('#sholat-result').html(`
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        `);

        $.post(sholatAjax.ajaxurl, {
            action: 'get_sholat_schedule',
            city_id: cityId,
            nonce: sholatAjax.nonce
        }, function (response) {
            if (response.success) {
                let jadwal = response.data;
                let html = `
                    <div class="card mt-3">
                        <div class="card-header text-center bg-success text-white">
                            <h6 class="mb-0">Jadwal Sholat (${jadwal.tanggal})</h6>
                        </div>
                        <div class="card-body p-2 table-responsive">
                            <table class="table table-sm table-bordered text-center">
                                <thead>
                                    <tr>
                                        <th>Imsak</th>
                                        <th>Subuh</th>
                                        <th>Terbit</th>
                                        <th>Dhuha</th>
                                        <th>Dzuhur</th>
                                        <th>Ashar</th>
                                        <th>Maghrib</th>
                                        <th>Isya</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>${jadwal.imsak}</td>
                                        <td>${jadwal.subuh}</td>
                                        <td>${jadwal.terbit}</td>
                                        <td>${jadwal.dhuha}</td>
                                        <td>${jadwal.dzuhur}</td>
                                        <td>${jadwal.ashar}</td>
                                        <td>${jadwal.maghrib}</td>
                                        <td>${jadwal.isya}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                `;
                $('#sholat-result').html(html);
            } else {
                $('#sholat-result').html('<div class="text-danger">Gagal memuat jadwal sholat</div>');
            }
        });
    }
});
