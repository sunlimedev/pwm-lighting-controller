<?php
require_once("/var/www/html/includes/user-check.php");
require_once("/var/www/html/includes/session-check.php");

// check if the key exists in the URL
if (isset($_GET['notify']))
{
    $notify = $_GET['notify'];

	// let user know that date is already occupied by event
	$message = "Event already exists for " . $notify . ". Select a different day or edit/remove the existing event.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    try
	{
		// database connect
		$db = new PDO('sqlite:/home/user/project/database/lighting.db');
		$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}
	catch (PDOException $e)
	{
		echo "Database error: " . $e->getMessage();
		exit;
	}
    
    $scene = filter_input(INPUT_POST, 'scene', FILTER_VALIDATE_INT);
    $year = filter_input(INPUT_POST, 'year', FILTER_VALIDATE_INT);
    $month = filter_input(INPUT_POST, 'month', FILTER_VALIDATE_INT);
    $day = filter_input(INPUT_POST, 'day', FILTER_VALIDATE_INT);
    $note = trim($_POST['note']);
    
    $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);

	if ($day > $days_in_month)
	{
		die("Invalid date");
	}
    
    // make date ISO8601 string YYYY-MM-DD
    $date_string = sprintf("%04d-%02d-%02d", $year, $month, $day);
    
    // get all event dates
	$stmt = $db->query("SELECT date FROM events");
	$event_dates = $stmt->fetchAll(PDO::FETCH_COLUMN);
	
	// do not allow event to be added if one exists on that day already
	if(in_array($date_string, $event_dates))
	{
		$date_string = sprintf("%02d-%02d-%04d", $month, $day, $year);
		header("Location: /add-event.php?notify=" . $date_string);
        exit;
	}

    if ($scene !== false)
    {
        try
        {
            $stmt = $db->prepare("
                INSERT INTO events (
					scene,
                    date,
                    note
				)
				VALUES
				(
					:scene,
					:date,
					:note
				)"
			);

            $stmt->execute([
                ':scene' => $scene,
                ':date' => $date_string,
                ':note' => $note
            ]);

            header("Location: /schedule.php");
            exit;
        }
        catch (PDOException $e)
        {
            echo "Database error: " . $e->getMessage();
            exit;
        }
    }
}

