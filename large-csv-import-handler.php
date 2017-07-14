<?php
/*
Plugin Name: Large CSV Import Handler
Description: Provides ability to import any type of data from large CSV files into Wordpress
Version: 0.9
Author: Oleg Kosolapov
License: GPL2+
Text Domain: large-csv-import-handler
Domain Path: /lang
*/

class LargeCSVImportHandlerPlugin {
    public function __construct()
    {
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));

        add_action( 'wp_ajax_do_import_step', array($this, 'do_import_step') );
        //add_action( 'wp_ajax_noprivs_do_import_step', array($this, 'do_import_step') );
        add_action( 'admin_init', array($this, 'register_settings') );
        add_action( 'plugins_loaded', array($this, 'load_textdomain') );
        add_filter( 'plugin_action_links', array($this, 'plugin_action_links'), 10, 2 );
    }

    function plugin_action_links($links, $file)
    {
        if( false === strpos( $file, basename(__FILE__) ) )
            return $links;

        $settings_link = '<a href="admin.php?page=large-csv-import-handler-settings">' . esc_html__( 'Settings', 'large-csv-import-handler' ) . '</a>';
        array_unshift( $links, $settings_link );

        return $links;
    }

    function load_textdomain()
    {
        load_plugin_textdomain( 'large-csv-import-handler', false, basename( dirname( __FILE__ ) ) . '/lang/' );
    }

    function register_settings()
    {
        register_setting( 'large-csv-import-handler-settings-group', 'lcih_csv_delimiter' );
        register_setting( 'large-csv-import-handler-settings-group', 'lcih_csv_enclosure' );
        register_setting( 'large-csv-import-handler-settings-group', 'lcih_csv_escape' );
    }



    function do_import_step()
    {
        $dir = wp_upload_dir();

        $file = $dir['path'].'/'.$_POST['file_name'];

        $count = $this->get_csv_count($file);

        $num = intval($_POST['count']);
        $row = $this->get_csv_row($file, $num);

        ob_start();
        do_action('lcih_import_csv_row', $row);
        $s = ob_get_clean();



        $res = array(
            'status' => 'ok',
            'msg' => __('Processed records:', 'large-csv-import-handler') . ' ' . ($num + 1) . ' '.__('of', 'large-csv-import-handler').' ' . $count,
            'output' => $s,
            'total' => $count
        );

        echo json_encode($res);

        exit();
    }

    function get_csv_count($file)
    {
        $f = fopen($file, 'r');
        $num = 0;
        fgets($f);
        while (fgets($f) !== false)
            $num++;
        fclose($f);

        return $num;
    }

    function get_csv_row($file, $row)
    {
        $f = fopen($file, "r");
        $header = fgetcsv($f, null, get_option('lcih_csv_delimiter'), get_option('lcih_csv_enclosure'), get_option('lcih_csv_escape'));
        for ($i = 0; $i < $row; $i++)
            fgets($f);
        $data = fgetcsv($f, null, get_option('lcih_csv_delimiter'), get_option('lcih_csv_enclosure'), get_option('lcih_csv_escape'));
        fclose($f);

        $res = array();

        foreach ($header as $k => $v) {
            $res[$v] = $data[$k];
        }

        return $res;
    }

    function admin_scripts()
    {
        if (is_admin())
        {
            wp_enqueue_script('large-csv-import-handler-admin', plugins_url('js/admin.js', __FILE__), array('jquery'));
            wp_enqueue_style('large-csv-import-handler-admin', plugins_url('css/admin.css', __FILE__));
        }
    }

    function admin_menu()
    {
        add_menu_page(__('CSV Import', 'large-csv-import-handler'), __('CSV Import', 'large-csv-import-handler'), 'administrator', 'large-csv-import-handler', array($this, 'admin_page'), plugins_url('img/icon.png', __FILE__));
        add_submenu_page('large-csv-import-handler', __('Settings', 'large-csv-import-handler'), __('Settings', 'large-csv-import-handler'), 'administrator', 'large-csv-import-handler-settings', array($this, 'settings_page'));
    }

    function settings_page()
    {
        ?>
        <div class="wrap">
            <h2><?php _e('CSV Settings', 'large-csv-import-handler') ?></h2>
            <form method="post" action="options.php">
                <?php settings_fields( 'large-csv-import-handler-settings-group' ); ?>
                <table class="lcih_admin_table">
                    <tr>
                        <th><?php _e('CSV Delimiter', 'large-csv-import-handler') ?>:</th>
                        <td>
                            <input type="text" name="lcih_csv_delimiter" value="<?=esc_attr(get_option('lcih_csv_delimiter'))?>" />
                            <p class="description"><?php _e('CSV field delimiter (one character only)') ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('CSV Enclosure', 'large-csv-import-handler') ?>:</th>
                        <td>
                            <input type="text" name="lcih_csv_enclosure" value="<?=esc_attr(get_option('lcih_csv_enclosure'))?>" />
                            <p class="description"><?php _e('CSV field enclosure character (one character only)') ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('CSV Escape', 'large-csv-import-handler') ?>:</th>
                        <td>

                            <input type="text" name="lcih_csv_escape" value="<?=esc_attr(get_option('lcih_csv_escape'))?>" />
                            <p class="description"><?php _e('CSV escape character (one character only)') ?></p>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
                </p>
            </form>
        </div>
    <?php
    }

    function admin_page()
    {
        $import = false;
        $error = '';
        $file = '';
        $file_name = '';
        if (isset($_POST['do-import']))
        {
            if (isset($_FILES['csv'])) {
                if ($_FILES['csv']['error'])
                    $error = __('CSV file not selected.', 'large-csv-import-handler');
                elseif (strpos($_FILES['csv']['name'], '.csv') === false)
                    $error = __('File must be in .csv format.', 'large-csv-import-handler');
                else {
                    $import = true;

                    $dir = wp_upload_dir();
                    if (move_uploaded_file($_FILES['csv']['tmp_name'], $dir['path'].'/'.$_FILES['csv']['name']))
                    {
                        $file = $dir['path'].'/'.$_FILES['csv']['name'];
                        $file_name = basename($file);

                    } else
                        $error = __('Error moving uploaded file.', 'large-csv-import-handler');
                }
            } else
                $error = __("CSV file not specified.", 'large-csv-import-handler');
        }

        ?>
        <script>var lcih_ajax_url = '<?=admin_url('admin-ajax.php')?>';</script>
        <script>
            var mi_file = '<?=$file_name?>';
            var mi_count = 0;
            <?php if ($import) { ?>
            doLCIHImportStep();
            <?php } ?>
        </script>
        <div class="wrap">
            <h2>
                <?php _e('CSV Import', 'large-csv-import-handler') ?>
                <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank" style="display: inline-block; vertical-align: middle; float: right;">
                    <input type="hidden" name="cmd" value="_s-xclick">
                    <input type="hidden" name="hosted_button_id" value="65AE4A3BTR6FE">
                    <input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
                    <img alt="" border="0" src="https://www.paypalobjects.com/ru_RU/i/scr/pixel.gif" width="1" height="1">
                </form>
            </h2>
            <?php if ($error) {?>
            <div class="error"><?=$error?></div>
        <?php } ?>
            <form method="post" enctype="multipart/form-data">
                <?php if (!$import) { ?>
                <?php _e('CSV file', 'large-csv-import-handler') ?>: <input type="file" name="csv" /><br>
                <?php } ?>
                <br>
                <button class="button button-primary btn-import" type="submit" name="do-import" <?php if ($import) echo 'disabled="disabled"'; ?>><?php _e('Start Import', 'large-csv-import-handler') ?></button>
            </form>
            <?php if ($import) { ?>
                <div class="lcih_import_wrap" style="display: none">
                    <h3><?php _e('Import Progress') ?>:</h3>
                    <div class="lcih_progress_wrap">
                        <div class="lcih_progress" style=" width: 0%;"></div>
                    </div>
                    <div class="mi-msg"></div>
                    Output:
                    <textarea class="mi-output" rows="10" style="width: 100%;" disabled="disabled"></textarea>
                </div>
            <?php } ?>
        </div>
    <?php
    }

    public static function download_image($url)
    {
        media_sideload_image($url, 0);
    }

    public static function download_post_thumbnail($post_id, $url)
    {
        add_action('add_attachment',array('LargeCSVImportHandlerPlugin', 'new_attachment_thumbnail'));
        media_sideload_image($url, $post_id);
        remove_action('add_attachment',array('LargeCSVImportHandlerPlugin', 'new_attachment_thumbnail'));
    }

    function new_attachment_thumbnail($att_id){
        // the post this was sideloaded into is the attachments parent!
        $p = get_post($att_id);
        update_post_meta($p->post_parent, '_thumbnail_id', $att_id);
    }
}

new LargeCSVImportHandlerPlugin();

register_activation_hook(__FILE__, 'install_large_csv_import_handler');
function install_large_csv_import_handler()
{
    if (!get_option('lcih_csv_delimiter'))
        update_option('lcih_csv_delimiter', ',');
    if (!get_option('lcih_csv_enclosure'))
        update_option('lcih_csv_enclosure', '"');
    if (!get_option('lcih_csv_escape'))
        update_option('lcih_csv_escape', '\\');
}