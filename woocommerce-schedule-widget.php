<?php
/**
 * Plugin Name: Woo Commerce Scheduling Widget
 * Description:  Schedule service appointment for completed orders.
 * Plugin URI:  https://aexki.wordpress.com/
 * Version:     1.0.3
 * Author:      Aman Jena
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Function to run on plugin activation
function create_schedule_table() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'aws_schedule_lookup';
    $orders_table_name = $wpdb->prefix . 'wc_orders';
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        schedule_datetime datetime,
        order_id bigint(20) unsigned NOT NULL,
        status varchar(20) DEFAULT 'Pending' NOT NULL,
        PRIMARY KEY (id),
        CONSTRAINT fk_order_id FOREIGN KEY (order_id) REFERENCES $orders_table_name(id) ON DELETE CASCADE
    ) $charset_collate;";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta($sql);
}
register_activation_hook( __FILE__, 'create_schedule_table' );

function create_unavailable_slots_table() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'aws_unavailable_slots';
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        unavailable_datetime datetime,
        status tinyint(1) DEFAULT 1 NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta($sql);
}
register_activation_hook( __FILE__, 'create_unavailable_slots_table' );

// Function to run on plugin deactivation
function remove_schedule_table() {
    global $wpdb;
    $schedule_table = $wpdb->prefix . 'aws_schedule_lookup';
    $unallowed_slots_table = $wpdb->prefix . 'aws_unavailable_slots';

    $sql = "DROP TABLE IF EXISTS $schedule_table;";
    $wpdb->query($sql);
    $sql = "DROP TABLE IF EXISTS $unallowed_slots_table;";
    $wpdb->query($sql);
    error_log('Schedule DB table removed.');
}
register_deactivation_hook(__FILE__, 'remove_schedule_table');



function register_schedule_widget( $widgets_manager ) {
	require_once  __DIR__ . '/widgets/schedule-widget.php' ;
	$widgets_manager->register( new \Schedule_Widget() );
}
add_action( 'elementor/widgets/register', 'register_schedule_widget' );


// Include the admin schedule management functionality Menu
include plugin_dir_path(__FILE__) . 'includes/schedule-admin-menu.php';

function enqueue_widget_styles() {
    wp_enqueue_style('admin-modal', plugin_dir_url(__FILE__) . '/assets/css/admin-modal.css');
    wp_enqueue_style('widget-styles', plugin_dir_url(__FILE__) . '/assets/css/widget-styles.css');
}
add_action('wp_enqueue_scripts', 'enqueue_widget_styles');

// Enqueue scripts and styles for the modal
add_action('admin_enqueue_scripts', function() {
    wp_enqueue_style('admin-modal-css', plugin_dir_url(__FILE__) . '/assets/css/admin-modal.css'); // Add your own CSS file
    wp_enqueue_script('admin-modal-js', plugin_dir_url(__FILE__) . '/assets/js/admin-modal.js', array('jquery'), null, true);
    wp_localize_script('admin-modal-js', 'ajaxurl', admin_url('admin-schedule.php'));
});


function handle_form_submission_and_send_email($to, $subject, $message) {
    // Collect email data
    $headers = array('Content-Type: text/html; charset=UTF-8');

    // Send the email
    wp_mail($to, $subject, $message, $headers);
}
add_action('admin_post_nopriv_your_form_submission_action', 'handle_form_submission_and_send_email');
add_action('admin_post_your_form_submission_action', 'handle_form_submission_and_send_email');

function check_zip_api() {
    register_rest_route('api/v1/', '/allowed-zips', array(
        'methods' => 'GET',
        'callback' => 'check_zip_callback',
    ));
}
add_action('rest_api_init', 'check_zip_api');

function check_zip_callback(WP_REST_Request $request) {
	global $wpdb;

    $table_name = $wpdb->prefix . 'aws_allowed_zips';

    // Fetch the zip column
    $results = $wpdb->get_col( "SELECT zip FROM {$table_name}" );

    $data = array(
        'allowed_zips' => $results,
        'status' => 'success'
    );
    return new WP_REST_Response($data, 200);
}

