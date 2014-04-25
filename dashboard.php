<?php 
	session_start();
	
	require_once("functions.php");
	require_once("classes/cDashboard.php");
	
	db_connect();
	
	check_login();

	$data = UserManager::get_userdata($_SESSION["user"]);
	
	global $mysqli;
	
	if(isset($_GET["nickname"])) {
		if($_GET["nickname"] == "new") {
			
			$nickname = $mysqli->real_escape_string($_POST["nickname"]);
			
			if(!empty($nickname)) {
			
				$stmt = $mysqli->prepare("
					INSERT INTO nicknames (
						nickname, `from`, `to`, accepted
					) VALUES (
						?, ?, ?, 0
					)
				");
				
				$stmt->bind_param("sii", $nickname, intval($data["id"]), intval($_POST["user"]));
				$stmt->execute();
				
				$res = $stmt->num_rows;
				$stmt->close();
				
				db_close();
				
				header("Location: ./dashboard.php?saved");
				
				die;
			}
			
			db_close();
			
			header("Location: ./dashboard.php?error");
			
			die;
		}
		else {
?>
	<div class="modal-dialog">
        	<div class="modal-content">
            	<form method="post" action="dashboard.php?nickname=new">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                        <h4>Spitzname vergeben</h4>
                    </div>
                    <div class="modal-body">
                        <input type="text" name="nickname" placeholder="Spitzname"/>
                        <select name="user">
                        <?php
							$stmt = $mysqli->prepare("
								SELECT users.id, users.prename, users.lastname
								FROM students
								LEFT JOIN users ON students.uid = users.id
							");
							
							$stmt->execute();
							$stmt->bind_result($user["id"], $user["prename"], $user["lastname"]);
							
							while($stmt->fetch()):
                        ?>
                        	<option value="<?php echo $user["id"]; ?>"><?php echo $user["prename"] . " " . $user["lastname"]; ?></option>
                        <?php
							endwhile;
							
							$stmt->close();
						?>
                        </select>
                    </div>
                    <div class="modal-footer">
                    	<button type="button" class="btn btn-default" data-dismiss="modal">Schließen</button>
                    	<button type="submit" class="btn btn-default">Speichern</button>
                    </div>
                </form>
        	</div>
        </div>
<?php
			die;
		}
	}
	
	if(isset($_GET["update"])) {
		$userdata["id"] = $data["id"];
		$userdata["birthday"] = $_POST["birthday"];
		$userdata["nickname"] = $_POST["nickname"];
		
		UserManager::update_userdata($userdata);
		
		$data = UserManager::get_userdata($_SESSION["user"]);
		
		$stmt = $mysqli->prepare("
			SELECT id
			FROM questions");
		
		$stmt->execute();
		$stmt->bind_result($q["id"]);
		$stmt->store_result();
		
		$fails = 0;
		
		while($stmt->fetch()) {
			if(isset($_POST["question_" . $q["id"]])) {
				if(Dashboard::update_user_questions($data["id"], $q["id"], $mysqli->real_escape_string($_POST["question_" . $q["id"]])))
					$fails++;
			}
		}
		
		$stmt->free_result();
		$stmt->close();
		
		$stmt = $mysqli->prepare("
			SELECT id
			FROM surveys");
			
		$stmt->execute();
		$stmt->bind_result($s["id"]);
		$stmt->store_result();
		
		while($stmt->fetch()) {
			$answer = array(
				"female" => NULL,
				"male" => NULL
			);
				
			if(isset($_POST["survey_w_" . $s["id"]]) && !empty($_POST["survey_w_" . $s["id"]]))
				$answer["female"] = intval($_POST["survey_w_" . $s["id"]]);
				
			if(isset($_POST["survey_m_" . $s["id"]]))
				$answer["male"]   = intval($_POST["survey_m_" . $s["id"]] && !empty($_POST["survey_m_" . $s["id"]]));
			
			if(Dashboard::update_user_surveys($data["id"], $s["id"], $answer))
				$fails++;
		}
		
		$stmt->free_result();
		$stmt->close();
		
		if(!$fails)
			header("Location: ./dashboard.php?saved");
		else
			header("Location: ./dashboard.php?failed=" . $fails);
		exit;
	}

	$students = array();
	
	$stmt = $mysqli->prepare("
		SELECT students.id, users.id, prename, lastname, female
		FROM students 
		LEFT JOIN users ON students.uid = users.id
		ORDER BY users.prename");
	
	$stmt->execute();
	$stmt->bind_result($row["sid"], $row["uid"], $row["prename"], $row["lastname"], $row["female"]);
						
	while($stmt->fetch()) {				
		array_push($students, array("sid" => $row["sid"], "uid" => $row["uid"], "prename" => $row["prename"], "lastname" => $row["lastname"], "gender" => $row["female"] ? "w" : "m"));
	}
	
	$stmt->close();
	
	// Alle von anderen Nutzer vergebenen Nicknames speichern
	
	$nicknames = array();
	
	$stmt = $mysqli->prepare("
		SELECT nicknames.id, nicknames.nickname, users.prename, users.lastname, nicknames.accepted
		FROM nicknames
		LEFT JOIN users ON nicknames.`from` = users.id
		WHERE 
		NOT	nicknames.`to` = nicknames.`from`
		AND nicknames.`to` = ?
	");
	
	$stmt->bind_param("i", $data["id"]);
	$stmt->execute();
	
	$stmt->bind_result($row["id"], $row["nickname"], $row["prename"], $row["lastname"], $row["accepted"]);
	
	while($stmt->fetch()) {
		$nicknames[intval($row["id"])] = array(
			"nickname" 	=> $row["nickname"],
			"from"		=> array(
				"prename" 	=> $row["prename"],
				"lastname" 	=> $row["lastname"]
			),
			"accepted" 	=> $row["accepted"]
		);
	}
	
	$stmt->close();
	
	// Alle Fragen speichern
	
	$questions = array();
	
	$stmt = $mysqli->prepare("
		SELECT id, title
		FROM questions");
		
	$stmt->execute();
	$stmt->bind_result($row["id"], $row["title"]);
	
	while($stmt->fetch()) {				
		$questions[intval($row['id'])] = array("title" => $row["title"]);
	}
	
	$stmt->close();
	
	$surveys = array();
	
	$stmt = $mysqli->prepare("
		SELECT id, title, m, w 
		FROM surveys");
		
	$stmt->execute();
	$stmt->bind_result($row["id"], $row["title"], $row["m"], $row["w"]);
	
	while($stmt->fetch()) {				
		$surveys[intval($row['id'])] = array("title" => $row["title"], "m" => ($row["m"] == '1'), "w" => ($row["w"] == '1'));
	}
	
	$stmt->close();
	
	// Alle Umfragen speichern
	
	$survey_answers = array();
	
	$stmt = $mysqli->prepare("
		SELECT survey, m, w
		FROM users_surveys
		WHERE user = ?
	");
	
	$stmt->bind_param("i", $data["id"]);
	$stmt->execute();
	$stmt->bind_result($row["survey"], $row["m"], $row["w"]);
	
	while($stmt->fetch()) {		
		$survey_answers[intval($row['survey'])] = array("m" => $row["m"], "w" => $row["w"]);
	}
	
	$stmt->close();
	
?>


<!DOCTYPE html>
<html>
	<head>
		<title>Abizeitung - Dashboard</title>
		<?php head(); ?>
        <script src="js/dashboard.js" type="text/javascript"></script>
        <script type="text/javascript">
			<?php 
				$stmt = $mysqli->prepare("
					SELECT file
					FROM images
					WHERE 
						uid = ? AND
						category = ?
					ORDER BY id DESC
					LIMIT 1
				");
				
				$stmt->bind_param("ii", $data["id"], intval(1));
				$stmt->execute();
				
				$stmt->bind_result($enrollment);
				$stmt->fetch();
				
				$stmt->close();
				
				$stmt = $mysqli->prepare("
					SELECT file
					FROM images
					WHERE 
						uid = ? AND
						category = ?
					ORDER BY id DESC
					LIMIT 1
				");
				
				$stmt->bind_param("ii", $data["id"], intval(2));
				$stmt->execute();
				
				$stmt->bind_result($current);
				$stmt->fetch();
				
				$stmt->close();
			?>
			
			function newNickname() {
				$('#dashboardModal').modal();
				$('#dashboardModal').load("dashboard.php?nickname");		
			}
			
			$(document).ready(function(){
				change_bg_img('#photo-enrollment', '<?php echo $enrollment; ?>');
				change_bg_img('#photo-current', '<?php echo $current; ?>');
				$("div.common *").tooltip();
			});
		</script>
	</head>
	
	<body>
		<?php require("nav-bar.php") ?>
		<div id="dashboard" class="container">
			<?php if(isset($_GET["saved"])): ?>
				<div class="alert alert-success">Änderungen gespeichert.</div>
            <?php else: if(isset($_GET["failed"])): ?>
                <div class="alert alert-danger">
                	Speichern fehlgeschlagen.<br />
                    <?php if($_GET["failed"] == 1): ?>
						1 Anfrage konnte nicht gespeichert werden.
                    <?php else: if($_GET["failed"] > 1): ?>
                    	<?php echo $_GET["failed"] ?> Anfragen konnten nicht gespeichert werden.
                    <?php endif; endif; ?>
                </div>
            <?php endif; endif; ?>
			<div class="intro">
				<h1>Hallo <?php echo $data["prename"] ?>!</h1>
				<p class="intro">Hier kannst du deine Daten für die Abizeitung angeben bzw. ergänzen. Die Daten werden für deinen Steckbrief verwendet. Die Ergebnisse der Umfragen kommen auch in die Abizeitung, auf Wunsch wird dein Name geschwärzt.</p>
				<p class="intro">Bitte achte auf Rechtschreibung und <b>vergiss das Speichern nicht</b> ;)</p>
			</div>
			<form id="data_form" name="data" action="dashboard.php?update" method="post"></form>
			<div class="common box">
				<h2>Allgemeines</h2>
				<div class="row">
					<div class="col-sm-4 data">
						<div class="row">
							<div class="col-xs-5 title">Vorname</div>
							<div class="col-xs-7"><?php echo $data["prename"] ?></div>
						</div>
						<div class="row">
							<div class="col-xs-5 title">Nachname</div>
							<div class="col-xs-7"><?php echo $data["lastname"] ?></div>
						</div>
                        <div class="row">
							<div class="col-xs-5 title">Spitzname</div>
							<div class="col-xs-7"><input name="nickname" type="text" form="data_form" value="<?php echo $data["nickname"] ?>" /></div>
						</div>
						<div class="row">
							<div class="col-xs-5 title">Geburtsdatum</div>
							<div class="col-xs-7"><input name="birthday" type="text" form="data_form" value="<?php echo $data["birthday"] ?>" /></div>
						</div>
						<div class="row">
							<div class="col-xs-5 title">Geschlecht</div>
							<div class="col-xs-7"><?php echo $data["female"] ? "Weiblich" : "Männlich" ?></div>
						</div>
						<div class="row">
							<div class="col-xs-5 title">Tutorium</div>
							<div class="col-xs-7"><?php if(isset($data["class"]["name"])) echo $data["class"]["name"] ?></div>
						</div>
						<div class="row">
							<div class="col-xs-5 title">Tutor</div>
							<div class="col-xs-7"><?php if(isset($data["class"]["tutor"]["lastname"])) echo $data["class"]["tutor"]["lastname"] ?></div>
						</div>
					</div>
					
					<div class="col-sm-4">
						<div id="photo-enrollment" class="photo" data-toggle="tooltip" data-placement="bottom" title="Einschulungsfoto">
							<form action="upload.php" id="image-form-enrollment" enctype="multipart/form-data"></form>
		                    <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo return_ini_bytes(ini_get('upload_max_filesize')); ?>" />
							<input id="photo-upload-enrollment" class="photo-upload" name="photo" type="file" form="image-form-enrollment" accept="image/x-png,image/jpeg" onchange="uploadImage(<?php echo $data["id"] ?>, 'enrollment', '#image-form-enrollment', '#photo-upload-state-enrollment', '#photo-enrollment')" />
							<div class="upload">
								<a href="javascript: openImageSelector('#photo-upload-enrollment')">
		                        	<span id="photo-upload-state-enrollment">
										<span class="icon-upload"></span>
										<br>Einschulungsfoto
										<br>hochladen...
		                            </span>
								</a>
							</div>
						</div>
					</div>
					
					<div class="col-sm-4">
		                <div id="photo-current" class="photo"  data-toggle="tooltip" data-placement="bottom" title="Aktuelles Foto">
		                	<form action="upload.php" id="image-form-current" enctype="multipart/form-data"></form>
		                    <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo return_ini_bytes(ini_get('upload_max_filesize')); ?>" />
							<input id="photo-upload-current" class="photo-upload" name="photo" type="file" form="image-form-current" accept="image/x-png,image/jpeg" onchange="uploadImage(<?php echo $data["id"] ?>, 'current', '#image-form-current', '#photo-upload-state-current', '#photo-current')" />
		                	<div class="upload">
		                    	<a href="javascript: openImageSelector('#photo-upload-current')">
		                        	<span id="photo-upload-state-current">
		                            	<span id="photo-upload-state-current">
										<span class="icon-upload"></span>
										<br>Aktuelles Foto
										<br>hochladen...
		                            </span>
		                            </span>
		                        </a>
		                    </div>
		                </div>
					</div>
				</div>
			</div>
            
            <div class="nicknames box">
            	<h2>Spitznamen</h2>
                <div class="nickname-list row">
                	<table class="table table-striped">
                        <thead>
                            <th>Spitzname</th>
                            <th>Vergeben von</th>
                            <th class="accept"></th>
                        </thead>
                        <tbody>
                <?php foreach($nicknames as $key => $nickname): ?>
                			<tr>
                            	<td><?php echo $nickname["nickname"] ?></td>
								<td><?php echo $nickname["from"]["prename"] . " " . $nickname["from"]["lastname"]; ?></td>
                                <td class="accept">
                                	<input id="accept" type="checkbox" value="1" name="accept"<?php if($nickname["accepted"]): ?> checked<?php endif; ?>/>
                                    <label for="accept">Spitzname akzeptieren</label>
                                </td>
                    		</tr>
                <?php endforeach; ?>
                		</tbody>
                 	</table>
                    
                    <div class="buttons">
						<a class="button" href="javascript:void(newNickname())"><span class="icon-plus-circled"></span> Nickname vergeben</a>
					</div>
                    
                </div>
            </div>
			
			<div class="questions box">
				<h2>Fragen</h2>
				<div class="question-list row">
				<?php foreach($questions as $key => $question): 
					$text = "";
					
					$stmt = $mysqli->prepare("
						SELECT text 
						FROM users_questions
						WHERE user = ? AND question = ?");
						
					$stmt->bind_param("ii", intval($data["id"]), $key);
					$stmt->execute();
					
					
					$stmt->bind_result($text);
					$stmt->fetch();
					
					$stmt->close();					
				?>
					<div class="col-sm-6 question">
						<div class="title"><?php echo $question["title"] ?></div>
						<div class=""><textarea name="question_<?php echo $key ?>" form="data_form"><?php echo $text ?></textarea></div>
					</div>
				<?php endforeach; ?>
				</div>
			</div>
			
			<div class="surveys box">
				<h2>Umfragen</h2>
				<div class="survey-list">
				<?php foreach($surveys as $key => $survey): ?>
					<div class="row">
						<div class="col-xs-12 col-sm-4 title"><?php echo $survey["title"] ?></div>
						<div class="col-xs-12 col-sm-4">
						<?php if($survey["m"] === true):
							$answer = 0;
							if(isset($survey_answers[$key]))
								$answer = $survey_answers[$key]["m"];		
						?>
							<div class="icon-male">
								<select name="survey_m_<?php echo $key ?>" form="data_form">
									<option value=""<?php echo ($answer) ? "" : " selected" ?>>-</option>
									<?php foreach($students as $student) {
										if($student["gender"] == "m") {
											echo "<option";
											if($answer == $student["uid"])
												echo " selected";
											echo " value=\"".$student["uid"]."\">".$student["prename"]." ".$student["lastname"]."</option>";	
										}
									}
									?>
								</select>
							</div>  
						<?php endif; ?>
						</div>
						<div class="col-xs-12 col-sm-4">
						<?php if($survey["w"] === true): 
							$answer = 0;
							if(isset($survey_answers[$key]))
								$answer = $survey_answers[$key]["w"];
						?>
							<div class="icon-female">
								<select name="survey_w_<?php echo $key ?>" form="data_form">
									<option value="" <?php echo ($answer) ? "" : " selected" ?>>-</option>
									<?php foreach($students as $student) {
										if($student["gender"] == "w") {
											echo "<option";
											if($answer == $student["uid"])
												echo " selected";
											echo " value=\"".$student["uid"]."\">".$student["prename"]." ".$student["lastname"]."</option>";	
										}
									}
									?>
								</select>
							</div>
						</div>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
				</div>
			</div>
			
			<div class="buttons">
				<input type="submit" value="Speichern" form="data_form" />
				<input type="reset" value="Änderungen verwerfen" form="data_form" />
			</div>
            
            <div class="modal fade" id="dashboardModal" tabindex="-1" role="dialog" aria-hidden="true">
            </div>

		</div>	
	</body>
</html>

<?php db_close(); ?>