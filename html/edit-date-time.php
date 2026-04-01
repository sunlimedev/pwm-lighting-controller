<?php
require_once("/var/www/html/includes/user-check.php");
require_once("/var/www/html/includes/session-check.php");

// ensure we can connect to the database
try
{
    $db = new PDO('sqlite:/home/user/project/database/lighting.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
    // get current database date and time
    $stmt = $db->prepare("SELECT * FROM clock");
    $stmt->execute();
    // store info in timestamp
    $timestamp = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $db->query("SELECT year FROM clock");
	$copyright_year = $stmt->fetch(PDO::FETCH_COLUMN);
}
catch (PDOException $e)
{
    echo "Database error: " . $e->getMessage();
    exit;
}

if($timestamp['hour'] == 0)
{
	$timestamp['hour'] += 12;
	$timestamp_ampm = "AM";
}
elseif($timestamp['hour'] == 12)
{
	$timestamp_ampm = "PM";
}
elseif($timestamp['hour'] < 12)
{
	$timestamp_ampm = "AM";
}
else //($timestamp['hour'] > 12)
{
	$timestamp['hour'] -= 12;
	$timestamp_ampm = "PM";
}


// data handling for form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    $year = (int)$_POST['year'];
    $month = (int)$_POST['month'];
    $day = (int)$_POST['day'];
    
    $hour = (int)$_POST['hour'];
    $minute = (int)$_POST['minute'];
    $ampm = $_POST['ampm'];

    // convert to 24hr time
    if($ampm == "AM")
    {
		if($hour == 12)
		{
			$hour -= 12;
		}
	}
	else
	{
		if($hour != 12)
		{
			$hour += 12;
		}
	}
	
	// insert new time and raise change flag to signify new user time
	try
	{
		$db->beginTransaction();
		$db->exec("DELETE FROM clock");
	
		$stmt = $db->prepare("
			INSERT INTO clock (
				year,
				month,
				day,
				hour,
				minute,
				change
			)
			VALUES
			(
				:year,
				:month,
				:day,
				:hour,
				:minute,
				:change
			)
		");

		$stmt->execute([
			':year' => $year,
			':month' => $month,
			':day' => $day,
			':hour' => $hour,
			':minute' => $minute,
			':change' => 1
		]);
		
		$db->commit();
		header("Location: /home.php");
		exit;
	}
	catch (PDOException $e)
	{
		// if there is any error stop all changes and revert to safe state
		$db->rollBack();
		echo "Database error: " . $e->getMessage();
		exit;
	}
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Edit Date & Time</title>
	<link rel="stylesheet" href="/css/tailwind.min.css">
</head>

<body class="bg-gray-100 min-h-screen">

	<div class="text-center py-6 flex justify-between items-center max-w-md mx-auto pl-7 pr-7">
		<span>
			<a href="/settings.php" class="inline-block">
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
				Edit Date & Time
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

				<!-- Floating popup -->
				<div id="info-box"
					class="absolute right-0 mt-2 w-64 bg-white p-4 rounded-lg shadow-lg hidden z-50">
					<p class="text-gray-800">
						Change the date and/or time of your controller.
					</p>
				</div>
			</div>
		</div>
    </div>

	<div class="max-w-md mx-auto p-1">
		<!-- big container for all of the scenes-->
		<div class="bg-gray-50 rounded-lg divide-y divide-gray-200">
			<div class="p-4">
				<div>
					<form method="POST">					
					
					<div class="font-medium">
						<span class="flex items-center gap-1">
							<label for="month" class="w-full">Month</label><br>
							<label for="day" class="w-full">Day</label><br>
							<label for="year" class="w-full">Year</label><br>
						<span>
					</div>
					<div class="font-medium">
						<span class="flex items-center gap-1 mb-2">
							<select name="month" class="bg-white border border-gray-200 rounded-xl px-4 py-3 w-full">
								<?php for($m = 1; $m <= 12; $m++): ?>
									<option value="<?= $m ?>" <?= ($timestamp['month'] == $m) ? 'selected' : ''  ?>>
										<?= $m ?>
									</option>
								<?php endfor; ?>
							</select>
							<select name="day" class="bg-white border border-gray-200 rounded-xl px-4 py-3 w-full">
								<?php for($d = 1; $d <= 31; $d++): ?>
									<option value="<?= $d ?>"  <?= ($timestamp['day'] == $d) ? 'selected' : ''  ?>>
										<?= $d ?>
									</option>
								<?php endfor; ?>
							</select>
							<select name="year" class="bg-white border border-gray-200 rounded-xl px-4 py-3 w-full">
								<?php for ($y = 2026; $y <= 2100; $y++): ?>
									<option value="<?= $y ?>"  <?= ($timestamp['year'] == $y) ? 'selected' : ''  ?>>
										<?= $y ?>
									</option>
								<?php endfor; ?>
							</select>
						</span>
					</div>
					
					<div class="font-medium">
						<span class="flex items-center gap-1">
							<label for="hour" class="w-full">Hour</label><br>
							<label for="minute" class="w-full">Minute</label><br>
							<label for="ampm" class="w-full">AM/PM</label><br>
						<span>
					</div>
					<div>
						<span class="flex items-center gap-1">
							<select name="hour" class="bg-white border border-gray-200 rounded-xl px-4 py-3 w-full">
								<?php for ($hr = 1; $hr <= 12; $hr++): ?>
									<option value="<?= $hr ?>" <?= ($timestamp['hour'] == $hr) ? 'selected' : ''  ?>>
										<?= $hr ?>
									</option>
								<?php endfor; ?>
							</select>
							<select name="minute" class="bg-white border border-gray-200 rounded-xl px-4 py-3 w-full">
								<?php for($min = 0; $min < 60; $min++): ?>
									<option value="<?= sprintf('%02d', $min) ?>" <?= ($timestamp['minute'] == $min) ? 'selected' : ''  ?>>
										<?= sprintf('%02d', $min) ?>
									</option>
								<?php endfor; ?>
							</select>
							<select name="ampm" class="bg-white border border-gray-200 rounded-xl px-4 py-3 w-full">
								<option value="AM" <?= ($timestamp_ampm == "AM") ? 'selected' : ''  ?>>AM</option>
								<option value="PM" <?= ($timestamp_ampm == "PM") ? 'selected' : ''  ?>>PM</option>
							</select>
						</span>
					</div>
					
					<div class="flex justify-between items-center mt-4">
							<a href="/settings.php" 
								class="px-4 py-3 bg-yellow-400 w-20 rounded-xl
								hover:bg-yellow-500 active:scale-95
								transition flex items-center justify-center">
								Cancel
							</a>
											
							<input class="px-4 py-3 bg-green-400 w-20 rounded-xl
								hover:bg-green-500 active:scale-95
								transition flex items-center justify-center" type="submit" value="Save">
					</div>
					</form>
				</div>
				
			</div>
		</div>
	</div>

	<div class="text-center text-gray-400 text-sm mt-8 mb-8">
		v1.0 - © <?= $copyright_year ?> Signal-Tech 
	</div>

<script>
    const toggleBtn = document.getElementById('toggle-info');
    const infoBox = document.getElementById('info-box');

    toggleBtn.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation(); // prevent this click from reaching document
        infoBox.classList.toggle('hidden');
    });

    // close when clicking anywhere else
    document.addEventListener('click', (e) => {
        if (!infoBox.contains(e.target) && !toggleBtn.contains(e.target)) {
            infoBox.classList.add('hidden');
        }
    });
</script>

<!-- this js code renders the correct number of days for each month -->
<script>
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
