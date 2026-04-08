<?php
try
{
	// database connect
    $db = new PDO('sqlite:/home/user/project/database/lighting.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
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
	<title>Reset</title>
	<link rel="stylesheet" href="/css/tailwind.min.css">
</head>

<body class="bg-gray-100 min-h-screen">
	<!-- logo and navigation buttons -->
	<div class="text-center py-6 flex justify-between items-center max-w-md mx-auto pl-7 pr-7">
		<span>
			<a href="/index.php" class="inline-block">
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
					alt="Log In"
					class="mx-auto w-9 h-9 pt-1">
			</a>
		</span>
	</div>
	
	<!-- page header and tootip/action buttons -->
	<div class="max-w-md mx-auto p-1">
		<div class="flex justify-between items-center mb-3">
			<h1 class="text-3xl font-semibold p-1">
				Reset
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
						This page contains information to reset your controller.
					</p>
				</div>
            </div>
		</div>
    </div>
    
    <!-- container for reset info -->
    <div class="max-w-md mx-auto p-1">
        <div class="bg-gray-50 rounded-lg divide-y divide-gray-200">
            <div class="p-4 break-words">
				Only one account may exist per controller. If you forget your username or password, the system can be reset to allow a new account to be created, or for any other reason.
				<br><br>
				To reset the system, hold the Reset button on the device for 10 seconds. The Reset light will start blinking once per second while the device is reconfigured. When then blinking stops, your controller has been reset.
				<div>
					<!-- board image -->
					<img src="/assets/logo.svg" 
						alt="Reset Button Image"
						class="mx-auto w-full">
				</div>
            </div>
        </div>
    </div>
    
   	<!-- copyright footer -->
	<div class="text-center text-gray-400 text-sm mt-6 mb-8">
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
