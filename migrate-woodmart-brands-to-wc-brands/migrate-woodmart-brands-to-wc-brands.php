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
        $attribute_taxonomy = 'pa_brand'; // ویژگی برند قبلی
        $woo_brand_taxonomy = 'product_brand'; // برند جدید ووکامرس

        $term_exists = term_exists($brand['slug'], $woo_brand_taxonomy);

        if (!$term_exists) {
            $new_term = wp_insert_term($brand['name'], $woo_brand_taxonomy, [
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
        } else {
            $new_term_id = is_array($term_exists) ? $term_exists['term_id'] : $term_exists;
        }

        $products = get_posts([
            'post_type' => 'product',
            'posts_per_page' => -1,
            'tax_query' => [
                [
                    'taxonomy' => $attribute_taxonomy,
                    'field' => 'slug',
                    'terms' => $brand['slug'],
                ],
            ],
            'fields' => 'ids',
        ]);

        if (!empty($products)) {
            foreach ($products as $product_id) {
                wp_set_object_terms($product_id, intval($new_term_id), $woo_brand_taxonomy, true);
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