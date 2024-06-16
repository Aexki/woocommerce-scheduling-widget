<?php

require_once('../../../../wp-load.php'); // Adjust the path as needed to load WordPress
if (!is_user_logged_in()) {
    wp_die('You must be logged in to access this page.');
}

global $wpdb, $selected_order_id, $selected_date, $unavailable_timeslots, $orders;
$current_user = wp_get_current_user();
if ($current_user->exists()) {
    $user_email = $current_user->user_email;
    $user_id = $current_user->ID;
}

function getScheduleTemplate($user_email, $orderDetails, $formattedDate, $formattedTime) {
    return '
    <div marginwidth="0" marginheight="0" style="background-color:#f7f7f7;padding:0;text-align:center" bgcolor="#f7f7f7">
        <table width="100%" id="m_8820884128353847947outer_wrapper" style="background-color:#f7f7f7" bgcolor="#f7f7f7">
            <tbody>
                <tr>
                    <td></td>
                    <td width="600">
                        <div id="m_8820884128353847947wrapper" dir="ltr" style="margin:0 auto;padding:70px 0;width:100%;max-width:600px" width="100%">
                            <table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%">
                                <tbody>
                                    <tr>
                                        <td align="center" valign="top">
                                            <div id="m_8820884128353847947template_header_image"></div>
                                            <table border="0" cellpadding="0" cellspacing="0" width="100%" id="m_8820884128353847947template_container" style="background-color:#fff;border:1px solid #dedede;border-radius:3px" bgcolor="#fff">
                                                <tbody>
                                                    <tr>
                                                        <td align="center" valign="top">
                                                            <table border="0" cellpadding="0" cellspacing="0" width="100%" id="m_8820884128353847947template_header" style="background-color:#fa6400;color:#fff;border-bottom:0;font-weight:bold;line-height:100%;vertical-align:middle;font-family:&quot;Helvetica Neue&quot;,Helvetica,Roboto,Arial,sans-serif;border-radius:3px 3px 0 0" bgcolor="#fa6400">
                                                                <tbody>
                                                                    <tr>
                                                                        <td id="m_8820884128353847947header_wrapper" style="padding:36px 48px;display:block">
                                                                            <h1 style="font-family:&quot;Helvetica Neue&quot;,Helvetica,Roboto,Arial,sans-serif;font-size:30px;font-weight:300;line-height:150%;margin:0;text-align:left;color:#fff;background-color:inherit" bgcolor="inherit">Appointment Scheduled</h1>
                                                                        </td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td align="center" valign="top">
                                                            <table border="0" cellpadding="0" cellspacing="0" width="100%" id="m_8820884128353847947template_body">
                                                                <tbody>
                                                                    <tr>
                                                                        <td valign="top" id="m_8820884128353847947body_content" style="background-color:#fff" bgcolor="#fff">
                                                                            <table border="0" cellpadding="20" cellspacing="0" width="100%">
                                                                                <tbody>
                                                                                    <tr>
                                                                                        <td valign="top" style="padding:48px 48px 32px">
                                                                                            <div id="m_8820884128353847947body_content_inner" style="color:#909396;font-family:&quot;Helvetica Neue&quot;,Helvetica,Roboto,Arial,sans-serif;font-size:14px;line-height:150%;text-align:left" align="left">
                                                                                                <p style="margin:0 0 16px">Hi ' . htmlspecialchars($orderDetails->first_name) . ',</p>
                                                                                                <p style="margin:0 0 16px">Your appointment for order #' . htmlspecialchars($orderDetails->order_id) . ' has been successfully scheduled for <b>'.$formattedDate.'</b> at <b>'.$formattedTime.'</b>.</p>
                                                                                                <table id="m_8820884128353847947addresses" cellspacing="0" cellpadding="0" border="0" style="width:100%;vertical-align:top;margin-bottom:40px;padding:0" width="100%">
                                                                                                    <tbody>
                                                                                                        <tr>
                                                                                                            <td valign="top" width="50%" style="text-align:left;font-family:&quot;Helvetica Neue&quot;,Helvetica,Roboto,Arial,sans-serif;border:0;padding:0" align="left">
                                                                                                                <h2 style="color:#fa6400;display:block;font-family:&quot;Helvetica Neue&quot;,Helvetica,Roboto,Arial,sans-serif;font-size:18px;font-weight:bold;line-height:130%;margin:0 0 18px;text-align:left">Billing address</h2>
                                                                                                                <address style="padding:12px;color:#909396;border:1px solid #e5e5e5">
                                                                                                                    ' . htmlspecialchars($orderDetails->first_name) . ' ' . htmlspecialchars($orderDetails->last_name) . '<br>
                                                                                                                    ' . htmlspecialchars($orderDetails->address_1) . '<br>
                                                                                                                    ' . htmlspecialchars($orderDetails->city) . ', ' . htmlspecialchars($orderDetails->country) . ' ' . htmlspecialchars($orderDetails->postcode) . '<br>
                                                                                                                    ' . htmlspecialchars($orderDetails->phone) . '<br>
                                                                                                                    <a href="mailto:' . htmlspecialchars($user_email) . '" target="_blank">' . htmlspecialchars($user_email) . '</a>
                                                                                                                </address>
                                                                                                            </td>
                                                                                                        </tr>
                                                                                                    </tbody>
                                                                                                </table>
                                                                                                <p style="margin:0 0 16px">Thanks for using <a href="https://parahomeservices.com" target="_blank">parahomeservices.com</a>!</p>
                                                                                            </div>
                                                                                        </td>
                                                                                    </tr>
                                                                                </tbody>
                                                                            </table>
                                                                        </td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    </div>';
}

