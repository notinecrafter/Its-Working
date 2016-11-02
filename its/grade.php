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
	
	//NOTICE: the grade is in $_POST["foo"], because I needed to check if the varname was the cause of a bug and I was too lazy to change it back.
	//first check if the number is valid
	if($_POST["foo"] === ""){
		exit("Please actually fill in a grade, thanks.");
	}else if(strlen($_POST["foo"]) > 4){
		exit("INVALID GRADE");
	}else if(floatval($_POST["foo"]) > 10 || floatval($_POST["foo"]) < 1){
		exit("INVALID GRADE");
	}
	echo $_POST["foo"];
	try{
		$sql = "UPDATE Group".$_POST['group'].$_POST["project"]."
		SET grade='".$_POST["foo"]."'
		WHERE id=".$_POST["id"].";"; 
		$stmt = $conn->prepare($sql);
		$stmt->execute();
	}catch(PDOException $e){
		echo "error: ".$e->getMessage().$e."<br/>";
	}
	if(!$debug){
		echo "<script type='text/javascript'>window.location.replace('index.php')</script>";
	}
	?>
</body>
</html>