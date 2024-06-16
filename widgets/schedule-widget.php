<?php
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

/**
 * Woo commerce schedule Widget.
 *
 * Woo commerce schedule Widget to schedule any services.
 *
 * @since 1.0.0
 */
class Schedule_Widget extends \Elementor\Widget_Base
{

	/**
	 * Get widget name.
	 *
	 * Retrieve schedule widget name.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string Widget name.
	 */
	public function get_name()
	{
		return 'schedule';
	}

	/**
	 * Get widget title.
	 *
	 * Retrieve schedule widget title.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string Widget title.
	 */
	public function get_title()
	{
		return esc_html__('Schedule Widget', 'schedule-widget');
	}

	/**
	 * Get widget icon.
	 *
	 * Retrieve schedule widget icon.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string Widget icon.
	 */
	public function get_icon()
	{
		return 'eicon-calendar';
	}

	/**
	 * Get widget categories.
	 *
	 * Retrieve the list of categories the schedule widget belongs to.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return array Widget categories.
	 */
	public function get_categories()
	{
		return ['general'];
	}

	/**
	 * Get widget keywords.
	 *
	 * Retrieve the list of keywords the schedule widget belongs to.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return array Widget keywords.
	 */
	public function get_keywords()
	{
		return ['schedule'];
	}

	/**
	 * Get custom help URL.
	 *
	 * Retrieve a URL where the user can get more information about the widget.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string Widget help URL.
	 */
	public function get_custom_help_url()
    {}

	/**
	 * Register schedule widget controls.
	 *
	 * Add input fields to allow the user to customize the widget settings.
	 *
	 * @since 1.0.0
	 * @access protected
	 */
	protected function register_controls()
    {}

