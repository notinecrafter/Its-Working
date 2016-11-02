<?php
session_start();
?>
<!DOCTYPE html>
<html>
<body>
	<?php
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
		$conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
	}catch(PDOException $e){
		echo "<br/>connection failed: ".$e->getMessage(); //TODO: should only echo in debug mode
	}

	//verification
	$verified = false;
	try{
		$sql="SELECT admins FROM Groups WHERE name='".$_POST["group"]."'";
		$stmt = $conn->prepare($sql);
		$stmt->execute();
		$groups = $stmt->fetchAll();
	}catch(PDOException $e){
		if($debug){echo "error: ".$e->getMessage();}
	}
	foreach($groups as $group){
		eval("\$admins = array(".$group["admins"].");");
		foreach($admins as $admin){
			if($admin == $_SESSION["user"]){
				$verified = true;
			}
		}
	}
	if(!$verified){
		exit("I'm sorry, ".$_SESSION["user"].", but I'm afraid I can't let you do that");
	}

	if($_POST["action"] = "add"){
		$creation = mkdir("files/".$_POST["group"].$_POST["location"]."/".$_POST["name"]);
		if($creation){
			echo "<p>folder created succesfully</p>";
			try{
				$sql = "INSERT INTO Group".$_POST['group']."(filename, filetype, location) VALUES ('".$_POST["name"]."','folder','".$_POST['location']."');";
				$stmt = $conn->prepare($sql);
				$stmt->execute();
			}catch(PDOException $e){
				if($debug){
					echo "error: ".$e->getMessage;
				}
			}
		}else{
			echo "folder was not created due to an unknown error.";
		}
	}else if($_POST["action"] == "remove"){
		$removal = rmdir("files/".$_POST["group"].$_POST["location"]."/".$_POST["name"]);
		if($removal){
			echo "<p>folder removed succesfully</p>";
			try{
				$sql = "DELETE FROM Group".$_POST["group"]." WHERE filename='".$_POST["name"]."' and filetype='folder' and location='".$_POST["location"]."');";
				$stmt = $conn->prepare($sql);
				$stmt->prepare();
			}catch(PDOException $e){
				if($debug){
					echo "error: ".$e->getMessage();
				}
			}
		}
	}
	if(!$debug){
		echo "<script type='text/javascript'>window.location.replace('index.php')</script>";
	}
	?>
</body>
</html>