try
{
	// database connect
	$db = new PDO('sqlite:/home/user/project/database/lighting.db');
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

	// get all scenes
	$stmt = $db->query("SELECT * FROM scenes ORDER BY scene_id ASC");
	$scenes = $stmt->fetchAll(PDO::FETCH_ASSOC);

	// get current year for copyright footer
	$stmt = $db->query("SELECT year FROM clock");
	$copyright_year = $stmt->fetch(PDO::FETCH_COLUMN);
}
catch (PDOException $e)
{
	echo "Database error: " . $e->getMessage();
	exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Add Event</title>
	<link rel="stylesheet" href="/css/tailwind.min.css">
</head>

<body class="bg-gray-100 min-h-screen">
	<!-- logo and navigation buttons -->
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
	
	<!-- page header and tootip/action buttons -->
	<div class="max-w-md mx-auto p-1">
		<div class="flex justify-between items-center mb-2">
			<h1 class="text-3xl font-semibold p-1">
				Add Event
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
					<p class="text-gray-800">
						Add an event to your schedule to override your Default lighting settings.
					</p>
				</div>
			</div>
		</div>
    </div>

    <!-- form container for all event settings -->
	<div class="max-w-md mx-auto p-1">
		<!-- duplicate date alert -->
		<?= $message != 0 ? '<div class="text-red-700 text-left text-xl font-bold p-1 mb-2"> ' . $message . ' </div>' : '' ?>
		<div class="bg-gray-50 rounded-lg divide-y divide-gray-200">
			<div class="p-4">
				<div>
					<form method="POST">
					<!-- scene dropdown -->
					<div class="font-medium">
						<label for="scene">Linked Scene</label><br>
						<select class="w-full bg-white border border-gray-200 rounded-xl px-4 py-3 mb-2" type="text" id="scene" name="scene"><br>
							<optgroup label="User Scenes">
								<?php foreach ($scenes as $row): ?>
									<option value="<?= $row['scene_id']; ?>">
										<?= $row['name']; ?>
									</option>
								<?php endforeach; ?>
							</optgroup>
						</select>
					</div>
					<!-- note field -->
					<div class="font-medium">
						<label for="note">Note</label><br>
						<input class="w-full border border-gray-200 rounded-xl px-4 py-3 mb-2" type="text" id="note" name="note" maxlength="200">
					</div>
					<div class="font-medium">
						<span class="flex items-center gap-1">
							<label for="month" class="w-full">Month</label><br>
							<label for="day" class="w-full">Day</label><br>
							<label for="year" class="w-full">Year</label><br>
						<span>
					</div>
					<!-- date dropdowns -->
					<div class="font-medium">
						<span class="flex items-center gap-1">
							<select name="month" class="bg-white border border-gray-200 rounded-xl px-4 py-3 w-full">
								<?php for($m = 1; $m <= 12; $m++): ?>
									<option value="<?= $m ?>">
										<?= $m ?>
									</option>
								<?php endfor; ?>
							</select>
							<select name="day" class="bg-white border border-gray-200 rounded-xl px-4 py-3 w-full">
								<?php for($d = 1; $d <= 31; $d++): ?>
									<option value="<?= $d ?>">
										<?= $d ?>
									</option>
								<?php endfor; ?>
							</select>
							<select name="year" class="bg-white border border-gray-200 rounded-xl px-4 py-3 w-full">
								<?php for ($y = 2026; $y <= 2100; $y++): ?>
									<option value="<?= $y ?>">
										<?= $y ?>
									</option>
								<?php endfor; ?>
							</select>
						</span>
					</div>
					<!-- save and cancel buttons -->
					<div class="flex justify-between items-center mt-4">
							<a href="/events.php" 
								class="px-4 py-3 bg-yellow-400 w-20 rounded-xl
								hover:bg-yellow-500 active:scale-95
								transition flex items-center justify-center">
								Cancel
							</a>			
							<input class="px-4 py-3 bg-green-400 w-20 rounded-xl
								hover:bg-green-500 active:scale-95
								transition flex items-center justify-center" type="submit" value="Add">
					</div>
					</form>
				</div>
			</div>
		</div>
	</div>

	<!-- copyright footer -->
	<div class="text-center text-gray-400 text-sm mt-8 mb-8">
		v1.0 - © <?= $copyright_year ?> Signal-Tech 
	</div>

<script>
	// tooltip box
    const toggleBtn = document.getElementById('toggle-info');
    const infoBox = document.getElementById('info-box');
	
	// stop click through tooltip box
    toggleBtn.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        infoBox.classList.toggle('hidden');
    });

    // close tooltip when clicking elsewhere
    document.addEventListener('click', (e) => {
        if (!infoBox.contains(e.target) && !toggleBtn.contains(e.target)) {
            infoBox.classList.add('hidden');
        }
    });
</script>

<script>
	// correct days per month logic
	const monthSelect = document.querySelector("select[name='month']");
	const daySelect   = document.querySelector("select[name='day']");
	const yearSelect  = document.querySelector("select[name='year']");

	function isLeapYear(year) {
		return (year % 4 === 0 && year % 100 !== 0) || (year % 400 === 0);
	}

	function getDaysInMonth(month, year) {
		if (month === 2) {
			return isLeapYear(year) ? 29 : 28;
		}

		if ([4, 6, 9, 11].includes(month)) {
			return 30;
		}
		return 31;
	}

	function updateDays() {
		const month = parseInt(monthSelect.value);
		const year  = parseInt(yearSelect.value);

		const daysInMonth = getDaysInMonth(month, year);

		const currentDay = parseInt(daySelect.value);

		// clear existing options
		daySelect.innerHTML = "";

		for (let d = 1; d <= daysInMonth; d++) {
			const option = document.createElement("option");
			option.value = d;
			option.textContent = d;

			if (d === currentDay) {
				option.selected = true;
			}

			daySelect.appendChild(option);
		}
	}

	// update when month or year changes
	monthSelect.addEventListener("change", updateDays);
	yearSelect.addEventListener("change", updateDays);

	// run once on page load
	updateDays();
</script>

</body>

</html>
