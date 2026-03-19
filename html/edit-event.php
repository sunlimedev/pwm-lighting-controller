<?php
// check if the key exists in the URL
if (isset($_GET['event_id']))
{
    // ensure event_id is a valid integer
    $event_id = filter_var($_GET['event_id'], FILTER_VALIDATE_INT);

    try
    {
		// connect to lighting.db
		$db = new PDO('sqlite:/home/user/project/database/lighting.db');
		$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		// get all valid event_ids
		$stmt = $db->query("SELECT event_id FROM events");
		// store info in event_ids
		$event_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
	}
	catch (PDOException $e)
	{
		echo "Database error: " . $e->getMessage();
		exit;
	}

	// redirect if there is an issue
    if (!in_array($event_id, $event_ids))
    {
		header("Location: /schedule.php");
		exit;
	}
}
// redirect if there is no key
else
{
    header("Location: /schedule.php");
    exit;
}

// get specific event's info
$stmt = $db->prepare("SELECT * FROM events WHERE event_id = :id");
// bind id value
$stmt->execute(['id' => $event_id]);
// store info in array
$event_info = $stmt->fetch(PDO::FETCH_ASSOC);

//echo "<pre> event_info";
//print_r($event_info);
//echo "</pre>";
    
// get all scene info
$stmt = $db->query("SELECT * FROM scenes ORDER BY scene_id ASC");
// store all scene info in result
$scenes = $stmt->fetchAll(PDO::FETCH_ASSOC);

//echo "<pre> scenes";
//print_r($scenes);
//echo "</pre>";

// data handling for form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    // delete button logic
	if (isset($_POST['delete_scene']))
	{
		try
		{
			$stmt = $db->prepare("
				DELETE FROM events
				WHERE event_id = :id
			");
			$stmt->execute([':id' => $event_id]);

			header("Location: /schedule.php");
			exit;
		}
		catch (PDOException $e)
		{
			$db->rollBack();
			echo "Database error: " . $e->getMessage();
			exit;
		}
	}
    
    $event_id = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);
    $scene = filter_input(INPUT_POST, 'scene', FILTER_VALIDATE_INT);
    $year = filter_input(INPUT_POST, 'year', FILTER_VALIDATE_INT);
    $month = filter_input(INPUT_POST, 'month', FILTER_VALIDATE_INT);
    $day = filter_input(INPUT_POST, 'day', FILTER_VALIDATE_INT);
    $note = trim($_POST['note']);
    
    // make date ISO8601 string YYYY-MM-DD
    $date_string = sprintf("%0002d-%02d-%02d", $year, $month, $day);

    if ($event_id !== false && $scene !== false)
    {
        try
        {
            $db = new PDO('sqlite:/home/user/project/database/lighting.db');
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $db->prepare("
                UPDATE events
                SET scene = :scene,
                    date = :date,
                    note = :note
                WHERE event_id = :id
            ");

            $stmt->execute([
                ':scene' => $scene,
                ':date' => $date,
                ':note' => $note,
                ':id' => $event_id
            ]);

            header("Location: /schedule.php");
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
	<title>Edit Event</title>
	<link rel="stylesheet" href="/css/tailwind.min.css">
</head>

<body class="bg-gray-100 min-h-screen">

	<div class="text-center py-6">
		<a href="/home.php" class="inline-block">
			<img src="/assets/logo.svg" 
				alt="Logo"
				class="mx-auto w-48">
		</a>
	</div>
	
	<div class="max-w-md mx-auto p-1">
		<div class="flex justify-between items-center mb-2">
			<h1 class="text-3xl font-semibold p-1">
				Edit Event
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
				<div id="info-box"
					class="absolute right-0 mt-2 w-64 bg-white p-4 rounded-lg shadow-lg hidden z-50">
					<p class="text-gray-800">
						placeholder
					</p>
				</div>
			</div>
		</div>
    </div>

	<div class="max-w-md mx-auto p-1">
		<!-- big container for all of the scenes-->
		<div class="bg-gray-50 rounded-lg divide-y divide-gray-200">
			<div class="p-4">
				<div>
					<form method="POST">
					<input type="hidden" name="event_id" value="<?= $event_id ?>">
					
					<div class="font-medium">
						<label for="scene">Linked Scene</label><br>
						<select class="w-full bg-white border border-gray-200 rounded-xl px-4 py-3 mb-2" type="text" id="scene" name="scene"><br>
							<optgroup label="User Scenes">
								<?php foreach ($scenes as $row): ?>

									<?php
										$selected = ($event_info['scene'] == $row['scene_id']) ? 'selected' : '';
									?>

									<option value="<?= $row['scene_id']; ?>" <?= $selected; ?>>
										<?= $row['name']; ?>
									</option>

								<?php endforeach; ?>
							</optgroup>
						</select>
					</div>
					
					<div class="font-medium">
						<label for="note">Note</label><br>
						<input class="w-full border border-gray-200 rounded-xl px-4 py-3 mb-2" type="text" id="note" name="note" value="<?php echo htmlspecialchars($rows1['note']);?>" maxlength="200">
					</div>
					
					<div class="font-medium">
						<span class="flex items-center gap-1">
							<label for="year" class="w-full pr-3">Year</label><br>
							<label for="month" class="w-full pr-3">Month</label><br>
							<label for="day" class="w-full">Day</label><br>
						<span>
					</div>
					
					<div class="font-medium">
						<span class="flex items-center gap-1">
							<select name="year" class="bg-white border border-gray-200 rounded-xl px-4 py-3 w-full">
								<?php for ($hr = 1; $hr <= 12; $hr++): ?>
									<option value="<?= $hr ?>">
										<?= $hr ?>
									</option>
								<?php endfor; ?>
							</select>
							<select name="month" class="bg-white border border-gray-200 rounded-xl px-4 py-3 w-full">
								<?php for($min = 0; $min < 60; $min++): ?>
									<option value="<?= sprintf('%02d', $min) ?>">
										<?= sprintf('%02d', $min) ?>
									</option>
								<?php endfor; ?>
							</select>
							<select name="day" class="bg-white border border-gray-200 rounded-xl px-4 py-3 w-full">
								<option value="AM" <?= ($open_ampm == 'AM') ? 'selected' : '' ?>>AM</option>
								<option value="PM" <?= ($open_ampm == 'PM') ? 'selected' : '' ?>>PM</option>
							</select>
						</span>
					</div>
					
					<div class="flex justify-between items-center mt-2">
							<a href="/connections.php" 
								class="px-4 py-3 bg-yellow-400 w-20 rounded-xl
								hover:bg-yellow-500 active:scale-95
								transition flex items-center justify-center">
								Cancel
							</a>
							
							<button	type="submit"
									name="delete_scene"
									value="1"
									onclick="return confirm("Delete this scene?");"
									class="px-4 py-3 bg-red-400 w-20 rounded-xl hover:bg-red-500 transition">
								Delete
							</button>
											
							<input class="px-4 py-3 bg-green-400 w-20 rounded-xl
								hover:bg-green-500 active:scale-95
								transition flex items-center justify-center" type="submit" value="Save">
					</div>
					</form>
				</div>
				
			</div>
		</div>
	</div>

	<div class="text-center text-gray-400 text-sm mt-8 mb-8">
		v1.0 - © 2026 Signal-Tech 
	</div>

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
