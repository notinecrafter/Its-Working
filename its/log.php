<!DOCTYPE HTML>
<html>
<head>
	<title>PWS log</title>
	<style>
		table, th, td {
    		border: 1px solid black;
    		border-collapse: collapse;
		}
		th, td {
    		padding: 5px;
		}
	</style>
</head>
<body>
	<form action='log.php' method='post'>
		<input type='text' name='action' placeholder='action'/>
		<input type='text' name='starttime' placeholder='starttime' id='starttime'/><button onClick='fixStartTime()' type='button'>fix</button>
		<input type='text' name='endtime' placeholder='endtime' id='endtime'/><button onClick='fixEndTime()' type='button'>fix</button><br/>
		<input type='text' name='problem' placeholder='problems?' id='problem'/>
		<input type='text' name='solution' placeholder='solution' id='solution'/><br/>
		<input type='password' name='password' placeholder='password'/>
		<input type='submit' value='register'/>
	</form>
	<script type='text/javascript'>
		function zero(string){
			if(string.length == 1){
				return "0" + string
			}else{
				return string
			}
		}
		function fixStartTime(){
			var currentdate = new Date()
			document.getElementById("starttime").value = currentdate.getFullYear().toString() + "-" + zero((currentdate.getMonth() + 1).toString()) + "-" + zero(currentdate.getDate().toString()) + " " + zero(currentdate.getHours().toString()) + ":" + zero(currentdate.getMinutes().toString()) + ":" + zero(currentdate.getSeconds().toString())
			console.log("test")
		}
		function fixEndTime(){
			var currentdate = new Date()
			document.getElementById("endtime").value = currentdate.getFullYear().toString() + "-" + zero((currentdate.getMonth() + 1).toString()) + "-" + zero(currentdate.getDate().toString()) + " " + zero(currentdate.getHours().toString()) + ":" + zero(currentdate.getMinutes().toString()) + ":" + zero(currentdate.getSeconds().toString())
		}
	</script>
	<?php 
	error_reporting(0);
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
			echo "<span style='color: #f00;'>gelieve alle velden in te vullen</span>";
			exit();
		}
		return htmlspecialchars($sql);
	}
		//connect to db
		$servername = 'localhost';
		$username = 'user';
		$password = 'testSQLpassword';
		$dbname = 'pws';
		$conn = new mysqli($servername, $username, $password, $dbname);
		//check connection:
		if($conn->connect_error){
			echo "<span style='color: #f00'>Connection to SQL database failed: ".$conn->connect_error;
			exit();
		}

		$action = sanitize($_POST['action']);
		$starttime = sanitize($_POST['starttime']);
		$endtime = sanitize($_POST['endtime']);
		$problem = sanitize($_POST['problem']);
		$solution = $_POST['solution'];
		$password = sanitize($_POST['password']);
		
		if($password == ""){

		}else if(sha1($password) == '3ab714ecca2984d1d5f74dc7e1838917afb61ab1'){
			$sql='INSERT INTO log(action, starttime, endtime, problem, solution)
			VALUES("'.$action.'","'.$starttime.'","'.$endtime.'","'.$problem.'","'.$solution.'")';
			if($conn->query($sql) === TRUE){
				echo "registered";
			}else{
				echo $conn->error;
			}
		}else{
			echo "<br/><span style='color: #f00;'>Wrong password</span><br/>";
		}

		$select = 'SELECT * FROM log';
		$log = $conn->query($select);
	?>
	<table>
		<tr>
			<th>Action</th>
			<th>Problem</th>
			<th>Solution</th>
			<th>StartTime</th>
			<th>EndTime</th>
			<th>&#x394;t (h)</th>
		</tr>
	<?php
		$total = 0;
		function hours($date0, $date1){
			$date0 = explode(" ", $date0);
			$date0 = $date0[1];
			$date1 = explode(" ", $date1);
			$date1 = $date1[1];

			list($hours0, $mins0, $secs0) = explode(":", $date0);
			list($hours1, $mins1, $secs1) = explode(":", $date1);

			$hours = intval($hours1)-intval($hours0);
			$mins = intval($mins1)-intval($mins0);
			$secs = intval($secs1)-intval($secs0);

			$time = $hours + $mins/60 + $secs/3600;
			global $total;
			$total += $time;
			return strval(round($time, 3));
		}

		if ($log->num_rows > 0){
			While($row = $log->fetch_assoc()) {
				echo "<tr><td>".$row['action']."</td><td>".$row['problem']."</td><td>".$row['solution']."</td><td>".$row['starttime']."</td><td>".$row['endtime']."</td><td>".hours($row['starttime'], $row['endtime'])."</td></tr>";
			}
		} else {
			echo "empty set; ".$conn->error;
		}
	?>
	</table>
	<?php
		echo "<p style='color: #fff'>".strval(round($total, 3))."</p>";
	?>
	<h3>week planning thingy</h3>
	<h4>before summmer holidays</h4>
	<table>
		<tr>
			<th>week</th>
			<th>action</th>
		</tr>
		<tr>
			<td>09</td>
			<td><strike>Doing all the paperwork so I can finally go to work g'dammit</strike></td>
		</tr>
		<tr>
			<td>10</td>
			<td><strike>Making and spreading the survey</strike></td>
		</tr>
		<tr>
			<td>11</td>
			<td><strike>creating and running setup.php</strike></td>
		</tr>
		<tr>
			<td>12</td>
			<td><strike>setup.php buffer week</strike></td>
		</tr>
		<tr>
			<td>13</td>
			<td><strike>index.php: authorization</strike></td>
		</tr>
		<tr>
			<td>14</td>
			<td><strike>index.php: basic interface</strike></td>
		</tr>
		<tr>
			<td>15</td>
			<td><strike>index.php: basic directory structure</strike></td>
		</tr>
		<tr>
			<td>16</td>
			<td><strike>index.php: buffer week</strike></td>
		</tr>
		<tr>
			<td>17</td>
			<td><strike>I'll be in Rome</strike></td>
		</tr>
		<tr>
			<td>18</td>
			<td><strike>index.php: uploading for teacher</strike></td>
		</tr>
		<tr>
			<td>19</td>
			<td><strike>index.php: buffer week</strike></td>
		</tr>
		<tr>
			<td>20</td>
			<td><strike>index.php projects</strike></td>
		</tr>
		<tr>
			<td>21</td>
			<td><strike>index.php projects pt. 2</strike></td>
		</tr>
		<tr>
			<td>22</td>
			<td><strike>index.php projects pt. 3</strike></td>
		</tr>
		<tr>
			<td>23</td>
			<td><strike>index.php project buffer week</strike></td>
		</tr>
		<tr>
			<td>24</td>
			<td><strike>admin.php pt.1</strike></td>
		</tr>
		<tr>
			<td>25</td>
			<td><strike>admin.php pt.2</strike></td>
		</tr>
		<tr>
			<td>26</td>
			<td><strike>admin.php buffer week</strike></td>
		</tr>
		<tr>
			<td>27</td>
			<td><strike>general buffer week</strike></td>
		</tr>
	</table>
	<h3>during and after summer holidays</h3>
	<table>
		<tr>
			<th>week</th>
			<th>action</th>
		</tr>
        <tr>
            <td>35</td>
            <td<strike>Paperwork, CSS part 1</strike></td>
        </tr>
        <tr>
            <td>36</td>
            <td><strike>CSS part 2 (maybe icons)</strike></td>
        </tr>
        <tr>
            <td>37</td>
            <td><strike>CSS part 3</strike></td>
        </tr>
        <tr>
            <td>38</td>
            <td><strike>CSS buffer week</strike></td>
        </tr>
        <tr>
            <td>39</td>
            <td><strike>Settings part 1</strike></td>
        </tr>
        <tr>
            <td>40</td>
            <td><strike>Settings part 2</strike></td>
        </tr>
        <tr>
            <td>41</td>
            <td><strike>Settings buffer week</strike></td>
        </tr>
        <tr>
            <td>42</td>
            <td>Adding extra features</td>
        </tr>
        <tr>
            <td>43</td>
            <td>Adding extra features</td>
        </tr>
        <tr>
            <td>44</td>
            <td>Start closed of Beta</td>
        </tr>
        <tr>
            <td>45</td>
            <td>Bugfixes and optimisations</td>
        </tr>
        <tr>
            <td>46</td>
            <td>Bugfixes and optimisations</td>
        </tr>
        <tr>
            <td>47</td>
            <td>Bugfixes and optimisations</td>
        </tr>
        <tr>
            <td>48</td>
            <td>Bugfixes and optimisations; maybe some improvements to the admin panel</td>
        </tr>
        <tr>
            <td>49</td>
            <td>paperwork for final presentation</td>
        </tr>
        <tr>
            <td>50</td>
            <td>paperwork for final presentation</td>
        </tr>
        <tr>
            <td>51</td>
            <td>paperwork for final presentation</td>
        </tr>
        <tr>
            <td>52</td>
            <td>Holiday celebrations</td>
        </tr>
        <tr>
            <td>01</td>
            <td>paperwork finilisations</td>
        </tr>
        <tr>
            <td>After that</td>
            <td>Waiting for the presentation to come, maybe install system for permanent use in the meantime</td>
        </tr>
        <tr>
            <td>When it's time</td>
            <td>Present</td>
        </tr>
	</table>
</body>
</html>