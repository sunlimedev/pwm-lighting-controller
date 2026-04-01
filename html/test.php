<?php
// connect to lighting.db
$db = new PDO('sqlite:/home/user/project/database/lighting.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 1. Prepare the statement
$stmt = $db->prepare("SELECT scene_id FROM scenes WHERE is_default = 1");

// 2. RUN the statement
$stmt->execute();
$nested_array = $stmt->fetch(PDO::FETCH_COLUMN);

echo "<h1>Times</h1>";

echo "<pre>";
print_r($nested_array);
echo "</pre>";

echo "<pre>";
print_r($nested_array[3][3]);
echo "</pre>";

$year = 2004;
$month = 1;
$day = 14;

// make date ISO8601 string YYYY-MM-DD
$date_string = sprintf("%0002d-%02d-%02d", $year, $month, $day);

echo "<pre>";
print_r($date_string);
echo "</pre>";
