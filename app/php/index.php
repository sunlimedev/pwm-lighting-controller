<?php
require_once("/var/www/html/includes/user-check.php");

// redirect logged in user away from login (idk if we need this check this whole file)
session_start();
if (isset($_SESSION['user_id']))
{
    header("Location: /home.php");
    exit();
}

// check if the key exists in the URL
if (isset($_GET['notify']))
{
    // ensure notify is a valid integer
    $notify = filter_var($_GET['notify'], FILTER_VALIDATE_INT);

	// redirect if there is an issue
    if ($notify !== false)
    {
		// account was just created
        if ($notify == 0)
        {
			$message = "Account successfully created. Please log in.";
		}
		elseif ($notify == 1)
        {
			$message = "Incorrect username or password.";
		}
		else
		{
			$message = 0;
		}
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
	try
	{
		$db = new PDO('sqlite:/home/user/project/database/lighting.db');
		$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		$username = $_POST['username'];
		$password = $_POST['password'];

		$stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
		$stmt->execute([$username]);
		$user = $stmt->fetch();

		if ($user && password_verify($password, $user['password']))
		{
			$_SESSION['user_id'] = $user['id'];
			header("Location: /home.php");
			exit;
		}
		else
		{
			header("Location: /index.php?notify=1");
			exit;
		}
	}
	catch (PDOException $e)
	{
		// print the error on the webpage
		echo "Database error: " . $e->getMessage();
		exit;
	}
}

try
{
	$db = new PDO('sqlite:/home/user/project/database/lighting.db');
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

	$stmt = $db->query("SELECT year FROM clock");
	$copyright_year = $stmt->fetch(PDO::FETCH_COLUMN);
}
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
	<title>Log In</title>
	<link rel="stylesheet" href="/css/tailwind.min.css">
</head>

<body class="bg-gray-100 min-h-screen">

	<div class="py-6 flex justify-between items-center max-w-md mx-auto">
		<img src="/assets/logo.svg" 
			alt="Logo"
			class="mx-auto w-48">
	</div>
	
	<div class="max-w-md mx-auto p-1">
		<div class="flex justify-between items-center mb-2">
			<h1 class="text-3xl font-semibold p-1">
				Log In
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
						Log in to your account to modify lighting settings.
						<br><br>
						If you are having issues with your username or password, tap the Help button.
					</p>
				</div>
			</div>
		</div>
    </div>

	<div class="max-w-md mx-auto p-1">
		
		<?= $message != 0 ? '<div class="text-red-700 text-left text-xl font-bold p-1 mb-2"> ' . $message . ' </div>' : '' ?>
		
		<div class="bg-gray-50 rounded-lg divide-y divide-gray-200">
			<div class="p-4">
				<div>
					<form method="POST">					
					<div class="font-medium">
						<label for="scene">Username</label><br>
						<input class="w-full border border-gray-200 rounded-xl px-4 py-3 mb-2" type="text" id="username" name="username" required>
					</div>
					
					<div class="font-medium">
						<label for="note">Password</label><br>
						<input class="w-full border border-gray-200 rounded-xl px-4 py-3 mb-2" type="password" id="password" name="password" required>
					</div>
					<div class="flex justify-between items-center mt-2">
							<a href="/forgot-user-pass.php" 
								class="px-4 py-3 bg-yellow-400 w-20 rounded-xl
								hover:bg-yellow-500 active:scale-95
								transition flex items-center justify-center">
								Help
							</a>
											
							<input class="px-4 py-3 bg-green-400 w-20 rounded-xl
								hover:bg-green-500 active:scale-95
								transition flex items-center justify-center" type="submit" value="Log In">
					</div>
					</form>
				</div>
				
			</div>
		</div>
	</div>

	<div class="text-center text-gray-400 text-sm mt-8 mb-8">
		v1.0 - © <?= $copyright_year ?> Signal-Tech 
	</div>

<script>
    const toggleBtn = document.getElementById('toggle-info');
    const infoBox = document.getElementById('info-box');

    toggleBtn.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation(); // prevent this click from reaching document
        infoBox.classList.toggle('hidden');
    });

    // close when clicking anywhere else
    document.addEventListener('click', (e) => {
        if (!infoBox.contains(e.target) && !toggleBtn.contains(e.target)) {
            infoBox.classList.add('hidden');
        }
    });
</script>

</body>

</html>
