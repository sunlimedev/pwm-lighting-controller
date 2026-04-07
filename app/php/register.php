<?php
// database connect
try
{
    $db = new PDO('sqlite:/home/user/project/database/lighting.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $db->query("SELECT EXISTS (SELECT 1 FROM users)");
    $result = $stmt->fetch(PDO::FETCH_NUM);
    $user_exists = $result[0];
    
    // redirect to login if user exists
    if($user_exists)
    {
		header("Location: /index.php");
		exit;
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

if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
	$username = trim($_POST['username']);
	$password_raw = $_POST['password'];
	
	// check that user and pass were filled, password is 4+ characters, and password has number
	if(empty($username))
	{
		echo "Username was not provided.";
		exit;
	}
	elseif(empty($password_raw))
	{
		echo "Password was not provided.";
		exit;
	}
	elseif(strlen($password_raw) < 4)
	{
		echo "Password was less than four characters.";
		exit;
	}
	elseif(!preg_match('/\d/', $password_raw))
	{
		echo "Password did not contain a number.";
		exit;
	}
	
	$password = password_hash($password_raw, PASSWORD_DEFAULT);

	$stmt = $db->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
	$stmt->execute([$username, $password]);
	
	header("Location: /index.php?notify=0");
	exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Create Account</title>
	<link rel="stylesheet" href="/css/tailwind.min.css">
</head>

<body class="bg-gray-100 min-h-screen">
	<!-- logo and navigation buttons -->
	<div class="py-6 flex justify-between items-center max-w-md mx-auto">
		<img src="/assets/logo.svg" 
			alt="Logo"
			class="mx-auto w-48">
	</div>

	<!-- page header and tootip/action buttons -->
	<div class="max-w-md mx-auto p-1">
		<div class="flex justify-between items-center mb-2">
			<h1 class="text-3xl font-semibold p-1">
				Create Account
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
						Create an account to modify your lighting. Only one account may exist per controller.
					</p>
				</div>
			</div>
		</div>
    </div>

	<!-- form container username and password fields -->
	<div class="max-w-md mx-auto p-1">
		<div class="bg-gray-50 rounded-lg divide-y divide-gray-200">
			<div class="p-4">
				<div>
					<form method="POST">					
					<div class="font-medium">
						<label for="scene">Username</label><br>
						<input class="w-full border border-gray-200 rounded-xl px-4 py-3 mb-2" type="text" id="username" name="username" maxlength="50" required>
					</div>
					<div class="font-medium">
						<label for="note">Password</label><br>
						<input class="w-full border border-gray-200 rounded-xl px-4 py-3" type="text" id="password" name="password" maxlength="50" required minlength="4" pattern=".*\d.*">
							<p class="text-gray-700">Password must be at least 4 characters and contain a number.</p>
					</div>
					<div class="flex justify-end items-center mt-2">
						<input class="px-4 py-3 bg-green-400 w-20 rounded-xl
							hover:bg-green-500 active:scale-95
							transition" type="submit" value="Create">
					</div>
					</form>
				</div>
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

</body>

</html>
