<?php
// check if account has been created
try
{
    $db = new PDO('sqlite:/home/user/project/database/lighting.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $db->query("SELECT EXISTS (SELECT 1 FROM users)");
    $result = $stmt->fetch(PDO::FETCH_NUM);
    $user_exists = $result[0];
    
    // if no user exists then redirect to register.php
    if($user_exists == False)
    {
		header("Location: /register.php");
		exit;
	}
}
catch (PDOException $e)
{
    echo "Database error: " . $e->getMessage();
    exit;
}
?>
