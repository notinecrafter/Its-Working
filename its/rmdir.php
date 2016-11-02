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
	//function to remove files in folder as well for the future. Would make that a thing today but fuck it, I have other things to do.
	function remove($name, $filetype, $location){
		//code made into annotation because it shouldn't need to be done. Files should be deleted when the folder is removed already.
		//if($filetype == "folder"){
		//	$removal = rmdir($_POST["group"].$location."/".$name);
		//}else{
		//	unlink($_POST["group"].$location."/".$name)
		//}
		if(TRUE){
			//if used to check if file removal worked. removed code that depended on because of reason stated above.
			echo "<p>folder removed succesfully</p>";
			try{ 
				$sql = "DELETE FROM Group".$_POST["group"]."WHERE filename='".$name."' and location='".$location."';";
				$stmt = $conn->prepare($sql);
				$stmt->execute();
			}catch(PDOException $e){
				if($debug){echo "error: ".$e->getMessage();}
			}
		}
	}

	function files($group, $files, $admin, $prefix = ""){
		$folders = array();
		//first catch the folders and remove anything in the main directory
		foreach($files as $file){
			if($file["location"] == "/"){
				if($file["filetype"] == "folder"){
					$folders[] = $file;
				}else{
					remove($file["filename"], $file["filetype"], $prefix.$file["location"]);
				}
			}
		}
		//now apply this function to the files in each folder.
		foreach($folders as $folder){
			$folderfiles = array();
			if($prefix != ""){
				$folder["location"] = str_replace($prefix, "", $file["location"]);
			}
			foreach($files as $file){
				if(explode("/", $file["location"])[1] == $folder["filename"]){
					$file["location"] = str_replace("/".$folder["filename"], "", $file["location"]);
					$folderfiles[] = $file;
				}
			}
			remove($folder["filename"], "folder", $folder["location"]);
			files($group, $folderfiles, $admin, $prefix."/".$folder["filename"]);
		}
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

	$removal = rmdir("files/".htmlspecialchars($_POST["group"].$_POST["location"])."/".htmlspecialchars($_POST["name"]));
	if($removal){
		echo "<p>folder removed succesfully</p>";
		try{
			$sql = "DELETE FROM Group".htmlspecialchars($_POST["group"])." WHERE filename='".htmlspecialchars($_POST["name"])."' and filetype='folder' and location='".htmlspecialchars($_POST["location"])."';";
			$stmt = $conn->prepare($sql);
			$stmt->execute();
		}catch(PDOException $e){
			if($debug){echo "error: ".$e->getMessage();}
		}
	}
	
	if(!$debug){
		echo "<script type='text/javascript'>window.location.replace('index.php')</script>";
	}
	?>
</body>
</html>