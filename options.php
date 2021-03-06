<?php
	session_start();
	
	require_once("functions.php");
	require_once("classes/cThumbnails.php");
	require_once("classes/cOptions.php");
	
	db_connect();
	check_login();
	check_admin();

	$data = UserManager::get_userdata($_SESSION["user"]);
	
	if(isset($_GET["modal"])) {
		switch($_GET["modal"]) {
			case "image":
				if(isset($_GET["edit"]) && $_GET["id"]) {
					Options::edit_images($_GET["id"]);
				}
				break;
			case "detail":
				if(isset($_GET["fileonly"])) {
					$data["fileonly"] = true;
				}
				else {
					$data["fileonly"]	= false;
					$data["id"]			= 0;
					$data["category"] 	= $_GET["category"];
					$data["name"] 		= $_GET["name"];
					
					if(isset($_GET["id"])) {
						$data["id"] = $_GET["id"];
					}
				}
				
				$data["file"] = $_GET["file"];
				
				Options::image_detail($data);
				
				break;
			default:
		}
		
		db_close();
		
		die;
	}
	
	if(isset($_GET["affected"])) {
		$action = NULL;
		$param = NULL;
		
		if(isset($_GET["action"])) {
			$action = $_GET["action"];
		}
		
		switch($_GET["affected"]) {
			case "images":
				
				$param = "&group=images";
				
				if(isset($_GET["id"])) {
					$data["id"] = $_GET["id"];
					
					if($action == "delete") {
						$errorHandler->add_error(Options::delete_images($data));
						
						$param .= "&detail=delete_images";
					}
					else {
						if(empty($_POST["name"])) {
							$errorHandler->add_error("empty-input");
						}
						else {
							$data["name"] = $_POST["name"];
							
							$errorHandler->add_error(Options::update_images($data));
						}
					}
				}
				else {
					$errorHandler->add_error("empty-input");
				}
				
				break;
			
			case "thumbnails":
				
				$param = "&group=images&detail=thumbnails";
				
				set_time_limit(0);
				
				$errorHandler->add_error(Options::create_thumbnails());
				
				break;
			
			case "detail":
				
				$param = "&group=images&category=" . $_GET["category"];
				
				if(isset($_GET["name"])) {
					$param .= "&name=" . $_GET["name"];
				}
				
				if($action == "delete") {
					if(isset($_GET["id"]) || isset($_GET["file"])) {
						if(isset($_GET["id"])) {
							$data["id"] = $_GET["id"];
						}
						else {
							$data["file"] 	= $_GET["file"];
							$data["id"] 	= 0;
						}
						
						$errorHandler->add_error(Options::delete_image($data));
						
						$param .= "&detail=delete_image";
					}
					else {
						$errorHandler->add_error("no-selected-file");
					}
				}
				
				break;
				
			case "options":
				
				$param = "&group=options";
				
				if(empty($_POST["nicknames"]) && empty($_POST["classes"]) && empty($_POST["questions"]) && empty($_POST["surveys"])) {
					$errorHandler->add_error("empty-input");	
				}
				else {
					$data["state_questions"] 	= $_POST["state_questions"];
					$data["state_surveys"] 		= $_POST["state_surveys"];
					
					$data["thumbnails_height"] 			= $_POST["thumbnails_height"];
					$data["thumbnails_quality_png"] 	= $_POST["thumbnails_qual_png"];
					$data["thumbnails_quality_jpeg"] 	= $_POST["thumbnails_qual_jpeg"];
					
					$data["nicknames"] 	= $_POST["nicknames"];
					$data["classes"] 	= $_POST["classes"];
					$data["questions"] 	= $_POST["questions"];
					$data["surveys"] 	= $_POST["surveys"];
					
					$errorHandler->add_error(Options::update_options($data));
				}
				
				break;
				
			case "files":
				
				$param = "&group=csv";
				
				$data["files"] = array();
				
				if(isset($_POST["file_count"])) {
					if($_POST["file_count"] > 0) {
						
						for($i = 1; $i <= $_POST["file_count"]; $i++) {
							if(isset($_POST["file_" . $i]) && isset($_POST["file_name_" . $i])) {
								array_push($data["files"], $_POST["file_name_" . $i]);
							}
						}
						
						$errorHandler->add_error(Options::delete_csv($data));
						
						$param .= "&detail=delete_csv";
					}
					else {
						$errorHandler->add_error("empty-input");
					}
				}
				else {
					$errorHandler->add_error("empty-input");
				}
				
				break;
			
		}
		
		db_close();
			
		if($errorHandler->is_error()) {
			header("Location: ./options.php?error" . $errorHandler->export_url_param(true) . $param);
		}
		else {
			header("Location: ./options.php?saved" . $param);
		}
		
		die;
	}
	
	if(isset($_GET["group"])) {
		$group = $_GET["group"];
	}
	else {
		$group = "options";
	}
?>


<!DOCTYPE html>
<html>
	<head>
		<title>Abizeitung - Einstellungen</title>
		<?php head(); ?>
        <?php Options::script(true); ?>
	</head>
	
	<body>
		<?php require("nav-bar.php") ?>
		<div id="options" class="container-fluid admin-wrapper">
        	<?php if(isset($_GET["saved"])): ?>
				<div class="alert alert-success">Änderungen gespeichert.<br />
            <?php
			
				if(isset($_GET["detail"])) {
					switch($_GET["detail"]) {
						case "delete_images":
							echo "Bilder wurden gelöscht.";
							break;
						case "delete_image":
							echo "Bild wurde gelöscht.";
							break;
						case "thumbnails":
							echo "Thumbnails wurden erstellt.";
							break;
						case "delete_csv":
							echo "Die ausgewählten CSV Dateien wurden gelöscht.";
							break;
					}
				}
			?>
            	</div>
			<?php else: if(isset($_GET["error"])): 
				$errorHandler->import_url_param($_GET);
			?>
				<div class="alert alert-danger">
					Speichern fehlgeschlagen: 
					<ul>
						<?php $errorHandler->get_errors("li"); ?>
					</ul>
				</div>
			<?php endif; endif; ?>
			<h1>Einstellungen</h1>
			<div class="box">
				<ul class="nav nav-tabs">
                	<li<?php if($group == "options"): 	?> class="active"<?php endif; ?>><a href="options.php?group=options">Optionen</a></li>
                	<li<?php if($group == "images"): 	?> class="active"<?php endif; ?>><a href="options.php?group=images">Bilder</a></li>
                    <li<?php if($group == "csv"): 		?> class="active"<?php endif; ?>><a href="options.php?group=csv">CSV Import</a></li>
                </ul>
                <?php
					switch($group) {
						case "images":
							if(isset($_GET["thumbnails"])) {
								Options::display_thumbnails_info();
							}
							elseif(isset($_GET["category"])) {
								if(isset($_GET["name"])) {
									Options::get_images($_GET["category"], $_GET["name"]);
								}
								else {
									Options::get_images($_GET["category"]);
								}
							}
							else {
								Options::display_images();
							}
							break;
						case "options":
							Options::display_options();
							break;
						case "csv":
							Options::display_csv();
							break;
					}
				?>
			</div>
		</div>	
        
        <div class="modal fade" id="optionsModal" tabindex="-1" role="dialog" aria-hidden="true"></div>
	</body>
</html>

<?php db_close(); ?>