<?php
require_once("/var/www/html/includes/user-check.php");
require_once("/var/www/html/includes/session-check.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    try
    {
		// database connect
        $db = new PDO('sqlite:/home/user/project/database/lighting.db');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}
	catch (PDOException $e)
    {
        echo "Database error: " . $e->getMessage();
        exit;
    }
    
	// verify the "i understand" checkbox was checked before resetting database
	if (!isset($_POST['confirm']))
	{
		die("Confirmation required.");
	}
	else
	{
		/* 
		Resetting Scenes and Connections:
		1 - copy scenes table from factory settings to main database
		2 - copy connections table from factory settings to main database
		3 - update sqlite_sequence table scenes value
		
		Resetting Events:
		1 - copy events table from factory settings to main database
		2 - update sqlite_sequence table events value
		
		Resetting Schedule Hours:
		1 - copy time table from factory settings to main database
		
		Resetting Username and Password:
		1 - delete existing user
		2 - update sqlite_sequence table users value
		
		Order for multiple resets:
		1 - Scenes and Connections
		2 - Events
		3 - Schedule Hours
		4 - Username and Password
		*/
		
		$scenes = isset($_POST['scenes']);
		$events = isset($_POST['events']);
		$schedule = isset($_POST['schedule']);
		$user = isset($_POST['user']);
		
		try
		{
			$db->beginTransaction();
			$db->exec("ATTACH DATABASE '/home/user/project/database/factory_settings.db' AS factory");

			if($scenes)
			{
				$db->exec("DELETE FROM scenes");
				$db->exec("INSERT INTO scenes SELECT * FROM factory.scenes");
				$db->exec("DELETE FROM connections");
				$db->exec("INSERT INTO connections SELECT * FROM factory.connections");
				$db->exec("UPDATE sqlite_sequence SET seq = 11 WHERE name = 'scenes'");
			}

			if($events)
			{
				$db->exec("DELETE FROM events");
				$db->exec("INSERT INTO events SELECT * FROM factory.events");
				$db->exec("UPDATE sqlite_sequence SET seq = 2 WHERE name = 'events'");
			}

			if($schedule)
			{
				$db->exec("DELETE FROM time");
				$db->exec("INSERT INTO time SELECT * FROM factory.time");
			}
			
			if($user)
			{
				$db->exec("DELETE FROM users");
				$db->exec("UPDATE sqlite_sequence SET seq = 0 WHERE name = 'users'");
			}

			$db->commit();
			$db->exec("DETACH DATABASE factory");

			header("Location: /home.php");
			exit;
		}
		catch (PDOException $e)
		{
			// if there is any error stop all changes and revert to safe state
			$db->rollBack();
			echo "Database error: " . $e->getMessage();
			exit;
		}
    }
}

try
{
	// database connect
	$db = new PDO('sqlite:/home/user/project/database/lighting.db');
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

	// get current year for copyright footer
	$stmt = $db->query("SELECT year FROM clock");
	$copyright_year = $stmt->fetch(PDO::FETCH_COLUMN);
}
catch (PDOException $e)
{
	echo "Database error: " . $e->getMessage();
	exit;
}
?>

<!DOCTYPE html>
<html lang="en">
	
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Reset</title>
	<link rel="stylesheet" href="/css/tailwind.min.css">
</head>

<body class="bg-gray-100 min-h-screen">
	<!-- logo and navigation buttons -->
	<div class="text-center py-6 flex justify-between items-center max-w-md mx-auto pl-7 pr-7">
		<span>
			<a href="/settings.php" class="inline-block">
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
	
	<!-- page header and tootip/action buttons -->
	<div class="max-w-md mx-auto p-1">
		<div class="flex justify-between items-center mb-2">
			<h1 class="text-3xl font-semibold p-1">
				Reset Options
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
				<div id="info-box"
					class="absolute right-0 mt-2 w-64 bg-white p-4 rounded-lg shadow-lg hidden z-50">
					<p class="text-gray-800">
						Reset certain information to the factory settings.
					</p>
				</div>
			</div>
		</div>
    </div>
    
	<!-- form container with reset options -->
	<div class="max-w-md mx-auto p-1">
		<div class="bg-gray-50 rounded-lg divide-y divide-gray-200">
			<div class="p-4">
				<div>
					<form method="POST">					
					<div class="font-medium">
						<div>
							<label class="flex items-center gap-2 mb-2">
								<input type="checkbox" name="scenes" id="scenes" class="w-8 h-8">
								<span>Reset Scenes and Connections</span>
							</label>
						</div>
						<div>
							<label class="flex items-center gap-2 mb-2">
								<input type="checkbox" name="schedule" id="schedule" class="w-8 h-8">
								<span>Reset Schedule Hours</span>
							</label>
						</div>
						<div>
							<label class="flex items-center gap-2 mb-2">
								<input type="checkbox" name="events" id="events" class="w-8 h-8">
								<span>Reset Events</span>
							</label>
						</div>
						<div>
							<label class="flex items-center gap-2 mb-2">
								<input type="checkbox" name="user" id="user" class="w-8 h-8">
								<span>Reset Username and Password</span>
							</label>
						</div>
						<div>
							<label class="flex items-center gap-2 mb-2 font-bold">
								<input type="checkbox" name="all" id="all" class="w-8 h-8">
								<span>Reset ALL</span>
							</label>
						</div>
						<div>
							<label class="flex items-center gap-2 mt-6 text-red-700 font-bold">
								<input type="checkbox" name="confirm" id="confirm" class="w-8 h-8 flex-shrink-0">
								<span class="w-full">I understand that the information I have selected will be permanently removed.</span>
							</label>
						</div>
					</div>
					<div class="flex justify-between items-center mt-4">
						<a href="/settings.php" 
							class="px-4 py-3 bg-yellow-400 w-20 rounded-xl
							hover:bg-yellow-500 active:scale-95
							transition flex items-center justify-center">
							Cancel
						</a>

						<input class="px-4 py-3 bg-red-400 w-20 rounded-xl
							hover:bg-red-500 active:scale-95
							transition flex items-center justify-center" type="submit" value="RESET">
					</div>
					</form>
				</div>
			</div>
		</div>
	</div>
	
	<!-- copyright footer -->
	<div class="text-center text-gray-400 text-sm mt-8 mb-8">
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

<script>
	// checkboxes
	const scenes = document.getElementById('scenes');
	const schedule = document.getElementById('schedule');
	const events = document.getElementById('events');
	const user = document.getElementById('user');
	const all = document.getElementById('all');
	const confirmBox = document.getElementById('confirm');
	const form = document.querySelector('form');

	// check above when all selected
	all.addEventListener('change', () => {
		const checked = all.checked;
		scenes.checked = checked;
		schedule.checked = checked;
		events.checked = checked;
		user.checked = checked;
	});

	// if above four are checked, check all box, too
	function updateAllCheckbox() {
		all.checked = scenes.checked && schedule.checked && events.checked && user.checked;
	}

	scenes.addEventListener('change', updateAllCheckbox);
	schedule.addEventListener('change', updateAllCheckbox);
	events.addEventListener('change', updateAllCheckbox);
	user.addEventListener('change', updateAllCheckbox);

	// prevent submit unless "I understand" is checked
	form.addEventListener('submit', (e) => {
		if (!confirmBox.checked) {
			e.preventDefault();
			alert("You must confirm before resetting.");
		}
	});
</script>

</body>

</html>
