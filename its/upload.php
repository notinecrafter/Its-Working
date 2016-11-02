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
		if($debug){
			echo "<p>connected to internal database</p>"; //TODO: should only echo in debug modeIcICTrek
		}
	}catch(PDOException $e){
		if($debug){
			echo "<br/>connection failed: ".$e->getMessage(); //TODO: should only echo in debug mode
		}
	}

	//begin upload process. code liberally borrowed from http://www.w3schools.com/php/php_file_upload.asp
	$target_dir = "files/".$_POST["group"]."/".$_POST["location"];
	$target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
	$uploadOk = 1;
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
	    	if($debug){
	        	echo "The file ". basename( $_FILES["fileToUpload"]["name"]). " has been uploaded.";
	        }
	        //add uploaded file to database. This is done after the file upload because the upload is more likely to go wrong...
	        try{
	        	$sql = "INSERT INTO Group".$_POST['group']."(filename, filetype, location) VALUES ('".basename($_FILES["fileToUpload"]["name"])."','text/plain','".$_POST['location']."');";
	        	$stmt = $conn->prepare($sql);
	        	$stmt->execute();
	        }catch(PDOException $e){
	        	if($debug){
	        		echo "error: ".$e->getMessage().$e."<br/>";
	        	}
	        }
	    } else {
	    	if($debug){
	        	echo "Sorry, there was an error uploading your file.";
	        }
	    }
	}
	if(!$debug){
		echo "<script type='text/javascript'>window.location.replace('index.php')</script>";
	}
	?>
</body>
</html>