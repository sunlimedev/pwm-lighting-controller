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

try
{
	// connect to lighting.db
    $db = new PDO('sqlite:/home/user/project/database/lighting.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

	// get all time info
    $stmt = $db->prepare("SELECT * FROM time ORDER BY weekday_id ASC");
    $stmt->execute();
    // store info in daily_hours
    $daily_hours = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    /* structure of daily_hours:
    Array
	(
		[0] => Array
			(
				[weekday_id] => 0
				[open_hour] => 0
				[open_minute] => 0
				[close_hour] => 0
				[close_minute] => 0
			)

		[1] => Array
			(
				[weekday_id] => 1
				[open_hour] => 8
				[open_minute] => 0
				[close_hour] => 11
				[close_minute] => 30
			)
		...
	*/
}
// catch block to handle error
catch (PDOException $e)
{
	// print the error on the webpage
    echo "Database error: " . $e->getMessage();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    function to24Hour($hour, $ampm) {
        $hour = (int)$hour;

        if ($ampm === 'AM') {
            return ($hour == 12) ? 0 : $hour;
        } else {
            return ($hour == 12) ? 12 : $hour + 12;
        }
    }
	
	function toMinutes($hour, $minute, $ampm) {
    $hour = (int)$hour;
    $minute = (int)$minute;

    if ($ampm === 'AM') {
        if ($hour == 12) $hour = 0;
    } else {
        if ($hour != 12) $hour += 12;
    }

    return $hour * 60 + $minute;
}

for ($id = 0; $id <= 6; $id++) {

    $open = toMinutes($_POST['open_hour'][$id], $_POST['open_minute'][$id], $_POST['open_ampm'][$id]);
    $close = toMinutes($_POST['close_hour'][$id], $_POST['close_minute'][$id], $_POST['close_ampm'][$id]);

    if ($close < $open) {
        die("Invalid time range on day $id");
    }
}
	
    $stmt = $db->prepare("
        UPDATE time
        SET open_hour = ?, open_minute = ?, close_hour = ?, close_minute = ?
        WHERE weekday_id = ?
    ");

    for ($id = 0; $id <= 6; $id++) {

        // pull values from POST
        $open_hour_12  = $_POST['open_hour'][$id];
        $open_minute   = $_POST['open_minute'][$id];
        $open_ampm     = $_POST['open_ampm'][$id];

        $close_hour_12 = $_POST['close_hour'][$id];
        $close_minute  = $_POST['close_minute'][$id];
        $close_ampm    = $_POST['close_ampm'][$id];

        // convert to 24-hour
        $open_hour_24  = to24Hour($open_hour_12, $open_ampm);
        $close_hour_24 = to24Hour($close_hour_12, $close_ampm);
		
		if($close_hour_24 == 23 and $close_minute == 59)
		{
			$close_hour_24 = 24;
			$close_minute = 0;
		}
		
        // execute update
        $stmt->execute([
            $open_hour_24,
            (int)$open_minute,
            $close_hour_24,
            (int)$close_minute,
            $id
        ]);
    }

    // optional: reload updated data
    header("Location: /schedule.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Schedule</title>
    <link rel="stylesheet" href="/css/tailwind.min.css">
</head>

<body class="bg-gray-100 min-h-screen">
	<!-- logo, header, and tooltip -->
	<div class="text-center py-6 flex justify-between items-center max-w-md mx-auto pl-7 pr-7">
		<span>
			<a href="/schedule.php" class="inline-block">
				<img src="/assets/back.svg" 
					alt="Back"
					class="mx-auto w-9 h-9 pt-2">
			</a>
		</span>
		<span>
			<img src="/assets/logo.svg" 
				alt="Logo"
				class="mx-auto w-48">
		</span>
		<span>
			<a href="/home.php" class="inline-block">
				<img src="/assets/home.svg" 
					alt="Home"
					class="mx-auto w-9 h-9 pt-1">
			</a>
		</span>
	</div>

	<div class="max-w-md mx-auto p-1">
		<div class="flex justify-between items-center mb-2">
			<h1 class="text-3xl font-semibold p-1">
				Edit Schedule
			</h1>
			
			<div class="relative pr-1">
				<a href="#" id="toggle-info"
					class="px-4 py-3 bg-purple-400 w-20 rounded-xl
                      hover:bg-purple-500 active:scale-95
                      transition flex items-center justify-center">
                <img src="/assets/help.svg"
                     alt="Help"
                     class="w-12 h-6">
				</a>

				<div id="info-box"
					class="absolute right-0 mt-2 w-64 bg-white p-4 rounded-lg shadow-lg hidden z-50">
					<p class="text-gray-700">
						placeholder
					</p>
				</div>
			</div>
		</div>
	</div>
	<!-- logo, header, and tooltip -->
	
	<!-- form inside container-->
	<div class="max-w-md mx-auto p-1">
		<form method="POST">
		<div class="bg-gray-50 rounded-lg divide-y divide-gray-200">
			<?php foreach ($days as $id => $day): ?>
			
			<?php
				// 24 hour to 12 hour
				$db_open_hour = $daily_hours[$id]['open_hour'];
				$db_open_minute = $daily_hours[$id]['open_minute'];

				$open_ampm = ($db_open_hour >= 12) ? 'PM' : 'AM';
				$open_hour_12 = $db_open_hour % 12;
				if ($open_hour_12 == 0) $open_hour_12 = 12;
				
				$db_close_hour = $daily_hours[$id]['close_hour'];
				$db_close_minute = $daily_hours[$id]['close_minute'];
				
				// rewrite 24:00 to 11:59p if necessary
				if($db_close_hour == 24)
				{
					$close_ampm = 'PM';
					$close_hour_12 = 11;
					$db_close_minute = 59;
				}
				else 
				{
					$close_ampm = ($db_close_hour >= 12) ? 'PM' : 'AM';
					$close_hour_12 = $db_close_hour % 12;
					if ($close_hour_12 == 0) $close_hour_12 = 12;
				}
				
				$isAllDay = $db_open_hour == 0 && $db_open_minute == 0 && $close_hour_12 == 11 && $db_close_minute == 59 && $close_ampm == 'PM';
				$isNone   = $db_open_hour == 0 && $db_open_minute == 0 && $db_close_hour == 0 && $db_close_minute == 0;
			?>
			<div id="day-<?= $id ?>" class="flex flex-col p-4 gap-2"
				data-all-day="<?= $isAllDay ? '1' : '0' ?>"
				data-none="<?= $isNone ? '1' : '0' ?>">
				<div class="flex justify-between items-center mb-2">
					<span class="font-medium"><?= $day ?></span>
                
					<div class="flex gap-2">
						<label class="flex items-center gap-1">
							<input type="checkbox" class="allDay w-8 h-8" <?= $isAllDay ? 'checked' : '' ?>>
							<span>All day</span>
						</label>
						<label class="flex items-center gap-1">
							<input type="checkbox" class="noneDay w-8 h-8" <?= $isNone ? 'checked' : '' ?>>
							<span>None</span>
						</label>
					</div>
				</div>

				<div>
					<span class="flex items-center gap-1">
						<p class="text-gray-700 pr-3 w-10">Start</p>
						<select name="open_hour[<?= $id ?>]" class="bg-white border border-gray-200 rounded-xl px-4 py-3 open_hour">
							<?php for ($hr = 1; $hr <= 12; $hr++): ?>
								<option value="<?= $hr ?>" <?= ($hr == $open_hour_12) ? 'selected' : '' ?>>
									<?= $hr ?>
								</option>
							<?php endfor; ?>
						</select>
						<select name="open_minute[<?= $id ?>]" class="bg-white border border-gray-200 rounded-xl px-4 py-3 open_minute">
							<?php for($min = 0; $min < 60; $min++): ?>
								<option value="<?= sprintf('%02d', $min) ?>" <?= ($min == $db_open_minute) ? 'selected' : '' ?>>
									<?= sprintf('%02d', $min) ?>
								</option>
							<?php endfor; ?>
						</select>
						<select name="open_ampm[<?= $id ?>]" class="bg-white border border-gray-200 rounded-xl px-4 py-3 open_ampm">
							<option value="AM" <?= ($open_ampm == 'AM') ? 'selected' : '' ?>>AM</option>
							<option value="PM" <?= ($open_ampm == 'PM') ? 'selected' : '' ?>>PM</option>
						</select>
					</span>
				</div>
				<div>
					<span class="flex items-center gap-1">
						<p class="text-gray-700 pr-3 w-10">Stop</p>
						<select name="close_hour[<?= $id ?>]" class="bg-white border border-gray-200 rounded-xl px-4 py-3 mb-2 close_hour">
							<?php for ($hr = 1; $hr <= 12; $hr++): ?>
								<option value="<?= $hr ?>" <?= ($hr == $close_hour_12) ? 'selected' : '' ?>>
									<?= $hr ?>
								</option>
							<?php endfor; ?>
						</select>
						<select name="close_minute[<?= $id ?>]" class="bg-white border border-gray-200 rounded-xl px-4 py-3 mb-2 close_minute">
							<?php for($min = 0; $min < 60; $min++): ?>
								<option value="<?= sprintf('%02d', $min) ?>" <?= ($min == $db_close_minute) ? 'selected' : '' ?>>
									<?= sprintf('%02d', $min) ?>
								</option>
							<?php endfor; ?>
						</select>
						<select name="close_ampm[<?= $id ?>]" class="bg-white border border-gray-200 rounded-xl px-4 py-3 mb-2 close_ampm">
							<option value="AM" <?= ($close_ampm == 'AM') ? 'selected' : '' ?>>AM</option>
							<option value="PM" <?= ($close_ampm == 'PM') ? 'selected' : '' ?>>PM</option>
						</select>
					</span>
				</div>
			</div>
			<?php endforeach; ?>
			<div class="flex justify-between items-center mt-2 p-4">
				<a href="/schedule.php" 
					class="px-4 py-3 bg-yellow-400 w-20 rounded-xl
							hover:bg-yellow-500 active:scale-95
							transition flex items-center justify-center">
					Cancel
				</a>
											
				<input class="px-4 py-3 bg-green-400 w-20 rounded-xl
								hover:bg-green-500 active:scale-95
								transition flex items-center justify-center"
						type="submit"
						value="Save">
			</div>
		</div>
		</form>
	</div>
    
    <!-- Debug -->
    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
        <pre class="mt-4 bg-gray-100 p-2 text-xs"><?php print_r($_POST); ?></pre>
    <?php endif; ?>

	</div>
	<div class="text-center text-gray-400 text-sm mt-8 mb-8">
		v1.0 - © 2026 Signal-Tech 
	</div>

<script>
    const toggleBtn = document.getElementById('toggle-info');
    const infoBox = document.getElementById('info-box');

    toggleBtn.addEventListener('click', (e) => {
        e.preventDefault(); // prevent default anchor navigation
        infoBox.classList.toggle('hidden');
    });
</script>

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

    // create hidden inputs to mirror selects
    function createHidden(name, select) {
        const input = document.createElement("input");
        input.type = "hidden";
        input.name = name;
        input.value = select.value;
        container.appendChild(input);
        return input;
    }

    const hiddenOpenHour = createHidden(openHour.name, openHour);
    const hiddenOpenMinute = createHidden(openMinute.name, openMinute);
    const hiddenOpenAMPM = createHidden(openAMPM.name, openAMPM);
    const hiddenCloseHour = createHidden(closeHour.name, closeHour);
    const hiddenCloseMinute = createHidden(closeMinute.name, closeMinute);
    const hiddenCloseAMPM = createHidden(closeAMPM.name, closeAMPM);

    function updateHidden() {
        hiddenOpenHour.value = openHour.value;
        hiddenOpenMinute.value = openMinute.value;
        hiddenOpenAMPM.value = openAMPM.value;
        hiddenCloseHour.value = closeHour.value;
        hiddenCloseMinute.value = closeMinute.value;
        hiddenCloseAMPM.value = closeAMPM.value;
    }

    function disableAll(state) {
        openHour.disabled = state;
        openMinute.disabled = state;
        openAMPM.disabled = state;
        closeHour.disabled = state;
        closeMinute.disabled = state;
        closeAMPM.disabled = state;

        if(state) {
            openHour.classList.add("bg-gray-100");
            openMinute.classList.add("bg-gray-100");
            openAMPM.classList.add("bg-gray-100");
            closeHour.classList.add("bg-gray-100");
            closeMinute.classList.add("bg-gray-100");
            closeAMPM.classList.add("bg-gray-100");
        } else {
            openHour.classList.remove("bg-gray-100");
            openMinute.classList.remove("bg-gray-100");
            openAMPM.classList.remove("bg-gray-100");
            closeHour.classList.remove("bg-gray-100");
            closeMinute.classList.remove("bg-gray-100");
            closeAMPM.classList.remove("bg-gray-100");
        }

        updateHidden();
    }
    
    // Check PHP-passed flags on load
    const isAllDay = container.dataset.allDay === '1';
    const isNone   = container.dataset.none === '1';

    if(isAllDay || isNone) {
        disableAll(true);
    }

    function toMinutes(hour, minute, ampm) {
        hour = parseInt(hour);
        minute = parseInt(minute);

        if (ampm === "AM") {
            if (hour === 12) hour = 0;
        } else { // PM
            if (hour !== 12) hour += 12;
        }

        return hour * 60 + minute;
    }

    function validateTime() {
        const open = toMinutes(openHour.value, openMinute.value, openAMPM.value);
        const close = toMinutes(closeHour.value, closeMinute.value, closeAMPM.value);

        if (close < open) {
            closeHour.classList.add("border-red-500");
            closeMinute.classList.add("border-red-500");
            closeAMPM.classList.add("border-red-500");
        } else {
            closeHour.classList.remove("border-red-500");
            closeMinute.classList.remove("border-red-500");
            closeAMPM.classList.remove("border-red-500");
        }

        updateHidden();
    }

    // attach listeners
    [openHour, openMinute, openAMPM, closeHour, closeMinute, closeAMPM]
        .forEach(el => el.addEventListener("change", validateTime));

    // run once on load
    validateTime();

    allDay.addEventListener("change", function () {
        if (this.checked) {
            noneDay.checked = false;
            openHour.value = "12"; openMinute.value = "00"; openAMPM.value = "AM";
            closeHour.value = "11"; closeMinute.value = "59"; closeAMPM.value = "PM";
            disableAll(true);
        } else {
            disableAll(false);
        }
        validateTime();
    });

    noneDay.addEventListener("change", function () {
        if (this.checked) {
            allDay.checked = false;
            openHour.value = "12"; openMinute.value = "00"; openAMPM.value = "AM";
            closeHour.value = "12"; closeMinute.value = "00"; closeAMPM.value = "AM";
            disableAll(true);
        } else {
            disableAll(false);
        }
        validateTime();
    });

    // form submit validation
    document.querySelector("form").addEventListener("submit", function (e) {
        let valid = true;
        document.querySelectorAll("[id^='day-']").forEach(container => {
            const openHour = container.querySelector(".open_hour");
            const openMinute = container.querySelector(".open_minute");
            const openAMPM = container.querySelector(".open_ampm");
            const closeHour = container.querySelector(".close_hour");
            const closeMinute = container.querySelector(".close_minute");
            const closeAMPM = container.querySelector(".close_ampm");

            const open = toMinutes(openHour.value, openMinute.value, openAMPM.value);
            const close = toMinutes(closeHour.value, closeMinute.value, closeAMPM.value);

            if (close < open) valid = false;
        });

        if (!valid) e.preventDefault();
    });
});
</script>

</body>
</html>
