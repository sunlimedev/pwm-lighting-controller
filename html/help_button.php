<?php
$db = new SQLite3('/home/user/project/database/lighting.db');
$result = $db->query("SELECT * from colors");

echo "<h1>Lighting Data</h1>";

while($row = $result->fetchArray())
{ 
        echo "<pre>";
        print_r($row);
        echo "</pre>";
}
?>

	<div class="max-w-md mx-auto p-1">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-semibold p-1">
            Connections
        </h1>

        <!-- Button container -->
        <div class="relative">
            <a href="#" id="toggle-info"
               class="px-4 py-3 bg-purple-400 rounded-xl
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
                    This is some extra info that appears when you click the button. Click again to close.
                </p>
            </div>
        </div>
    </div>
	</div>

<script>
    const toggleBtn = document.getElementById('toggle-info');
    const infoBox = document.getElementById('info-box');

    toggleBtn.addEventListener('click', (e) => {
        e.preventDefault(); // prevent default anchor navigation
        infoBox.classList.toggle('hidden');
    });
</script>
