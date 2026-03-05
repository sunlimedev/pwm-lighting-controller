<?php

/* Preloaded selections (example when editing) */
//$preselected = [3, 8, 8, 15];
$preselected = [];

/* Mapping of color IDs (1–64) to SVG filenames */
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

?>

<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<title>Color Selector</title>
	<link rel="stylesheet" href="/css/tailwind.min.css">
</head>

<body class="bg-gray-100 p-8">
	<div class="max-w-md mx-auto bg-white p-6 rounded-xl shadow">
		<h2 class="text-lg font-semibold mb-4">Choose Colors</h2>
		<form method="POST" action="">

			<!-- 8x8 COLOR GRID -->
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

			<!-- SELECTED COLORS BOX -->
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
		</form>
	</div>

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
