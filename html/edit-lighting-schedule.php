<?php
// try database connection
try
{
	// connect to lighting.db
    $db = new PDO('sqlite:/home/user/project/database/lighting.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // get all hours for week
    $stmt = $db->prepare("SELECT * FROM time");
    $days = $stmt->fetch(PDO::FETCH_ASSOC);
}
// catch block to handle error
catch (PDOException $e)
{
	// print the error on the webpage
    echo "Database error: " . $e->getMessage();
    exit;
}

// data handling for form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
	// gather other scene parameters
	$name = trim($_POST['name']);
    $behavior = $_POST['behavior'];
    $behavior = $behavior_names_to_db[$behavior];
    $brightness = filter_input(INPUT_POST, 'brightness', FILTER_VALIDATE_INT);
    $speed = filter_input(INPUT_POST, 'speed', FILTER_VALIDATE_INT);
    
    // get colors from color selector and make unselected null
    $colors = [];
	for ($i = 0; $i < 10; $i++)
	{
		if (isset($_POST["color$i"]) && $_POST["color$i"] !== "")
		{
			$colors[$i] = (int)$_POST["color$i"];
		}
		else
		{
			$colors[$i] = null;
		}
	}

	// update table if id is valid
    if ($scene_id !== false)
    {
        try
        {
            $db = new PDO('sqlite:/home/user/project/database/lighting.db');
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $db->prepare("
                UPDATE scenes
                SET name = :name,
                    behavior = :behavior,
                    brightness = :brightness,
                    speed = :speed,
                    color0 = :color0,
                    color1 = :color1,
                    color2 = :color2,
                    color3 = :color3,
                    color4 = :color4,
                    color5 = :color5,
                    color6 = :color6,
                    color7 = :color7,
                    color8 = :color8,
                    color9 = :color9
                WHERE scene_id = :id
            ");

            $stmt->execute([
                ':name' => $name,
                ':behavior' => $behavior,
                ':brightness' => $brightness,
                ':speed' => $speed,
                ':id' => $scene_id,
                ':color0' => $colors[0],
                ':color1' => $colors[1],
                ':color2' => $colors[2],
                ':color3' => $colors[3],
                ':color4' => $colors[4],
                ':color5' => $colors[5],
                ':color6' => $colors[6],
                ':color7' => $colors[7],
                ':color8' => $colors[8],
                ':color9' => $colors[9]
            ]);

            header("Location: /scenes.php");
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
	<title>Edit Lighting Schedule</title>
	<link rel="stylesheet" href="/css/tailwind.min.css">
</head>

<body class="bg-gray-100 min-h-screen">

	<!-- signal-tech logo -->
	<div class="text-center py-6">
		<a href="/home.php" class="inline-block">
			<img src="/assets/logo.svg" 
				alt="Logo"
				class="mx-auto w-48">
		</a>
	</div>
	
	<!-- page header -->
	<div class="max-w-md mx-auto p-1">
		<div class="flex justify-between items-center mb-2">
			<h1 class="text-3xl font-semibold p-1">
				Edit Lighting Schedule
			</h1>

			<!-- Floating popup -->
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
						
					</p>
				</div>
			</div>
		</div>
    </div>
	<div class="max-w-md mx-auto p-1">
		<div class="bg-gray-50 rounded-lg divide-y divide-gray-200">
			<div class="p-4">
				<form method="POST">
					
					<!-- hidden scene_id integer -->
					<input type="hidden" name="scene_id" value="<?= $scene_id ?>">
					
					<!-- name field with read only for default scene -->
					<div class="font-medium">
						<label for="name">Name</label><br>
						<input <?= ($scene_id == 1) ? 'readonly' : '' ?>
							class="w-full <?= ($scene_id == 1) ? 'bg-gray-50' : '' ?> border border-gray-200 rounded-xl px-4 py-3 mb-2"
							type="text"
							id="name"
							name="name"
							value="<?= htmlspecialchars($rows1['name']); ?>"
							maxlength = "30">
					</div>
						
					<!-- behavior select dropdown with prefilled behavior string -->
					<div class="font-medium">
						<label for="behavior">Behavior</label><br>
						<select class="w-full bg-white border border-gray-200 rounded-xl px-4 py-3 mb-2" type="text" id="behavior" name="behavior"><br>
							<optgroup label="Behavior">
								
							</optgroup>
						</select>
					</div>

					<!-- save, delete, and cancel buttons -->
					<div class="flex justify-between items-center mt-4">
						<a href="/schedule.php" 
							class="px-4 py-3 bg-yellow-400 2-20 rounded-xl
								hover:bg-yellow-500 active:scale-95
								transition">
								Cancel
						</a>
						
						<input class="px-4 py-3 bg-green-400 w-20 rounded-xl
								hover:bg-green-500 active:scale-95
								transition" type="submit" value="Save">
					</div>
				</form>
			</div>
		</div>
	</div>
	
	<!-- copyright footer -->
	<div class="text-center text-gray-400 text-sm mt-8 mb-8">
		v1.0 - © 2026 Signal-Tech 
	</div>

<!-- javascript for tooltip button -->
<script>
    const toggleBtn = document.getElementById('toggle-info');
    const infoBox = document.getElementById('info-box');

    toggleBtn.addEventListener('click', (e) => {
        e.preventDefault(); // prevent default anchor navigation
        infoBox.classList.toggle('hidden');
    });
</script>

</body>
</html>
