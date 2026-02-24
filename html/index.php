<?php
$db = new SQLite3('/home/user/project/database/lighting.db');
$result = $db->query("SELECT * from colors");

echo "<h1>Lighting Data</h1>";

while($row = $result->fetchArray())
{ 
        echo "<pre>";
        print_r($row);
        echo "</pre>";
}
?>
