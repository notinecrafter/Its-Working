<?php
session_start();
    
//evaluate setup.ini
$file = fopen("setup.ini", 'r');
$setup = fread($file, filesize('setup.ini'));
eval($setup);
if($debug){
    echo "evaluated settings<br/>";
}
?>
<!DOCTYPE HTML>
<html>
<head>
    <title>It's Working build <?php echo date("o")."W".date("W");?></title>
    <link rel="stylesheet" type="text/css" href="light.css">
</head>
<body onresize="resize()">
<div id='header'><img id='logo' src='logo-light.png'/></div>
<div id='main'>
	<form action='login.php' method='post'>
        <table id='logintable'><tbody>
            <tr><td>Name:</td><td><input type="text" name="name"/></td></tr>
            <tr><td>Password:</td><td><input type='password' name='password'/></td></tr>
            <tr><td><input type='submit' value='login'/></td></tr>
        </tbody></table>
	</form>
	<?php
    error_reporting(-$debug);
	function sanitize($sql){
		for($k = 0; $k < strlen($sql); $k++){
			$letter = substr($sql, $k, 1);
			if ($letter == ';'){
				echo "<span style='color: #f00;'>ERROR: SQL INJECTION DETECTED. I sanitize my input. Too bad.</span>";
				exit();
			}
			if ($letter == '*'){
				echo "<span style='color: #f00;'>ERROR: SQL INJECTION DETECTED. I sanitize my input. Too bad.</span>";
				exit();
			}
			if ($letter == '='){
				echo "<span style='color: #f00;'>ERROR: SQL INJECTION DETECTED. I sanitize my input. Too bad.</span>";
				exit();
			}
		}
		if ($sql === ''){
			echo "<span style='color: #f00;'>please fill in all fields</span>";
			exit();
		}
		return htmlspecialchars($sql);
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

    //retrieve and sanitize form
	$user = sanitize($_POST["name"]);
	$password = sanitize($_POST["password"]);
	//prevent unneccesary SQL queries from taking up resources
	if($user == ""){
        $try = false;
    }else{
        $try = true;
    }
    if($try){
        //try on db 1
        try{
            $sql1 = "SELECT * FROM $ext1_user_table WHERE $ext1_id_name = '$user' AND ".$ext1_condition;
            $stmt1 = $ext1_conn->prepare($sql1);
            $stmt1->execute();
            $result = $stmt1->setFetchMode(PDO::FETCH_ASSOC);
            foreach($stmt1->fetchAll() as $row){
                //if there are no matches, none of this code will be executed
                if($row[$ext1_id_name] == $user){
                    if($row[$ext1_password_name] == hash($ext1_hash_method, $password)){
                        $_SESSION["user"] = $row[$ext1_id_name];
                        $_SESSION["type"] = $ext1_user_type;
                        echo "<script type='text/javascript'>window.location.replace('index.php')</script>"; //not the nicest way, but should work.
                        exit();
                    }else{
                        echo "<center>Incorrect password or username</center>";
                    }
                }
            }
        }catch(PDOException $e){
            echo "Error: ".$e->getMessage();
            //if I ever add a debug mode switch thingy, this should only be echoed if debug is on. It will result in an error if the user is not found...
        }
        //try on db 2
        try{
            $sql2 = "SELECT * FROM $ext2_user_table WHERE $ext2_id_name = '$user' AND ".$ext2_condition;
            $stmt2 = $ext2_conn->prepare($sql2);
            $stmt2->execute();
            $result = $stmt2->setFetchMode(PDO::FETCH_ASSOC);
            foreach($stmt2->fetchAll() as $row){
                //if there are no matches, none of this code will be executed
                if($row[$ext2_id_name] == $user){
                    if($row[$ext2_password_name] == hash($ext2_hash_method, $password)){
                        $_SESSION["user"] = $row[$ext2_id_name];
                        $_SESSION["type"] = $ext2_user_type;
                        echo "<script type='text/javascript'>window.location.replace('index.php')</script>"; //not the nicest way, but should work.
                        exit();
                    }else{
                        echo "<center>Incorrect password or username</center>";
                    }
                }
            }
        }catch(PDOException $e){
            echo "Error: ".$e->getMessage();
            //if I ever add a debug mode switch thingy, this should only be echoed if debug is on. It will result in an error if the user is not found...
        }
        //try on db 3
        try{
            $sql3 = "SELECT * FROM $ext2_user_table WHERE $ext2_id_name = '$user' AND ".$ext3_condition;
            $stmt3 = $ext3_conn->prepare($sql3);
            $stmt3->execute();
            $result = $stmt3->setFetchMode(PDO::FETCH_ASSOC);
            foreach($stmt3->fetchAll() as $row){
                //if there are no matches, none of this code will be executed
                if($row[$ext3_id_name] == $user){
                    if($row[$ext2_password_name] == hash($ext3_hash_method, $password)){
                        $_SESSION["user"] = $row[$ext2_id_name];
                        $_SESSION["type"] = $ext3_user_type;
                        echo "<script type='text/javascript'>window.location.replace('index.php')</script>"; //not the nicest way, but should work.
                        exit();
                    }else{
                        echo "<center>Incorrect password or username</center>";
                    }
                }
            }
        }catch(PDOException $e){
            if($debug){
                echo "Error: ".$e->getMessage();
            }
        }
    	//if the login returns no results:
    	echo "<center>Incorrect password or username lolwut</center>";
    }
	?>
</div>
<div style='width: 100%' id='test'><!--this element is only here for the responsive page width thing to get the page width--></div>
<script type='text/javascript'>
if(parseInt(document.getElementById("test").offsetWidth) >= 1250){
    document.getElementById("main").style.width = "875px";
}else{
    document.getElementById("main").style.width = "70%";
}
function resize(){
    if(parseInt(document.getElementById("test").offsetWidth) >= 1250){
        document.getElementById("main").style.width = "875px";
    }else{
        document.getElementById("main").style.width = "70%";
    }
}
</script>
</body>
</html>