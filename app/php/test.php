<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Server Status</title>
</head>

<body>

	<h1>
		Web server is running.
	</h1>
	<h1>
		<?php
			try
			{
				$db = new PDO('sqlite:/home/user/project/database/lighting.db');
				$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

				// test query
				$stmt = $db->query("SELECT EXISTS (SELECT 1 FROM users)");
				$result = $stmt->fetch(PDO::FETCH_NUM);

				echo "Lighting database is accessible.";
			}
			catch (PDOException $e)
			{
				echo "Lighting database error: " . $e->getMessage();
			}
		?>
	</h1>
	<h1>
		<?php
			try
			{
				$db = new PDO('sqlite:/home/user/project/database/factory_settings.db');
				$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				
				// test query
				$stmt = $db->query("SELECT EXISTS (SELECT 1 FROM users)");
				$result = $stmt->fetch(PDO::FETCH_NUM);

				echo "Settings database is accessible.";
			}
			catch (PDOException $e)
			{
				echo "Settings database error: " . $e->getMessage();
			}
		?>
	</h1>

</body>

</html>
