<!DOCTYPE HTML>
<html>
<body>
	<?php
		error_reporting(-1);

		echo "php running in ".error_reporting()." mode <br/>";
		//evaluate setup.ini
		$file = fopen("setup.ini", 'r');
		$setup = fread($file, filesize('setup.ini'));
		eval($setup);
		
		echo "evaluated settings<br/>";
		
		//connect to external db 1
		try {
    		$ext1_conn = new PDO("$ext1_server_type:host=$ext1_db_server;port=$ext1_port;dbname=$ext1_db_name", $ext1_user, $ext1_password);
    		// set the PDO error mode to exception
    		$ext1_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    		echo "Connected to connection 1<br/>";
    	}catch(PDOException $e){
    		echo "Connection failed: " . $e->getMessage();
    	}
		//connect to external db 2
		try {
    		$ext2_conn = new PDO("$ext2_server_type:host=".$ext2_db_server.";dbname=".$ext2_db_name, $ext2_user, $ext2_password);
    		// set the PDO error mode to exception
    		$ext2_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    		echo "Connected to connection 2<br/>";
    	}catch(PDOException $e){
    		echo "Connection failed: " . $e->getMessage();
    	}

		//connect to external db 3
		try {
    		$ext3_conn = new PDO("$ext3_server_type:host=".$ext3_db_server.";dbname=".$ext3_db_name, $ext3_user, $ext3_password);
    		// set the PDO error mode to exception
    		$ext3_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    		echo "Connected to connection 3<br/>";
    	}catch(PDOException $e){
    		echo "Connection failed: " . $e->getMessage();
    	}

		//connect to internal db
		try{
			$conn = new PDO("$server_type:host=$db_server;dbname=$db_name", $db_user, $db_password);
			echo "connected to internal database<br/>";
		}catch(PDOException $e){
			echo "connection failed: ".$e->getMessage();
		}
		

		//creates new user db (will return non fatal error on repeat run; no need to fix)
		$sql1 = "CREATE TABLE users(
			id VARCHAR(20) PRIMARY KEY,
			preferences VARCHAR(1000),
			memberof VARCHAR(9000),
			membertype INT(1)
			)";
		
		try{
			$conn->exec($sql1);
			echo "succesfully created user database<br/>";
		}catch(PDOException $e){
			echo "error creatin user database: ".$e->getMessage()."<br/>";
			//somehow doesn't report already existing database. Isn't really broken, ain't gonna fix it.
		}

		//adds the users from the external database to the internal one
		function add($ext_user_table, $ext_conn, $ext_id_name, $ext_user_type, $ext_condition){
			global $conn, $server_type;
			$sql2 = "SELECT * FROM ".$ext_user_table." WHERE ".$ext_condition;
			$stmt2 = $ext_conn->prepare($sql2);
			$stmt2->execute();
			$users = $stmt2->setFetchMode(PDO::FETCH_ASSOC);
			if($users === TRUE){
				echo "selected all users from external database<br/>";
			}else{
				echo "error at sql2 (".$sql2."): ";
				echo $ext_conn->error."<br/>";
				//no exit(); because it always resulted in a blank error with MySQLi but somehow still worked...? Maybe figure out why someday. This document has no need for security anyway.
			}
			if(TRUE){
				//if statement is a remnant from the good old days of MySQLi, when the condition used to check if($users->num_rows > 0)
				foreach($stmt2->fetchAll() as $row){
					$sql3 = "INSERT INTO users(id, preferences, memberof, membertype)
					VALUES('".$row[$ext_id_name]."','','',".strval($ext_user_type).")";
					try{
						$conn->exec($sql3);
						//somehow doesn't throw error upon duplicate entry of primary key. Isn't really broken, ain't gonna fix it.
						echo "added user ".$row[$ext_id_name]."<br/>";
					}catch(PDOException $e){
						echo "error: ".$e->getMessage()."<br/>";
					}
				}
			}
		}

		echo "adding external table 1:<br/>";
		add($ext1_user_table, $ext1_conn, $ext1_id_name, $ext1_user_type, $ext1_condition);
		echo "adding external table 2:<br/>";
		add($ext2_user_table, $ext2_conn, $ext2_id_name, $ext2_user_type, $ext2_condition);
		echo "adding external table 3:<br/>";
		add($ext3_user_table, $ext3_conn, $ext3_id_name, $ext3_user_type, $ext3_condition);
	?>
</body>
</html>