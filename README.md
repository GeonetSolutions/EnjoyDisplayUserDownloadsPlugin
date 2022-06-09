# Display User Downloads

Displays the number of downloads a user has downloaded on the site for certain attachments.   

Not so much plugin and play because of the way it was originally implemented. See instructions below on what to do!

&nbsp;

### PHP

Put this code somewhere in your functions.php file

---

```
/* User Add Count
-------------------------------------------- */
function user_addcount()
{
    if (is_user_logged_in()) {

        if ($_POST['attachment'] != null) {
            $response['attachment'] = $_POST['attachment'];
        }

        if ($_POST['size'] != null) {
            $response['size'] = $_POST['size'];
        }

        // Add Table
        global $wpdb;
        $table_name = $wpdb->prefix . 'hush_user_downloads';

        $wpdb->insert($table_name, array(
            'user_id'           => get_current_user_id(),
            'attachment_id'     => $_POST['attachment'],
            'attachment_size'   => $_POST['size'] ?? '',
            'download_type'     => $_POST['type'],
        ));

        $response['status'] = "success";
    } else {
        $response['status'] = "not logged in";
    }

    echo json_encode($response);
    exit; // leave ajax call
}
add_action('wp_ajax_user_addcount', 'user_addcount');
add_action('wp_ajax_nopriv_user_addcount', 'user_addcount');
```
&nbsp;

### Javascript

Put this code somewhere in your JS file. *NOTE: The url for the ajax call will need to be the localised one you've enqueued for your JS asset.*

---

```
$(document).on('click', '.tippy-link', function (e) {
    // get attachment id
    const attachment_id = $(this).parent().data("id");
    if (attachment_id > 0) {
        $.ajax({
            type: "POST",
            url: child_js.ajax_url,
            dataType: "JSON",
            data: {
                action: "user_addcount", // name of the action
                attachment: attachment_id,
                size: $(this).data("size"),
                type: "media",
            },
            success: function (response) {
                //console.log(response);
            },
        });
    }
});
```
&nbsp;

### Enjoy!
