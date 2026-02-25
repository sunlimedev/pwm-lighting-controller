<?php

try {
    $db = new PDO('sqlite:/database/lighting.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $db->query("SELECT * FROM time ORDER BY weekday ASC");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
    exit;
}

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
    <title>Business Hours</title>
    <link rel="stylesheet" href="/html/css/tailwind.min.css">
</head>
<body class="bg-gray-100 min-h-screen p-6">

<div class="max-w-md mx-auto bg-white rounded-xl shadow p-6">
    <h1 class="text-2xl font-semibold mb-6 text-center">
        Business Hours
    </h1>

    <div class="space-y-4">

        <?php foreach ($rows as $row):

            $day = $weekday_names[$row['weekday']];

            $open = sprintf("%02d:%02d",
                    $row['open_hour'],
                    $row['open_minute']
            );

            $close = sprintf("%02d:%02d",
                    $row['close_hour'],
                    $row['close_minute']
            );
            ?>

            <div class="flex justify-between items-center bg-gray-50 p-4 rounded-lg">
            <span class="font-medium">
                <?php echo $day; ?>
            </span>

                <span class="text-gray-700">
                <?php echo $open . " – " . $close; ?>
            </span>
            </div>

        <?php endforeach; ?>

    </div>
</div>

</body>
</html>
