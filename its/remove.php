<?php
session_start();
?>
<!DOCTYPE html>
<html>
<body>
	<?php
	//evaluate setup.ini
		$file = fopen("setup.ini", 'r');
		$setup = fread($file, filesize('setup.ini'));
		eval($setup);
		fclose($file);

	//connect to internal db
	try{
		$conn = new PDO("$server_type:host=$db_server;dbname=$db_name", $db_user, $db_password);
		if($debug){echo "<p>connected to internal database</p>";}
	}catch(PDOException $e){
		if($debug){echo "<br/>connection failed: ".$e->getMessage();}
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

	if($debug){echo "deleting: files/".htmlspecialchars($_POST["group"].$_POST['location'].$_POST["filename"]);}
	$sql = "DELETE FROM Group".htmlspecialchars($_POST['group'])." where filename='".htmlspecialchars($_POST["filename"])."' and location='".htmlspecialchars($_POST["location"])."'";
	try{
		$stmt = $conn->prepare($sql);
		$stmt->execute();
		unlink("files/".htmlspecialchars($_POST["group"].$_POST['location'].$_POST["filename"]));
	}catch(PDOException $e){
		if($debug){echo "error: ".$e->getMessage()."<br/>";}
	}
	if(!$debug){
		echo "<script type='text/javascript'>window.location.replace('index.php')</script>";
	}
	?>
</body>
</html>