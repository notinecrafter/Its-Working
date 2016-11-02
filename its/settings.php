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

	$string = "";
	$string = $string."'style'=>'".htmlspecialchars($_POST["style"])."'";
	if($_POST["dispname"] == "" || !isset($_POST["dispname"])){
		$dispname = $_SESSION["user"];
	}else{
		$dispname = htmlspecialchars($_POST["dispname"]);
	}
	$string = $string.", 'dispname'=>'".$dispname."'";
	try{
		$sql = "UPDATE users
		SET preferences=\"".$string."\"
		WHERE id='".$_SESSION["user"]."'";
		$stmt = $conn->prepare($sql);
		$stmt->execute();
	}catch(PDOException $e){
		if($debug){
			echo "error: ".$e->getMessage;
		}
	}

	if(!$debug){
		echo "<script type='text/javascript'>window.location.replace('index.php')</script>";
	}
	?>
</body>
</html>