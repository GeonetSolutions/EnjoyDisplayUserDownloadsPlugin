<?php

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class UserDownloads extends WP_List_Table
{

    /** Class constructor */
    public function __construct()
    {
        parent::__construct([
            'singular' => __('Download', 'sp'), //singular name of the listed records
            'plural' => __('Downloads', 'sp'), //plural name of the listed records
            'ajax' => false //does this table support ajax?
        ]);
    }


    /**
     * Retrieve user downloads data from the database
     *
     * @param int $user_id
     * @param int $per_page
     * @param int $page_number
     *
     * @return mixed
     */
    public static function get_downloads($user_id = 0, $per_page = 5, $page_number = 1)
    {

        global $wpdb;

        $table_name = $wpdb->prefix . 'hush_user_downloads';

        $sql = "SELECT *
FROM $table_name
WHERE user_id = '$user_id'";

        if (!empty($_REQUEST['orderby'])) {
            $sql .= ' ORDER BY ' . esc_sql($_REQUEST['orderby']);
            $sql .= !empty($_REQUEST['order']) ? ' ' . esc_sql($_REQUEST['order']) : ' ASC';
        } else {
            $sql .= ' ORDER BY downloaded DESC';
        }

        $sql .= " LIMIT $per_page";
        $sql .= ' OFFSET ' . ($page_number - 1) * $per_page;


        $result = $wpdb->get_results($sql, 'ARRAY_A');

        return $result;
    }

    /**
     * Returns the count of records in the database.
     *
     * @param int $user_id
     *
     * @return null|string
     */
    public static function record_count($user_id)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'hush_user_downloads';

        $sql = "SELECT COUNT(*) AS num
FROM $table_name
WHERE user_id = '$user_id'";

        return $wpdb->get_var($sql);
    }

    /** Text displayed when no customer data is available */
    public function no_items()
    {
        _e('This user has not downloaded anything.', 'hush');
    }

    /**
     * Render a column when no column specific method exist.
     *
     * @param array $item
     * @param string $column_name
     *
     * @return mixed
     */
    public function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'downloaded':
                return date('l, jS F, Y (H:i)', strtotime($item[$column_name]));
            case 'download_type':
                if ($item[$column_name] == 'media') {
                    return "Media Library";
                } elseif ($item[$column_name] == 'industry') {
                    return "Industry Resources";
                }

                return "hello";
            case 'attachment_id':

                // Media Library
                if ($item['download_type'] == 'media') {
                    // get attachment
                    $attachment = get_post($item[$column_name]);
                    //print_r($attachment);
                    $attachment_image = wp_get_attachment_image($item[$column_name], ['48'], true);

                    // construct
                    $media = '<a href="' . wp_get_attachment_url($item[$column_name]) . '" style="display: flex; align-items: center">';
                    $media .= $attachment_image;
                    $media .= '<div style="margin-left: 10px">' . $attachment->post_title . '</div>';
                    $media .= '</a>';

                    // return
                    return $media;
                } elseif ($item['download_type'] == 'industry') {
                    // get attachment
                    $attachment = get_post($item[$column_name]);
                    //print_r($attachment);

                    // get file icon
                    $file = get_field('file', $attachment->ID);
                    $fileType = $file['url'];
                    $info = pathinfo($fileType);

                    if ($info["extension"] == "pdf") {
                        $image = plugin_dir_url(__FILE__) . "/images/pdf.svg";
                    } elseif ($info["extension"] == "zip") {
                        $image = plugin_dir_url(__FILE__) . "/images/zip.svg";
                    } elseif ($info["extension"] == "jpg") {
                        $image = plugin_dir_url(__FILE__) . "/images/jpg.svg";
                    } elseif ($info["extension"] == "png") {
                        $image = plugin_dir_url(__FILE__) . "/images/png.svg";
                    } else {
                        $image = plugin_dir_url(__FILE__) . "/images/doc.svg";
                    }

                    // construct
                    $media = '<a href="' . $file['url'] . '" style="display: flex; align-items: center">';
                    $media .= '<img src="' . $image . '" style="width: 48px; height: auto">';
                    $media .= '<div style="margin-left: 10px">' . $attachment->post_title . '</div>';
                    $media .= '</a>';

                    // return
                    return $media;
                }
            case 'attachment_size':
                return $item[$column_name];
            default:
                return print_r($item, true); //Show the whole array for troubleshooting purposes
        }
    }

    /**
     * Associative array of columns
     *
     * @return array
     */
    function get_columns()
    {
        $columns = [
            'downloaded' => __('Date Downloaded', 'hush'),
            'download_type' => __('Download Type', 'hush'),
            'attachment_id' => __('Attachment', 'hush'),
            'attachment_size' => __('Size', 'hush')
        ];

        return $columns;
    }


    /**
     * Columns to make sortable.
     *
     * @return array
     */
    public function get_sortable_columns()
    {
        $sortable_columns = array(
            'downloaded' => array('downloaded', true),
            'download_type' => array('download_type', false),
            'attachment_id' => array('attachment_id', false),
            'attachment_size' => array('attachment_size', false)
        );

        return $sortable_columns;
    }


    /**
     * Handles data query and filter, sorting, and pagination.
     */
    public function prepare_items()
    {

        $user_id = htmlspecialchars($_GET["user_id"]);

        $this->_column_headers = $this->get_column_info();

        /** Process bulk action */
        $this->process_bulk_action();

        $per_page = $this->get_items_per_page('downloads_per_page', 25);
        $current_page = $this->get_pagenum();
        $total_items = self::record_count($user_id);

        $this->set_pagination_args([
            'total_items' => $total_items, //WE have to calculate the total number of items
            'per_page' => $per_page //WE have to determine how many items to show on a page
        ]);

        $this->items = self::get_downloads($user_id, $per_page, $current_page);
    }
}
