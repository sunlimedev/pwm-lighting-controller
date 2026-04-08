<?php
require_once("/var/www/html/includes/user-check.php");
require_once("/var/www/html/includes/session-check.php");

try
{
	// database connect
    $db = new PDO('sqlite:/home/user/project/database/lighting.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

	// get all connections
    $stmt = $db->query("SELECT * FROM connections ORDER BY connection_id ASC");
    $connections = $stmt->fetchAll(PDO::FETCH_ASSOC);

	// get scene ids and names
    $stmt = $db->query("SELECT scene_id, name FROM scenes ORDER BY scene_id ASC");
    $scene_ids_names = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // make scene_id name key value pair array
    $scenes = [];
	foreach ($scene_ids_names as $row)
	{
		$scenes[$row['scene_id']] = $row['name'];
	}

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
    <title>Connections</title>
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
				Connections
			</h1>
			<a href="/connections.php" 
			class="px-4 py-3 bg-blue-400 w-20 rounded-xl
					hover:bg-blue-500 active:scale-95
					transition flex items-center
					justify-center"> 
				<img src="/assets/refresh.svg" 
					alt="Refresh" 
					class="w-8 h-6">
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
						Connections turn green when active, and only one connection may be active at any time.
						<br><br>
						The lowest connection number has priority. For example, if connections 2 and 4 receive power simultaneously, only connection 2 will be active.
						<br><br>
						If you are integrating this system with other devices, send 24VDC power to a connection, then hit the blue refresh button to check your wiring.
					</p>
				</div>
			</div>
		</div>
	</div>
	
    <!-- container for all connections and buttons -->
    <div class="max-w-md mx-auto p-1">
		<div class="bg-gray-50 rounded-lg divide-y divide-gray-200">
			<?php foreach ($connections as $connection): ?>
			<div class="p-4">
				<div class="flex justify-between items-center">
					<span class="font-medium text-left whitespace-nowrap pr-8">
						<?php
							if($connection['is_active'] == 1)
							{
								echo "🟢";
							}
							else
							{
								echo "🔴";
							}
							echo " - Connection " . $connection['connection_id'];
						?>
					</span>
					<span class="text-right truncate">
						<?php
							$index = (int) $connection['scene'];
							echo "Scene: " . $scenes[$index];
						?>
					</span>
				</div>
				<div class="mb-2 break-words">
						<span class="text-gray-700">
						<?php echo "Note: " . $connection['note']; ?>
						</span>
				</div>
				<div class="relative">
						<?php
							echo '<a href="edit-connection.php?connection_id=' . $connection['connection_id'] . '" id="toggle-info"
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

</body>

</html>
