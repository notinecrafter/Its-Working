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
		if($debug){
			echo "<p>connected to internal database</p>";
			$conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
		}
	}catch(PDOException $e){
		if($debug){
			echo "<br/>connection failed: ".$e->getMessage();
		}
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
if(!isset($preferences["dispname"])){
	$preferences["dispname"] = $_SESSION["user"];
}
?>
<!DOCTYPE html>
<html>
<head>
	<title>It's Working build <?php echo date("o")."W".date("W");?></title>
	<link rel="stylesheet" type="text/css" href="<?php echo $preferences["style"];?>.css">
</head>
<body>
	<div id='header'>
		<img id='logo' src='logo-<?php echo $preferences["style"]; ?>.png'/>
		<div id='headright'>
			<table class='structuretable'><tbody><tr>
				<td><button id='settings'>settings</button></td>
	<?php
	//echo login info
	if($_SESSION["user"] == ""){
		echo "<script type='text/javascript'>window.location.replace('login.php')</script>"; //not the nicest way, but should work.
	}else{
		echo "<td><span id='username'>".$preferences["dispname"]."</span></td>";
		echo "<td><form action='logout.php' method='post' id='logoutform'><input type='submit' value='log out'></form></td>";
	}
	?>
		</tr></tbody></table>
		</div>
	</div>
	<?php

	

	//exiting php for basic markup ?>
	<div id='main'>
	<?php
	//function for detecting firefox. need this later on for the datepicker
	function isfirefox(){
		if(strpos(strtolower($_SERVER["HTTP_USER_AGENT"]), "firefox") !== false){
			return true;
		}else{
			return false;
		}
	}

	function convert_datetime($str){
		list($date, $time) = explode(' ', $str);
		list($year, $month, $day) = explode('-', $date);
		list($hour, $minute, $second) = explode(':', $time);

		$timestamp = mktime(intval($hour), intval($minute), intval($second), intval($month), intval($day), intval($year));

		return $timestamp;
	}

	//function for echoing the folders. this is a function so it can recurse on itself.
	function showfiles($group, $files, $admin, $prefix = ""){
		global $conn;
		global $preferences;
		echo "<ul id='ul/".$group["name"].$prefix."'>";
		$folders = array();
		//first catch the folders and echo anything else in the main directory
		foreach($files as $file){
			if($file["location"] == "/"){
				if($file["filetype"] == "folder"){
					$folders[] = $file;
				}else if($file["filetype"] == "project"){
					//filter out the projects first
					if($admin){
						//display submissions and everything
						try{
							$sql = "SELECT * FROM Group".$group["name"].$file["filename"].";";
							$stmt = $conn->prepare($sql);
							$stmt->execute();
							$stmt->setFetchMode(PDO::FETCH_ASSOC);
							echo "<li><table class='structuretable'><tbody><tr>";
							echo "<td>Project ".$file["filename"].":</td>";
							echo "<td><form action='removeproject.php' method='post' enctype='multipart/form-data'><input type='hidden' name='group' value='".$group["name"]."'/><input type='hidden' name='filename' value='".$file["filename"]."'/><input type='hidden' name='location' value='".$prefix.$file["location"]."'/><input type='submit' class='deletebutton' value=''/></form></td>";
							if(isset($file["properties"])){
								eval("\$properties = array(".$file["properties"].");");
								if(isset($properties["deadline"])){
									echo "<td>deadline: ".$properties["deadline"]."</td>";
								}
							}
							echo "</tr></tbody></table>";
							echo "<table><tr><th>User</th><th>File</th><th>time</th><th>grade</th><th>change grade?</th>";
							foreach($stmt->fetchAll() as $submission){
								$ownership = array();
								eval("\$members = array(".$submission["owner"].");");
								foreach($members as $member){
									try{
										$sql = "SELECT preferences FROM users WHERE id='$member'";
										$stmt = $conn->prepare($sql);
										$stmt->execute();
										$prefs = $stmt->fetchAll();
										foreach($prefs as $pref){
											eval("\$memberprefs = array(".$pref["preferences"].");");
											if(isset($memberprefs["dispname"])){
												$membername = $memberprefs["dispname"];
											}else{
												$membername = $member;
											}
										}
									}
									catch(PDOException $e){
										if($debug){
											echo "Error: ".$e->getMessage."<br/>";
										}
										//in case PDO throws an exception, it's most likely just an empty set. This means a non-existing username has somehow gotten into the table.
										$membername = "<span style='color: #ff0000'>ERROR: User does not exist</span>";
									}
									//now append that to the array of owners
									array_push($ownership, $membername);
								}
								$ownership = implode(", ", $ownership);
								//check if it was submitted in time
								if(isset($file["properties"])){
									if(convert_datetime($submission["time"]) > convert_datetime($properties["deadline"])){
										$timecolor = "#ff0000";
									}else{
										$timecolor = "#00ff00";
									}
								}else{
									//if there is no deadline
									$timecolor = "inherited";
								}
								echo "<tr><td>".$ownership."</td><td><a href='files/".$group["name"]."/".$prefix.$file["location"]."/project".$file["filename"]."/".$submission["filename"]."'>".$submission["filename"]."</a></td><td style='color: ".$timecolor."'>".$submission["time"]."</td><td>".$submission["grade"]."</td><td><form action='grade.php' method='post'><input type='hidden' name='group' value='".$group["name"]."'><input type='hidden' name='project' value='".$file["filename"]."'><input type='hidden' name='id' value='".$submission["ID"]."'><input name='foo' type='text'/><input type='submit' value='change'></form></td></tr>";
							}
							echo "</table></li>";
						}catch(PDOException $e){
							echo "<p>error: ".$e->getMessage()."</p>";
						}
						
					}else{
						echo "<li><table class='structuretable'><tbody><tr><td>Project: ".$file["filename"]."</td>";
						if(isset($file["properties"])){
							eval("\$properties = array(".$file["properties"].");");
							if(isset($properties["deadline"])){
								echo "<td>deadline: ".$properties["deadline"]."</td>";
							}
						}
						echo "<td><a href='#' id='".$group["name"].$prefix."/".$file["filename"]."' class='collapser'>&#x25B6;</a></td>";
						echo "</tr></tbody></table>";
						//see if user has already submitted something
						$submitted = false;
						try{
							$sql = "SELECT * FROM Group".$group["name"].$file["filename"].";";
							$stmt = $conn->prepare($sql);
							$stmt->execute();
							$stmt->setFetchMode(PDO::FETCH_ASSOC);
							foreach($stmt->fetchAll() as $submission){
								//since the owner list is actually an array we need to explode this and check the members
								$owners = explode(',', $submission["owner"]);
								foreach($owners as $owner){
									if($owner == "'".$_SESSION["user"]."'"){
										//code to get displaynames copied from the form maker below
										//owners are called members to avoid duplicate variable names
										$ownership = array();
										eval("\$members = array(".$submission["owner"].");");
										foreach($members as $member){
											try{
												$sql = "SELECT preferences FROM users WHERE id='$member'";
												$stmt = $conn->prepare($sql);
												$stmt->execute();
												$prefs = $stmt->fetchAll();
												foreach($prefs as $pref){
													eval("\$memberprefs = array(".$pref["preferences"].");");
													if(isset($memberprefs["dispname"])){
														$membername = $memberprefs["dispname"];
													}else{
														$membername = $member;
													}
												}
											}
											catch(PDOException $e){
												if($debug){
													echo "Error: ".$e->getMessage."<br/>";
												}
												//in case PDO throws an exception, it's most likely just an empty set. This means a non-existing username has somehow gotten into the table.
												$membername = "<span style='color: #ff0000'>ERROR: User does not exist</span>";
											}
											//now append that to the array of owners
											array_push($ownership, $membername);
										}
										$ownership = implode(", ", $ownership);
										//check if it was submitted in time
										if(isset($properties["deadline"])){
											if(convert_datetime($submission["time"]) > convert_datetime($properties["deadline"])){
												$timecolor = "#ff0000";
											}else{
												$timecolor = "#00ff00";
											}
										}else{
											$timecolor = "inherited";
										}
										echo "<table id='ul/".$group["name"].$prefix."/".$file["filename"]."' class='projecttable'><tr><th>Submission</th><th>Owner(s)</th><th>Time</th><th>Grade</th></tr><tr><td><a href='files/".$group["name"]."/".$prefix.$file["location"]."/project".$file["filename"]."/".$submission["filename"]."'>".$submission["filename"]."</a></td><td>".$ownership."</td><td style='color: ".$timecolor."'>".$submission["time"]."</td><td>".$submission["grade"]."</td></tr></table>";
										$submitted = true;
									}
								}
								//legacy code for legacy cases where the owner column is not an array.
								if($submission["owner"] == $_SESSION["user"]){
									echo "<table id='ul/".$group["name"].$prefix."/".$file["filename"]."' class='projecttable'><tr><th>Submission</th><th>grade</th></tr><tr><td><a href='files/".$group["name"]."/".$prefix.$file["location"]."/project".$file["filename"]."/".$submission["filename"]."'>".$submission["filename"]."</a></td><td>".$submission["grade"]."</td></tr></table>";
									$submitted = true;
								}
							}
						}catch(PDOException $e){
							echo "error: ".$e->getMessage()."<br/>";
						}
						if(!$submitted){
							//begin student upload form
							echo "<table id='ul/".$group["name"].$prefix."/".$file["filename"]."' class='projecttable'><tbody><tr><td><form action='submit.php' method='post' enctype='multipart/form-data'><input type='hidden' name='group' value='".$group["name"]."'/><input type='hidden' name='project' value='".$file["filename"]."'/><input type='hidden' name='location' value='".$prefix.$file["location"]."'/><input type='file' name='fileToUpload' id='fileToUpload'/>
							Select additional members<select name='members[]' multiple>";
							//make the group select options
							try{
								$sql = "SELECT members FROM Groups WHERE name = '".$group["name"]."';";
								$stmt = $conn->prepare($sql);
								$stmt->execute();
								$stmt->setFetchMode(PDO::FETCH_ASSOC);
								$members = $stmt->fetchAll();
								//I'm probably doing this the wrong way, but whatever. It's the only way I know and it works
							}catch(PDOException $e){
								if($debug){
									echo "Error: ".$e->getMessage."<br/>";
								}
							}
							//remove the parentheses from the SQL
							eval("\$members = array(".$members[0]["members"].");");
							//this code will be really useful in a second
							$sql = "SELECT owner from Group".$group["name"].$file["filename"];
							$stmt = $conn->prepare($sql);
							$stmt->execute();
							$stmt->setFetchMode(PDO::FETCH_ASSOC);
							$owners = $stmt->fetchAll();
							//remove the parentheses from the SQL. legacy entries not supported here.
							eval("\$owners = array(".$owners[0]["owner"].");");

							foreach($members as $member){
								//get the displayname from user preferences
								try{
									$sql = "SELECT preferences FROM users WHERE id='$member'";
									$stmt = $conn->prepare($sql);
									$stmt->execute();
									$prefs = $stmt->fetchAll();
									foreach($prefs as $pref){
										eval("\$memberprefs = array(".$pref["preferences"].");");
										if(isset($memberprefs["dispname"])){
											$membername = $memberprefs["dispname"];
										}else{
											$membername = $member;
										}
									}
								}
								catch(PDOException $e){
									if($debug){
										echo "Error: ".$e->getMessage."<br/>";
									}
								}
								if($member != $_SESSION["user"]){
									//check if the member already submitted something. TODO: verify this backend.
									$membersubmit = false;
									foreach($owners as $owner){
										if($member == $owner){
											$membersubmit = true;
										}
									}
									if(!$membersubmit){
										echo "<option value='$member'>$membername</option>";
									}
								}
							}
							echo "</select><input type='submit' value='submit'/></form></td></tr></tbody></table>";
						}
					}
				}else{
					//echo the regular files
					echo "<li><a href='files/".$group["name"].$prefix.$file["location"].$file["filename"]."'>".$file["filename"]."</a>";
					if($admin){
						//TODO: make deleting and adding a single file
						echo "<table class='hidden'><tbody><tr><td><form action='remove.php' method='post' enctype='multipart/form-data'><input type='hidden' name='group' value='".$group["name"]."'/><input type='hidden' name='filename' value='".$file["filename"]."'/><input type='hidden' name='location' value='".$prefix.$file["location"]."'/><input type='submit' class='deletebutton' value=''/></form></td></tr></tbody></table>";
					}
					echo "</li>";
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
				//explode(...)[1] causes compatibility issues somehow
				$test = explode("/", $file["location"]);
				if($test[1] == $folder["filename"]){
					$file["location"] = str_replace("/".$folder["filename"], "", $file["location"]);
					$folderfiles[] = $file;
				}
			}
			echo "<li>";
			if($admin){
				echo "<img src='icons/edit.ico' class='editbutton' id='edit/".$group["name"].$prefix."/".$folder["filename"]."'/>";
			}
			echo $folder["filename"]."<a href='#' id='".$group["name"].$prefix."/".$folder["filename"]."' class='collapser'>&#x25B6;</a>";
			if($admin){
				echo "<div id='menu/".$group["name"].$prefix."/".$folder["filename"]."'>";
				echo "<table class='hidden' id='icontable'><tbody><td><img class='addFileIcon' src='icons/add_file.ico'/></td><td><img class='addProjectIcon' src='icons/doctor_suitecase.ico'/></td><td><img class='addFolderIcon' src='icons/add_folder.ico'/></td><td><img class='deleteIcon' src='icons/cross.ico'/></td><td><form action='create.php' method='post' enctype='multipart/form-data'><input type='hidden' name='location' value='".$prefix."/".$folder["filename"]."/'><input type='hidden' name='group' value='".$group["name"]."'><input type='hidden' name='create' value='false'><input id='newTextSubmit' type='submit' value=''></form></td></tr></tbody></table>";
				//TODO: make deleting and adding into a single file
				echo "<div class='addfile'><form action='upload.php' method='post' enctype='multipart/form-data'>add file? <input type='file' name='fileToUpload' id='fileToUpload'><input type='hidden' name='group' value='".$group["name"]."'><input type='hidden' name='location' value='".$prefix."/".$folder["filename"]."/'><input type='submit' value='upload'></form></div>";
				echo "<div class='addproject'><form action='project.php' method='post' enctype='multipart/form-data'>add project? name: <input type='text' name='name' placeholder='name'> deadline";
				if(isfirefox()){echo ' (YYYY-MM-DD HH:MM:SS)';}
				$date = explode("+", date("c"));
				echo ":<input type='datetime-local' name='deadline' min='".$date[0]."'> <input type='hidden' name='group' value='".$group["name"]."'><input type='hidden' name='location' value='".$prefix."/".$folder["filename"]."/'><input type='submit' value='create'></form></div>";
				echo "<div class='addfolder'><form action='folder.php' method='post' enctype='multipart/form-data'>add folder? <input type='hidden' name='action' value='add'><input type='text' name='name' placeholder='name'><input type='hidden' name='group' value='".$group["name"]."'><input type='hidden' name='location' value='".$prefix."/".$folder["filename"]."/'><input type='submit' value='create'></form></div>";
				echo "<div class='deletefolder'><form action='rmdir.php' method='post' enctype='multipart/form-data'><input type='hidden' name='action' value='remove'><input type='hidden' name='name' value='".$folder["filename"]."'><input type='hidden' name='group' value='".$group["name"]."'><input type='hidden' name='location' value='".$prefix."/'><input type='submit' value='delete folder'></form></div>";
				echo "<div class='addtext'><form action='create.php' method='post' enctype='multipart/form-data'><input type='hidden' name='location' value='".$prefix."/".$folder["filename"]."/'><input type='hidden' name='group' value='".$group["name"]."'><input type='hidden' name='create' value='false'><input type='submit' value='new text'></form></div>";
				echo "</div>";
			}
			echo "</li>";
			//and apply the files to itself
			//it sets the previous prefix + the folder name as the new prefix so that the URL's don't screw up.
			showfiles($group, $folderfiles, $admin, $prefix."/".$folder["filename"]);
		}
		echo "</ul>";
	}

	//Echo link to admin.php if user is admin

	if($_SESSION["type"] == 2){
		echo "<p>click <a href='admin.php'>here</a> for administrator access</p>";
	}

	//retrieve group info from external db
	try{
		$sql = "SELECT * FROM groups";
		$stmt = $conn->prepare($sql);
		$stmt->execute();
		$stmt->setFetchMode(PDO::FETCH_ASSOC);
		if($debug){
			echo "<p>selected from group db</p>"; //TODO: should only echo in debug mode
		}
	}catch(PDOException $e){
		if($debug){
			echo "<p>selection error: ".$e->getMessage()."</p>";
		}
	}
	//save fetched data to array because fetching the same $stmt twice doesn't work, and I need it again for the admin check. No use in bothering the db twice for the same data
	$fetched = $stmt->fetchAll(); 
	//see which groups you are a member of
	//TODO: make a function out of this instead of copying it later for the teachers...
	foreach($fetched as $group){
		eval("\$members = array(".$group["members"].");");
		foreach($members as $member){
			if($member === $_SESSION["user"]){
				//list directory
				try{
					$sql = "SELECT * FROM group".$group["name"];
					$stmt = $conn->prepare($sql);
					$stmt->execute();
					$stmt->setFetchMode(PDO::FETCH_ASSOC);
					$files = $stmt->fetchAll();
				}catch(PDOException $e){
					echo "<p>error: ".$e->getMessage()."</p>";
				}
				echo "<h4>".$group["name"]."<a href='#' class='collapser' id='".$group["name"]."'>&#x25B6;</a></h4>";
				showfiles($group, $files, false);
			}
		}
	}
	//see which groups you are an admin of. only works for teachers or admins.
	if($_SESSION["type"] >= 1){
		foreach($fetched as $group){
			eval("\$members = array(".$group["admins"].");");
			foreach($members as $member){
				if($member === $_SESSION["user"]){
					//list directory
					try{
						$sql = "SELECT * FROM group".$group["name"];
						$stmt = $conn->prepare($sql);
						$stmt->execute();
						$stmt->setFetchMode(PDO::FETCH_ASSOC);
						$files = $stmt->fetchAll();
					}catch(PDOException $e){
						if($debug){
							echo "<p>error: ".$e->getMessage()."</p>";
						}
					}
					echo "<h4><img src='icons/edit.ico' class='editbutton' id='edit/".$group["name"]."''/>".$group["name"]."<a href='#' class='collapser' id='".$group["name"]."'>&#x25B6;</a></h4>";
					echo "<div id='menu/".$group["name"]."'>";
					echo "<table class='hidden' id='icontable'><tbody><td><img class='addFileIcon' src='icons/add_file.ico'/></td><td><img class='addFolderIcon' src='icons/add_folder.ico'/></td><td><form action='create.php' method='post' enctype='multipart/form-data'><input type='hidden' name='location' value='/'><input type='hidden' name='group' value='".$group["name"]."'><input type='hidden' name='create' value='false'><input id='newTextSubmit' type='submit' value=''></form></td></tr></tbody></table>";
					//TODO: make deleting and adding a single file
					echo "<div class='addfile'><form action='upload.php' method='post' enctype='multipart/form-data'>add file? <input type='file' name='fileToUpload' id='fileToUpload'><input type='hidden' name='group' value='".$group["name"]."'><input type='hidden' name='location' value='/'><input type='submit' value='upload'></form></div>";
					echo "<div class='addfolder'><form action='folder.php' method='post' enctype='multipart/form-data'>add folder? <input type='hidden' name='action' value='add'><input type='text' name='name' placeholder='name'><input type='hidden' name='group' value='".$group["name"]."'><input type='hidden' name='location' value='/'><input type='submit' value='create'></form></div>";
					echo "</div>";
					showfiles($group, $files, true);
				}
			}
		}
	}
	?>
	</div>

	<div style='width: 100%' id='test'><!--this element is only here for the responsive page width thing to get the page width--></div>
	<script
	src="https://code.jquery.com/jquery-3.1.0.min.js"
	integrity="sha256-cCueBR6CsyA4/9szpPfrX3s49M9vUU5BgtiJj06wt/s="
	crossorigin="anonymous"></script>
	<script src='js.cookie.js'></script>
	<script type='text/javascript'>
	// if(parseInt(document.getElementById("test").offsetWidth) >= 1250){
	// 		document.getElementById("main").style.width = "875px";
	// 	}else{
	// 		document.getElementById("main").style.width = "70%";
	// 	}
	// function resize(){
	// 	if(parseInt(document.getElementById("test").offsetWidth) >= 1250){
	// 		document.getElementById("main").style.width = "875px";
	// 	}else{
	// 		document.getElementById("main").style.width = "70%";
	// 	}
	// }

	function main(){
		//hide all lists once the page is loaded
		$('ul').hide();
		$('.projecttable').hide();
		//reopen  the lists that have a positive cookie associated with them
		var cookies = Cookies.get();
		for(var cookie in cookies){
			if(cookie.slice(0, 9) == "collapse/" && cookies[cookie] == 1){
				var dir = cookie.slice(9)
				$("ul[id='ul/" + dir + "']").show();
				$("table[id='ul/" + dir + "']").show();
				//also set the arrows for those
				$("a[id='" + dir + "']").html("&#x25BC;");
			}
		}
		//now for the code to open it back again if the user so desires
		$('.collapser').click(function(event){
			event.preventDefault();
			var id = event.target.id;
			//some weird notation here (wildcard notation without a wildcard): that's so jQuery can work with the slashes in the Id names
			$("ul[id='ul/" + id + "']").toggle();
			//do the same for tables in case of project
			$("table[id='ul/" + id + "']").toggle();
			//set the cookies as well as the arrows
			if(Cookies.get("collapse/" + id) == 1){
				Cookies.set("collapse/" + id, 0);
				$("a[id='" + id + "']").html("&#x25B6;");
			}else{
				Cookies.set("collapse/" + id, 1);
				$("a[id='" + id + "']").html("&#x25BC;");
			}
		});
		

		//the same code as above, but for edit menus
		//hide all menus once the page is loaded
		$("div[id^='menu/']").hide();
		//reopen  the lists that have a positive cookie associated with them
		var cookies = Cookies.get();
		for(var cookie in cookies){
			if(cookie.slice(0, 5) == "edit/" && cookies[cookie] == 1){
				var dir = cookie.slice(5)
				$("div[id='menu/" + dir + "']").show();
			}
		}
		//now for the code to open it back again if the user so desires
		$('.editbutton').click(function(event){
			event.preventDefault();
			var id = event.target.id.slice(5);
			//some weird notation here (wildcard notation without a wildcard): that's so jQuery can work with the slashes in the Id names
			$("div[id='menu/" + id + "']").toggle();
			//set the cookies as well as the arrows
			if(Cookies.get("edit/" + id) == 1){
				Cookies.set("edit/" + id, 0);
			}else{
				Cookies.set("edit/" + id, 1);
			}
		});

		//code to make the edit menus open
		$('.addFileIcon').click(function(event){
			$(this).parent().parent().parent().parent().parent().children('.addfile').toggle();
			$(this).parent().parent().parent().parent().parent().children('.addproject').hide();
			$(this).parent().parent().parent().parent().parent().children('.addfolder').hide();
			$(this).parent().parent().parent().parent().parent().children('.deletefolder').hide();
		})
		$('.addProjectIcon').click(function(event){
			$(this).parent().parent().parent().parent().parent().children('.addproject').toggle();
			$(this).parent().parent().parent().parent().parent().children('.addfile').hide();
			$(this).parent().parent().parent().parent().parent().children('.addfolder').hide();
			$(this).parent().parent().parent().parent().parent().children('.deletefolder').hide();
		})
		$('.addFolderIcon').click(function(event){
			$(this).parent().parent().parent().parent().parent().children('.addfolder').toggle();
			$(this).parent().parent().parent().parent().parent().children('.addfile').hide();
			$(this).parent().parent().parent().parent().parent().children('.addproject').hide();
			$(this).parent().parent().parent().parent().parent().children('.deletefolder').hide();
		})
		$('.deleteIcon').click(function(event){
			$(this).parent().parent().parent().parent().parent().children('.deletefolder').toggle();
			$(this).parent().parent().parent().parent().parent().children('.addfile').hide();
			$(this).parent().parent().parent().parent().parent().children('.addproject').hide();
			$(this).parent().parent().parent().parent().parent().children('.addfolder').hide();
		})
	}
	$(document).ready(main);

	</script>
	<script src="popper.js"></script>
	<script type="text/javascript">
	var content = "<div style='padding: 5px;'><h4 style='text-align: center;'>Settings</h4> <p>Style</p> <form action='settings.php' method='post'> <input type='radio' name='style' value='light' <?php if($preferences["style"]=='light'){ echo "checked"; } ?>>Light<br/> <input type='radio' name='style' value='dark' <?php if($preferences["style"]=='dark'){ echo "checked"; } ?>>Dark<br/> <span style='display: none'><input type='radio' name='style' value='1337'>1337<br/></span> Display Name:</br><input type='text' ' name='dispname' value='<?php echo $preferences["dispname"]; ?>'><br/><input type='submit' value='Submit'> </form> </div>"
	var reference = document.querySelector('#settings');
	popperActive = false
	$('#settings').click(function(){
		if(popperActive){
			//Is this incredibly stupid? Yes. Does it work? Also yes.
			$('.popper').remove()
			popperActive = false
		}else{
			var popper = new Popper(
			    reference,
			    {
			        content: content,
			        contentType: 'html'
			    },
			    {
			        // popper options here
			    }
			);
			popperActive = true
		}
	});
		
	</script>
</body>
</html>