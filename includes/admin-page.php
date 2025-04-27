<?php
add_action('admin_menu', function() {
    add_submenu_page(
        'edit.php?post_type=product',
        'مهاجرت برندها',
        'مهاجرت برندها',
        'manage_options',
        'migrate-woodmart-brands',
        'render_migrate_brands_page'
    );
});

function render_migrate_brands_page() {
    ?>
    <div class="wrap">
        <h1>مهاجرت ویژگی برندها به برندهای ووکامرس</h1>
        <button id="start-migration" class="button button-primary">شروع عملیات مهاجرت</button>
        <div id="migration-status" style="text-align: center; margin-top: 30px;">
            <div id="progress-text" style="margin-top: 10px;">درصد پیشرفت: 0%</div>
            <div id="migration-message" style="margin-top: 20px;"></div>
        </div>
    </div>
    <?php
    wp_enqueue_script('migrate-brands-script', plugin_dir_url(__FILE__) . '../assets/migrate.js', ['jquery'], '1.0', true);
}