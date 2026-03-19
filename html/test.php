<?php
// connect to lighting.db
$db = new PDO('sqlite:/home/user/project/database/lighting.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 1. Prepare the statement
$stmt = $db->prepare("SELECT * FROM time");

// 2. RUN the statement
$stmt->execute();
$nested_array = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h1>Times</h1>";

echo "<pre>";
print_r($nested_array);
echo "</pre>";

echo "<pre>";
print_r($nested_array[3][3]);
echo "</pre>";
