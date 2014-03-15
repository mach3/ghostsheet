<?php

require "../php/Ghostsheet.php";

function getVar($key, $vars, $default = null){
	if(array_key_exists($key, $vars)){
		return $vars[$key];
	}
	return $default;
}

try {

	$gs = new Ghostsheet();

	$mode = getVar("mode", $_GET, "cache");
	$id = getVar("id", $_GET);
	$data = null;

	switch($mode){
		case "cache":
			$data = $gs->get($id, $mode);
			if(! $data){
				$data = $gs->load($id);
			}
			break;
		case "update":
			$gs->get($id, $mode);
			$data = array(
				"message" => "Cache updated"
			);
		default: break;
	}
	if(! $data){
		throw new Exception("Invalid Data");
	}

	header("Content-Type: application/json");
	echo json_encode($data);

} catch(Exception $e){
	header("HTTP/1.1 500 Internal Server Error");
	echo json_encode(array("message" => $e->getMessage()));
}

