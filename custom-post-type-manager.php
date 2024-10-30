<?php
/*
Plugin Name: Custom Post Type Manager
Plugin URI:  http://wordpress.org/plugins/custom-post-type-manager/
Description: Create the custom post type.
Author: MKT-SYSTEM
Author URI: http://mkt-system.jp/
Version: 0.9
License: GPL2
License URI: license.txt
Text Domain: cptm
Domain Path: /lang
*/

if (!defined('ABSPATH')) exit;

// プラグイン情報
define('CPTM_VERSION', '1.0');
define('CPTM_DB_VERSION', '1.0');

define('CPTM_MAIN_TABLE', 'custom_posts');

if (!defined('CPTM_PLUGIN_PATH'))
    define('CPTM_PLUGIN_PATH', untrailingslashit(dirname(__FILE__)));

if (!defined('CPTM_PLUGIN_URL'))
    define('CPTM_PLUGIN_URL', plugins_url('', __FILE__));

// 外部ファイル読み込み
require_once CPTM_PLUGIN_PATH . '/cptm-core.php';
require_once CPTM_PLUGIN_PATH . '/cptm-admin.php';

// プラグイン有効時の処理
register_activation_hook(__FILE__, 'cptm_install');
// プラグイン無効時の処理
register_deactivation_hook(__FILE__, 'cptm_deactive');

// プラグイン用翻訳ファイル読み込み
load_plugin_textdomain('cptm', false, dirname(plugin_basename(__FILE__)) . '/lang/');

// メニュー追加
function cptm_admin_menu() {
    add_menu_page(__('カスタム投稿設定', 'cptm'), __('カスタム投稿設定', 'cptm'), 'administrator', __FILE__, 'cptm_config');
}

// アクション追加
add_action('admin_menu', 'cptm_admin_menu');


#### ここから ####
// 設定取得
    function custom_post_settings() {
        global $wpdb;
        $sql = <<< EOF
SELECT
 name,menu,post_type,supports,menu_position,menu_icon,category,taxonomy
FROM
 {$wpdb->prefix}custom_posts
WHERE
 stop_flag = 0
EOF;
        $custom_posts = $wpdb->get_results($sql, ARRAY_A);

        return $custom_posts;
    }

// カスタム投稿タイプを作成
    add_action('init', 'custom_post_init');
    function custom_post_init() {
        $settings = custom_post_settings();

        foreach ($settings as $setting) {
            $labels = array(
                'name' => __($setting['menu'], 'cptm'),
                'singular_name' => sprintf(__('%s一覧', 'cptm'), $setting['name']),
                'add_new' => __('新規追加', 'cptm'),
                'add_new_item' => sprintf(__('%sを追加', 'cptm'), $setting['name']),
                'edit_item' => sprintf(__('%sを編集', 'cptm'), $setting['name']),
                'new_item' => sprintf(__('新しい%s', 'cptm'), $setting['name']),
                'view_item' =>sprintf( __('%sを表示', 'cptm'), $setting['name']),
                'search_items' => sprintf(__('%sを探す', 'cptm'), $setting['name']),
                'not_found' =>  sprintf(__('%sはありません', 'cptm'), $setting['name']),
                'not_found_in_trash' => sprintf(__('ゴミ箱に%sはありません', 'cptm'), $setting['name']),
                'parent_item_colon' => ''
            );
            $args = array(
                'labels' => $labels,
                'description' => '',
                'public' => true,
                'capability_type' => 'post',
                'hierarchical' => false,
                'supports' => json_decode($setting['supports']),
                'has_archive' => true,
                'menu_position' => (int)$setting['menu_position'],
                'rewrite' => true
            );
            if ((!empty($setting['menu_icon'])) && (!is_null($setting['menu_icon'])) && ($setting['menu_icon'] != 'null')) {
                $args['menu_icon'] = CPTM_PLUGIN_URL . '/icons/' . $setting['menu_icon'] . '.png';
            }
            register_post_type($setting['post_type'], $args);

            if ($setting['category'] == true) {
                // カテゴリータイプ
                $args = array(
                    'label' => __('Category', 'cptm'),
                    'public' => true,
                    'show_ui' => true,
                    'hierarchical' => true
                );
                register_taxonomy($setting['post_type'] . '_category', $setting['post_type'], $args);
            }
 
            if ($setting['taxonomy'] == true) {
                // タグタイプ
                $args = array(
                    'label' => __('Tag', 'cptm'),
                    'public' => true,
                    'show_ui' => true,
                    'hierarchical' => false
                );
                register_taxonomy($setting['post_type'] . '_tag', $setting['post_type'], $args);
            }
        }
    }

// 投稿時のメッセージ
    add_filter('post_updated_messages', 'custom_updated_messages');
    function custom_updated_messages($messages) {
        global $post;
        $settings = custom_post_settings();

        foreach ($settings as $setting) {
            $messages[$setting['post_type']] = array(
                0 => '',// 使用しない
                1 => sprintf(__('%sを更新しました <a href="%s">記事を見る</a>', 'cptm'), $setting['name'], esc_url(get_permalink($post->ID))),
                2 => __('カスタムフィールドを更新しました', 'cptm'),
                3 => __('カスタムフィールドを削除しました', 'cptm'),
                4 => sprintf(__('%s更新'), $setting['name']),
                5 => isset($_GET['revision']) ? sprintf(__('%s 前に%sを保存しました', 'cptm'), wp_post_revision_title((int)$_GET['revision'], false), $setting['name']) : false,
                6 => sprintf(__('%sが公開されました <a href="%s">記事を見る</a>', 'cptm'), $setting['name'], esc_url(get_permalink($post->ID))),
                7 => sprintf(__('%s記事を保存'), $setting['name'], 'cptm'),
                8 => sprintf(__('%s記事を送信 <a target="_blank" href="%s">プレビュー</a>', 'cptm'), $setting['name'], esc_url(add_query_arg('preview', 'true', get_permalink($post->ID)))),
                9 => sprintf(__('%1$sを予約投稿しました: <strong>%2$s</strong>. <a target="_blank" href="%3$s">プレビュー</a>', 'cptm'), $setting['name'], date_i18n(__('M j, Y @ G:i'), strtotime($post->post_date)), esc_url(get_permalink($post->ID))),
                10 => sprintf(__('%sの下書きを更新しました <a target="_blank" href="%s">プレビュー</a>', 'cptm'), $setting['name'], esc_url(add_query_arg('preview', 'true', get_permalink($post->ID)))),
            );

            return $messages;
        }
    }

    function my_getarchives_where($where, $r) {
        global $my_archives_post_type;

        $my_archives_post_type = '';
        if (isset($r['post_type'])) {
            $my_archives_post_type = $r['post_type'];
            $where = str_replace('"post"', '"' . $r['post_type'] . '"', $where);
        }
        return $where;
    }

    function my_get_archives_link($link_html) {
        global $my_archives_post_type;

        if ($my_archives_post_type != '') {
            $add_link .= '?post_type=' . $my_archives_post_type;
            $link_html = preg_replace('/href=\'(.+)\'\s/', 'href="$1' . $add_link . '"', $link_html);
        }
        return $link_html;
    }
