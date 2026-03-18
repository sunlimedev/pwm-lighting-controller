<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Lighting Hours Test</title>
    <link rel="stylesheet" href="/css/tailwind.min.css">
</head>
<body class="bg-gray-100 p-6">

<div class="max-w-md mx-auto bg-white p-4 rounded shadow">

    <h2 class="text-lg font-medium mb-4">Lighting Schedule</h2>

    <form method="POST" class="space-y-4">

    <?php
    $days = [
        0 => "Monday",
        1 => "Tuesday",
        2 => "Wednesday",
        3 => "Thursday",
        4 => "Friday",
        5 => "Saturday",
        6 => "Sunday"
    ];

    foreach ($days as $id => $day):
    ?>

    <div class="border p-3 rounded space-y-2" id="day-<?= $id ?>">

        <div class="flex justify-between items-center">
            <span class="font-medium"><?= $day ?></span>

            <div class="flex gap-3">
                <label class="flex items-center gap-1">
                    <input type="checkbox" class="allDay w-4 h-4">
                    <span>All Day</span>
                </label>

                <label class="flex items-center gap-1">
                    <input type="checkbox" class="noneDay w-4 h-4">
                    <span>None</span>
                </label>
            </div>
        </div>

        <!-- Opening -->
        <div class="flex gap-2">
            <select name="open_hour[<?= $id ?>]" class="border p-1 rounded open_hour">
                <?php for ($i = 1; $i <= 12; $i++): ?>
                    <option value="<?= $i ?>"><?= $i ?></option>
                <?php endfor; ?>
            </select>

            <select name="open_minute[<?= $id ?>]" class="border p-1 rounded open_minute">
                <option value="00">00</option>
                <option value="15">15</option>
                <option value="30">30</option>
                <option value="45">45</option>
            </select>

            <select name="open_ampm[<?= $id ?>]" class="border p-1 rounded open_ampm">
                <option value="AM">AM</option>
                <option value="PM">PM</option>
            </select>
        </div>

        <!-- Closing -->
        <div class="flex gap-2">
            <select name="close_hour[<?= $id ?>]" class="border p-1 rounded close_hour">
                <?php for ($i = 1; $i <= 12; $i++): ?>
                    <option value="<?= $i ?>"><?= $i ?></option>
                <?php endfor; ?>
            </select>

            <select name="close_minute[<?= $id ?>]" class="border p-1 rounded close_minute">
                <option value="00">00</option>
                <option value="15">15</option>
                <option value="30">30</option>
                <option value="45">45</option>
            </select>

            <select name="close_ampm[<?= $id ?>]" class="border p-1 rounded close_ampm">
                <option value="AM">AM</option>
                <option value="PM">PM</option>
            </select>
        </div>

    </div>

    <?php endforeach; ?>

        <button type="submit"
                class="w-full mt-4 px-4 py-2 bg-blue-500 text-white rounded">
            Save
        </button>

    </form>

    <!-- Debug -->
    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
        <pre class="mt-4 bg-gray-100 p-2 text-xs"><?php print_r($_POST); ?></pre>
    <?php endif; ?>

</div>

<script>
document.querySelectorAll("[id^='day-']").forEach(container => {

    const allDay = container.querySelector(".allDay");
    const noneDay = container.querySelector(".noneDay");

    const openHour = container.querySelector(".open_hour");
    const openMinute = container.querySelector(".open_minute");
    const openAMPM = container.querySelector(".open_ampm");

    const closeHour = container.querySelector(".close_hour");
    const closeMinute = container.querySelector(".close_minute");
    const closeAMPM = container.querySelector(".close_ampm");

    function disableAll(state) {
        openHour.disabled = state;
        openMinute.disabled = state;
        openAMPM.disabled = state;

        closeHour.disabled = state;
        closeMinute.disabled = state;
        closeAMPM.disabled = state;
    }

    allDay.addEventListener("change", function () {
        if (this.checked) {
            noneDay.checked = false;

            // 12:00 AM → 11:59 PM
            openHour.value = "12";
            openMinute.value = "00";
            openAMPM.value = "AM";

            closeHour.value = "11";
            closeMinute.value = "59";
            closeAMPM.value = "PM";

            disableAll(true);
        } else {
            disableAll(false);
        }
    });

    noneDay.addEventListener("change", function () {
        if (this.checked) {
            allDay.checked = false;

            // 12:00 AM → 12:00 AM
            openHour.value = "12";
            openMinute.value = "00";
            openAMPM.value = "AM";

            closeHour.value = "12";
            closeMinute.value = "00";
            closeAMPM.value = "AM";

            disableAll(true);
        } else {
            disableAll(false);
        }
    });

});
</script>

</body>
</html>
