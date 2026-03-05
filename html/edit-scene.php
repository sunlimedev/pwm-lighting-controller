<?php
// check if the key exists in the URL
if (isset($_GET['scene_id']))
{
    // ensure scene id is a valid integer
    $scene_id = filter_var($_GET['scene_id'], FILTER_VALIDATE_INT);
	
	// connect to lighting.db
    $db = new PDO('sqlite:/home/user/project/database/lighting.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

	// get number of scenes
	$stmt = $db->query("SELECT COUNT(*) AS total FROM scenes");
	$row = $stmt->fetch(PDO::FETCH_ASSOC);
	$num_scenes = $row['total'];
	
	// redirect if there is an issue
    if ($scene_id !== false)
    {
        if ($scene_id < 1 or $scene_id > $num_scenes)
        {
			header("Location: /scenes.php");
			exit;
		}
    }
    else
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

// form name to db name
$behavior_names_to_db = [
	"Sequence - Solid"   => "sequence_solid",
	"Sequence - Fade"    => "sequence_fade",
	"Sequence - Decay"   => "sequence_decay",
	"Sequence - Morse"   => "sequence_morse",
	"Sequence - Wigwag"  => "sequence_wigwag",
	"Sequence - SOS"     => "sequence_sos",
	"Sequence - Breathe" => "sequence_breathe",
	"Crossfade"          => "crossfade",
	"Crossfade - Hold"   => "crossfade_hold"
	];

// array of db names
$int_to_behavior_names_db = [
	0 => "sequence_solid",
	1 => "sequence_fade",
	2 => "sequence_decay",
	3 => "sequence_morse",
	4 => "sequence_wigwag",
	5 => "sequence_sos",
	6 => "sequence_breathe",
	7 => "crossfade",
	8 => "crossfade_hold"
	];

// array of form names
$int_to_behavior_names_styled = [
	0 => "Sequence - Solid",
	1 => "Sequence - Fade",
	2 => "Sequence - Decay",
	3 => "Sequence - Morse",
	4 => "Sequence - Wigwag",
	5 => "Sequence - SOS",
	6 => "Sequence - Breathe",
	7 => "Crossfade",
	8 => "Crossfade - Hold"
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

/* Preloaded selections (example when editing) */
//$preselected = [3, 8, 8, 15];
$preselected = [];

// try database connection
try
{
	// connect to lighting.db
    $db = new PDO('sqlite:/home/user/project/database/lighting.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

	// get specific scene's info
    $stmt = $db->prepare("SELECT * FROM scenes WHERE scene_id = :id");
    // bind value ???
    $stmt->execute(['id' => $scene_id]);
    // store info in rows1
    $rows1 = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // get all color info
    $stmt = $db->query("SELECT * FROM colors ORDER BY color_id ASC");
    // store all color's info in rows2
    $rows2 = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
	// check that submitted id is valid
	$scene_id = filter_input(INPUT_POST, 'scene_id', FILTER_VALIDATE_INT);
	
	// gather other scene parameters
	$name = trim($_POST['name']);
    $behavior = $_POST['behavior'];
    $behavior = $behavior_names_to_db[$behavior];
    $brightness = filter_input(INPUT_POST, 'brightness', FILTER_VALIDATE_INT);
    $speed = filter_input(INPUT_POST, 'speed', FILTER_VALIDATE_INT);

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
                    speed = :speed
                WHERE scene_id = :id
            ");

            $stmt->execute([
                ':name' => $name,
                ':behavior' => $behavior,
                ':brightness' => $brightness,
                ':speed' => $speed,
                ':id' => $scene_id
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
	<title>Edit Scene</title>
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
				<?php echo "Edit Scene " . $scene_id?>
			</h1>

			<!-- Floating popup -->
			<div class="relative pr-1">
				<a href="#" id="toggle-info"
					class="px-4 py-3 bg-purple-400 rounded-xl
					hover:bg-purple-500 active:scale-95
					transition flex items-center justify-center">
					<img src="/assets/help.svg"
						alt="Help"
						class="w-12 h-6">
				</a>
				<div id="info-box" class="absolute right-0 mt-2 w-64 bg-white p-4 rounded-lg shadow-lg hidden z-50">
					<p class="text-gray-800">
						Placeholder.
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
						<label for="name">Name:</label><br>
						<input <?= ($scene_id == 1) ? 'readonly' : '' ?>
							class="w-full <?= ($scene_id == 1) ? 'bg-gray-50' : '' ?> border border-gray-200 rounded-xl px-4 py-3 mb-2"
							type="text"
							id="name"
							name="name"
							value="<?= htmlspecialchars($rows1['name']); ?>">
					</div>
						
					<!-- behavior select dropdown with prefilled behavior string -->
					<div class="font-medium">
						<label for="behavior">Behavior:</label><br>
						<select class="w-full bg-white border border-gray-200 rounded-xl px-4 py-3 mb-2" type="text" id="behavior" name="behavior"><br>
							<optgroup label="Behavior">
								<?php for ($i = 0; $i < 9; $i++): ?>
									<?php if ($int_to_behavior_names_db[$i] == $rows1['behavior']): ?>
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
						<label for="brightness">Brightness:</label><br>
						<div class="bg-white border border-gray-200 rounded-xl px-4 pt-3 mb-2">
							<input class="w-full" type="range" id="brightness" name="brightness" min="1" max="5" value="<?= $rows1['brightness']; ?>" step="1"/>
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
						<label for="speed">Speed:</label><br>
						<div class="bg-white border border-gray-200 rounded-xl px-4 pt-3 mb-2">
							<input class="w-full" type="range" id="speed" name="speed" min="1" max="5" value="<?= $rows1['speed']; ?>" step="1"/>
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
					<div class="grid grid-cols-8 gap-2 mb-6">
						<?php foreach ($color_files as $id => $file): ?>
							<button
								type="button"
								class="color-btn border rounded hover:scale-95 transition"
								data-id="<?= $id ?>">
								<img
									src="/assets/colors/<?= $file ?>.svg"
									class="w-10 h-10"
									alt="<?= $file ?>">
							</button>
						<?php endforeach; ?>
					</div>

					<!-- selected colors box -->
					<div class="border rounded-lg p-3 mb-4">
						<div class="text-sm text-gray-600 mb-2">
							Selected Colors (max 10)
						</div>
						<div id="selectedBox" class="flex flex-wrap gap-2"></div>
					</div>

					<div id="hiddenInputs"></div>
					<input
						type="submit"
						class="px-4 py-2 bg-purple-500 text-white rounded hover:bg-purple-600"
						value="Submit">

					<!-- save and cancel buttons -->
					<div class="flex justify-between items-center mt-4">
						<a href="/connections.php" 
							class="px-4 py-3 bg-red-400 rounded-xl
								hover:bg-red-500 active:scale-95
								transition flex items-center justify-center">
								Cancel
						</a>
						<input class="px-4 py-3 bg-green-400 rounded-xl
								hover:bg-green-500 active:scale-95
								transition flex items-center justify-center" type="submit" value="Submit">
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

<script>
	const colorFiles = <?= json_encode($color_files) ?>;

	/* preload selections */
	let selected = <?= json_encode($preselected) ?>;

	const selectedBox = document.getElementById("selectedBox");
	const hiddenInputs = document.getElementById("hiddenInputs");

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


	/* grid click handler */
	document.querySelectorAll(".color-btn").forEach(btn=>{
		btn.addEventListener("click",()=>{
			if(selected.length>=10)
				return;
			const id=parseInt(btn.dataset.id);
				selected.push(id);
				renderSelected();
		});
	});

	if(selected.length>0)
		renderSelected();
</script>

</body>
</html>
