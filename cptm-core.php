<?php
if (!defined('ABSPATH')) exit;

function cptm_install() {
    global $wpdb;

    $table_name = $wpdb->prefix . CPTM_MAIN_TABLE;

    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $query = <<< EOF
CREATE TABLE `{$table_name}` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '識別番号',
  `position` int(10) DEFAULT NULL COMMENT '表示順',
  `name` varchar(100) DEFAULT NULL COMMENT 'カスタム名',
  `menu` varchar(100) DEFAULT NULL COMMENT 'メニュー表示名',
  `post_type` varchar(50) DEFAULT NULL COMMENT '短縮名',
  `supports` text COMMENT '機能',
  `menu_position` smallint(2) DEFAULT NULL COMMENT 'メニューの位置',
  `menu_icon` varchar(50) DEFAULT NULL COMMENT 'メニューのアイコン',
  `category` tinyint(1) unsigned DEFAULT '0' COMMENT 'カテゴリーフラグ',
  `taxonomy` tinyint(1) unsigned DEFAULT '0' COMMENT 'タグフラグ',
  `stop_flag` tinyint(1) unsigned DEFAULT '0' COMMENT '停止フラグ',
  `created` datetime DEFAULT NULL COMMENT '作成日',
  `updated` datetime DEFAULT NULL COMMENT '更新日',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
EOF;

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($query);

        add_option('cptm_version', CPTM_VERSION);
        add_option('cptm_db_version', CPTM_DB_VERSION);
    }
}