function getRescheduleTemplate($user_email, $orderDetails, $formattedDate, $formattedTime) {
    return '
    <div marginwidth="0" marginheight="0" style="background-color:#f7f7f7;padding:0;text-align:center" bgcolor="#f7f7f7">
        <table width="100%" id="m_8820884128353847947outer_wrapper" style="background-color:#f7f7f7" bgcolor="#f7f7f7">
            <tbody>
                <tr>
                    <td></td>
                    <td width="600">
                        <div id="m_8820884128353847947wrapper" dir="ltr" style="margin:0 auto;padding:70px 0;width:100%;max-width:600px" width="100%">
                            <table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%">
                                <tbody>
                                    <tr>
                                        <td align="center" valign="top">
                                            <div id="m_8820884128353847947template_header_image"></div>
                                            <table border="0" cellpadding="0" cellspacing="0" width="100%" id="m_8820884128353847947template_container" style="background-color:#fff;border:1px solid #dedede;border-radius:3px" bgcolor="#fff">
                                                <tbody>
                                                    <tr>
                                                        <td align="center" valign="top">
                                                            <table border="0" cellpadding="0" cellspacing="0" width="100%" id="m_8820884128353847947template_header" style="background-color:#fa6400;color:#fff;border-bottom:0;font-weight:bold;line-height:100%;vertical-align:middle;font-family:&quot;Helvetica Neue&quot;,Helvetica,Roboto,Arial,sans-serif;border-radius:3px 3px 0 0" bgcolor="#fa6400">
                                                                <tbody>
                                                                    <tr>
                                                                        <td id="m_8820884128353847947header_wrapper" style="padding:36px 48px;display:block">
                                                                            <h1 style="font-family:&quot;Helvetica Neue&quot;,Helvetica,Roboto,Arial,sans-serif;font-size:30px;font-weight:300;line-height:150%;margin:0;text-align:left;color:#fff;background-color:inherit" bgcolor="inherit">Reschedule Your Service</h1>
                                                                        </td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td align="center" valign="top">
                                                            <table border="0" cellpadding="0" cellspacing="0" width="100%" id="m_8820884128353847947template_body">
                                                                <tbody>
                                                                    <tr>
                                                                        <td valign="top" id="m_8820884128353847947body_content" style="background-color:#fff" bgcolor="#fff">
                                                                            <table border="0" cellpadding="20" cellspacing="0" width="100%">
                                                                                <tbody>
                                                                                    <tr>
                                                                                        <td valign="top" style="padding:48px 48px 32px">
                                                                                            <div id="m_8820884128353847947body_content_inner" style="color:#909396;font-family:&quot;Helvetica Neue&quot;,Helvetica,Roboto,Arial,sans-serif;font-size:14px;line-height:150%;text-align:left" align="left">
                                                                                                <p style="margin:0 0 16px">Hi ' . htmlspecialchars($orderDetails->first_name) . ',</p>
                                                                                                <p style="margin:0 0 16px">We\'ve have successfully rescheduled your appointment for order #' . htmlspecialchars($orderDetails->order_id) . ' on <b>'.$formattedDate.'</b> at <b>'.$formattedTime.'.</b></p>
                                                                                                <table id="m_8820884128353847947addresses" cellspacing="0" cellpadding="0" border="0" style="width:100%;vertical-align:top;margin-bottom:40px;padding:0" width="100%">
                                                                                                    <tbody>
                                                                                                        <tr>
                                                                                                            <td valign="top" width="50%" style="text-align:left;font-family:&quot;Helvetica Neue&quot;,Helvetica,Roboto,Arial,sans-serif;border:0;padding:0" align="left">
                                                                                                                <h2 style="color:#fa6400;display:block;font-family:&quot;Helvetica Neue&quot;,Helvetica,Roboto,Arial,sans-serif;font-size:18px;font-weight:bold;line-height:130%;margin:0 0 18px;text-align:left">Billing address</h2>
                                                                                                                <address style="padding:12px;color:#909396;border:1px solid #e5e5e5">
                                                                                                                    ' . htmlspecialchars($orderDetails->first_name) . ' ' . htmlspecialchars($orderDetails->last_name) . '<br>
                                                                                                                    ' . htmlspecialchars($orderDetails->address_1) . '<br>
                                                                                                                    ' . htmlspecialchars($orderDetails->city) . ', ' . htmlspecialchars($orderDetails->country) . ' ' . htmlspecialchars($orderDetails->postcode) . '<br>
                                                                                                                    ' . htmlspecialchars($orderDetails->phone) . '<br>
                                                                                                                    <a href="mailto:' . htmlspecialchars($user_email) . '" target="_blank">' . htmlspecialchars($user_email) . '</a>
                                                                                                                </address>
                                                                                                            </td>
                                                                                                        </tr>
                                                                                                    </tbody>
                                                                                                </table>
                                                                                                <p style="margin:0 0 16px">Thanks for using <a href="https://parahomeservices.com" target="_blank">parahomeservices.com</a>!</p>
                                                                                            </div>
                                                                                        </td>
                                                                                    </tr>
                                                                                </tbody>
                                                                            </table>
                                                                        </td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    </div>';
}

