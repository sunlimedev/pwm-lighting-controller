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
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // format:
    // [
    // 'weekday_id' => 0,
    // 'open_hour' => 9,
    // 'open_minute' => 0,
    // 'close_hour' => 17,
    // 'close_minute' => 0
    // ]

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
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business Hours</title>
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
		<h1 class="text-3xl font-semibold text-center mb-8">
			Business Hours
		</h1>

		<!-- big container for all of the weekdays-->
		<div class="bg-gray-50 rounded-lg divide-y divide-gray-200">
			<?php foreach ($rows as $row):

				$day = $weekday_names[$row['weekday_id']];
				
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
			?>

			<div class="flex justify-between items-center p-4">
				<span class="font-medium">
					<?php echo $day; ?>
				</span>

				<span class="text-gray-700">
					<?php echo $open . " – " . $close; ?>
				</span>
			</div>

			<?php endforeach; ?>
		</div>

		<div class="flex flex-col mt-6">
			<a href="/edit-business-hours.php" 
			   class="w-full py-5 text-xl font-medium
					  bg-yellow-400 text-black rounded-xl
					  hover:bg-yellow-500 active:scale-95
					  transition block text-center">
				Edit Business Hours
			</a>
			
			<div class="text-center text-gray-400 text-sm mt-8">
				v1.0 - © 2026 Signal-Tech 
			</div>
		</div>
	</div>

</body>
</html>