	/**
	 * Render schedule widget output on the frontend.
	 *
	 * Written in PHP and used to generate the final HTML.
	 *
	 * @since 1.0.0
	 * @access protected
	 */
	protected function render()
	{
		global $wpdb, $selected_order_id, $selected_date, $unavailable_timeslots, $orders;
        $current_user = wp_get_current_user();
		// Check if the user is logged in.
		if ($current_user->exists()) {
			// Get the user's email address.
            $user_id = $current_user->ID;
		}

		function getCompletedOrdersWithSchedule($user_id) {
			global $wpdb;

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
                ORDER BY order_table.date_created_gmt DESC;",
				$user_id
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
            echo '<script>window.history.pushState({ path: "'.$newUrl.'" }, "", "'.$newUrl.'");</script>';
        }

		$orders = getCompletedOrdersWithSchedule($user_id);
?>

		<div class="schedule-widget">
			<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
            <h2 style="display: flex;">
                <a class="back"
                    href="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] != 'localhost' ? $_SERVER['HTTP_HOST'] : 'localhost/wordpress') ?>/my-account"
                    style="display: flex; justify-content: center; align-items: center; padding-right: 15px;"
                >
                    <i class="fa-solid fa-angle-left"></i>
                </a>
                Schedule Your Quarterly Service
            </h2>

<?php
            if (!empty($orders)) {
                echo '<div id="outerContainer"></div>
					<div style="width: 100%; margin-bottom: 20px; overflow: auto">
                        <table id="orders_table" style="width: 100%;">
                            <thead>
                                <tr style="text-align: left">
                                    <th>Order ID</th>
                                    <th>Purchase Date</th>
                                    <th>Total</th>
                                    <th>Appointment Date</th>
                                    <th style="text-align: center">Action</th>
                                </tr>
                            </thead>
                            <tbody id="orders_table_body">
                            </tbody>
                        </table>
                        <div id="orders_table_short">
                        </div>
                    </div>
                    <div id="pagination-controls" style="text-align: center; margin-top: 10px;">
                        <button class="pagination-button" onclick="changePage(-1)" disabled id="prev-btn"><i class="fa-solid fa-angle-left"></i></button>
                        <span id="page-info"></span>
                        <button class="pagination-button" onclick="changePage(1)" id="next-btn"><i class="fa-solid fa-angle-right"></i></button>
                    </div>
                    <div id="schedule-widget-body">
                        <div class="calendarContainer">
                            <div class="container">
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="calendarBox">
                                            <div class="header">
                                                <button id="prevMonth">
                                                    <i class="fa-solid fa-angle-left"></i>
                                                </button>
                                                <div id="month">
                                                    <select id="selectMonth"></select>
                                                    <select id="selectYear"></select>
                                                </div>
                                                <button id="nextMonth">
                                                    <i class="fa-solid fa-chevron-right"></i>
                                                </button>
                                            </div>
                                            <div id="calendar"></div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="box">
                                            <div class="information">
                                                <p id="appointment">Set Appointment for :</p>
                                                <h1 id="timeSelected"></h1>
                                                <p id="addressLocation"></p>
                                            </div>
                                            <div id="spinner" class="spinner" style="display: none">
                                                <i class="fas fa-spinner fa-spin"></i>
                                                <p style="font-size: 15px; margin: 0">Fetching Available Slots</p>
                                            </div>
                                            <div id="timeSlotContainer">
                                                <div id="timeSlot"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div style="width: 100%; display: flex; align-items: center; justify-content: center">
                            <button onClick="scheduleAppointment()" id="setAppointment" disabled>Set Appointment</button>
                        </div>
                    </div>';

                $date = date('D M d Y', strtotime('+1 day'));
                if(isset($_GET['orderId'])) {
					$selected_order_id = $_GET['orderId'];
					list($unavailable_timeslots, $selected_date) = getUnavailableTimeSlots($selected_order_id, $date, true);
				} else {
					$selected_order_id = esc_attr($orders[0]->id);
					list($unavailable_timeslots, $selected_date) = getUnavailableTimeSlots($selected_order_id, $date, true);
					updateUrlParameter('orderId', $selected_order_id);
				}
            } else {
                echo "<br/>
                    <div style='width: 100%; height: 50px; display: flex; justify-content: center; align-items: center; border: 1px solid black'>
                        No order has been made yet.
                    </div>";
            }
?>

			<script>
				const parseDateTimeToUTC = (dateTimeString) => {
					const dateTimeParts = dateTimeString.split(' ');
					const dateParts = dateTimeParts[0].split('-');
					const timeParts = dateTimeParts[1].split(':');

					// Extract and parse individual components
					const year = parseInt(dateParts[0], 10);
					const month = parseInt(dateParts[1], 10) - 1; // Months are zero-based in JavaScript Date (0 = January, 1 = February, etc.)
					const day = parseInt(dateParts[2], 10);
					const hour = parseInt(timeParts[0], 10);
					const minute = parseInt(timeParts[1], 10);
					const second = parseInt(timeParts[2], 10);

					// Create and return the UTC Date object
					return new Date(Date.UTC(year, month, day, hour, minute, second));
				}

                function parseToUTCDate(dateTimeString) {
                    const parts = dateTimeString.split(' ');
                    const [dayOfWeek, month, day, year, time, period] = parts;

                    // Convert month name to month index (0-11)
                    const monthIndex = new Date(`${month} 1, 2020`).getMonth();
                    const [hours, minutes] = time.split(':').map(Number);

                    // Adjust hours for AM/PM
                    let adjustedHours = hours;
                    if (period === 'PM' && hours !== 12) {
                        adjustedHours += 12;
                    }
                    if (period === 'AM' && hours === 12) {
                        adjustedHours = 0;
                    }

                    // Create the Date object in UTC
                    const utcDate = new Date(Date.UTC(year, monthIndex, day, adjustedHours, minutes));

                    return utcDate;
                }

                const formatDate = (date) => {
                    const months = [
                        'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                        'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
                    ];

                    const month = months[date.getMonth()];
                    const day = date.getDate();
                    const year = date.getFullYear();

                    let hours = date.getHours();
                    const minutes = date.getMinutes();
                    const ampm = hours >= 12 ? 'PM' : 'AM';

                    hours = hours % 12;
                    hours = hours ? hours : 12; // the hour '0' should be '12'
                    const strMinutes = minutes < 10 ? '0' + minutes : minutes;

                    return `${month} ${day}, ${year} ${hours}:${strMinutes} ${ampm}`;
                }


                const url = window.location.href;
                const urlObj = new URL(url);
                const params = new URLSearchParams(urlObj.search);

                // Get the value of the 'orderId' parameter
                const notAvailable = [];
                let selectedTimeSlot = "";
                let selectedOrderId = params.get('orderId');
                let curr_year = new Date().getFullYear();
                const timeSlots = ["09:00 AM", "01:00 PM", "03:00 PM"];

                const monthNames = [
                    "January",
                    "February",
                    "March",
                    "April",
                    "May",
                    "June",
                    "July",
                    "August",
                    "September",
                    "October",
                    "November",
                    "December",
                ];

                const dayNames = [
                    "Sun",
                    "Mon",
                    "Tue",
                    "Wed",
                    "Thu",
                    "Fri",
                    "Sat",
                ];

                let newDate = new Date();
                newDate.setDate(newDate.getDate() + 1);
                let dateSlotSelected = "<?php echo !empty($selected_date) ? $selected_date->format('Y-m-d H:i:s') : "" ?>";
                let selectedDate = dateSlotSelected ? parseDateTimeToUTC(dateSlotSelected) : newDate;
                let currentDate = dateSlotSelected ? parseDateTimeToUTC(dateSlotSelected) : newDate;
                let scheduledDate = selectedDate;
                let scheduledTimeSlot = selectedDate.toLocaleTimeString('en-US', {
                    timeZone: 'UTC',
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: true
                });
                selectedTimeSlot = scheduledTimeSlot;

                let busy_slots = JSON.parse(<?php echo json_encode($unavailable_timeslots) ?>);
                let currentPage = 1;
                const itemsPerPage = 5;
                const totalItems = <?php echo count($orders); ?>;
                const totalPages = Math.ceil(totalItems / itemsPerPage);

                if (document.getElementById('page-info')) document.getElementById('page-info').innerText = `Page ${currentPage} of ${totalPages}`;
                document.getElementById("prevMonth")?.addEventListener("click", goToPreviousMonth);
                document.getElementById("nextMonth")?.addEventListener("click", goToNextMonth);
                const calendarElement = document.getElementById("calendar");
                const addressLocation = document.getElementById("addressLocation");

				const viewOrder = (orderId) => {
					const base_url = "<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] != 'localhost' ? $_SERVER['HTTP_HOST'] : 'localhost/wordpress') . '/my-account/view-order/'; ?>";
					const url = base_url + encodeURIComponent(orderId);

 					window.location.href = url;
				};

                const scheduleAppointment = () => {
                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', '<?php echo plugins_url('schedule-widget-handler.php', __FILE__); ?>', true);
                    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                    xhr.onload = function() {
                        if (xhr.status === 200) {
                            document.getElementById("schedule-widget-body").style.display = "none";
                            document.getElementById("outerContainer").innerHTML = xhr.responseText;
                            document.getElementById("outerContainer").scrollIntoView({ behavior: 'smooth' });
                            updateOrdersTable();
                        }
                    };

                    const formData = new FormData();
                    formData.append('selected_order_id', selectedOrderId);
                    formData.append('schedule_order_datetime', parseToUTCDate(new Date(selectedDate).toDateString() + " " + selectedTimeSlot).toISOString().slice(0, 19).replace('T', ' '));

                    xhr.send(new URLSearchParams(formData).toString());
                }

                const getAddress = () => {
                    var address = "<?php
                        $address_detail = $wpdb->get_results("SELECT * FROM wp_wc_order_addresses WHERE order_id='$selected_order_id' AND address_type='billing'");
                        if ($address_detail) {
                            // Loop through each address detail
                            $countries = WC()->countries->countries;
                            foreach ($address_detail as $address) {
                                // Display the address information
                                $country_code = $address->country;
                                echo $address->city . ", " . (isset( $countries[ $country_code ] ) ? $countries[ $country_code ] : 'Unknown Country');
                            }
                        } else {
                            echo "No billing address found for order ID: $selected_order_id";
                        }
                    ?>";
                    if (addressLocation) {
                        addressLocation.innerText = address;
                    }
                };

                const scheduleAction = (newSelectedDate, orderId) => {
                    const date = new Date();
                    date.setDate(date.getDate() + 1);
                    handleDateSelect(newSelectedDate === "null" ? date.toString().slice(3, 24).replace('T', ' ') : newSelectedDate, orderId);
                }

                const handleDateSelect = (newSelectedDate, orderId) => {
                    const url = new URL(window.location);
                    const prevSelectedRow = document.getElementById("orderId-"+selectedOrderId);
                    const selectedRow = document.getElementById("orderId-"+orderId);
                    const prevShortSelectedRow = document.getElementById("orderShortId-"+selectedOrderId);
                    const shortSelectedRow = document.getElementById("orderShortId-"+orderId);
                    if (prevSelectedRow) {
                        prevSelectedRow.classList.remove("selected-row");
                    }
                    if (selectedRow) {
                        selectedRow.classList.add("selected-row");
                    }
                    if (prevShortSelectedRow) {
                        prevShortSelectedRow.classList.remove("selected-row");
                    }
                    if (shortSelectedRow) {
                        shortSelectedRow.classList.add("selected-row");
                    }
                    selectedOrderId = orderId;
                    url.searchParams.set('orderId', selectedOrderId);
                    window.history.pushState({ path: url.href }, '', url.href);

                    selectedDate = newSelectedDate;
                    currentDate = new Date(newSelectedDate);
                    scheduledDate = currentDate;
                    scheduledTimeSlot = parseDateTimeToUTC(newSelectedDate).toLocaleTimeString('en-US', {
                        timeZone: 'UTC',
                        hour: '2-digit',
                        minute: '2-digit',
                        hour12: true
                    });
                    selectedTimeSlot = scheduledTimeSlot;

                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', '<?php echo plugins_url('schedule-widget-handler.php', __FILE__); ?>', true);
                    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                    xhr.onload = function() {
                        if (xhr.status === 200 && xhr.responseText) {
                            const response = JSON.parse(xhr.responseText);
                            busy_slots = response.unavailable_timeslots;
                            fetchAvailableTimeSlots(selectedDate);
                            document.getElementById("spinner").style.display = "none";
                            document.getElementById("timeSlotContainer").style.display = "block";
                            document.getElementById("schedule-widget-body").style.display = "block";
                            document.getElementById("outerContainer").innerHTML = "";
                            updateCalendar();
                        } else {
                            console.log("Error Fetching Data.")
                        }
                    };

                    const formData = new FormData();
                    formData.append('selected_order_id', '<?php echo $selected_order_id; ?>');
                    formData.append('selected_date_slot', selectedDate);

                    document.getElementById("spinner").style.display = "block";
                    document.getElementById("timeSlotContainer").style.display = "none";
                    xhr.send(new URLSearchParams(formData).toString());
                }

                const generateOrdersShortTable = (orders) => {
                    let orders_html = "";
                    orders.forEach(order => {
                        orderHTML = `
                        <div id="orderShortId-${order.id}" style="display: flex; flex-direction: column; border: 1px solid #CCC; padding: .5rem; margin: 5px 0">
                            <div class="table-row">
                                <b>Order ID:</b> <a style="cursor: pointer" onClick="viewOrder(${order.id})">#${order.id}</a>
                            </div>
                            <div class="table-row">
                                <b>Purchase Date:</b> ${new Date(order.date_created_gmt).toDateString()}
                            </div>
                            <div class="table-row">
                                <b>Total:</b> <span>$${order.total_sales} for ${order.num_items_sold} items</span>
                            </div>
                            <div class="table-row">
                                <b>Appointment Date:</b> <span style="text-align: right">${order.schedule_datetime ? formatDate(parseDateTimeToUTC(order.schedule_datetime)) : '-'}</span>
                            </div>
                            <div class="table-row">
                                <b>Action:</b> <button onClick="scheduleAction('${order.schedule_datetime}', '${order.id}')" class="schedule-order-btn">${order.schedule_datetime ? "Reschedule" : "Schedule"}</button>
                            </div>
                        </div>`;
                        orders_html += orderHTML;
                    })
                    return orders_html;
                };

                function updateOrdersTable() {
                    // Update the pagination controls
                    if (document.getElementById('page-info')) { document.getElementById('page-info').innerText = `Page ${currentPage} of ${totalPages}`; }
                    if (document.getElementById('prev-btn')) document.getElementById('prev-btn').disabled = currentPage === 1;
                    if (document.getElementById('next-btn')) document.getElementById('next-btn').disabled = currentPage === totalPages;

                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', '<?php echo plugins_url('schedule-widget-handler.php', __FILE__); ?>', true);
                    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                    xhr.onload = function() {
                        if (xhr.status === 200 && xhr.responseText !== "") {
                            const response = JSON.parse(xhr.responseText);
                            if (document.getElementById('orders_table_body')) document.getElementById('orders_table_body').innerHTML = response.orderTableHTML;
                            if (document.getElementById('orders_table_short')) document.getElementById('orders_table_short').innerHTML = generateOrdersShortTable(response.orderData);
                            const selectedRow = document.getElementById("orderId-"+selectedOrderId);
                            const shortSelectedRow = document.getElementById("orderShortId-"+selectedOrderId);
                            if (selectedRow) {
                                selectedRow.classList.add("selected-row");
                            }
                            if (shortSelectedRow) {
                                shortSelectedRow.classList.add("selected-row");
                            }
                        }
                    };

                    const formData = new FormData();
                    formData.append('page', currentPage);
                    formData.append('items_per_page', itemsPerPage);
                    xhr.send(new URLSearchParams(formData).toString());
                }

                function changePage(direction) {
                    if (direction === 1 && currentPage < totalPages) {
                        currentPage++;
                    } else if (currentPage > 1 && direction === -1) {
                        currentPage--;
                    }
                    updateOrdersTable();
                }

                function genRateMonth(year, month) {
                    const selectMonth = document.getElementById("selectMonth");
                    const selectYear = document.getElementById("selectYear");

                    if (selectMonth && selectYear) {
                        // Populate select options for month
                        monthNames.forEach((monthName, index) => {
                            const option = document.createElement("option");
                            option.value = index + 1;
                            option.text = monthName;
                            selectMonth.appendChild(option);
                        });

                        // Populate select options for year
                        const currentYear = new Date().getFullYear();
                        for (let i = currentYear; i <= currentYear + 4; i++) {
                            const option = document.createElement("option");
                            option.value = i;
                            option.text = i;
                            selectYear.appendChild(option);
                        }

                        // Set initial selected values
                        selectMonth.value = month;
                        selectYear.value = year;

                        // Add change event listeners to select elements
                        selectMonth.addEventListener("change", function() {
                            generateCalendar(selectYear.value, this.value);
                        });

                        selectYear.addEventListener("change", function() {
                            generateCalendar(this.value, selectMonth.value);
                        });
                    }
                }

                function generateCalendar(yearParam, monthParam) {
                    let year = yearParam;
                    let month = monthParam;
                    const daysInMonth = new Date(year, month, 0).getDate();
                    const firstDayOfMonth = new Date(
                        year,
                        month - 1,
                        1
                    ).getDay();

                    let calendarHTML = `<thead><tr>`;
                    for (let i = 0; i < 7; i++) {
                        calendarHTML += `<th class="day-name">${dayNames[i]}</th>`;
                    }
                    calendarHTML += `</tr></thead><tbody>`;

                    let dayCounter = 1;
                    let prevMonthDays =
                        firstDayOfMonth === 0 ? 7 : firstDayOfMonth;

                    for (let i = 1; i <= 6; i++) {
                        calendarHTML += `<tr>`;
                        for (let j = 1; j <= 7; j++) {
                            if (prevMonthDays > 0) {
                                calendarHTML += `<td class="prev-month beforeDate">${new Date(
                                    year,
                                    month - 1,
                                    -prevMonthDays + 1
                                ).getDate()}</td>`;
                                prevMonthDays--;
                            } else if (dayCounter <= daysInMonth) {
                                let dateClass = "";
                                const currentDateObj = new Date(
                                    year,
                                    month - 1,
                                    dayCounter
                                );
                                const dateString =
                                    currentDateObj.toDateString();

                                if (notAvailable.includes(dateString) || currentDateObj < new Date() || ([0, 6]).includes(currentDateObj.getDay())) {
                                    dateClass = "not-available";
                                } else if (selectedDate === dateString || new Date(selectedDate).toDateString() === dateString) {
                                    dateClass = "selected date";
                                } else {
                                    dateClass = "not-selected date";
                                }

                                calendarHTML += `<td class="${dateClass}" data-date="${dateString}">${dayCounter}</td>`;
                                dayCounter++;
                            } else {
                                calendarHTML += `<td class="next-month beforeDate">${dayCounter - daysInMonth
                                    }</td>`;
                                dayCounter++;
                            }
                        }
                        calendarHTML += `</tr>`;
                    }

                    calendarHTML += `</tbody>`;
                    if (calendarElement) {
                        calendarElement.innerHTML = `<table>${calendarHTML}</table>`;
                    }

                    const dateCells = document.querySelectorAll(".date");
                    dateCells.forEach((cell) => {
                        cell.addEventListener("click", function() {
                            selectedTimeSlot = null;
                            const newSelectedDate = this.getAttribute("data-date");
                            if (!this.classList.contains("selected")) {
                                // If the cell is not already selected, add the 'selected' class and remove the 'not-selected' class
                                this.classList.add("selected");
                                this.classList.remove("not-selected");
                                handleDateSelect(newSelectedDate, selectedOrderId);
                            }
                        });
                    });
                }

                function updateCalendar() {
                    generateCalendar(curr_year, currentDate.getMonth() + 1);
                    genRateMonth(curr_year, currentDate.getMonth() + 1);
                }

                function goToPreviousMonth() {
                    const currentYear = currentDate.getFullYear();
                    const currentMonth = currentDate.getMonth();

                    // If current month and year are same as today's date, do not go to previous month
                    const isGoPrev = currentMonth === new Date().getMonth() && currentYear === new Date().getFullYear();

                    // Check if previous year is within the allowed range and current month is January
                    if (updateNavigationButtons(currentYear - 1) && currentMonth === 0) {
                        return;
                    }

                    // If not going back to previous year and month, update currentDate
                    if (!isGoPrev) {
                        if (currentMonth === 0) { // If current month is January
                            currentDate.setFullYear(currentYear - 1); // Decrement year
                            currentDate.setMonth(11); // Set month to December
                        } else {
                            currentDate.setMonth(currentMonth - 1); // Decrement month
                        }

                        // Update select components
                        document.getElementById("selectMonth").value = currentDate.getMonth() + 1;
                        document.getElementById("selectYear").value = currentDate.getFullYear();

                        // Update curr_year and calendar
                        curr_year = currentDate.getFullYear();
                        updateCalendar();
                    }
                }

                function goToNextMonth() {
                    if (updateNavigationButtons(currentDate.getFullYear() + 1) && currentDate.getMonth() === 11) {
                        return
                    } else {
                        const currentMonth = currentDate.getMonth();
                        const currentYear = currentDate.getFullYear();

                        if (currentMonth === 11) { // If current month is December
                            currentDate.setFullYear(currentYear + 1); // Increment year
                            currentDate.setMonth(0); // Set month to January
                        } else {
                            currentDate.setMonth(currentMonth + 1); // Increment month
                        }

                        const selectMonth = document.getElementById("selectMonth");
                        const selectYear = document.getElementById("selectYear");

                        const newMonth = currentDate.getMonth() + 1;
                        const newYear = currentDate.getFullYear();

                        selectMonth.value = newMonth; // Set month value
                        selectYear.value = newYear; // Set year value

                        curr_year = newYear; // Update curr_year

                        updateCalendar();
                    }
                }

                async function fetchAvailableTimeSlots(selectedDate) {
                    const dateObj = new Date(selectedDate);
                    const options = {
                        weekday: "long",
                        month: "long",
                        day: "numeric",
                        year: "numeric",
                    };
                    const fullDate = dateObj.toLocaleDateString(
                        "en-US",
                        options
                    );
                    const timeSelected =
                        document.getElementById("timeSelected");
                    if (timeSelected) {
                        timeSelected.innerHTML = fullDate;
                    }
                    renderTimeSlots(timeSlots);
                }

                function renderTimeSlots(timeSlots) {
                    const unavailable_timeslots = busy_slots;
                    const timeSlotsElement =
                    document.getElementById("timeSlot");
                    if (timeSlotsElement) {
                        timeSlotsElement.innerHTML = ""; // Clear previous slots
                        timeSlots.forEach((slot) => {
                            const button = document.createElement("button");
                            button.innerText = slot;
                            button.addEventListener("click", () => {
                                setSelectedTimeSlot(slot);
                            });
                            button.className =
                            unavailable_timeslots.includes(slot) ? ((scheduledDate.toDateString() === new Date(selectedDate).toDateString()) && scheduledTimeSlot === slot) ? "scheduled timeslot" : "disabled timeslot" : selectedTimeSlot !== slot ?
                                "timeslot" :
                                "selected timeslot";
                            button.disabled = unavailable_timeslots.includes(slot);
                            button.style.cursor = unavailable_timeslots.includes(slot) ? "default" : "pointer";
                            timeSlotsElement.appendChild(button);
                        });
                    }
                }

                // Function to update the state of the navigation buttons
                function updateNavigationButtons(year) {
                    const currentYear = new Date().getFullYear();
                    return !(year > currentYear && year <= currentYear + 4);
                }

                function setSelectedTimeSlot(slot) {
                    if (selectedTimeSlot === slot) {
                        selectedTimeSlot = null;
                        document.getElementById("setAppointment").disabled = true;
                    } else {
                        selectedTimeSlot = slot;
                        document.getElementById("setAppointment").disabled = false;
                    }
                    renderTimeSlots(timeSlots);
                }

                document.addEventListener("DOMContentLoaded", (event) => {
                    updateOrdersTable();
                    generateCalendar(new Date(selectedDate).getFullYear(), new Date(selectedDate).getMonth() + 1);
                    genRateMonth(new Date(selectedDate).getFullYear(), new Date(selectedDate).getMonth() + 1);
                    getAddress();
                    fetchAvailableTimeSlots(selectedDate);
                });
			</script>
		</div>
<?php
	}
}
