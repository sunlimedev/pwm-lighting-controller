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
    $stmt = $db->query("SELECT name FROM scenes ORDER BY scene_id ASC");
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
		<a href="/home.html" class="inline-block">
			<img src="/assets/logo.svg" 
				alt="Logo"
				class="mx-auto w-48">
		</a>
	</div>

	<div class="max-w-md mx-auto p-1">
		<div class="flex justify-between items-center mb-6">
			<h1 class="text-3xl font-semibold p-1">
				Connections
			</h1>
			
			<a href="/connections.php" 
			class="px-4 py-3 bg-blue-400 rounded-xl
					hover:bg-blue-500 active:scale-95
					transition flex items-center
					justify-center"> 
				<img src="/assets/refresh.svg" 
					alt="Refresh" 
					class="w-12 h-6">
			</a>
		</div>
	</div>

    <div class="max-w-md mx-auto p-1">
	
		<!-- big container for all of the events 2026-03-17-->
		<div class="bg-gray-50 rounded-lg divide-y divide-gray-200">
			<?php foreach ($rows1 as $row): ?>

			<div class="p-4 space-y-2">
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

					<span class="text-gray-700 text-right">
						<?php
							$index = (int) $row['scene'] - 1;
							echo "Scene: " . $rows2[$index]['name'];
						?>
					</span>
				</div>
			
				<div>
					<span class="text-gray-700">
						<?php echo "Note: " . $row['note']; ?>
					</span>
				</div>
			</div>
			<?php endforeach; ?>
		</div>

		<div class="flex flex-col mt-1">
			<a href="/edit-connections.php" 
			   class="w-full py-5 text-xl font-medium 
					  bg-yellow-400 text-black rounded-xl
					  hover:bg-yellow-500 active:scale-95
					  transition block text-center">
				Edit Connections
			</a>
		</div>
		
	</div>
	
	<div class="max-w-md mx-auto p-1">
	
		<!-- big container for all of the events 2026-03-17-->
		<div class="bg-gray-100 rounded-lg divide-y divide-gray-200">
			<div class="pt-4 pl-4 pr-4 space-y-2">
				<div class="flex justify-between items-center">
					<span class="font-medium">
						<span class="font-medium inline-block">
				Connections turn green when active, and only one connection may be active at any time.<br><br>The lowest connection number has priority. For example, if connections 2 and 4 both receive power, only connection 2 will be active.
						</span>
					</span>

				</div>
			</div>
		</div>

	</div>
	
	<div class="text-center text-gray-400 text-sm mt-8 mb-8">
		v1.0 - © 2026 Signal-Tech 
	</div>
	
</body>
</html>
