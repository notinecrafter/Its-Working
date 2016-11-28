<?php
session_start();

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

//read the prefences
$sql = "SELECT preferences FROM users where id = '".$_SESSION["user"]."'";
$stmt = $conn->prepare($sql);
$stmt->execute();
$prefs = $stmt->fetchAll();
foreach($prefs as $pref){
	$preferences = $pref["preferences"];
}
eval("\$preferences = array(".$preferences.");");
if(!isset($preferences["style"])){
	//set the style so it doesn't crash everything if undefined
	$preferences["style"] = "light";
}
?>
<!DOCTYPE html>
<html>
<head>
	<title>It's Working build <?php echo date("o")."W".date("W");?></title>
	<link rel="stylesheet" type="text/css" href="<?php echo $preferences["style"]; ?>.css">
</head>
<body onresize='resize()'>
	<div id='header'>
		<img id='logo' src='logo-<?php echo $preferences["style"]; ?>.png'/>
		<table id='usertable'><tbody><tr>
	<?php
	//echo login info
	if($_SESSION["user"] == ""){
		echo "<script type='text/javascript'>window.location.replace('login.php')</script>"; //not the nicest way, but should work.
	}else{
		echo "<td>".$_SESSION["user"]."</td>";
		echo "<td><form action='logout.php' method='post'><input type='submit' value='log out'></form></td>";
	}
	?>
		</tr></tbody></table>
	</div>
	<div id='main'>
	<?php
	//TODO: security

	if($_POST["create"] == "true"){
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
	
		//require the markdown converter
		require_once("parsedown/parsedown.php");
		$file = fopen("files/".$_POST["group"]."/".$_POST["location"].$_POST["title"].".html", "w");
		fwrite($file, "<h1>".htmlspecialchars($_POST["title"])."</h1>");
		$ParseDown = new Parsedown();
		fwrite($file, $ParseDown->text($_POST["text"]));
		fclose($file);
		echo "The file ".$_POST["title"]." has been created.";
		try{
			//delete the already existing entry for this file to prevent duplicate entries, as fopen can simply overwrite the files, unlike upload.
			$sql = "DELETE FROM Group".$_POST["group"]." WHERE filename = '".$_POST["title"].".html' and location='".$_POST['location'].";";
			$stmt = $conn->prepare($sql);
			$stmt->execute();
			echo "<p>overwriting</p>";
		}catch(PDOException $e){
			echo "<p>creating new file</p>";
		}
		try{
	    	//add create file to database. This is done after the file creation because the creation is more likely to go wrong...
	    	$sql = "INSERT INTO Group".$_POST['group']."(filename, filetype, location) VALUES ('".$_POST["title"].".html"."','text/html','".$_POST['location']."');";
	    	$stmt = $conn->prepare($sql);
	    	$stmt->execute();
	    }catch(PDOException $e){
	    	echo "error: ".$e->getMessage().$e."<br/>";
	    }
	    if(!$debug){
			echo "<script type='text/javascript'>window.location.replace('index.php')</script>";
		}
	}else{
		echo "<form id='form 'action='create.php' method='post' enctype='multipart/form-data'><h1>create new file</h1><input type='text' name='title' placeholder='title'><br/><textarea name='text' rows='10' style='width: 100%;'></textarea><input type='hidden' value='true' name='create'><input type='hidden' name='group' value='".$_POST["group"]."'><input type='hidden' name='location' value='".$_POST["location"]."'><br/><input type='submit' value='create file'></form>";
	}
	?>
	<p>It's Working uses markdown. Formatting help:</p>
	<p>
		<table class="md">
			<tbody>
				<tr style="background-color: #ffff99; text-align: center; color: #000000;"><td><em>you type:</em></td><td><em>you see:</em></td></tr>
				<tr><td>*italics*</td><td><em>italics</em></td></tr>
				<tr><td>**bold**</td><td><b>bold</b></td></tr>
				<tr><td>[website](https://example.com)<br/><b><i>Always specify the protocol! forgetting the http:// will break the link</i></b></td><td><a href="http://example.com">website</a></td></tr>
				<tr><td>Lines starting with four spaces<br>are treated like code:<br><br><span class="spaces">&nbsp;&nbsp;&nbsp;&nbsp;</span>if 1 * 2 &lt; 3:<br><span class="spaces">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>print "hello, world!"<br></td><td>Lines starting with four spaces<br>are treated like code:<br><pre>if 1 * 2 &lt; 3:<br>&nbsp;&nbsp;&nbsp;&nbsp;print "hello, world!"</pre></td></tr>
				<tr><td>~~strikethrough~~</td><td><strike>strikethrough</strike></td></tr>
			</tbody>
		</table>
	</p>
	</div>
	<div style='width: 100%' id='test'><!--this element is only here for the responsive page width thing to get the page width--></div>
</body>
<script type="text/javascript">
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
</html>