function getCompletedOrdersWithSchedule($user_id, $current_page, $items_per_page) {
    global $wpdb;

    // Calculate the offset
    $offset = ($current_page - 1) * $items_per_page;

    // Define table names
    $order_table = $wpdb->prefix . 'wc_orders';
    $order_stats_table = $wpdb->prefix . 'wc_order_stats';
    $schedule_table = $wpdb->prefix . 'aws_schedule_lookup';

    // Prepare the query
    $query = $wpdb->prepare(
        "SELECT order_table.*,
                order_detail_table.num_items_sold,
                order_detail_table.total_sales,
                schedule_table.schedule_datetime
         FROM $order_table order_table
         JOIN $order_stats_table order_detail_table
           ON order_table.id = order_detail_table.order_id
         LEFT JOIN $schedule_table schedule_table
           ON order_table.id = schedule_table.order_id
         WHERE order_table.customer_id = %d AND order_table.status <> 'trash'
         ORDER BY order_table.date_created_gmt DESC
         LIMIT %d OFFSET %d;",
        $user_id, $items_per_page, $offset
    );

    return $wpdb->get_results($query);
}

function getUnavailableTimeSlots($selectedOrderId, $initialDateSlot, $isInitialLoad=false) {
    global $wpdb;
    $selectedDate = new DateTime($initialDateSlot, new DateTimeZone('UTC'));
    $selectedOrderId = intval($selectedOrderId);
    $schedule_table = $wpdb->prefix . 'aws_schedule_lookup';
    $unavailable_slots_table = $wpdb->prefix . 'aws_unavailable_slots';

    if ($isInitialLoad) {
        // Check if the order ID already exists
        $orderExists = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $schedule_table WHERE order_id = %d",
            "SELECT * FROM $schedule_table WHERE order_id = %d",
            $selectedOrderId
        ));

        if (!empty($orderExists)) {
            $selectedDate = new DateTime($orderExists[0]->schedule_datetime, new DateTimeZone('UTC'));
        }
    }

    $query = $wpdb->prepare(
        "SELECT DISTINCT(schedule_datetime) FROM $schedule_table WHERE DATE(schedule_datetime) = '%s'",
        $selectedDate->format('Y-m-d')
    );
    $distinctUsedSlots = $wpdb->get_results($query);
    $query2 = $wpdb->prepare(
        "SELECT DISTINCT(unavailable_datetime) FROM $unavailable_slots_table WHERE DATE(unavailable_datetime) = '%s' and status = 1",
        $selectedDate->format('Y-m-d')
    );
    $unavailableSlots = $wpdb->get_results($query2);
    $timeSlots = [];
    foreach ($distinctUsedSlots as $row) {
        $dateTime = new DateTime(esc_html($row->schedule_datetime), new DateTimeZone('UTC'));
        $timeSlots[] = $dateTime->format("h:i A");
    }
    foreach ($unavailableSlots as $row) {
        $dateTime = new DateTime(esc_html($row->unavailable_datetime), new DateTimeZone('UTC'));
        $timeSlots[] = $dateTime->format("h:i A");
    }

    updateUrlParameter('scheduled', false, true);
    return array(json_encode($timeSlots), $selectedDate);
}

