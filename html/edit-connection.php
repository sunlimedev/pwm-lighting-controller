<?php
// check if the key exists in the URL
if (isset($_GET['connection_id']))
{
    // ensure connection_id is a valid integer
    $connection_id = filter_var($_GET['connection_id'], FILTER_VALIDATE_INT);

	// redirect if there is an issue
    if ($connection_id !== false)
    {
        if ($connection_id < 1 or $connection_id > 8)
        {
			header("Location: /connections.php");
			exit;
		}
    }
    else
    {
        header("Location: /connections.php");
		exit;
    }
}
// redirect if there is no key
else
{
    header("Location: /connections.php");
    exit;
}

// data handling for form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    $connection_id = filter_input(INPUT_POST, 'connection_id', FILTER_VALIDATE_INT);
    $scene = filter_input(INPUT_POST, 'scene', FILTER_VALIDATE_INT);
    $note = trim($_POST['note']);

    if ($connection_id !== false && $scene !== false)
    {
        try
        {
            $db = new PDO('sqlite:/home/user/project/database/lighting.db');
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $db->prepare("
                UPDATE connections
                SET scene = :scene,
                    note = :note
                WHERE connection_id = :id
            ");

            $stmt->execute([
                ':scene' => $scene,
                ':note' => $note,
                ':id' => $connection_id
            ]);

            header("Location: /connections.php");
            exit;

        }
        catch (PDOException $e)
        {
            echo "Database error: " . $e->getMessage();
            exit;
        }
    }
}



// try database connection
try
{
	// connect to lighting.db
    $db = new PDO('sqlite:/home/user/project/database/lighting.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

	// get specific connection's info
    $stmt = $db->prepare("SELECT * FROM connections WHERE connection_id = :id");
    // bind id value
    $stmt->execute(['id' => $connection_id]);
    // store info in rows1
    $rows1 = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // get all scene info
    $stmt = $db->query("SELECT * FROM scenes ORDER BY scene_id ASC");
    // store all scene info in rows2
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
	<title>Edit Connection</title>
	<link rel="stylesheet" href="/css/tailwind.min.css">
</head>

<body class="bg-gray-100 min-h-screen">

	<div class="text-center py-6 flex justify-between items-center max-w-md mx-auto pl-7 pr-7">
		<span>
			<a href="/connections.php" class="inline-block">
				<img src="/assets/back.svg" 
					alt="Logo"
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
					alt="Logo"
					class="mx-auto w-9 h-9 pt-1">
			</a>
		</span>
	</div>
	
	<div class="max-w-md mx-auto p-1">
		<div class="flex justify-between items-center mb-2">
			<h1 class="text-3xl font-semibold p-1">
				<?php echo "Edit Connection " . $connection_id?>
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
						<?php echo "Select which of your scenes will play when Connection " . $connection_id . " is active."?>
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
					<input type="hidden" name="connection_id" value="<?= $connection_id ?>">
					
					<div class="font-medium">
						<label for="scene">Linked Scene</label><br>
						<select class="w-full bg-white border border-gray-200 rounded-xl px-4 py-3 mb-2" type="text" id="scene" name="scene"><br>
							<optgroup label="User Scenes">
								<?php foreach ($rows2 as $row): ?>

									<?php
										$selected = ($rows1['scene'] == $row['scene_id']) ? 'selected' : '';
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
					<div class="flex justify-between items-center mt-2">
							<a href="/connections.php" 
								class="px-4 py-3 bg-yellow-400 w-20 rounded-xl
								hover:bg-yellow-500 active:scale-95
								transition flex items-center justify-center">
								Cancel
							</a>
											
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
