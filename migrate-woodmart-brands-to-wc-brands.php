<?php
/*
Plugin Name: Migrate WoodMart Brands to WooCommerce Product Brands
Description: برندهای محصولات را از ویژگی‌های قالب WoodMart به برندهای محصولات ووکامرس منتقل کنید، فقط با یک کلیک جابجایی و انتقال برندها انجام می‌شود.
Version: 1.1
Author: Reza Mohammadzadeh
Author URI: https://github.com/rezamhmdzdh
*/

if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . 'includes/admin-page.php';

add_action('wp_ajax_get_brands_for_migration', function () {
    $terms = get_terms([
        'taxonomy' => 'pa_brand',
        'hide_empty' => false,
    ]);
    if (is_wp_error($terms)) {
        wp_send_json_error('مشکل در دریافت برندها');
    }
    $brands = [];
    foreach ($terms as $term) {
        $brands[] = [
            'id' => $term->term_id,
            'name' => $term->name,
            'slug' => $term->slug,
            'description' => $term->description,
        ];
    }
    wp_send_json_success($brands);
});

add_action('wp_ajax_migrate_brands_batch', function () {
    $brands = isset($_POST['brands']) ? $_POST['brands'] : [];
    $failed = [];
    foreach ($brands as $brand) {
        $term_exists = term_exists($brand['slug'], 'product_brand');
        if (!$term_exists) {
            $new_term = wp_insert_term($brand['name'], 'product_brand', [
                'slug' => $brand['slug'],
                'description' => $brand['description'],
            ]);
            if (is_wp_error($new_term)) {
                $failed[] = $brand['name'];
                continue;
            }
            $new_term_id = $new_term['term_id'];
            $image_meta = get_term_meta($brand['id'], 'image', true);
            if (!empty($image_meta)) {
                if (is_serialized($image_meta)) {
                    $image_meta = maybe_unserialize($image_meta);
                }
                if (!empty($image_meta['id'])) {
                    update_term_meta($new_term_id, 'thumbnail_id', intval($image_meta['id']));
                }
            }
        }
    }
    wp_send_json_success($failed);
});


add_filter('plugin_action_links_' . plugin_basename(__FILE__), function($links) {
    $settings_link = '<a href="' . admin_url('edit.php?post_type=product&page=migrate-woodmart-brands') . '">تنظیمات</a>';
    array_unshift($links, $settings_link);
    return $links;
});