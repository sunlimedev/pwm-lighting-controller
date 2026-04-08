<?php
require_once("/var/www/html/includes/user-check.php");
require_once("/var/www/html/includes/session-check.php");

try
{
	// database connect
    $db = new PDO('sqlite:/home/user/project/database/lighting.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

	// get weekly schedule
    $stmt = $db->query("SELECT * FROM time ORDER BY weekday_id ASC");
    $week_schedule = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // get all events
    $stmt = $db->query("SELECT * FROM events ORDER BY date ASC");
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

	// get scene ids and names
    $stmt = $db->query("SELECT scene_id, name FROM scenes ORDER BY scene_id ASC");
    $scene_ids_names = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // make scene_id name key value pair array
    $scenes = [];
	foreach ($scene_ids_names as $row)
	{
		$scenes[$row['scene_id']] = $row['name'];
	}

	// get current year for copyright footer
	$stmt = $db->query("SELECT year FROM clock");
	$copyright_year = $stmt->fetch(PDO::FETCH_COLUMN);
}
catch (PDOException $e)
{
    echo "Database error: " . $e->getMessage();
    exit;
}

// array with mapped values to convert python weekday int to day string
$weekday_names = [
        0 => "Monday",
        1 => "Tuesday",
        2 => "Wednesday",
        3 => "Thursday",
        4 => "Friday",
        5 => "Saturday",
        6 => "Sunday"
		];
		
// array with mapped values to convert db month string to text
$month_names = [
		"01" => "Jan",
		"02" => "Feb",
		"03" => "Mar",
		"04" => "Apr",
		"05" => "May",
		"06" => "Jun",
		"07" => "Jul",
		"08" => "Aug",
		"09" => "Sep",
		"10" => "Oct",
		"11" => "Nov",
		"12" => "Dec"
		];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule</title>
    <link rel="stylesheet" href="/css/tailwind.min.css">
</head>

<body class="bg-gray-100 min-h-screen">
	<!-- logo and navigation buttons -->
	<div class="text-center py-6 flex justify-between items-center max-w-md mx-auto pl-7 pr-7">
		<span>
			<a href="/home.php" class="inline-block">
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

	<!-- schedule page header and tootip/action buttons -->
	<div class="max-w-md mx-auto p-1">
		<div class="flex justify-between items-center mb-2">
			<h1 class="text-3xl font-semibold p-1">
				Lighting Schedule
			</h1>
			<div class="relative pr-1">
            <a href="#" id="toggle-info1"
               class="px-4 py-3 bg-purple-400 w-20 rounded-xl
                      hover:bg-purple-500 active:scale-95
                      transition flex items-center justify-center">
                <img src="/assets/help.svg"
                     alt="Help"
                     class="w-12 h-6">
            </a>
            <div id="info-box1"
                 class="absolute right-0 mt-2 w-64 bg-white p-4 rounded-lg shadow-lg hidden z-50">
                <p class="text-gray-700">
                    The Lighting Schedule defines when the light tubes will display your default scene. If the current time is outside of your chosen hours, then the light tubes will turn off.
                </p>
            </div>
        </div>
		</div>
    </div>
    
    <!-- container for schedule and edit button -->
	<div class="max-w-md mx-auto p-1">
		<div class="bg-gray-50 rounded-lg divide-y divide-gray-200 flex flex-col mb-6">
			<?php foreach ($week_schedule as $day):
				if($day['open_hour'] == $day['close_hour'] and $day['open_minute'] == $day['close_minute'])
				{
					$hours = "None";
				}
				elseif($day['open_hour'] == 0 and $day['open_minute'] == 0 and $day['close_hour'] == 24 and $day['close_minute'] == 0)
				{
					$hours = "All day";
				}
				else
				{
					if($day['open_hour'] > 12) {
						$open = sprintf("%2d:%02dp", $day['open_hour'] - 12, $day['open_minute']);
					} elseif($day['open_hour'] == 12) {
						$open = sprintf("%2d:%02dp", $day['open_hour'], $day['open_minute']);
					} elseif($day['open_hour'] == 0) {
						$open = sprintf("%2d:%02da", $day['open_hour'] + 12, $day['open_minute']);
					} else {
						$open = sprintf("%2d:%02da", $day['open_hour'], $day['open_minute']);
					}
					if($day['close_hour'] > 12) {
						if($day['close_hour'] == 24 and $day['close_minute'] == 0)
						{
							$close = "11:59p";
						}
						else
						{
							$close = sprintf("%2d:%02dp", $day['close_hour'] - 12, $day['close_minute']);
						}
					} elseif($day['close_hour'] == 12) {
						$close = sprintf("%2d:%02dp", $day['close_hour'], $day['close_minute']);
					} elseif($day['close_hour'] == 0) {
						$close = sprintf("%2d:%02da", $day['close_hour'] + 12, $day['close_minute']);
					} else {
						$close = sprintf("%2d:%02da", $day['close_hour'], $day['close_minute']);
					}
					$hours = $open . " – " . $close;
				}
				$day_name = $weekday_names[$day['weekday_id']];
			?>
			<div class="flex justify-between items-center p-4">
				<span class="font-medium">
					<?php echo $day_name; ?>
				</span>

				<span class="text-gray-700">
					<?php echo $hours; ?>
				</span>
			</div>
			<?php endforeach; ?>
			<div class="mt-auto p-4">
				<span>
					<a href="edit-lighting-schedule.php" id="toggle-info"
						class="px-4 py-3 bg-yellow-400 rounded-xl
						hover:bg-yellow-500 active:scale-95
						transition flex items-center justify-center">
						<img src="/assets/pencil.svg" alt="Edit" class="w-6 h-6">
						</a>
				</span>
			</div>
		</div>
	</div>
	
	<!-- event page header and tootip/action buttons -->
	<div class="max-w-md mx-auto p-1">
		<div class="flex justify-between items-center mb-2">
			<h1 class="text-3xl font-semibold p-1">
				Event Schedule
			</h1>
			<a href="/add-event.php" 
			class="px-4 py-3 bg-blue-400 w-20 rounded-xl
					hover:bg-blue-500 active:scale-95
					transition flex items-center
					justify-center"> 
				<img src="/assets/plus.svg" 
					alt="Add scene" 
					class="w-12 h-6">
			</a>
        <div class="relative">
            <a href="#" id="toggle-info2"
               class="px-4 py-3 bg-purple-400 w-20 rounded-xl
                      hover:bg-purple-500 active:scale-95
                      transition flex items-center justify-center">
                <img src="/assets/help.svg"
                     alt="Help"
                     class="w-12 h-6">
            </a>
            <div id="info-box2"
                 class="absolute right-0 mt-2 w-64 bg-white p-4 rounded-lg shadow-lg hidden z-50">
                <p class="text-gray-700">
                    The Event Schedule defines which days will have special lighting. If the current day is an event day, then the light tubes will display the associated scene.
                </p>
            </div>
        </div>
		</div>
    </div>
	
	<!-- container for events and buttons -->
	<div class="max-w-md mx-auto p-1">
		<div class="bg-gray-50 rounded-lg divide-y divide-gray-200">
			<?php foreach ($events as $event):
				$month = $month_names[substr($event['date'], 5, 2)];
				$day = substr($event['date'], 8, 2);
				$year = substr($event['date'], 0, 4);
			?>
			<div class="p-4">
				<div class="flex justify-between items-center">
					<span class="font-medium text-left whitespace-nowrap pr-8">
						<?php echo $month . " " . $day . ", " . $year; ?>
					</span>

					<span class="text-right truncate">
						<?php
							$index = (int) $event['scene'];
							echo "Scene: " . $scenes[$index];
						?>
					</span>
				</div>
				<div class="mb-2 break-words">
					<span class="text-gray-700">
						<?php echo "Note: " . $event['note']; ?>
					</span>
				</div>
				<div class="relative">
						<?php
							echo '<a href="edit-event.php?event_id=' . $event['event_id'] . '" id="toggle-info"
									class="px-4 py-3 bg-yellow-400 rounded-xl
									hover:bg-yellow-500 active:scale-95
									transition flex items-center justify-center">
									<img src="/assets/pencil.svg" alt="Edit" class="w-6 h-6">
								</a>';
						?>
				</div>
			</div>
			<?php endforeach; ?>
		</div>
	</div>

	<!-- copyright footer -->
	<div class="text-center text-gray-400 text-sm mt-8 mb-8">
		v1.0 - © <?= $copyright_year ?> Signal-Tech 
	</div>

<script>
	// tooltip box
    const toggleBtn1 = document.getElementById('toggle-info1');
    const infoBox1 = document.getElementById('info-box1');
    const toggleBtn2 = document.getElementById('toggle-info2');
    const infoBox2 = document.getElementById('info-box2');

	// stop click through tooltip box
    toggleBtn1.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        infoBox1.classList.toggle('hidden');
    });
    toggleBtn2.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        infoBox2.classList.toggle('hidden');
    });
    
    // close tooltip when clicking elsewhere
    document.addEventListener('click', (e) => {
        if (!infoBox1.contains(e.target) && !toggleBtn1.contains(e.target)) {
            infoBox1.classList.add('hidden');
        }
        if (!infoBox2.contains(e.target) && !toggleBtn2.contains(e.target)) {
            infoBox2.classList.add('hidden');
        }
    });
</script>

</body>

</html>
