<?php
require_once("/var/www/html/includes/user-check.php");
require_once("/var/www/html/includes/session-check.php");

try
{
	// connect to db
    $db = new PDO('sqlite:/home/user/project/database/lighting.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

	// get all values from row of clock table
    $stmt = $db->query("SELECT * FROM clock");
    $clock = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $db->query("SELECT year FROM clock");
	$copyright_year = $stmt->fetch(PDO::FETCH_COLUMN);
}
catch (PDOException $e)
{
	// print the error on the webpage
    echo "Database error: " . $e->getMessage();
    exit;
}

$date_string = sprintf("%04d-%02d-%02d", $clock['year'], $clock['month'], $clock['day']);
$minute = sprintf("%02d", $clock['minute']);

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
	<title>Settings</title>
	<link rel="stylesheet" href="/css/tailwind.min.css">
</head>

<body class="bg-gray-100 min-h-screen">

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

	<div class="max-w-md mx-auto p-1">
		<div class="flex justify-between items-center mb-3">
			<h1 class="text-3xl font-semibold p-1">
				Settings
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
            <div id="info-box" class="absolute right-0 mt-2 w-64 bg-white p-4 rounded-lg shadow-lg hidden z-50">
                <p class="text-gray-800">
					Change the date and/or time of your controller or reset certain information to the factory settings.
                </p>
                </div>
            </div>
		</div>
    </div>

    <div class="max-w-md mx-auto p-1">
        
        <div class="flex flex-col">

            <a href="/edit-date-time.php" 
               class="w-full py-5 text-xl font-medium
                      bg-yellow-400 text-black rounded-xl mb-1
                      hover:bg-yellow-500 active:scale-95
                      transition block text-center">
                Edit Date & Time
            </a>
            
            <a href="/reset.php" 
               class="w-full py-5 text-xl font-medium
                      bg-red-400 text-black rounded-xl
                      hover:bg-red-500 active:scale-95
                      transition block text-center">
                Reset Device
            </a>
            
        </div>
        
        <!-- add system time -->
		<div class="text-black text-center text-xl font-bold pl-1 pr-1 mt-4">
			<?= "System Time: " . $month_names[(int)$clock['month']] . " " . $clock['day'] . ", " . $clock['year'] . " " . $hour12 . ":" . $minute . " " . $ampm; ?>
		</div>
        
    </div>
    
	<div class="text-center text-gray-400 text-sm mt-6 mb-8">
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

</body>

</html>
