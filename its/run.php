<?php
$file = fopen("setup.ini", 'r');
$setup = fread($file, filesize('setup.ini'));
eval($setup);
if($debug){
    echo "evaluated settings<br/>";
}

//connect to external db 1
try {
	$ext1_conn = new PDO("$ext1_server_type:host=$ext1_db_server;port=$ext1_port;dbname=$ext1_db_name", $ext1_user, $ext1_password);
	// set the PDO error mode to exception
	$ext1_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    if($debug){
        echo "Connected to connection 1<br/>";
    }
}catch(PDOException $e){
    if($debug){
        echo "Connection failed: " . $e->getMessage();
    }
}
//connect to external db 2
try {
	$ext2_conn = new PDO("$ext2_server_type:host=".$ext2_db_server.";dbname=".$ext2_db_name, $ext2_user, $ext2_password);
	// set the PDO error mode to exception
	$ext2_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    if($debug){
        echo "Connected to connection 2<br/>";
    }
}catch(PDOException $e){
    if($debug){
        echo "Connection failed: " . $e->getMessage();
    }
}

//connect to external db 3
try {
	$ext3_conn = new PDO("$ext3_server_type:host=".$ext3_db_server.";dbname=".$ext3_db_name, $ext3_user, $ext3_password);
	// set the PDO error mode to exception
	$ext3_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    if($debug){
        echo "Connected to connection 3<br/>";
    }
}catch(PDOException $e){
    if($debug){
        echo "Connection failed: " . $e->getMessage();
    }
}

echo hash("sha512","p@ssw0rd");
try{
	$sql = "INSERT INTO passwords(id, password, type)
	VALUES('Floris','".hash("sha512","p@ssw0rd")."',2)";
	$stmt = $ext3_conn->prepare($sql);
	$stmt->execute();
}catch(PDOException $e){
	echo "error: ".$e->getMessage();
}
