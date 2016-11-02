<?php
session_start();
?>
<!DOCTYPE html>
<html>
<head>

</head>
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
		if($debug){$conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );}
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

	function tableExists($conn) {
	//check if the table already exists first. code copied from http://stackoverflow.com/a/14355475/4546084 and edited for this usecase
    try {
    	$stmt = $conn->prepare("SELECT * FROM Group".$_POST["group"].$_POST["name"].";");
        $result = $stmt->execute();
    } catch (Exception $e) {
        // We got an exception == table not found
        return FALSE;
    }
		// Result is either boolean FALSE (no table found) or PDOStatement Object (table found)
    	return $result !== FALSE;
	}

	if(tableExists($conn) != FALSE){
		exit("project already exists");
	}

	//make the properties
	$deadline = implode(" ", explode("T", $_POST["deadline"]));
	if($deadline == ""){
		//allow to not fill in a deadline
		$properties = "";
	}else{
		//check if the deadline is a valid datetime
		if (DateTime::createFromFormat('Y-m-d G:i:s', $deadline) === FALSE) {
		  exit("please fill in a valid deadline");
		}
		$properties = "'deadline'=>'".$deadline."'";
	}

	//now that we've verified everything is OK, actually start making the bloody project
	try{
		$sql = "CREATE TABLE Group".htmlspecialchars($_POST["group"].$_POST["name"])." (
			ID INT(6) AUTO_INCREMENT,
			owner VARCHAR(50),
			filename VARCHAR(50),
			grade DOUBLE(2, 1),
			time VARCHAR(19),
			PRIMARY KEY (ID)
			);";
		$stmt = $conn->prepare($sql);
		$stmt->execute();
	}catch (PDOException $e){
		if($debug){echo "error: ".$e->getMessage();}
	}

	$creation = mkdir("files/".htmlspecialchars($_POST["group"].$_POST["location"])."/Project".htmlspecialchars($_POST["name"]));
	if($creation){
		if($debug){echo "<p>folder created succesfully</p>";}

		try{
			$sql = "INSERT INTO Group".htmlspecialchars($_POST['group'])."(filename, filetype, properties, location) VALUES ('".htmlspecialchars($_POST["name"])."','project',\"".$properties."\", '".htmlspecialchars($_POST['location'])."');";
			$stmt = $conn->prepare($sql);
			$stmt->execute();
			if($debug){echo "<p>project created succesfully</p>";}
		}catch(PDOException $e){
			if($debug){echo "error: ".$e->getMessage;}
		}
	}else{
		echo "folder was not created due to an unknown error.";
	}
	if(!$debug){
		echo "<script type='text/javascript'>window.location.replace('index.php')</script>";
	}
	?>
</body>

</html>