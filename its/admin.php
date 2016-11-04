<?php
session_start();
?>
<!DOCTYPE html>
<html>
<head>
	<title>It's Working build <?php echo date("o")."W".date("W");?></title>
	<style>
		table, th, td {
    		border: 1px solid black;
    		border-collapse: collapse;
		}
		th, td {
    		padding: 5px;
		}
	</style>
</head>
<body>
	<?php
	if($_SESSION["type"] != 2){
	 	exit("you are not an admin");
	}

	//TODO: security
	//evaluate setup.ini
		$file = fopen("setup.ini", 'r');
		$setup = fread($file, filesize('setup.ini'));
		eval($setup);
		fclose($file);

	//connect to internal db
	try{
		$conn = new PDO("$server_type:host=$db_server;dbname=$db_name", $db_user, $db_password);
		echo "<p>connected to internal database</p>"; //TODO: should only echo in debug mode
	}catch(PDOException $e){
		echo "<br/>connection failed: ".$e->getMessage(); //TODO: should only echo in debug mode
	}

	//connect to external db's for password changes
	//connect to external db 1
	try {
    	$ext1_conn = new PDO("$ext1_server_type:host=$ext1_db_server;port=$ext1_port;dbname=$ext1_db_name", $ext1_user, $ext1_password);
    	// set the PDO error mode to exception
    	$ext1_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }catch(PDOException $e){
    	echo "Connection failed: " . $e->getMessage();
    }
	//connect to external db 2
	try {
    	$ext2_conn = new PDO("$ext2_server_type:host=".$ext2_db_server.";dbname=".$ext2_db_name, $ext2_user, $ext2_password);
    	// set the PDO error mode to exception
    	$ext2_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }catch(PDOException $e){
    	echo "Connection failed: " . $e->getMessage();
    }

	//connect to external db 3
	try {
    	$ext3_conn = new PDO("$ext3_server_type:host=".$ext3_db_server.";dbname=".$ext3_db_name, $ext3_user, $ext3_password);
    	// set the PDO error mode to exception
    	$ext3_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }catch(PDOException $e){
    	echo "Connection failed: " . $e->getMessage();
    }

	//TODO: there are bugs with exceptions not being thrown that can result in non-functional groups. e.g.: a groupMath-A will not have it's own table but will be created in the groups table. No exception is thrown. somehow.
	if($_POST["editGroups"]){
		try{
			$sql = "UPDATE groups
			SET ".$_POST['column']." = \"".$_POST['value']."\"
			WHERE name = \"".$_POST["groupName"]."\";";
			$stmt = $conn->prepare($sql);
			$stmt->execute(); 
		}catch(PDOException $e){
			echo "<p>error: ".$e->getMessage()."</p>";
		}
	}

	if($_POST["newGroup"]){
		try{
			//add the group to the groups table
			$sql = "INSERT INTO groups
			(name, admins, members)
			VALUES (\"".$_POST["groupName"]."\",\"".$_POST["admins"]."\",\"".$_POST["members"]."\");";
			$stmt = $conn->prepare($sql);
			$stmt->execute();
			//create a new table for the group
			$sql = "CREATE TABLE group".$_POST["groupName"]."(
				filename VARCHAR(100),
				filetype VARCHAR(100),
				properties VARCHAR(1000),
				location VARCHAR(1000)
				);";
			$stmt = $conn->prepare($sql);
			$stmt->execute();
		}catch(PDOException $e){
			echo "error: ".$e->getMessage();
		}
	}

	if($_POST["removeGroup"]){
		try{
			$sql = "DELETE FROM groups
			WHERE name = \"".$_POST["groupName"]."\";";
			$stmt = $conn->prepare($sql);
			$stmt->execute();
			$sql = "DROP TABLE group".$_POST["groupName"];
			$stmt = $conn->prepare($sql);
			$stmt->execute();
		}catch(PDOException $e){
			echo "error: ".$e->getMessage();
		}
	}

	if($_POST["passwordChange"]){
		try{
			if($_POST['db'] == 1){
				$hash = eval("return ".$ext1_hash_method."('".$_POST["password"]."');");
				$sql = "UPDATE $ext1_user_table
				SET $ext1_password_name = '".$hash."'
				WHERE $ext1_id_name = '".$_POST["user"]."';";
				$stmt = $ext1_conn->prepare($sql);
				$stmt->execute();
			}else if($_POST['db'] == 2){
				$sql = "UPDATE $ext2_user_table
				SET $ext2_password_name = '".eval($ext2_hash_method."(".$_POST["password"].");")."'
				WHERE $ext2_id_name = '".$_POST["user"]."');";
				$stmt = $ext2_conn->prepare($sql);
				$stmt->execute();
			}else if($_POST['db'] == 3){
				$sql = "UPDATE $ext3_user_table
				SET $ext3_password_name = '".eval($ext3_hash_method."(".$_POST["password"].");")."'
				WHERE $ext3_id_name = '".$_POST["user"]."');";
				$stmt = $ext2_conn->prepare($sql);
				$stmt->execute();
			}
		}catch(PDOException $e){
			echo "error: ".$e->getMessage();
		}
	}

	$sql = "SELECT * FROM groups";
	$stmt = $conn->prepare($sql);
	$stmt->execute();
	$stmt->setFetchMode(PDO::FETCH_ASSOC);
	$groups = $stmt->fetchAll();
	?>
	<h2>Groups</h2>
	<?php
	//echo the groups table including forms. so many forms.
	echo "<table><tbody><tr><th>Name</th><th>Admins</th><th>Members</th><th>Remove?</th></tr>";
	foreach($groups as $group){
		echo"<tr><td>".$group["name"]."
		</td><td><form action='admin.php' method='post'><input type='hidden' name='editGroups' value='True'><input type='hidden' name='column' value='admins'><input type='hidden' name='edit' value='True'><input type='text' name='value' value=\"".$group["admins"]."\"><input type='hidden' name='groupName' value=\"".$group["name"]."\"><input type='submit' value='modify'></form>
		</td><td><form action='admin.php' method='post'><input type='hidden' name='editGroups' value='True'><input type='hidden' name='column' value='members'><input type='text' name='value' value=\"".$group["members"]."\"><input type='hidden' name='groupName' value=\"".$group["name"]."\"><input type='submit' value='modify'></form></td>
		<td><form action='admin.php' method='post'><input type='hidden' name='removeGroup' value='True'><input type='hidden' name='groupName' value='".$group["name"]."'><input type='submit' value='remove'></form></tr>";
	}
	echo "</tbody></table>";
	?>
	<p>
		<!--all these hidden inputs with value='True' are so the PHP actually does something. Do NOT mess with them-->
		<form action='admin.php' method='post'><input type='hidden' name='newGroup' value='True'><input type='text' name='groupName' placeholder='name'><input type='text' name='admins' placeholder='admins'><input type='text' name='members' placeholder='members'><input type='submit' value='add group'></form>
	</p>
	<h2>Users</h2>
	<?php
    //select all from the tables
	$sql1 = "SELECT * FROM ".$ext1_user_table." WHERE ".$ext1_condition;
	$sql2 = "SELECT * FROM ".$ext2_user_table." WHERE ".$ext2_condition;
	$sql3 = "SELECT * FROM ".$ext3_user_table." WHERE ".$ext3_condition;
	$stmt1 = $ext1_conn->prepare($sql1);
	$stmt2 = $ext2_conn->prepare($sql2);
	$stmt3 = $ext3_conn->prepare($sql3);
	$stmt1->execute();
	$stmt2->execute();
	$stmt3->execute();
	$stmt1->setFetchMode(PDO::FETCH_ASSOC);
	$stmt2->setFetchMode(PDO::FETCH_ASSOC);
	$stmt3->setFetchMode(PDO::FETCH_ASSOC);
	$result1 = $stmt1->fetchAll();
	$result2 = $stmt2->fetchAll();
	$result3 = $stmt3->fetchAll();
	//make a nice table out of that code above
	echo "<table><tbody><tr><th>Username</th><th>Type</th>";
	if($ext1_write || $ext2_write || $ext3_write){
		$write = True;
		echo "<th>Change Password?</th>";
	}else{
		$write = False;
	}
	echo "</tr>";
	foreach($result1 as $user){
		echo "<tr><td>".$user[$ext1_id_name]."</td><td>".$ext1_user_type."</td>";
		//TODO: make the user type a word instead of a number
		if($ext1_write){
			echo "<td><form action='admin.php' method='post'><input type='hidden' name='passwordChange' value='True'><input type='hidden' name = 'db' value='1'><input type='hidden' name='user' value='".$user[$ext1_id_name]."'><input type='password' name='password'><input type='submit' value='change'></form></td>";
		}else if($write){
			echo "<td><em>disabled</em></td>";
		}
		echo "</tr>";
	}
	foreach($result2 as $user){
		echo "<tr><td>".$user[$ext2_id_name]."</td><td>".$ext2_user_type."</td>";
		//TODO: make the user type a word instead of a number
		if($ext2_write){
			echo "<td><form action='admin.php' method='post'><input type='hidden' name='passwordChange' value='True'><input type='hidden' name = 'db' value='2'><input type='hidden' name='user' value='".$user[$ext2_id_name]."'><input type='password' name='password'><input type='submit' value='change'></form></td>";
		}else if($write){
			echo "<td><em>disabled</em></td>";
		}
		echo "</tr>";
	}
	foreach($result3 as $user){
		echo "<tr><td>".$user[$ext3_id_name]."</td><td>".$ext3_user_type."</td>";
		//TODO: make the user type a word instead of a number
		if($ext3_write){
			echo "<td><form action='admin.php' method='post'><input type='hidden' name='passwordChange' value='True'><input type='hidden' name = 'db' value='3'><input type='hidden' name='user' value='".$user[$ext3_id_name]."'><input type='password' name='password'><input type='submit' value='change'></form></td>";
		}else if($write){
			echo "<td><em>disabled</em></td>";
		}
		echo "</tr>";
	}
	echo "</tbody></table>";

	?>
</body>
</html>