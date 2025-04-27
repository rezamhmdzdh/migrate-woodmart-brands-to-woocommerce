jQuery(document).ready(function($) {
    $('#start-migration').on('click', function() {
        $('#migration-message').html('');
        $.post(ajaxurl, {action: 'get_brands_for_migration'}, function(response) {
            if (response.success) {
                let brands = response.data;
                let total = brands.length;
                let batchSize = 10;
                let current = 0;
                let failedBrands = [];

                function migrateNextBatch() {
                    if (current >= total) {
                        $('#migration-message').html('<b>عملیات با موفقیت انجام شد.</b>');

                        if (failedBrands.length > 0) {
                            $('#migration-message').append('<br>برندهای منتقل نشده: ' + failedBrands.join(', '));
                        }

                        $('#progress-text').text('درصد پیشرفت: 100%');
                        return;
                    }
                    let batch = brands.slice(current, current + batchSize);
                    $.post(ajaxurl, {action: 'migrate_brands_batch', brands: batch}, function(res) {
                        if (res.success) {
                            failedBrands = failedBrands.concat(res.data);
                        }
                        current += batchSize;
                        let percent = Math.min(100, Math.round((current / total) * 100));
                        $('#progress-text').text('درصد پیشرفت: ' + percent + '%');
                        migrateNextBatch();
                    });
                }

                migrateNextBatch();
            } else {
                $('#migration-message').html('خطا در دریافت برندها');
            }
        });
    });
});
