<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class SCHEDULE_TABLE extends WP_List_Table {

    function __construct() {
        parent::__construct([
            'singular' => 'Record',
            'plural'   => 'Records',
            'ajax'     => false
        ]);
    }

    function get_columns() {
        return [
            'id'                => 'Schedule Id',
            'schedule_datetime' => 'Schedule Datetime',
            'status' 			=> 'Status',
            'order_id'          => 'Order ID'
        ];
    }

    function get_sortable_columns() {
        return [
            'id'                => ['id', false],
            'schedule_datetime' => ['schedule_datetime', false],
            'status' => ['status', false],
            'order_id'          => ['order_id', false]
        ];
    }

    function prepare_items() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'aws_schedule_lookup';

        $orderby = (!empty($_GET['orderby'])) ? $_GET['orderby'] : 'id';
        $order = (!empty($_GET['order'])) ? $_GET['order'] : 'asc';

        $query = "SELECT * FROM $table_name ORDER BY $orderby $order";
        $data = $wpdb->get_results($query, ARRAY_A);

        $this->items = $data;

        $columns = $this->get_columns();
        $hidden = [];
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = [$columns, $hidden, $sortable];
    }

    function column_default($item, $column_name) {
		$scheduleDatetime = new DateTime($item['schedule_datetime'], new DateTimeZone('UTC'));

        switch ($column_name) {
            case 'id':
                return '<a href="#" class="schedule-id" data-id="' . esc_html($item['id']) . '" data-datetime="' . esc_html($scheduleDatetime->format('M d, Y  h:i A')) . '" data-status="' . esc_html($item['status']) . '" >#' . esc_html($item['id']) . '</a>';
            case 'order_id':
                return esc_html($item[$column_name]);
            case 'schedule_datetime':
                return esc_html($scheduleDatetime->format('M d, Y  h:i A'));
            case 'status':
                switch ($item[$column_name]) {
                    case 'Completed':
                        return '<span style="color: #46b450; font-weight: bold;">Completed</span>';
                    case 'Pending':
                        return '<span style="color: #ffba00; font-weight: bold;">Pending</span>';
                    case 'Cancelled':
                        return '<span style="color: #dc3232; font-weight: bold;">Cancelled</span>';
                    default:
                        return esc_html($item[$column_name]);
                }
            default:
                return print_r($item, true);
        }
    }

	function column_order_id($item) {
        $edit_link = admin_url('admin.php?page=wc-orders&action=edit&id=' . $item['order_id']);
        return '<a href="' . esc_url($edit_link) . '">#' . esc_html($item['order_id']) . '</a>';
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['schedule_id'])) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'aws_schedule_lookup';

        // Get the POST data
        $schedule_id = intval($_POST['schedule_id']);
        $schedule_datetime = new DateTime(sanitize_text_field($_POST['schedule_datetime_input']), new DateTimeZone('UTC'));
        $status = sanitize_text_field($_POST['status_input']);

        // Update the schedule in the database
        $result = $wpdb->update(
            $table_name,
            array(
                'schedule_datetime' => $schedule_datetime->format('Y-m-d H:i:s'),
                'status' => $status
            ),
            array('id' => $schedule_id)
        );

        // Check if the update was successful
        if ($result !== false) {
            wp_send_json_success('Schedule updated successfully!');
        } else {
            wp_send_json_error('Error updating schedule: ' . $wpdb->last_error);
        }
    }
}

function display_schedule_table() {
    $myTable = new SCHEDULE_TABLE();
    $myTable->prepare_items();
    ?>
    <div class="wrap">
        <h1>Schedules</h1>
        <p>Manage all your service schedules easily and efficiently. Keep track of appointments and updates in one place.</p>
        <form method="get">
            <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
            <?php $myTable->display(); ?>
        </form>
        <div id="scheduleEditModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2 id="modalHeader">Edit Schedule</h2>
                <form id="editScheduleForm">
                    <label for="schedule_datetime">Schedule DateTime:</label>
                    <input type="hidden"  name="schedule_datetime_input" id="schedule_datetime_input">
                    <input type="text" name="schedule_datetime_hidden" id="schedule_datetime_hidden" disabled>

                    <label for="status">Status:</label>
                    <select id="status_input" name="status_input">
                        <option value="Completed">Completed</option>
                        <option value="Pending">Pending</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>

                    <input type="hidden" id="schedule_id" name="schedule_id">
                    <button type="submit">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-timepicker/1.3.5/jquery.timepicker.min.css">

    <!-- jQuery library -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- jQuery UI library -->
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-timepicker/1.3.5/jquery.timepicker.min.js"></script>
    <?php
}

add_action('admin_menu', function() {
    add_menu_page('Schedule Appointments', 'Schedule Appointments', 'manage_options', 'schedule-service', 'display_schedule_table', 'dashicons-calendar-alt');
});