jQuery(document).ready(function($) {
    $('#start-migration').on('click', function() {
        startMigration();
    });

    function startMigration() {
        $('#migration-status').text('در حال دریافت برندها...');

        $.post(ajaxurl, { action: 'get_brands_for_migration' }, function(response) {
            if (response.success) {
                const brands = response.data;
                const batchSize = 10;
                let offset = 0;

                function processBatch() {
                    const batch = brands.slice(offset, offset + batchSize);
                    if (batch.length === 0) {
                        $('#migration-status').text('مهاجرت تمام شد 🎉');
                        return;
                    }

                    $('#migration-status').text(`در حال مهاجرت برند ${offset + 1} تا ${offset + batch.length} از ${brands.length}`);

                    $.post(ajaxurl, { action: 'migrate_brands_batch', brands: batch }, function(response) {
                        if (response.success) {
                            offset += batchSize;
                            processBatch();
                        } else {
                            $('#migration-status').text('خطایی رخ داده است.');
                        }
                    }).fail(function() {
                        $('#migration-status').text('خطای شبکه هنگام ارسال درخواست.');
                    });
                }

                processBatch();
            } else {
                $('#migration-status').text('مشکل در دریافت برندها.');
            }
        });
    }
});
