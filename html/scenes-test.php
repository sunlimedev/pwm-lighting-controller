<?php
// php try block so a database error does not crash the page
try
{
	// create database object using sqlite driver and file path
    $db = new PDO('sqlite:/home/user/project/database/lighting.db');
    // throw error on database failure
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

	// statement object is a container holding result of query
    $stmt = $db->query("SELECT * FROM scenes ORDER BY scene_id ASC");
    // extract each row as an array of values
    $rows1 = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // statement object is a container holding result of query
    $stmt = $db->query("SELECT * FROM colors ORDER BY color_id ASC");
    // extract each row as an array of values
    $rows2 = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
// catch block to handle error
catch (PDOException $e)
{
	// print the error on the webpage
    echo "Database error: " . $e->getMessage();
    exit;
}

$behavior_names = [
	"sequence_solid"   => "Sequence - Solid",
	"sequence_fade"    => "Sequence - Fade",
	"sequence_decay"   => "Sequence - Decay",
	"sequence_morse"   => "Sequence - Morse",
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
				Scenes
			</h1>
			
			<!-- Button container -->
        <div class="relative pr-1">
            <a href="#" id="toggle-info"
               class="px-4 py-3 bg-purple-400 rounded-xl
                      hover:bg-purple-500 active:scale-95
                      transition flex items-center justify-center">
                <img src="/assets/help.svg"
                     alt="Help"
                     class="w-12 h-6">
            </a>

            <!-- Floating popup -->
            <div id="info-box"
                 class="absolute right-0 mt-2 w-64 bg-white p-4 rounded-lg shadow-lg hidden z-50">
                <p class="text-gray-700">
                    All of your scenes are listed here.
                    <br><br>
                    Scenes include a lighting behavior, some colors, a brightness setting, and a speed setting.
                    <br><br>
                    Scene 1 (Default) will play during the lighting hours you schedule. It can modified but not removed.
                    <br><br>
                    Create or modify other scenes to play when a connection is active or for a special event.
                    <br><br>
                    The various lighting settings are explained at the bottom of this page. You can test what each setting does by changing it on the default scene.
                </p>
            </div>
        </div>
		</div>
	</div>

    <div class="max-w-md mx-auto p-1">
	
		<!-- big container for all of the scenes-->
		<div class="bg-gray-50 rounded-lg divide-y divide-gray-200">
			<?php foreach ($rows1 as $row): ?>

			<div class="p-4">
				<div class="flex justify-between items-center">
					<span class="font-medium">
						<?php echo "Scene " . $row['scene_id']; ?>
					</span>

					<span class="text-right">
						<?php echo $row['name']; ?>
					</span>
				</div>
				
				<div>
					<span class="text-gray-700">
						<?php echo "Behavior: " . $behavior_names[$row['behavior']]; ?>
					</span>
				</div>
				
				<div>
					<span class="text-gray-700">
						<?php echo "Brightness: " . $row['brightness']; ?>
					</span>
				</div>
				
				<div class = "mb-1">
					<span class="text-gray-700">
						<?php echo "Speed: " . $row['speed']; ?>
					</span>
				</div>
				
				<!-- show color images -->
				<div class= "mb-2">
					<span class="flex flex-wrap justify-start items-center gap-1">
						<?php for ($i = 0; $i < 10; $i++): ?>
							<?php
								$key = "color" . $i;
								if (!empty($row[$key])):
									$color_id = $row[$key] - 1;
									$color_name = $rows2[$color_id]['name'];
							?>
							<img src="/assets/colors/<?= $color_name; ?>.svg"
								 alt="<?= $color_name; ?>"
								 class="w-8 h-8">
							<?php endif; ?>
						<?php endfor; ?>
					</span>
				</div>
				<div class="relative">
						<?php
							echo '<a href="edit-scenes.php?scene_id=' . $row['scene_id'] . '" id="toggle-info"
									class="px-4 py-3 bg-yellow-400 rounded-xl
									hover:bg-yellow-500 active:scale-95
									transition flex items-center justify-center">
									<img src="/assets/pencil.svg" alt="Edit" class="w-6 h-6">
								</a>';
						?>
				</div>
			</div>
			<?php endforeach; ?>
		</div>
	</div>
	
	<div class="max-w-md mx-auto p-1">
	
		<!-- big container for all of the events 2026-03-17-->
		<div class="bg-gray-100 rounded-lg divide-y divide-gray-200">
			<div class="pt-4 pl-4 pr-4 space-y-2">
				<div class="flex justify-between items-center">
					<span class="font-medium">
						<span class="font-medium inline-block">
							You can choose from two types of behaviors. The Sequence behaviors play one color, then the next. The Crossfade behaviors gradually shift between colors.
							<br><br>
							The Brightness setting defines the intensity of the light tubes.
							<br><br>
							The Speed setting defines how fast the colors will transition between each other.
							<br><br>
							There are 64 colors to choose from. Each scene may include up to 10 colors.
						</span>
					</span>

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

