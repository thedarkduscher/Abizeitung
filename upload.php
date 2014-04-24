<?php
require_once("functions.php");

if(!file_exists("photos/"))
	mkdir("photos/");
	
if(!isset($_GET["user"]) || !isset($_GET["category-name"]))
	die("1");

$user["id"] 		= intval($_GET["user"]);
$category["name"] 	= mysql_real_escape_string($_GET["category-name"]);
$category["id"]		= -1;
	
if(!file_exists("photos/" . $category["name"]))
	mkdir("photos/" . $category["name"]);
	
if(!isset($_FILES['photo']['name']))
	die("2");

if($_FILES['photo']['size'] > return_ini_bytes(ini_get("upload_max_filesize")))
	die("3");

$file = "photos/" . $category["name"] . "/photo_" . time(). "_" . $_FILES['photo']['name'];

if(!($_FILES['photo']['type'] == "image/jpeg" || $_FILES['photo']['type'] == "image/png"))
	die("4");

if(move_uploaded_file($_FILES['photo']['tmp_name'], realpath(dirname(__FILE__)) . "/" . $file)) {
	db_connect();
	
	global $mysqli;
	
	$category["id"] = get_category_id($category["name"]);
	
	if($category["id"] < 0) {
		$stmt = $mysqli->prepare("
			INSERT INTO categories (
				name
			) VALUES (
				?
			)
		");
		
		$stmt->bind_param("s", $mysqli->real_escape_string($category["name"]));
		$stmt->execute();
		
		$res = $stmt->affected_rows;
		$stmt->close();
		
		if(!$res) {
			db_close();
			die("5");
		}
			
		$category["id"] = get_category_id($category["name"]);
		
		if($category["id"] < 0) {
			db_close();
			die("6");
		}
	}
	
	$stmt = $mysqli->prepare("
		INSERT INTO images (
			uid, category, file
		) VALUES (
			?, ?, ?
		)
	");
	
	$stmt->bind_param("iis", $user["id"], $category["id"], $file);
	$stmt->execute();
	
	$res = $stmt->affected_rows;
	$stmt->close();
	
	db_close();
		
	if(!$res)
		die("7");
	
	echo $file;
} else
	die("8");

?>