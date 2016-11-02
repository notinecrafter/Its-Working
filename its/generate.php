<!DOCTYPE HTML>
<body>
	<?php
	//connect to SQL database
	$servername = 'localhost';
	$username = 'user';
	$password = 'testSQLpassword';
	$dbname = 'sample';
	$conn = new mysqli($servername, $username, $password, $dbname);
	//check connection:
	if($conn->connect_error){
		echo "<span style='color: #f00'>Connection to SQL database failed: ".$conn->connect_error."<br/>Probeer het later nog eens. Dit kaartje is niet geldig. </span>";
		exit();
	}

	$users = array();
	$hashes = array();
	for($x = 0; $x <= 20; $x++){
		$id = mt_rand(12900, 12999);
		$password = md5("password");
		$users[] = $id;
		$hashes[] = $password;
		$sql = "INSERT INTO user(id, password)
		VALUES(".strval($id).",'".$password."')";
		if($conn->query($sql) === TRUE){
			echo "user ".strval($id)." generated<br/>";
		}else{
			echo $conn->error;
		}
	}
	?>
</body>