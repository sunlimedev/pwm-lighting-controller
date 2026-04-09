<?php
require_once("/var/www/html/includes/user-check.php");
require_once("/var/www/html/includes/session-check.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
	// database connect
    $db = new PDO('sqlite:/home/user/project/database/lighting.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	
	// swap default scene logic
	if (isset($_POST['new_default_scene']))
	{
		$new_default_scene = filter_input(INPUT_POST, 'new_default_scene', FILTER_VALIDATE_INT);
		
		try
		{
			$db->beginTransaction();

			$db->exec("UPDATE scenes SET is_default = 0");
			
			// kill test mode
			$db->exec("UPDATE testmode SET flag = 0");
			
			// set new default
			$stmt = $db->prepare("
				UPDATE scenes
				SET is_default = 1
				WHERE scene_id = :id
			");
			$stmt->execute([':id' => $new_default_scene]);

			$db->commit();

			header("Location: /scenes.php");
			exit;
		}
		catch (PDOException $e)
		{
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

	// get all scenes
    $stmt = $db->query("SELECT * FROM scenes ORDER BY scene_id ASC");
    $scenes = $stmt->fetchAll(PDO::FETCH_ASSOC);

	// get all colors
    $stmt = $db->query("SELECT * FROM colors ORDER BY color_id ASC");
    $colors = $stmt->fetchAll(PDO::FETCH_ASSOC);

	// get current year for copyright footer
    $stmt = $db->query("SELECT year FROM clock");
	$copyright_year = $stmt->fetch(PDO::FETCH_COLUMN);
}
catch (PDOException $e)
{
    echo "Database error: " . $e->getMessage();
    exit;
}

// db string to formatted string mapping
$behavior_names = [
	"sequence_solid"   => "Sequence - Solid",
	"sequence_fade"    => "Sequence - Fade",
	"sequence_decay"   => "Sequence - Decay",
	"sequence_wigwag"  => "Sequence - Wigwag",
	"sequence_sos"     => "Sequence - SOS",
	"sequence_breathe" => "Sequence - Breathe",
	"crossfade"        => "Crossfade",
	"crossfade_hold"   => "Crossfade - Hold"
	];
?>

<!DOCTYPE html>
<html lang="en">
	
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scenes</title>
    <link rel="stylesheet" href="/css/tailwind.min.css">
</head>

<body class="bg-gray-100 min-h-screen">
	<!-- logo and navigation buttons -->
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
	
	<!-- page header and tootip/action buttons -->
	<div class="max-w-md mx-auto p-1">
		<div class="flex justify-between items-center mb-2">
			<h1 class="text-3xl font-semibold p-1">
				Scenes
			</h1>
			<a href="/add-scene.php" 
			class="px-4 py-3 bg-blue-400 w-20 rounded-xl
					hover:bg-blue-500 active:scale-95
					transition flex items-center
					justify-center"> 
				<img src="/assets/plus.svg" 
					alt="Add scene" 
					class="w-12 h-6">
			</a>
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
					<p class="text-gray-700">
						Scenes include a lighting behavior, some colors, a brightness setting, and a speed setting.
						<br><br>
						The highlighted scene in your list is the Default scene. It will play during the lighting hours you schedule.
						<br><br>
						Create or modify scenes to play when a connection is active or for a special event.
					</p>
				</div>
			</div>
		</div>
	</div>

	<!-- form container for all scenes and buttons -->
    <div class="max-w-md mx-auto p-1">
	<form method="POST">
		<div class="bg-gray-50 rounded-lg divide-y divide-gray-200">
			<?php foreach ($scenes as $scene): ?>
			<div class="p-4 <?= ($scene['is_default'] == 1) ? 'bg-blue-100' : '' ?>">
				<div class="flex justify-between items-center">
					<span class="text-left truncate <?= ($scene['is_default'] == 1) ? 'font-bold text-2xl' : 'font-medium' ?>">
						<?php echo $scene['name']; ?>
					</span>
				</div>
				<div>
					<span class="text-gray-700">
						<?php echo "Behavior: " . $behavior_names[$scene['behavior']]; ?>
					</span>
				</div>
				<div>
					<span class="text-gray-700">
						<?php echo "Brightness: " . $scene['brightness']; ?>
					</span>
				</div>
				<div class = "mb-1">
					<span class="text-gray-700">
						<?php echo "Speed: " . $scene['speed']; ?>
					</span>
				</div>
				<!-- show color images -->
				<div class= "mb-2">
					<span class="flex flex-wrap items-center gap-1">
						<?php for ($i = 0; $i < 10; $i++): ?>
							<?php
								$key = "color" . $i;
								if (!empty($scene[$key])):
									$color_id = $scene[$key] - 1;
									$color_name = $colors[$color_id]['name'];
							?>
							<img src="/assets/colors/<?= $color_name; ?>.svg"
								 alt="<?= $color_name; ?>"
								 class="w-7 h-7">
							<?php endif; ?>
						<?php endfor; ?>
					</span>
				</div>
				<!-- edit button for each scene -->
				<div class="relative flex justify-between items-center gap-3">
						<?= ($scene['is_default'] == 0) ? '<button
							type="submit"
							name="new_default_scene"
							value="' . $scene['scene_id'] . '"
							class="px-4 py-3 bg-green-400 w-full rounded-xl hover:bg-green-500 transition">
							Set Default
							</button>' : ''
						?>
						<?='<a href="edit-scene.php?scene_id=' . $scene['scene_id'] . '" id="toggle-info"
									class="px-4 py-3 bg-yellow-400 w-full rounded-xl
									hover:bg-yellow-500 active:scale-95
									transition flex items-center justify-center">
									<img src="/assets/pencil.svg" alt="Edit" class="w-6 h-6">
								</a>'
						?>
				</div>
			</div>
			<?php endforeach; ?>
		</div>
	</form>
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
	// return to scroll position on page refresh
	window.addEventListener('scroll', () => {
		sessionStorage.setItem('scrollPosition', window.scrollY);
	});

	window.addEventListener('load', () => {
		const scrollPos = sessionStorage.getItem('scrollPosition');
		if (scrollPos) {
			window.scrollTo(0, parseInt(scrollPos));
		}
	});
</script>

</body>

</html>

