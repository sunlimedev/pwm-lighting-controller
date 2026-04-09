<?php
require_once("/var/www/html/includes/user-check.php");
require_once("/var/www/html/includes/session-check.php");

if (isset($_GET['scene_id']))
{
    // ensure scene id is a valid integer
    $scene_id = filter_var($_GET['scene_id'], FILTER_VALIDATE_INT);
	
	// database connect
    $db = new PDO('sqlite:/home/user/project/database/lighting.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

	// get valid scene numbers
	$stmt = $db->query("SELECT scene_id FROM scenes");
	$valid_scene_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
	
	// get specific scene
    $stmt = $db->prepare("SELECT color0, color1, color2, color3, color4, color5, color6, color7, color8, color9 FROM scenes WHERE scene_id = :id");
    $stmt->execute(['id' => $scene_id]);
    $color_row = $stmt->fetch(PDO::FETCH_ASSOC);
	
	// remove null colors
	$preselected = [];
	for ($i = 0; $i < 10; $i++)
	{
		$key = "color" . $i;

		if (!empty($color_row[$key]))
		{
			$preselected[] = (int)$color_row[$key];
		}
		else
		{
			break;
		}
	}
	
	// redirect if there is an issue
    if ($scene_id == false or in_array($scene_id, $valid_scene_ids) == false)
    {
		header("Location: /scenes.php");
		exit;
    }
}
// redirect if there is no key
else
{
    header("Location: /scenes.php");
    exit;
}

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
	
	// check that submitted id is valid
	$scene_id = filter_input(INPUT_POST, 'scene_id', FILTER_VALIDATE_INT);
	
	// delete button logic
	if (isset($_POST['delete_scene']))
	{
		try
		{
			$db->beginTransaction();
			
			$stmt = $db->query("SELECT scene_id FROM scenes WHERE is_default = 1");
			$default_scene_id = $stmt->fetch(PDO::FETCH_COLUMN);
			
			// reset connections to default scene
			$stmt = $db->prepare("
				UPDATE connections
				SET scene = :default
				WHERE scene = :id
			");
			$stmt->execute([':default' => $default_scene_id, ':id' => $scene_id]);

			// reset events to default scene
			$stmt = $db->prepare("
				UPDATE events
				SET scene = :default
				WHERE scene = :id
			");
			$stmt->execute([':default' => $default_scene_id, ':id' => $scene_id]);
	
			// delete the scene
			$stmt = $db->prepare("
				DELETE FROM scenes
				WHERE scene_id = :id
			");
			
			// disable test mode if on
			$stmt->execute([':id' => $scene_id]);
			$db->exec("UPDATE testmode SET flag = 0");

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
	
	// form name to db name
	$behavior_names_to_db = [
		"Sequence - Solid"   => "sequence_solid",
		"Sequence - Fade"    => "sequence_fade",
		"Sequence - Decay"   => "sequence_decay",
		"Sequence - Wigwag"  => "sequence_wigwag",
		"Sequence - SOS"     => "sequence_sos",
		"Sequence - Breathe" => "sequence_breathe",
		"Crossfade"          => "crossfade",
		"Crossfade - Hold"   => "crossfade_hold"
	];
	
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
	
	// test button logic
	if (isset($_POST['test_scene']))
	{
		try
		{
			// signal 30s popup to play
			echo json_encode(["status" => "ok"]);

			$db->beginTransaction();
			$stmt = $db->prepare("
				UPDATE testmode
				SET flag = 1,
					reload = 1,
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
			");
			$stmt->execute([
				':behavior' => $behavior,
				':brightness' => $brightness,
				':speed' => $speed,
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
			
			$db->commit();
			exit;
		}
		catch (PDOException $e)
		{
			$db->rollBack();
			echo json_encode(["status" => "error"]);
			echo "Database error: " . $e->getMessage();
			exit;
		}
	}
	
	// update table if id is valid
    if ($scene_id !== false)
    {
        try
        {
			$db->beginTransaction();

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
            
            $db->exec("UPDATE testmode SET flag = 0");
            $db->commit();

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

// array of db names
$int_to_behavior_names_db = [
	0 => "sequence_solid",
	1 => "sequence_fade",
	2 => "sequence_decay",
	3 => "sequence_wigwag",
	4 => "sequence_sos",
	5 => "sequence_breathe",
	6 => "crossfade",
	7 => "crossfade_hold"
	];

// array of form names
$int_to_behavior_names_styled = [
	0 => "Sequence - Solid",
	1 => "Sequence - Fade",
	2 => "Sequence - Decay",
	3 => "Sequence - Wigwag",
	4 => "Sequence - SOS",
	5 => "Sequence - Breathe",
	6 => "Crossfade",
	7 => "Crossfade - Hold"
	];

// color_id to name
$color_files = [
	1  => "red1",
	2  => "red2",
	3  => "red3",
	4  => "orange1",
	5  => "orange2",
	6  => "orange3",
	7  => "orange4",
	8  => "orange5",
	9  => "yellow1",
	10 => "yellow2",
	11 => "yellow3",
	12 => "yellow4",
	13 => "lime1",
	14 => "lime2",
	15 => "lime3",
	16 => "lime4",
	17 => "lime5",
	18 => "green1",
	19 => "green2",
	20 => "green3",
	21 => "green4",
	22 => "green5",
	23 => "green6",
	24 => "green7",
	25 => "green8",
	26 => "teal1",
	27 => "teal2",
	28 => "teal3",
	29 => "teal4",
	30 => "cyan1",
	31 => "cyan2",
	32 => "cyan3",
	33 => "cyan4",
	34 => "blue1",
	35 => "blue2",
	36 => "blue3",
	37 => "blue4",
	38 => "blue5",
	39 => "blue6",
	40 => "blue7",
	41 => "blue8",
	42 => "purple1",
	43 => "purple2",
	44 => "purple3",
	45 => "purple4",
	46 => "purple5",
	47 => "purple6",
	48 => "purple7",
	49 => "magenta1",
	50 => "magenta2",
	51 => "magenta3",
	52 => "pink1",
	53 => "pink2",
	54 => "pink3",
	55 => "pink4",
	56 => "pink5",
	57 => "pink6",
	58 => "pink7",
	59 => "red4",
	60 => "red5",
	61 => "white1",
	62 => "white2",
	63 => "white3",
	64 => "off"
];

try
{
	// get specific scene
    $stmt = $db->prepare("SELECT * FROM scenes WHERE scene_id = :id");
    $stmt->execute(['id' => $scene_id]);
    $scene = $stmt->fetch(PDO::FETCH_ASSOC);
    
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
	<title>Edit Scene</title>
	<link rel="stylesheet" href="/css/tailwind.min.css">
</head>

<body class="bg-gray-100 min-h-screen">
	<!-- logo and navigation buttons -->
	<div class="text-center py-6 flex justify-between items-center max-w-md mx-auto pl-7 pr-7">
		<span>
			<a href="/scenes.php" class="inline-block">
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
				Edit Scene
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
				<div id="info-box" class="absolute right-0 mt-2 w-64 bg-white p-4 rounded-lg shadow-lg hidden z-50">
					<p class="text-gray-800">
						You can choose from two types of behaviors. The Sequence behaviors play one color, then the next. The Crossfade behaviors gradually shift between colors.
						<br><br>
						The Brightness setting defines the intensity of the light tubes.
						<br><br>
						The Speed setting defines how fast the colors will transition between each other.
						<br><br>
						There are 64 colors to choose from. Each scene may include up to 10 colors.
					</p>
				</div>
			</div>
		</div>
    </div>
    
    <!-- form container for all scenes settings -->
	<div class="max-w-md mx-auto p-1">
		<div class="bg-gray-50 rounded-lg divide-y divide-gray-200">
			<div class="p-4">
				<form method="POST">
					<!-- hidden scene_id integer -->
					<input type="hidden" name="scene_id" value="<?= $scene_id ?>">
					<!-- name field -->
					<div class="font-medium">
						<label for="name">Scene Name</label><br>
						<input
							class="w-full border border-gray-200 rounded-xl px-4 py-3 mb-2"
							type="text"
							id="name"
							name="name"
							value="<?= htmlspecialchars($scene['name']); ?>"
							maxlength = "100">
					</div>
					<!-- behavior select dropdown with prefilled behavior string -->
					<div class="font-medium">
						<label for="behavior">Behavior</label><br>
						<select class="w-full bg-white border border-gray-200 rounded-xl px-4 py-3 mb-2" type="text" id="behavior" name="behavior"><br>
							<optgroup label="Behavior">
								<?php for ($i = 0; $i < 8; $i++): ?>
									<?php if ($int_to_behavior_names_db[$i] == $scene['behavior']): ?>
										<option selected="selected" value="<?= $int_to_behavior_names_styled[$i]; ?>">
											<?= $int_to_behavior_names_styled[$i]; ?>
										</option>
									<?php else: ?>
										<option value="<?= $int_to_behavior_names_styled[$i]; ?>">
											<?= $int_to_behavior_names_styled[$i]; ?>
										</option>
									<?php endif; ?>
								<?php endfor; ?>
							</optgroup>
						</select>
					</div>
					<!-- brightness slider -->
					<div class="font-medium">
						<label for="brightness">Brightness</label><br>
						<div class="bg-white border border-gray-200 rounded-xl px-4 pt-3 mb-2">
							<input class="w-full" type="range" id="brightness" name="brightness" min="1" max="5" value="<?= $scene['brightness']; ?>" step="1"/>
							<div class="flex justify-between items-center pl-1 pr-1 mb-2">
								<span>1</span>
								<span>2</span>
								<span>3</span>
								<span>4</span>
								<span>5</span>
							</div>
						</div>
					</div>
					<!-- speed slider -->
					<div class="font-medium">
						<label for="speed">Speed</label><br>
						<div class="bg-white border border-gray-200 rounded-xl px-4 pt-3 mb-2">
							<input class="w-full" type="range" id="speed" name="speed" min="1" max="5" value="<?= $scene['speed']; ?>" step="1"/>
							<div class="flex justify-between items-center pl-1 pr-1 mb-2">
								<span>1</span>
								<span>2</span>
								<span>3</span>
								<span>4</span>
								<span>5</span>
							</div>
						</div>
					</div>
					<!-- color changing code -->
					<div class="font-medium">
						<label for="speed">Colors (up to 10)</label><br>
						<div class="grid grid-cols-8 gap-1 bg-white border border-gray-200 rounded-xl p-2 mb-2">
							<?php foreach ($color_files as $id => $file): ?>
								<button
									type="button"
									class="color-btn rounded hover:scale-95 transition"
									data-id="<?= $id ?>">
									<img
										src="/assets/colors/<?= $file ?>.svg"
										class="w-10 h-10"
										alt="<?= $file ?>">
								</button>
							<?php endforeach; ?>
						</div>
					</div>
					<!-- selected colors box -->
					<div class="border rounded-lg p-3 bg-white">
						<div class="text-sm text-gray-600 mb-2">
							Selected colors (tap to remove)
						</div>
						<div id="selectedBox" class="flex flex-wrap gap-2"></div>
					</div>
					<div id="hiddenInputs"></div>
					<!-- save, delete, test, and cancel buttons -->
					<div class="flex justify-between items-center mt-4">
						<a href="/scenes.php" 
							class="px-4 py-3 bg-yellow-400 2-20 rounded-xl
								hover:bg-yellow-500 active:scale-95
								transition">
								Cancel
						</a>
						<!-- delete button only shown if scene is not selected as default -->
						<?php if($scene['is_default'] != 1)
						{
							echo '<button
							type="submit"
							name="delete_scene"
							value="1"
							onclick="return confirm(\'Are you sure you want to delete this scene?\');"
							class="px-4 py-3 bg-red-400 w-20 rounded-xl hover:bg-red-500 transition">
							Delete
							</button>';
						}
						?>
						<button type="button" id="testBtn"
							class="px-4 py-3 bg-blue-400 w-20 rounded-xl hover:bg-blue-500 transition">
							Test
						</button>
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
	// color grid and selector
	const colorFiles = <?= json_encode($color_files) ?>;

	// load colors in scene already
	let selected = <?= json_encode($preselected) ?>;

	const selectedBox = document.getElementById("selectedBox");
	const hiddenInputs = document.getElementById("hiddenInputs");

	document.getElementById("testBtn").addEventListener("click", async () => {
    const form = document.querySelector("form");
    const formData = new FormData(form);

    // tell PHP this is a test request
    formData.append("test_scene", "1");

	// respond with ok or error if POST worked
    try {
        const response = await fetch("", {
            method: "POST",
            body: formData
        });

        const result = await response.json();

        if (result.status === "ok") {
            alert("Your scene settings will now play for 30 seconds.");
        } else {
            alert("Test mode was unable to start. Please refresh the page and try again.");
        }
    } catch (err) {
        console.error("Request error:", err);
    }
	});

	// show selected colors in box
	function renderSelected()
	{
		selectedBox.innerHTML = "";
		selected.forEach((id,index)=>{
			const img = document.createElement("img");
			img.src = "/assets/colors/" + colorFiles[id] + ".svg";
			img.className = "w-10 h-10 cursor-pointer";
			img.alt = colorFiles[id];
			img.addEventListener("click",()=>{
				selected.splice(index,1);
				renderSelected();
			});
		selectedBox.appendChild(img);
		});
		updateInputs();
	}

	// add tapped color to selected box
	function updateInputs()
	{
		hiddenInputs.innerHTML = "";
		selected.forEach((id,i)=>{
			const input = document.createElement("input");
			input.type="hidden";
			input.name="color"+i;
			input.value=id;
			hiddenInputs.appendChild(input);
		});
	}


	// grid click handler
	document.querySelectorAll(".color-btn").forEach(btn=>{
		btn.addEventListener("click",()=>{
			if(selected.length>=10)
				return;
			const id=parseInt(btn.dataset.id);
				selected.push(id);
				renderSelected();
		});
	});

	// expand selected box if any are selected
	if(selected.length>0)
		renderSelected();
</script>

</body>
</html>
