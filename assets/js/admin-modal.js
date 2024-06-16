jQuery(document).ready(function ($) {
    // Get the modal
    const modal = document.getElementById("scheduleEditModal");

    // Get the <span> element that closes the modal
    const span = document.getElementsByClassName("close")[0];

    const modalHeader = document.getElementById("modalHeader");

    // When the user clicks on <span> (x), close the modal
    if (span) {
        span.onclick = function () {
            modal.style.display = "none";
        };
    }

    // When the user clicks anywhere outside of the modal, close it
    window.onclick = function (event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    };

    // Open the modal and populate the form
    $(".schedule-id").click(function () {
        const scheduleId = $(this).data("id");
        const scheduleDatetime = $(this).data("datetime");
        const status = $(this).data("status");
        const timezone = $(this).data("timezone");

        $("#schedule_id").val(scheduleId);
        $("#schedule_datetime_input").val(scheduleDatetime);
        $("#schedule_datetime_hidden").val(scheduleDatetime);
        $("#status_input").val(status);
        $("#timezone").val(timezone);
        modalHeader.textContent = "Schedule #" + scheduleId;

        modal.style.display = "block";
    });

    // Handle form submission
    $("#editScheduleForm").submit(function (e) {
        e.preventDefault();
        const formData = $(this).serialize();

        fetch(ajaxurl, {
            method: "POST",
            body: formData,
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
        })
            .then((response) => {
                if (response.ok) {
                    alert("Schedule updated successfully!");
                    modal.style.display = "none";
                    location.reload(); // Reload the page to reflect changes
                } else {
                    alert("Error updating schedule: " + response.data);
                }
            })
            .catch((error) => {
                console.error("Error:", error);
            });
    });
    // $("#schedule_datetime").datepicker({
    //     dateFormat: "yy-mm-dd",
    //     timeFormat: "HH:mm:ss",
    //     showTime: true,
    //     changeYear: true,
    //     changeMonth: true,
    //     yearRange: "c-100:c+10",
    //     controlType: "select",
    //     oneLine: true,
    //     onSelect: function (dateText, inst) {
    //         // Do something when a date is selected
    //     },
    // });
    // $("#schedule_time").timepicker({
    //     minuteStep: 1,
    //     showMeridian: false,
    //     defaultTime: "current",
    // });
});
