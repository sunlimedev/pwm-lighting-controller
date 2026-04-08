<?php 
require_once("/var/www/html/includes/user-check.php");
require_once("/var/www/html/includes/session-check.php");

try
{
	// database connect
    $db = new PDO('sqlite:/home/user/project/database/lighting.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

	// get clock information
    $stmt = $db->query("SELECT * FROM clock");
    $clock = $stmt->fetch(PDO::FETCH_ASSOC);

    // get all event dates
    $stmt = $db->query("SELECT date FROM events");
    $event_dates = $stmt->fetchAll(PDO::FETCH_COLUMN);

	// get current year for copyright footer
    $stmt = $db->query("SELECT year FROM clock");
	$copyright_year = $stmt->fetch(PDO::FETCH_COLUMN);
}
catch (PDOException $e)
{
    echo "Database error: " . $e->getMessage();
    exit;
}

$num_events = count($event_dates);
$date_string = sprintf("%04d-%02d-%02d", $clock['year'], $clock['month'], $clock['day']);
$minute = sprintf("%02d", $clock['minute']);

// convert to 12hr time
if($clock['hour'] == 0)
{
	$ampm = "AM";
	$hour12 = 12;
}
elseif($clock['hour'] < 12)
{
	$ampm = "AM";
	$hour12 = $clock['hour'];
}
elseif($clock['hour'] == 12)
{
	$ampm = "PM";
	$hour12 = $clock['hour'];
}
else
{
	$ampm = "PM";
	$hour12 = $clock['hour'] - 12;
}

$month_names = [
		1 => "Jan",
		2 => "Feb",
		3 => "Mar",
		4 => "Apr",
		5 => "May",
		6 => "Jun",
		7 => "Jul",
		8 => "Aug",
		9 => "Sep",
		10 => "Oct",
		11 => "Nov",
		12 => "Dec"
];
?>

<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Home</title>
	<link rel="stylesheet" href="/css/tailwind.min.css">
</head>

<body class="bg-gray-100 min-h-screen">
	<!-- logo and navigation buttons -->
	<div class="text-center py-6">
		<img src="/assets/logo.svg" 
			alt="Logo"
			class="mx-auto w-48">
	</div>

	<!-- page header and tootip/action buttons -->
	<div class="max-w-md mx-auto p-1">
		<div class="flex justify-between items-center mb-3">
			<h1 class="text-3xl font-semibold p-1">
				Home
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
            <div id="info-box" class="absolute right-0 mt-2 w-64 bg-white p-4 rounded-lg shadow-lg hidden z-50">
                <p class="text-gray-800">
					Welcome to the Home page. If this is your first time here, or you haven't modified your lighting settings in a while, consider visiting the Setup Guide for more information.
                </p>
            </div>
            </div>
		</div>
    </div>
    
    <!-- container for all page buttons -->
	<div class="max-w-md mx-auto p-1">
        <!-- show if event is running -->
		<?= in_array($date_string, $event_dates) ? '<div class="text-red-700 text-left text-xl font-bold p-1 mb-2"> Your Default lighting is currently overridden by a special event. </div>' : '' ?>
        <div class="flex flex-col">
            <a href="/scenes.php" 
               class="w-full py-5 text-xl font-medium
                      bg-yellow-400 text-black rounded-xl mb-1
                      hover:bg-yellow-500 active:scale-95
                      transition block text-center">
                Scenes
            </a>
            <a href="/connections.php" 
               class="w-full py-5 text-xl font-medium
                      bg-yellow-400 text-black rounded-xl mb-1
                      hover:bg-yellow-500 active:scale-95
                      transition block text-center">
                Connections
            </a>
            <a href="/schedule.php" 
               class="w-full py-5 text-xl font-medium
                      bg-yellow-400 text-black rounded-xl mb-1
                      hover:bg-yellow-500 active:scale-95
                      transition block text-center">
                Schedule
            </a>
            <a href="/setup-guide.php" 
               class="w-full py-5 text-xl font-medium
                      bg-yellow-400 text-black rounded-xl mb-1
                      hover:bg-yellow-500 active:scale-95
                      transition block text-center">
                Setup Guide
            </a>
            <a href="/settings.php" 
               class="w-full py-5 text-xl font-medium
                      bg-yellow-400 text-black rounded-xl
                      hover:bg-yellow-500 active:scale-95
                      transition block text-center">
                Settings
            </a>
        </div>
        <!-- add system time -->
		<div class="text-black text-center text-xl font-bold pl-1 pr-1 mt-4">
			<?= "System Time: " . $month_names[(int)$clock['month']] . " " . $clock['day'] . ", " . $clock['year'] . " " . $hour12 . ":" . $minute . " " . $ampm; ?>
		</div>
    </div>

	<!-- copyright footer -->
	<div class="text-center text-gray-400 text-sm mt-6 mb-8">
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

</body>

</html>
