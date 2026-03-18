<?php
// php try block so a database error does not crash the page
try
{
	// create database object using sqlite driver and file path
    $db = new PDO('sqlite:/home/user/project/database/lighting.db');
    // throw error on database failure
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

	// statement object is a container holding result of query
    $stmt = $db->query("SELECT * FROM connections ORDER BY connection_id ASC");
    // extract each row as an array of values
    $rows1 = $stmt->fetchAll(PDO::FETCH_ASSOC);

	// statement object is a container holding result of query
    $stmt = $db->query("SELECT scene_id, name FROM scenes ORDER BY scene_id ASC");
    // extract each row as an array of values
    $rows2 = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // make scene_id name key value pair array
    $scenes = [];
	foreach ($rows2 as $row)
	{
		$scenes[$row['scene_id']] = $row['name'];
	}   
}
// catch block to handle error
catch (PDOException $e)
{
	// print the error on the webpage
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

					<!-- Floating popup -->
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

    <div class="max-w-md mx-auto p-1">
	
		<!-- big container for all of the connections-->
		<div class="bg-gray-50 rounded-lg divide-y divide-gray-200">
			<?php foreach ($rows1 as $row): ?>

			<div class="p-4">
				<div class="flex justify-between items-center">
					<span class="font-medium">
						<?php
							if($row['is_active'] == 1)
							{
								echo "🟢";
							}
							else
							{
								echo "🔴";
							}
							echo " - Connection " . $row['connection_id'];
						?>
					</span>

					<span class="text-right">
						<?php
							$index = (int) $row['scene'];
							echo "Scene: " . $scenes[$index];
						?>
					</span>
				</div>
			
				<div class="mb-2">
						<span class="text-gray-700">
						<?php echo "Note: " . $row['note']; ?>
						</span>
				</div>
				<div class="relative">
						<?php
							echo '<a href="edit-connection.php?connection_id=' . $row['connection_id'] . '" id="toggle-info"
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
