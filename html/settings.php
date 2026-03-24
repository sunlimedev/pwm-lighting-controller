<?php 

?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Settings</title>
	<link rel="stylesheet" href="/css/tailwind.min.css">
</head>

<body class="bg-gray-100 min-h-screen">

	<div class="text-center py-6 flex justify-between items-center max-w-md mx-auto pl-7 pr-7">
		<span>
			<a href="/home.php" class="inline-block">
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
		<div class="flex justify-between items-center mb-3">
			<h1 class="text-3xl font-semibold p-1">
				Settings
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
            <div id="info-box" class="absolute right-0 mt-2 w-64 bg-white p-4 rounded-lg shadow-lg hidden z-50">
                <p class="text-gray-800">
					placeholder
                </p>
            </div>
		</div>
    </div>

    <div class="max-w-md mx-auto p-1">
        
        <div class="flex flex-col">

            <a href="/edit-date-time.php" 
               class="w-full py-5 text-xl font-medium
                      bg-yellow-400 text-black rounded-xl mb-1
                      hover:bg-yellow-500 active:scale-95
                      transition block text-center">
                Edit Date & Time
            </a>
            
            <a href="/factory-reset.php" 
               class="w-full py-5 text-xl font-medium
                      bg-yellow-400 text-black rounded-xl
                      hover:bg-yellow-500 active:scale-95
                      transition block text-center">
                Factory Reset
            </a>
            
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
