<?php

if (!defined('WP_UNINSTALL_PLUGIN')) exit();

function cptm_uninstall() {
    global $wpdb;

    delete_option('cptm_version');
    delete_option('cptm_db_version');

    $posts = get_posts(array(
        'numberposts' => -1,
        'post_type' => $post_type,
        'post_status' => 'any'
    ));
    foreach ($posts as $post) {
        wp_delete_post($post->ID, true);
    }

    $table_name = $wpdb->prefix . CPTM_MAIN_TABLE;
    $wpdb->query("DROP TABLE IF EXISTS '$table_name'");
}

cptm_uninstall();
