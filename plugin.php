<?php
/*
Plugin Name: Display User Downloads
Description: Enjoy Tees Valley - show user downloads
Version: 1.1
Author: Hush Digital
Author URI:  https://hush.digital/
*/

global $hush_user_downloads_db_version;
$hush_user_downloads_db_version = '1.0';

require_once(plugin_dir_path(__FILE__) . 'user_downloads_list_table.php');

function hush_user_downloads_install()
{
    global $wpdb;
    global $hush_user_downloads_db_version;

    $table_name = $wpdb->prefix . 'hush_user_downloads';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
		id bigint(20) NOT NULL AUTO_INCREMENT,
		downloaded datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        user_id  bigint(20) NOT NULL,
        attachment_id  bigint(20) NOT NULL,
        attachment_size varchar(255) NOT NULL,
        download_type varchar(255) NOT NULL,
		PRIMARY KEY (id)
	) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    maybe_create_table($table_name, $sql);

    add_option('hush_user_downloads_db_version', $hush_user_downloads_db_version);
}
register_activation_hook(__FILE__, 'hush_user_downloads_install');

class Hush_Display_User_Downloads
{

    // class instance
    static $instance;

    // customer WP_List_Table object
    public $customers_obj;

    // class constructor
    public function __construct()
    {
        add_filter('set-screen-option', [__CLASS__, 'set_screen'], 10, 3);
        add_action('admin_menu', [$this, 'plugin_menu']);

        add_filter('manage_users_columns', [$this, 'new_modify_user_table']);
        add_filter('manage_users_custom_column', [$this, 'new_modify_user_table_row'], 10, 3);

        require_once(plugin_dir_path(__FILE__) . 'user_downloads_list_table.php');
    }

    public static function set_screen($status, $option, $value)
    {
        return $value;
    }

    public function plugin_menu()
    {

        $hook = add_submenu_page(
            null,
            'User Downloads',
            'User Downloads',
            'manage_options',
            'user_downloads',
            [$this, 'plugin_settings_page']
        );

        add_action("load-$hook", [$this, 'screen_option']);
    }

    /**
     * Plugin settings page
     */
    public function plugin_settings_page()
    {

        $user_id = htmlspecialchars($_GET["user_id"]);

        if ($user_id > 0) {
            $user = get_userdata($user_id);
?>
            <div class="wrap">
                <h2><?php echo $user->display_name; ?> Downloads</h2>

                <div id="poststuff">
                    <div id="post-body" class="metabox-holder columns-1">
                        <div id="post-body-content">
                            <div class="meta-box-sortables ui-sortable">

                                <?php
                                $this->customers_obj->prepare_items();
                                $this->customers_obj->display();
                                ?>

                            </div>
                        </div>
                    </div>
                    <br class="clear">
                </div>
            </div>
<?php
        } // user_id
    }

    /**
     * Screen options
     */
    public function screen_option()
    {

        $option = 'per_page';
        $args   = [
            'label'   => 'Downloads',
            'default' => 30,
            'option'  => 'downloads_per_page'
        ];

        add_screen_option($option, $args);

        $this->customers_obj = new UserDownloads();
    }

    /* Add User Downloads
--------------------------------------------------------------------------------------- */
    function new_modify_user_table($column)
    {
        $column['downloads'] = 'Downloads';
        return $column;
    }

    function new_modify_user_table_row($val, $column_name, $user_id)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'hush_user_downloads';

        switch ($column_name) {
            case 'downloads':
                $sql = "SELECT COUNT(user_id) AS num
            FROM $table_name
            WHERE user_id = '$user_id'";
                $downloads = $wpdb->get_results($sql);

                return '<a href="' . admin_url('admin.php?page=user_downloads&user_id=' . $user_id) . '">' . $downloads[0]->num . '</a>';
            default:
        }
        return $val;
    }

    /** Singleton instance */
    public static function get_instance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}

add_action('plugins_loaded', function () {
    Hush_Display_User_Downloads::get_instance();
});
