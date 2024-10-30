<?php
if (!defined('ABSPATH')) exit;

    function cptm_config() {
        global $wpdb;

        $table_name = $wpdb->prefix . CPTM_MAIN_TABLE;

        $url = admin_url('admin.php?page=' . $_GET['page']);

        if (isset($_GET['message'])) {
            $message = $_GET['message'];
            $messages = array(
                '1' => __('Item added.'),
                '2' => __('Item deleted.'),
                '3' => __('Item updated.'),
                '4' => __('Item not added.'),
                '5' => __('Item not updated.'),
                '6' => __('Error in deleting…')
            );
            if (array_key_exists($message, $messages)) {
                echo '<div class="updated"><p><strong>' . $messages[$message] . '</strong></p></div>';
            }
        }

        if (!empty($_POST)) {
            $nonce = $_REQUEST['_wpnonce'];
            if (!wp_verify_nonce($nonce, 'cptm-nonce')) die('Security check'); 

            // 保存処理
            $_POST['supports'] = (array_key_exists('supports', $_POST)) ? $_POST['supports'] : array('title', 'editor');
            $_data = array(
                'name' => $_POST['name'],
                'menu' => $_POST['menu'],
                'post_type' => $_POST['post_type'],
                'supports' => json_encode($_POST['supports']),
                'menu_position' => $_POST['menu_position'],
                'menu_icon' => $_POST['menu_icon'],
                'category' => $_POST['category'],
                'taxonomy' => $_POST['taxonomy'],
                'stop_flag' => $_POST['stop_flag']
            );

            $err_flag = false;
            $err_mes = array();
            // 未入力チェック
            $required = array('name', 'menu', 'post_type');
            // 半角英数字
            $alphanumeric = array('post_type');
            foreach ($_data as $key => $value) {
                if ((in_array($key, $required)) && (empty($value))) {
                    $err_flag = true;
                    $err_mes[] = '<div class="error"><p>' . __($key, 'cptm') . __('が未入力です。', 'cptm') . '</p></div>';
                }
                if (in_array($key, $alphanumeric)) {
                    if (!preg_match('/^[a-zA-Z0-9]+$/', $value)) {
                        $err_flag = true;
                        $err_mes[] = '<div class="error"><p>' . __($key, 'cptm') . __('は半角英数字で入力してください。', 'cptm') . '</p></div>';
                    }
                }
            }

            if (!empty($err_mes) ) {
                foreach ($err_mes as $msg) {
                    echo $msg;
                }
            }

            if (!$err_flag) {
                if (!empty($_POST['id'])) {
                    // 更新
                    $id = $_POST['id'];
                    $success = 3;
                    $error = 5;
                    $result = $wpdb->update($table_name, $_data, array('id' => $id));
                } else {
                    // 新規
                    $success = 1;
                    $error = 4;
                    $result = $wpdb->insert($table_name, $_data);
                    $id = $wpdb->insert_id;
                }

                // メッセージ変数を持ってリダイレクト
                if ($result) {
                    $meslink = $url . '&message=' . $success;
                } else {
                    $meslink = $url . '&message=' . $error;
                }
                echo '<script>document.location = "' . $meslink . '";</script>';
            }
        }

        if ((isset($_GET['action'])) && ($_GET['action'] == 'delete')) {
            check_admin_referer('cptm-delete');

            // 削除処理
            $id = $_GET['custom_ID'];

            $sql = <<< EOF
DELETE FROM
 {$table_name}
WHERE
 id = {$id}
EOF;

            $wpdb->query($sql);

            // メッセージ変数も持ってリダイレクト
            $meslink = $url . '&message=2';
            echo '<script>document.location = "' . $meslink . '";</script>';
        }

        $sql = <<< EOF
SELECT
 id,name,menu,post_type,supports,menu_position,menu_icon,category,taxonomy,stop_flag
FROM
 {$table_name}
EOF;
        $results = $wpdb->get_results($sql, ARRAY_A);
        foreach ($results as $result) {
            $custom_posts[$result['id']] = $result;
        }

        $data = array();
        $id = '';
        $h2_link = '';
        $subtitle = __('新規カスタム投稿を追加', 'cptm');

        if ((isset($_GET['action'])) && ($_GET['action'] == 'edit')) {
            check_admin_referer('cptm-edit');

            // 編集用画面表示
            $id = $_GET['custom_ID'];
            $data = $custom_posts[$id];
            $subtitle = __('カスタム投稿を編集', 'cptm');
            $h2_link = ' <a href="' . $url . '" class="add-new-h2">' . __('新規登録', 'cptm') . '</a>';
        }

        $name = (array_key_exists('name', $data)) ? esc_html($data['name']) : '';
        $menu = (array_key_exists('menu', $data)) ? esc_html($data['menu']) : '';
        $post_type = (array_key_exists('post_type', $data)) ? esc_html($data['post_type']) : '';
        $supports = (array_key_exists('supports', $data)) ? json_decode($data['supports']) : array();
        $category_check = ((array_key_exists('category', $data)) && ($data['category'])) ? ' checked="checked"' : '';
        $taxonomy_check = ((array_key_exists('taxonomy', $data)) && ($data['taxonomy'])) ? ' checked="checked"' : '';
        $stop_flag_check = ((array_key_exists('stop_flag', $data)) && ($data['stop_flag'])) ? ' checked="checked"' : '';

        $supports_select = array(
            'title' => __('title', 'cptm'),
            'editor' => __('editor', 'cptm'),
            'author' => __('author', 'cptm'),
            'thumbnail' => __('thumbnail', 'cptm'),
            'excerpt' => __('excerpt', 'cptm'),
            'custom-fields' => __('custom-fields', 'cptm')
        );

        $menu_position_select = array(
            '5' => __('投稿の下', 'cptm'),
            '10' => __('メディアの下', 'cptm'),
            '20' => __('ページの下', 'cptm')
        );

        $menu_icon_select = array(
            '' => __('指定しない', 'cptm'),
            'address-book' => __('連絡帳', 'cptm'),
            'alarm-clock' => __('時計', 'cptm'),
            'calendar' => __('カレンダー', 'cptm'),
            'report' => __('ノート', 'cptm'),
            'sticky-notes' => __('メモ帳', 'cptm')
        );

        $editaction = 'cptm-edit';
        $delaction = 'cptm-delete';
?>
<div class="wrap">
    <div id="icon-options-general" class="icon32"><br /></div>
    <h2><?php echo __('カスタム投稿設定', 'cptm') . $h2_link; ?></h2>

    <div id="ajax-response"></div>

    <div id="col-container">

        <div id="col-right">
            <div class="col-wrap">
                <table class="wp-list-table widefat fixed" cellspacing="0">
                    <thead>
                        <tr>
                            <th scope="col" class="manage-column"><?php echo __('name', 'cptm'); ?></th>
                            <th scope="col" class="manage-column"><?php echo __('post_type', 'cptm'); ?></th>
                            <th scope="col" class="manage-column"><?php echo __('stop_flag', 'cptm'); ?></th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <th scope="col" class="manage-column">名前</th>
                            <th scope="col" class="manage-column">投稿タイプ</th>
                            <th scope="col" class="manage-column">状態</th>
                        </tr>
                    </tfoot>
                    <tbody id="the-list">
<?php
if (!empty($custom_posts)) :
    foreach ($custom_posts as $custom_post) :
        $editurl = $url . '&action=edit&custom_ID=' . $custom_post['id'];
        $editlink = wp_nonce_url($editurl, $editaction);
        $delurl = $url . '&action=delete&custom_ID=' . $custom_post['id'];
        $dellink = wp_nonce_url($delurl, $delaction);
?>
                        <tr>
                            <td class="post-title page-title column-title">
                                <strong><?php echo $custom_post['name']; ?></strong>
                                <div class="row-actions"><span class="edit"><a href="<?php echo $editlink; ?>"><?php echo __('edit', 'cptm'); ?></a></span> | <span class="delete"><a href="<?php echo $dellink; ?>"><?php echo __('delete', 'cptm'); ?></a></span></div>
                            </td>
                            <td><?php echo $custom_post['post_type']; ?></td>
                            <td><?php echo ($custom_post['stop_flag'] == 0) ? __('active', 'cptm') : __('deactive', 'cptm'); ?></td>
                        </tr>
<?php endforeach; endif; ?>
                    </tbody>
                </table>

            </div>
        </div><!-- /col-right -->

        <div id="col-left">
            <div class="col-wrap">

                <div class="form-wrap">
                    <h3><?php echo $subtitle; ?></h3>
                    <form method="post" action="<?php echo $url; ?>">
                    <input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('cptm-nonce'); ?>" />

                        <input type="hidden" name="id" value="<?php echo $id; ?>">
                        <div class="form-field">
                            <label for="name"><?php echo __('name', 'cptm'); ?></label>
                            <input type="text" name="name" value="<?php echo $name; ?>" class="regular-text">
                            <p><?php echo __('登録画面等に表示されます。', 'cptm'); ?></p>
                        </div>

                        <div class="form-field">
                            <label for="menu"><?php echo __('menu', 'cptm'); ?></label>
                            <input type="text" name="menu" value="<?php echo $menu; ?>" class="regular-text">
                            <p><?php echo __('管理画面のメニューに表示されます。', 'cptm'); ?></p>
                        </div>

                        <div class="form-field">
                            <label for="post_type"><?php echo __('post_type', 'cptm'); ?></label>
                            <input type="text" name="post_type" value="<?php echo $post_type; ?>" class="regular-text">
                            <p><?php echo __('半角英数字で入力してください。', 'cptm'); ?></p>
                        </div>

                        <div class="form-field">
                            <label for="supports"><?php echo __('supports', 'cptm'); ?></label>
                            <select name="supports[]" id="supports" multiple>
                            <?php
                                foreach ($supports_select as $key => $value) {
                                    $selected = '';
                                    if (in_array($key, $supports)) {
                                        $selected = ' selected="selected"';
                                    }
                                    echo '<option value="' . $key . '"' . $selected . '>' . $value . '</option>';
                                }
                            ?>
                            </select>
                            <p><?php echo __('使用する機能を選択してください。（複数選択可）', 'cptm'); ?><br />
                            <small>※選択しなかった場合は『<?php echo __('title', 'cptm'); ?>』と『<?php echo __('editor', 'cptm'); ?>』を使用します。</small></p>
                        </div>


                        <div class="form-field">
                            <label for="menu_position"><?php echo __('menu_position', 'cptm'); ?></label>
                            <select name="menu_position" id="menu_position">
                            <?php
                                foreach ($menu_position_select as $key => $value) {
                                    $selected = '';
                                    if ($key == $data['menu_position']) {
                                        $selected = ' selected="selected"';
                                    }
                                    echo '<option value="' . $key . '"' . $selected . '>' . $value . '</option>';
                                }
                            ?>
                            </select>
                            <p><?php echo __('管理画面のメニュー表示位置です。', 'cptm'); ?></p>
                        </div>

                        <div class="form-field">
                            <label for="menu_icon"><?php echo __('menu_icon', 'cptm'); ?></label>
                            <select name="menu_icon" id="menu_icon">
                            <?php
                                foreach ($menu_icon_select as $key => $value) {
                                    $selected = '';
                                    if ($key == $data['menu_icon']) {
                                        $selected = ' selected="selected"';
                                    }
                                    echo '<option value="' . $key . '"' . $selected . '>' . $value . '</option>';
                                }
                            ?>
                            </select>
                            <p><?php echo __('管理画面のメニューに使用するアイコンです。', 'cptm'); ?></p>
                        </div>

                        <div>
                            <input type="hidden" name="category" value="0" />
                            <label class="selectit"><input value="1" type="checkbox" name="category"<?php echo $category_check; ?> /> <?php echo __('カテゴリーを使用する', 'cptm'); ?></label>
                            <input type="hidden" name="taxonomy" value="0" />
                            <label class="selectit"><input value="1" type="checkbox" name="taxonomy"<?php echo $taxonomy_check; ?> /> <?php echo __('タグを使用する', 'cptm'); ?></label>
                            <input type="hidden" name="stop_flag" value="0" />
                            <label class="selectit"><input value="1" type="checkbox" name="stop_flag"<?php echo $stop_flag_check; ?> /> <?php echo __('このカスタム投稿タイプを一時停止にする', 'cptm'); ?></label>
                        </div>

<?php submit_button(__('Save', 'cptm'), 'primary'); ?>

                    </form>

                </div>
            </div>
        </div><!-- /col-left -->

    </div><!-- /col-container -->
</div>
<?php
}
