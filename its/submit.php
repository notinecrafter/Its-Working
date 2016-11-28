<?php
session_start();
?>
<!DOCTYPE html>
<html>
<body>
	<?php
	//TODO: backend verification
	//evaluate setup.ini
		$file = fopen("setup.ini", 'r');
		$setup = fread($file, filesize('setup.ini'));
		eval($setup);
		fclose($file);

	//connect to internal db
	try{
		$conn = new PDO("$server_type:host=$db_server;dbname=$db_name", $db_user, $db_password);
		if($debug){
			echo "<p>connected to internal database</p>";
		}
	}catch(PDOException $e){
		if($debug){
			echo "<br/>connection failed: ".$e->getMessage();
		}
	}

	//some code to work with the additional member thing
	$members = array("'".$_SESSION["user"]."'");
        if(isset($_POST["members"]){
	        foreach($_POST["members"] as $member){
		        $members[] = "'$member'";
	        }
        }
	$members = implode(",", $members);

	//verification
	$verified = 0;
	//check if the user if a member of the group
	try{
		$sql = "SELECT * FROM groups WHERE name = '".$_POST["group"]."';";
		$stmt = $conn->prepare($sql);
		$stmt->execute();
		$stmt->setFetchMode(PDO::FETCH_ASSOC);
		$fetched = $stmt->fetchAll(); 
		if($debug){
			echo "<p>selected from group db</p>"; //TODO: should only echo in debug mode
		}
	}catch(PDOException $e){
		if($debug){
			echo "<p>selection error: ".$e->getMessage()."</p>";
		}
	}
	foreach($fetched as $group){
		//these owners are actually the members of the group, but if I call it members it confuses the script...
		eval("\$owners = array(".$group["members"].");");
		foreach($owners as $owner){
			if($owner === $_SESSION["user"]){
				$verified = 1;
			}
		}
	}
	//check if the user has already submitted something
	$submitted = false;
	try{
		$sql = "SELECT * FROM Group".$_POST['group'].$_POST["project"].";";
		$stmt = $conn->prepare($sql);
		$stmt->execute();
		$stmt->setFetchMode(PDO::FETCH_ASSOC);
		foreach($stmt->fetchAll() as $submission){
			//since the owner list is actually an array we need to explode this and check the members
			$owners = explode(',', $submission["owner"]);
			foreach($owners as $owner){
				if($owner == "'".$_SESSION["user"]."'"){
					$submitted = true;
				}
			}
		}
	}catch(PDOException $e){
		echo "error: ".$e->getMessage()."<br/>";
	}
	//stop script if not verified:
	if(!$verified){
		exit("I'm sorry, ".$_SESSION["user"].", but I'm afraid I can't let you do that");
	}
	if($submitted){
		exit("I'm sorry, ".$_SESSION["user"].", but I'm afraid I can't let you do that.");
	}

	//begin upload process. code liberally borrowed from http://www.w3schools.com/php/php_file_upload.asp
	$target_dir = "files/".$_POST["group"]."/".$_POST["location"]."project".$_POST["project"]."/";
	$target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
	$uploadOk = 1;
	// Check if file is php. If it is, ABANDON SHIP.
	if(pathinfo($target_file,PATHINFO_EXTENSION) == "php"){
		exit("<span style='color: #ff0000'>PHP files are not allowed for security reasons</span>");
	}
	// Check if file already exists
	if (file_exists($target_file)) {
    	echo "Sorry, file already exists.";
    	$uploadOk = 0;
	}
	// Check if $uploadOk is set to 0 by an error
	if ($uploadOk == 0) {
   	 	echo "<br/>Sorry, your file was not uploaded.";
   	 	exit();
	// if everything is ok, try to upload file
	} else {
	    if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
	        if($debug){echo "The file ". basename( $_FILES["fileToUpload"]["name"]). " has been uploaded.";}
	        //add uploaded file to database. This is done after the file upload because the upload is more likely to go wrong...
	        try{
	        	$sql = "INSERT INTO Group".$_POST['group'].$_POST["project"]."(owner, filename, time) VALUES (\"".$members."\",\"".htmlspecialchars(basename($_FILES["fileToUpload"]["name"]))."\", \"".date("o")."-".date("m")."-".date("d")." ".date("H").":".date("i").":".date("s")."\");";
	        	$stmt = $conn->prepare($sql);
	        	$stmt->execute();
	        }catch(PDOException $e){
	        	if($debug){echo "error: ".$e->getMessage().$e."<br/>";}
	        }
	    } else {
	        echo "Sorry, there was an error uploading your file.";
	    }
	}
	if(!$debug){
		echo "<script type='text/javascript'>window.location.replace('index.php')</script>";
	}
	?>
</body>
</html>