function updateUrlParameter($key, $value, $removeIfExists = false) {
    // Get the current URL
    $currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

    // Parse the current URL to extract existing parameters
    $parts = parse_url($currentUrl);
    parse_str($parts['query'] ?? '', $params);

    // Add, update, or remove the parameter based on the third parameter
    if ($removeIfExists) {
        if (isset($params[$key])) {
            unset($params[$key]);
        }
    } else {
        $params[$key] = $value;
    }

    // Reconstruct the URL with the new parameters
    $query = http_build_query($params);
    $newUrl = $parts['scheme'] . '://' . $parts['host'] . $parts['path'] . ($query ? '?' . $query : '');

    // Use JavaScript to update the browser's URL without reloading the page
    return '<script>window.history.pushState({ path: "'.$newUrl.'" }, "", "'.$newUrl.'");</script>';
}

function generateOrdersTableBody($orders, $current_page, $items_per_page) {
    $html = '';
    $start = ($current_page - 1) * $items_per_page;
    $end = min($start + $items_per_page, count($orders));

    for ($i = $start; $i < $end; $i++) {
        $row = $orders[$i];
        $datetime = new DateTime();
        $datetime->modify('+1 day');

        $html .= '<tr id="orderId-' . esc_attr($row->id) . '">';
        $html .= '<td><a style="cursor: pointer" onClick="viewOrder(' . esc_attr($row->id) . ')">#' . esc_html($row->id) . '</a></td>';
        $html .= '<td>' . date("M d, Y", strtotime(esc_html($row->date_created_gmt))) . '</td>';
        $html .= '<td>$' . esc_html($row->total_sales) . ' for ' . esc_html($row->num_items_sold) . ' items</td>';
        if (!is_null($row->schedule_datetime)) {
            $datetime = new DateTime($row->schedule_datetime, new DateTimeZone('UTC'));
            $formattedScheduleDatetime = $datetime->format('m-d-Y h:i A');
        } else {
            $formattedScheduleDatetime = '-';
        }
        $html .= '<td>' . $formattedScheduleDatetime . '</td>';
        $html .= '<td>
                    <div style="display: flex; align-items: center; justify-content: center">
                        <button onClick="scheduleAction(`' . $datetime->format('M d, Y h:i A') . '`, `'. esc_attr($row->id) . '`)" class="schedule-order-btn action-button">'.(!is_null($row->schedule_datetime) ? "Reschedule" : "Schedule").'</button>
                    </div>
                  </td>';
        $html .= '</tr>';
    }
    return $html;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['schedule_order_datetime'])) {
        $selected_order_id = intval($_POST['selected_order_id']);
        $table_name = $wpdb->prefix . 'aws_schedule_lookup';
        $scheduleOrderDatetime = $_POST['schedule_order_datetime'];

        // Check if the order ID already exists
        $order_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE order_id = %d",
            $selected_order_id
        ));

        $data = array(
            'schedule_datetime' => $scheduleOrderDatetime
        );
        $dateTime = new DateTime($scheduleOrderDatetime, new DateTimeZone('UTC'));
        $formattedDate = $dateTime->format('m-d-Y');
        $formattedTime = $dateTime->format('h:i A');
        $headers = array('Content-Type: text/html; charset=UTF-8');

        if ($order_exists > 0) {
            // Order ID exists, update the existing record
            $where = array('order_id' => $selected_order_id);
            $result = $wpdb->update($table_name, $data, $where);

            if ($result === false) {
                echo "Error while updating: " . $wpdb->last_error;
            } else {
                $html = "<br/>
                <div style='width: 100%; border: 1px solid #CCC; margin-bottom: 30px; text-align: center;'>
                    Appointment time rescheduled for order ID&nbsp;<b>$selected_order_id</b>&nbsp;on&nbsp;<b>$formattedDate</b>&nbsp;at&nbsp;<b>$formattedTime</b>!
                </div>";
                echo $html;

                $query = $wpdb->prepare(
                    "SELECT * FROM `wp_wc_order_addresses` t1 JOIN `wp_aws_schedule_lookup` t2 ON t1.order_id = t2.order_id WHERE t1.order_id = %d AND address_type = 'billing';", $selected_order_id
                );

                $orderDetails = $wpdb->get_results($query);
                $reschedule_template = getRescheduleTemplate($user_email, $orderDetails[0], $formattedDate, $formattedTime);
                wp_mail($user_email, "[Para Home Services] Reschedule Appointment Details", $reschedule_template, $headers);
                exit;
            }
        } else {
            $data['order_id'] = $selected_order_id;
            $result = $wpdb->insert($table_name, $data);

            if ($result === false) {
                echo "Error while scheduling: " . $wpdb->last_error;
            } else {
                $html = "<br/>
                <div style='width: 100%; border: 1px solid #CCC; margin-bottom: 30px; text-align: center;'>
                    Appointment scheduled successfully on&nbsp;<b>$formattedDate</b>&nbsp;at&nbsp;<b>$formattedTime</b>&nbsp;for Order ID&nbsp;<b>$selected_order_id</b>!
                </div>";
                echo $html;

                $query = $wpdb->prepare(
                    "SELECT * FROM `wp_wc_order_addresses` t1 JOIN `wp_aws_schedule_lookup` t2 ON t1.order_id = t2.order_id WHERE t1.order_id = %d AND address_type = 'billing';", $selected_order_id
                );

                $orderDetails = $wpdb->get_results($query);
                $schedule_template = getScheduleTemplate($user_email, $orderDetails[0], $formattedDate, $formattedTime);
                wp_mail($user_email, "[Para Home Services] Appointment Details", $schedule_template, $headers);
                exit;
            }
        }
        unset($selected_order_id);
        updateUrlParameter('scheduled', true);
    } elseif (isset($_POST['selected_date_slot'])) {
        $selected_order_id = intval($_POST['selected_order_id']);
        list($unavailable_timeslots, $selected_date) = getUnavailableTimeSlots($selected_order_id, $_POST['selected_date_slot']);

        $response = [
            'unavailable_timeslots' => json_decode($unavailable_timeslots),
            'selected_date' => $selected_date
        ];

        echo json_encode($response);
        exit;
    } elseif (isset($_POST['page'])) {
        $current_page = intval($_POST['page']);
        $items_per_page = intval($_POST['items_per_page']);
        $orders = getCompletedOrdersWithSchedule($user_id, $current_page, $items_per_page);

        $response = [
            "orderData" => $orders,
            'orderTableHTML' => generateOrdersTableBody($orders, $current_page, $items_per_page)
        ];
        echo json_encode($response);
        exit;
    }
}