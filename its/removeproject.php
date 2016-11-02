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

	//function copied from https://stackoverflow.com/questions/3349753/delete-directory-with-files-in-it#3349792
	function deleteDir($dirPath) {
	    if (! is_dir($dirPath)) {
	        throw new InvalidArgumentException("$dirPath must be a directory");
	    }
	    if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
	        $dirPath .= '/';
	    }
	    $files = glob($dirPath . '*', GLOB_NOSORT);
	    foreach ($files as $file) {
	    	echo "lol ";
	        if (is_dir($file)) {
	            deleteDir($file);
	        } else {
	            unlink($file);
	        }
	    }
	    //the .ds_store is a file created by macOS finder in every directory, even remote ones. PHP will not see it, as it starts with a dot, but it will stop rmdir. Therefore, there is an instruction to delete it just in case.
	    unlink($dirPath.".ds_store");
	    return rmdir($dirPath);
	}

	if($debug){echo "deleting: files/".htmlspecialchars($_POST["group"].$_POST['location'].$_POST["filename"]."/<br/>");}
	try{
		$deletion = deleteDir("files/".htmlspecialchars($_POST["group"].$_POST['location']."Project".$_POST["filename"]));
		if(!$deletion){
			exit("folder not deleted");
		}
		$sql = "DROP TABLE Group".$_POST["group"].$_POST["filename"];
		$stmt = $conn->prepare($sql);
		$stmt->execute();
		$sql = "DELETE FROM Group".htmlspecialchars($_POST['group'])." where filename='".htmlspecialchars($_POST["filename"])."' and location='".htmlspecialchars($_POST["location"])."'";
		$stmt = $conn->prepare($sql);
		$stmt->execute();
	}catch(PDOException $e){
		if($debug){echo "error: ".$e->getMessage()."<br/>";}
	}
	if(!$debug){
		echo "<script type='text/javascript'>window.location.replace('index.php')</script>";
	}
	?>
</body>
</html>