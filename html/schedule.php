<?php
// php try block so a database error does not crash the page
try
{
	// create database object using sqlite driver and file path
    $db = new PDO('sqlite:/home/user/project/database/lighting.db');
    // throw error on database failure
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

	// statement object is a container holding result of query
    $stmt = $db->query("SELECT * FROM time ORDER BY weekday_id ASC");
    // extract each row as an array of values
    $rows1 = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // statement object is a container holding result of query
    $stmt = $db->query("SELECT * FROM events ORDER BY date ASC");
    // extract each row as an array of values
    $rows2 = $stmt->fetchAll(PDO::FETCH_ASSOC);

	// statement object is a container holding result of query
    $stmt = $db->query("SELECT name FROM scenes ORDER BY scene_id ASC");
    // extract each row as an array of values
    $rows3 = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
// catch block to handle error
catch (PDOException $e)
{
	// print the error on the webpage
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
	
	<div class="text-center py-6">
		<a href="/home.html" class="inline-block">
			<img src="/assets/logo.svg" 
				alt="Logo"
				class="mx-auto w-48">
		</a>
	</div>

	<div class="max-w-md mx-auto p-1">
		<div class="flex justify-between items-center mb-6">
			<h1 class="text-3xl font-semibold p-1">
				Lighting Schedule
			</h1>
		
			<a href="/schedule.php" 
				class="px-4 py-3 bg-blue-400 rounded-xl
						hover:bg-blue-500 active:scale-95
						transition flex items-center
						justify-center">

			<img src="/assets/refresh.svg"
				alt="Refresh"
				class="w-12 h-6">
			</a>
		</div>
    </div>


	<div class="max-w-md mx-auto p-1">

		<!-- big container for all of the weekdays-->
		<div class="bg-gray-50 rounded-lg divide-y divide-gray-200">
			<?php foreach ($rows1 as $row):

				$day = $weekday_names[$row['weekday_id']];
				if($row['open_hour'] == $row['close_hour'] and $row['open_minute'] == $row['close_minute'])
				{
					$hours = "None";
				}
				elseif($row['open_hour'] == 0 and $row['open_minute'] == 0 and $row['close_hour'] == 24 and $row['close_minute'] == 0)
				{
					$hours = "All day";
				}
				else
				{
					if($row['open_hour'] > 12) {
						$open = sprintf("%2d:%02dp", $row['open_hour'] - 12, $row['open_minute']);
					} elseif($row['open_hour'] == 12) {
						$open = sprintf("%2d:%02dp", $row['open_hour'], $row['open_minute']);
					} else {
						$open = sprintf("%2d:%02da", $row['open_hour'], $row['open_minute']);
					}
				
					if($row['close_hour'] > 12) {
						$close = sprintf("%2d:%02dp", $row['close_hour'] - 12, $row['close_minute']);
					} elseif($row['close_hour'] == 12) {
						$close = sprintf("%2d:%02dp", $row['close_hour'], $row['close_minute']);
					} else {
						$close = sprintf("%2d:%02da", $row['close_hour'], $row['close_minute']);
					}
					
					$hours = $open . " – " . $close;
				}
			?>

			<div class="flex justify-between items-center p-4">
				<span class="font-medium">
					<?php echo $day; ?>
				</span>

				<span class="text-gray-700">
					<?php echo $hours; ?>
				</span>
			</div>

			<?php endforeach; ?>
		</div>

		<div class="flex flex-col mt-1">
			<a href="/edit-lighting-schedule.php" 
			   class="w-full py-5 text-xl font-medium mb-6
					  bg-yellow-400 text-black rounded-xl
					  hover:bg-yellow-500 active:scale-95
					  transition block text-center">
				Edit Lighting Schedule
			</a>
			
		</div>
	</div>
	
	<div class="max-w-md mx-auto p-1">
		<div class="flex justify-between items-center mb-6">
			<h1 class="text-3xl font-semibold p-1">
				Event Schedule
			</h1>
		
			<a href="/schedule.php" 
				class="px-4 py-3 bg-blue-400 rounded-xl
						hover:bg-blue-500 active:scale-95
						transition flex items-center
						justify-center">

			<img src="/assets/refresh.svg"
				alt="Refresh"
				class="w-12 h-6">
			</a>
		</div>
    </div>
	
	<div class="max-w-md mx-auto p-1">
	
		<!-- big container for all of the events 2026-03-17-->
		<div class="bg-gray-50 rounded-lg divide-y divide-gray-200">
			<?php foreach ($rows2 as $row):
				$month = $month_names[substr($row['date'], 5, 2)];
				$day = substr($row['date'], 8, 2);
				$year = substr($row['date'], 0, 4);
				
			?>

			<div class="p-4 space-y-2">

				<!-- First row -->
				<div class="flex justify-between items-center">
					<span class="font-medium">
						<?php echo $month . " " . $day . ", " . $year; ?>
					</span>

					<span class="text-gray-700 text-right">
						<?php
						$index = (int) $row['scene'] - 1;
						echo "Scene " . $row['scene'] . ": " . $rows3[$index]['name'];
						?>
					</span>
				</div>

				<!-- Second row -->
				<div>
					<span class="text-gray-700">
						<?php echo "Note: " . $row['note']; ?>
					</span>
				</div>

			</div>
			<?php endforeach; ?>
		</div>

		<div class="flex flex-col mt-1">
			<a href="/edit-event-schedule.php" 
			   class="w-full py-5 text-xl font-medium 
					  bg-yellow-400 text-black rounded-xl
					  hover:bg-yellow-500 active:scale-95
					  transition block text-center">
				Edit Event Schedule
			</a>
		</div>
		
	</div>
	
	<div class="text-center text-gray-400 text-sm mt-8 mb-8">
		v1.0 - © 2026 Signal-Tech 
	</div>

</body>
</html>
