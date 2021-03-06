<?php

	class Dashboard {
		public static function update_user_nicknames($user, $nicknameId, $accept) {
			global $mysqli;
			
			$stmt = $mysqli->prepare("
				UPDATE nicknames
				SET accepted = ?
				WHERE id = ?
				AND `to` = ?
			");
			
			$stmt->bind_param("iii", intval($accept), intval($nicknameId), intval($user));
			$stmt->execute();
			
			$res = $stmt->affected_rows;
			$stmt->close();
			
			if($mysqli->error || $res < 0)
				return true;
			else
				return false;
		}
		
		public static function update_user_questions($user, $question, $answer) {
			global $mysqli;
			
			$stmt = $mysqli->prepare("
				SELECT id, text
				FROM users_questions
				WHERE user = ? AND question = ?
				LIMIT 1");
			
			$stmt->bind_param("ii", $user, $question);
			$stmt->execute();
			$stmt->store_result();
			$stmt->bind_result($users_questions["id"], $users_questions["text"]);
			
			$stmt->fetch();
			
			if($stmt->num_rows > 0) {
				if(empty($answer)) {
					$stmt2 = $mysqli->prepare("
						DELETE FROM users_questions
						WHERE id = ?
						LIMIT 1");
					
					$stmt2->bind_param("i", $users_questions["id"]);
					
					$stmt2->execute();
					$stmt2->close();
				}
				else {
					$stmt2 = $mysqli->prepare("
						UPDATE users_questions
						SET text = ?
						WHERE id = ?
						LIMIT 1");
						
					$stmt2->bind_param("si", $answer, $users_questions["id"]);
					
					$stmt2->execute();
					$stmt2->close();
				}
			}
			else {
				if(!empty($answer)) {
					$stmt2 = $mysqli->prepare("
						INSERT INTO users_questions (
							user, text, question
						) VALUES (
							?, ?, ?
						)");
												
					$stmt2->bind_param("isi", $user, $answer, $question);
					$stmt2->execute();
					
					$stmt2->close();
				}
			}
			
			$stmt->free_result();
			$stmt->close();
			
			if($mysqli->error)
				return true;
			else
				return false;
		}
		
		public static function update_user_surveys($user, $survey, $answer) {
			global $mysqli;
			
			$stmt = $mysqli->prepare("
				SELECT id
				FROM users_surveys
				WHERE user = ? AND survey = ?
				LIMIT 1");
				
			$stmt->bind_param("ii", $user, $survey);
			$stmt->execute();
			$stmt->bind_result($user_survey["id"]);
			$stmt->store_result();
			
			if($stmt->fetch()) {
				$stmt2 = $mysqli->prepare("
					UPDATE users_surveys
					SET m = ?, w = ?
					WHERE id = ?
					LIMIT 1");
				
				$stmt2->bind_param("iii", null_on_empty($answer["male"]), null_on_empty($answer["female"]), $user_survey["id"]);
				$stmt2->execute();
				$stmt2->close();
			}
			else {
				$stmt2 = $mysqli->prepare("
					INSERT INTO users_surveys (
						user, survey, m, w
					) VALUES (
						?, ?, ?, ?
					)");
					
				$stmt2->bind_param("iiii", $user, $survey, null_on_empty($answer["male"]), null_on_empty($answer["female"]));
				$stmt2->execute();
				$stmt2->close();
			}
			
			$stmt->free_result();
			$stmt->close();
			
			if($mysqli->error)
				return true;
			else
				return false;
		}
		
		public static function insert_nickname($data) {
			global $mysqli;
			
			$stmt = $mysqli->prepare("
				SELECT id
				FROM nicknames
				WHERE
					`to` = ?
				AND nickname = ?
			");
			
			$stmt->bind_param("is", $data["user"], $data["nickname"]);
			$stmt->execute();
			
			$res = $stmt->fetch();
			$stmt->close();
			
			if(!$res)  {
			
				$stmt = $mysqli->prepare("
					INSERT INTO nicknames (
						nickname, `from`, `to`, accepted
					) VALUES (
						?, ?, ?, 0
					)
				");
				
				$stmt->bind_param("sii", $data["nickname"], $data["id"], $data["user"]);
				$stmt->execute();
				
				$res = $stmt->num_rows;
				$stmt->close();
			}
			else {
				return "nickname-already-exists";
			}
			
			return 0;
		}
		
		public static function insert_question($data) {
			global $mysqli;
			
			$stmt = $mysqli->prepare("
				INSERT INTO questions (
					title, user, accepted
				) VALUES (
					?, ?, 0
				)");
				
			$stmt->bind_param("si", $data["question"], intval($data["id"]));
			
			$stmt->execute();
			
			$stmt->close();
			
			return 0;
		}
		
		public static function insert_survey($data) {
			global $mysqli;
			
			$stmt = $mysqli->prepare("
				INSERT INTO surveys (
					title, m, w, user, accepted
				) VALUES (
					?, ?, ?, ?, 0
				)");
				
			$stmt->bind_param("siii", $data["survey"], intval(isset($data["male"])), intval(isset($data["female"])), intval($data["id"]));
			
			$stmt->execute();
			
			$stmt->close();
			
			return 0;
		}
		
		public static function suggest_nickname($data) {
			global $mysqli;
?>
<div class="modal-dialog">
    <div class="modal-content">
        <form method="post" action="dashboard.php?affected=nickname">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4>Spitzname vergeben</h4>
            </div>
            <div class="modal-body">
                <input type="text" name="nickname" placeholder="Spitzname"/>
                <select name="user">
                <?php
					
					switch(db_get_option("nicknames")) {
						case "1":
							$stmt = $mysqli->prepare("
								SELECT users.id, users.prename, users.lastname
								FROM students
								LEFT JOIN users ON students.uid = users.id
								WHERE NOT users.id = ?
								ORDER BY users.lastname ASC
							");
							
							$stmt->bind_param("i", $data["id"]);
							break;
						case "3":
							// Todo: 
							// select all students from same classes
							//break;
						default:
							$stmt = $mysqli->prepare("
								SELECT users.id, users.prename, users.lastname
								FROM students
								LEFT JOIN users ON students.uid = users.id
								WHERE 
									NOT users.id = ?
									AND students.tutorial = ?
								ORDER BY users.lastname ASC
							");
							
							$stmt->bind_param("ii", $data["id"], $data["tutorial"]["id"]);
					}
                    
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
		}
		
		public static function suggest_question() {
?>
<div class="modal-dialog">
    <div class="modal-content">
        <form method="post" action="dashboard.php?affected=question">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4>Frage vorschlagen</h4>
            </div>
            <div class="modal-body">
                <textarea name="question" placeholder="Frage eingeben..."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Schließen</button>
                <button type="submit" class="btn btn-default">Speichern</button>
            </div>
        </form>
    </div>
</div>
<?php
		}
		
		public static function suggest_survey() {
?>
<div class="modal-dialog">
    <div class="modal-content">
        <form method="post" action="dashboard.php?affected=survey">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4>Umfrage vorschlagen</h4>
            </div>
            <div class="modal-body">
                <textarea name="survey" placeholder="Frage eingeben..."></textarea>
            </div>
            <div class="modal-body">
                <h4>Personengruppe</h4>
                <div>
                    <input id="male" type="checkbox" name="m" value="1" checked>
                    <label for="male">Männlich</label>
                </div>
                <div>
                    <input id="female" type="checkbox" name="w" value="1" checked>
                    <label for="female">Weiblich</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Schließen</button>
                <button type="submit" class="btn btn-default">Speichern</button>
            </div>
        </form>
    </div>
</div>
<?php
		}
		
		public static function suggest_error() {
?>
<div class="modal-dialog">
    <div class="modal-content">
        <form method="post" action="dashboard.php?affected=error-report">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4>Fehler melden</h4>
            </div>
            <div class="modal-body">
                <textarea name="text" placeholder="Was ist los?"><?php
						if(isset($_GET["name"])) {
							echo "Ich bin nicht im Kurs \"" . $_GET["name"] . "\".";
						}
				?></textarea>
            </div>
            <?php if(isset($_GET["id"])): ?>
            <input type="hidden" name="error-category" value="170<?php echo $_GET["id"]; ?>" />
            <?php else: ?>
            <div class="modal-body">
                <input type="hidden" id="error-category" name="error-category" />
                <h4>Kategorie</h4>
                <div class="error-category">
                	<ul>
                    	<div class="scroll">
                            <li value="1">
                                Allgemeines
                                <ul>
                                    <div class="scroll">
                                        <li value="11">Vorname</li>
                                        <li value="12">Nachname</li>
                                        <li value="13">Geschlecht</li>
                                        <li value="14">Tutorium</li>
                                        <li value="15">Tutor</li>
                                        <li value="16">Bilder</li>
                                        <li value="17">Kurse</li>
                                    </div>
                                </ul>
                            </li>
                            <li value="2">
                                Spitznamen
                                <ul>
                                	<div class="scroll">
                                        <li value="21">Spitzname vorschlagen</li>
                                        <li value="22">Vorgeschlagene Spitznamen</li>
                                        <li value="23">Versendete Spitznamen</li>
                                    </div>
                                </ul>
                            </li>
                            <li value="3">
                                Fragen
                            </li>
                            <li value="4">
                                Umfragen
                            </li>
                        </div>
                    </ul>
                </div>
            </div>
            <?php endif; ?>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Schließen</button>
                <button type="submit" class="btn btn-default">Speichern</button>
            </div>
        </form>
    </div>
</div>
<script type="text/javascript">
	$(function() {
		$(".error-category").miller({
			value: true,
			attribute: "value",
			inputId: "#error-category"
		});
	});
</script>
<?php
		}
		
		public static function delete_nickname($data, $id) {
			global $mysqli;
			
			$stmt = $mysqli->prepare("
				SELECT id
				FROM nicknames
				WHERE `from` = ?
				AND id = ?
			");
			
			$stmt->bind_param("ii", $data["id"], $id);
			$stmt->execute();
			
			$stmt->bind_result($id);
			$stmt->store_result();
			
			if($stmt->fetch()) {
				$stmt2 = $mysqli->prepare("
					DELETE FROM nicknames
					WHERE
						id = ?
					AND accepted = 0
					LIMIT 1
				");
				
				$stmt2->bind_param("i", $id);
				$stmt2->execute();
				
				$stmt2->close();
				
				$stmt->free_result();
				$stmt->close();
				
				return true;
			}
			
			$stmt->free_result();
			$stmt->close();
			
			return false;
		}
		
		public static function script($jstag = false) {
			if($jstag): ?><script type="text/javascript"><?php endif; ?>
            function suggest(name) {
				var param = "";
				
				if(arguments.length > 1) {
					for(i = 1; i < arguments.length; i++)
						param += "&" + arguments[i];
				}
				
				$('#dashboardModal').modal();
				$('#dashboardModal').load("dashboard.php?suggest=" + name + param.replace(" ", ""), function() {
					$("#dashboardModal select").fancySelect();
				});
			}
		<?php if($jstag): ?></script><?php endif; ?>
<?php
		}
	}
	
	/*
		-- SAMPLES --
	
		###
		### JavaScript modal
		###
	
		<?php Dashboard::script(true); ?>
		
		-- or --
		
		<script type="text/javascript">
			<?php Dashboard::script(); ?>
		</script>
		
		###
		### HTML buttons
		###
		
		<div class="buttons">
			<a class="button" href="javascript:void(suggest('nickname'))"><span class="icon-plus-circled"></span> Spitzname vergeben</a>
		</div>
		
		<div class="buttons">
			<a class="button" href="javascript:void(suggest('question'))"><span class="icon-plus-circled"></span> Frage vorschlagen</a>
		</div>
		
		<div class="buttons">
			<a class="button" href="javascript:void(suggest('survey'))"><span class="icon-plus-circled"></span>Frage vorschlagen</a>
		</div>
	*/
	
?>
