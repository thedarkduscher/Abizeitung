<?php 
	
	session_start();
	
	require_once("functions.php");
	
	db_connect();
	check_login();
	check_admin();
	
	if(isset($_GET["delete"])) {
		$stmt = $mysqli->prepare("
			SELECT id
			FROM teachers
			WHERE id = ?
		");
		
		$stmt->bind_param("i", intval($_GET["user"]));
		$stmt->execute();
		
		$res = $stmt->affected_rows;
		$stmt->close();
		
		if($res > 0) {
			$stmt = $mysqli->prepare("
				DELETE FROM teachers
				WHERE uid = ?
			");
			
			$stmt->bind_param("i", intval($_GET["user"]));
			$stmt->execute();
			
			$stmt->close();
		}
		else {
			
			$stmt = $mysqli->prepare("
				DELETE FROM students
				WHERE uid = ?
			");
			
			$stmt->bind_param("i", intval($_GET["user"]));
			$stmt->execute();
			
			$stmt->close();
			
		}
			
		$stmt = $mysqli->prepare("
			DELETE FROM users
			WHERE id = ?
			LIMIT 1
		");
		
		$stmt->bind_param("i", intval($_GET["user"]));
		$stmt->execute();
		
		$stmt->close();
		
		header("Location: ./users.php");
		exit;	
	}
	
	$data = UserManager::get_userdata($_SESSION["user"]);
	
	$edit = UserManager::get_userdata($_GET["user"]);
	
	if(isset($_GET["edit"])) {
		if(isset($_POST["prename"]) || isset($_POST["lastname"]) || isset($_POST["email"])) {
			$userdata["id"] 		= $_GET["user"];
			$userdata["prename"] 	= $_POST["prename"];
			$userdata["lastname"] 	= $_POST["lastname"];
			$userdata["tutorial"]	= $_POST["tutorial"];
			$userdata["birthday"] 	= $_POST["birthday"];
			$userdata["nickname"] 	= $_POST["nickname"];
			$userdata["email"] 		= $_POST["email"];
			$userdata["password"] 	= $_POST["password"];
			$userdata["admin"] 		= isset($_POST["admin"]);
			$userdata["teacher"]	= isset($_POST["teacher"]);
			if($_POST["gender"] == "f") {
				$userdata["female"] = true;
			}
			else {
				$userdata["female"] = false;
			}
			
			$param = UserManager::edit_user($userdata);
			
			if($param == 0) {
				header("Location: ./edit-user.php?user=" . $_GET["user"] . "&saved");
			}
			else {
				header("Location: ./edit-user.php?user=" . $_GET["user"] . "&error=" . $param);
			}
			
			exit;
		}
	}
?>


<!DOCTYPE html>
<html>
	<head>
		<title>Abizeitung - Nutzerverwaltung</title>
		<?php head(); ?>
	</head>
	
	<body>
		<?php require("nav-bar.php") ?>
		<div id="user-management" class="container-fluid admin-wrapper">
        	<?php if(isset($_GET["saved"])): ?>
				<div class="alert alert-success">Änderungen gespeichert.</div>
            <?php else: if(isset($_GET["error"])): ?>
                <div class="alert alert-danger">
                	Speichern fehlgeschlagen.<br />
                    <ul>
                <?php
					$errorHandler->import_url_param($_GET);
					
					echo $errorHandler->get_errors("li");
				?>
                	</ul>
                </div>
            <?php endif; endif; ?>
			<h1>Nutzerverwaltung</h1>
			
			<form id="data_form" name="data" action="edit-user.php?user=<?php echo $_GET["user"] ?>&edit" method="post"></form>
			
			<div class="box">
			
				<div class="add-user">
					<h2>Nutzer bearbeiten</h2>
					<table>
						<tr>
							<td class="title">Vorname</td>
							<td><input name="prename" type="text" form="data_form" value="<?php echo $edit["prename"] ?>" /></td>
						</tr>
						<tr>
							<td class="title">Nachname</td>
							<td><input name="lastname" type="text" form="data_form" value="<?php echo $edit["lastname"] ?>" /></td>
						</tr>
						<tr>
							<td class="title">Geschlecht</td>
							<td>
	                        	<select name="gender" form="data_form">
	                                <option value="m" <?php if($edit["female"] == 0) echo "selected" ?>>Männlich</option>
	                                <option value="f" <?php if($edit["female"] == 1) echo "selected" ?>>Weiblich</option>
	                            </select>
	                        </td>
						</tr>
	
						<tr>
							<td class="title">Tutorium</td>
							<td>
	                        	<select name="tutorial" form="data_form">
	                        		<option>-</option>
	                                <?php 
									
										$stmt = $mysqli->prepare("
											SELECT id, name
											FROM tutorials
										");
										
										$stmt->execute();
										$stmt->bind_result($tutorial["id"], $tutorial["name"]);
										
										$select = 0;
										if(isset($edit["tutorial"]["id"]))
											$select = $edit["tutorial"]["id"];
										
										while($stmt->fetch()) {
											if($select == $tutorial["id"])
												echo '<option value="' . $tutorial["id"] . '" selected>' . $tutorial["name"] . "</option>";
											else
												echo '<option value="' . $tutorial["id"] . '">' . $tutorial["name"] . "</option>";
										}
										
										$stmt->close();
									?>
	                            </select>
	                        </td>
						</tr>
						<tr>
							<td class="title">Geburtsdatum</td>
							<td><input name="birthday" type="text" form="data_form" value="<?php echo $edit["birthday"] ?>" /></td>
						</tr>
						<tr>
							<td class="title">Spitzname</td>
							<td><input name="nickname" type="text" form="data_form" value="<?php echo $edit["nickname"] ?>" /></td>
						</tr>
						<tr>
							<td class="title">E-Mail</td>
							<td><input name="email" type="text" form="data_form" value="<?php echo $edit["email"] ?>" /></td>
						</tr>
						<tr>
							<td class="title">Passwort</td>
							<td><input name="password" type="password" form="data_form" placeholder="Unverändert" /></td>
						</tr>
	                    <tr>
							<td class="title">Lehrer</td>
							<td><input name="teacher" type="checkbox" form="data_form" <?php if($edit["isteacher"] == true) echo "checked" ?> /></td>
						</tr>
						<tr>
							<td class="title">Administrator</td>
							<td><input name="admin" type="checkbox" form="data_form" <?php if($edit["admin"] == true) echo "checked" ?> /></td>
						</tr>
					</table>
				</div>
							
				<div class="buttons">
					<input type="submit" value="Speichern" form="data_form" />
					<a class="button" href="users.php<?php if($edit["isteacher"]) echo "?group=teachers"; ?>">Zurück</a>
	                <a class="button delete" href="edit-user.php?user=<?php echo $_GET["user"] ?>&delete">Löschen</a>
				</div>
				
			</div>

		</div>
        <?php if(isset($_GET["delete"])): ?>	
			<script type="text/javascript">
				if(confirm("Benutzer <?php echo $edit["prename"] . " " . $edit["lastname"] ?> löschen?"))
					window.location = window.location + "=do";
			</script>
		<?php endif; ?>
	</body>
</html>

<?php db_close(); ?>