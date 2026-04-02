<?php
require_once("/var/www/html/includes/user-check.php");
require_once("/var/www/html/includes/session-check.php");

// check if the key exists in the URL
if (isset($_GET['event_id']))
{
    // ensure event_id is a valid integer
    $event_id = filter_var($_GET['event_id'], FILTER_VALIDATE_INT);

    try
    {
		// connect to lighting.db
		$db = new PDO('sqlite:/home/user/project/database/lighting.db');
		$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		// get all valid event_ids
		$stmt = $db->query("SELECT event_id FROM events");
		// store info in event_ids
		$event_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
	}
	catch (PDOException $e)
	{
		echo "Database error: " . $e->getMessage();
		exit;
	}

	// redirect if there is an issue
    if (!in_array($event_id, $event_ids))
    {
		header("Location: /schedule.php");
		exit;
	}
}
// redirect if there is no key
else
{
    header("Location: /schedule.php");
    exit;
}

// get specific event's info
$stmt = $db->prepare("SELECT * FROM events WHERE event_id = :id");
// bind id value
$stmt->execute(['id' => $event_id]);
// store info in array
$event_info = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $db->query("SELECT year FROM clock");
$copyright_year = $stmt->fetch(PDO::FETCH_COLUMN);

// slice string into parts
$event_year = (int)substr($event_info['date'], 0, 4);
$event_month = (int)substr($event_info['date'], 5, 2);
$event_day = (int)substr($event_info['date'], 8, 2);

// get all scene info
$stmt = $db->query("SELECT * FROM scenes ORDER BY scene_id ASC");
// store all scene info in result
$scenes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// data handling for form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    // delete button logic
	if (isset($_POST['delete_scene']))
	{
		try
		{
			$stmt = $db->prepare("
				DELETE FROM events
				WHERE event_id = :id
			");
			$stmt->execute([':id' => $event_id]);

			header("Location: /schedule.php");
			exit;
		}
		catch (PDOException $e)
		{
			$db->rollBack();
			echo "Database error: " . $e->getMessage();
			exit;
		}
	}
    
    $event_id = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);
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

    if ($event_id !== false && $scene !== false)
    {
        try
        {
            $db = new PDO('sqlite:/home/user/project/database/lighting.db');
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $db->prepare("
                UPDATE events
                SET scene = :scene,
                    date = :date,
                    note = :note
                WHERE event_id = :id
            ");

            $stmt->execute([
                ':scene' => $scene,
                ':date' => $date_string,
                ':note' => $note,
                ':id' => $event_id
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
?>

<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Edit Event</title>
	<link rel="stylesheet" href="/css/tailwind.min.css">
</head>

<body class="bg-gray-100 min-h-screen">

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
				Edit Event
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
						Modify or delete an event on your schedule that overrides your Default lighting settings.
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
					<input type="hidden" name="event_id" value="<?= $event_id ?>">
					
					<div class="font-medium">
						<label for="scene">Linked Scene</label><br>
						<select class="w-full bg-white border border-gray-200 rounded-xl px-4 py-3 mb-2" type="text" id="scene" name="scene"><br>
							<optgroup label="User Scenes">
								<?php foreach ($scenes as $row): ?>

									<?php
										$selected = ($event_info['scene'] == $row['scene_id']) ? 'selected' : '';
									?>

									<option value="<?= $row['scene_id']; ?>" <?= $selected; ?>>
										<?= $row['name']; ?>
									</option>

								<?php endforeach; ?>
							</optgroup>
						</select>
					</div>
					
					<div class="font-medium">
						<label for="note">Note</label><br>
						<input class="w-full border border-gray-200 rounded-xl px-4 py-3 mb-2" type="text" id="note" name="note" value="<?php echo htmlspecialchars($event_info['note']);?>" maxlength="200">
					</div>
					
					<div class="font-medium">
						<span class="flex items-center gap-1">
							<label for="month" class="w-full">Month</label><br>
							<label for="day" class="w-full">Day</label><br>
							<label for="year" class="w-full">Year</label><br>
						<span>
					</div>
					
					<div class="font-medium">
						<span class="flex items-center gap-1">
							<select name="month" class="bg-white border border-gray-200 rounded-xl px-4 py-3 w-full">
								<?php for($m = 1; $m <= 12; $m++): ?>
									<option value="<?= $m ?>" <?= ($event_month == $m) ? 'selected' : '' ?>>
										<?= $m ?>
									</option>
								<?php endfor; ?>
							</select>
							<select name="day" class="bg-white border border-gray-200 rounded-xl px-4 py-3 w-full">
								<?php for($d = 1; $d <= 31; $d++): ?>
									<option value="<?= $d ?>" <?= ($event_day == $d) ? 'selected' : '' ?>>
										<?= $d ?>
									</option>
								<?php endfor; ?>
							</select>
							<select name="year" class="bg-white border border-gray-200 rounded-xl px-4 py-3 w-full">
								<?php for ($y = 2026; $y <= 2100; $y++): ?>
									<option value="<?= $y ?>"  <?= ($event_year == $y) ? 'selected' : '' ?>>
										<?= $y ?>
									</option>
								<?php endfor; ?>
							</select>
						</span>
					</div>
					
					<div class="flex justify-between items-center mt-4">
							<a href="/connections.php" 
								class="px-4 py-3 bg-yellow-400 w-20 rounded-xl
								hover:bg-yellow-500 active:scale-95
								transition flex items-center justify-center">
								Cancel
							</a>
							
							<button	type="submit"
									name="delete_scene"
									value="1"
									onclick="return confirm('Are you sure you want to delete this event?');"
									class="px-4 py-3 bg-red-400 w-20 rounded-xl hover:bg-red-500 transition">
								Delete
							</button>
											
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
