<?php

/**
 * Ghostsheet
 * ----------
 * Load Google spreadsheet, parse it, output data or response as JSON
 *
 * @version 0.9
 * @author mach3
 * @url http://github.com/mach3/ghostsheet
 *
 */

class Ghostsheet {

	private $options = array(
		// cachDir : Directory to save cache files
		"cacheDir" => "./cache/", 
		// cache : Use cache or not
		"cache" => true, 
		// prefix : Prefix for Google Spreadsheet URL
		"prefix" => "http://spreadsheets.google.com/feeds/cells/",
		// suffix : Suffix for Google Spreadsheet URL
		"suffix" => "/public/basic?alt=json",
		// timeout : Timeout for cURL request
		"timeout" => 30,
		// expires : Expire second of cache lifetime
		"expires" => 3600, 
		// jsonp : Allow jsonp in ajax()
		"jsonp" => false
	);

	private $types = array(
		"int", "integer",
		"bool", "boolean",
		"float", "double", "real",
		"string"
	);

	/**
	 * Constructor
	 * @constructor
	 * @param Array $options
	 */
	public function __construct($options = null){
		if(is_array($options)){
			$this->config($options);
		}
	}

	/**
	 * Configure options
	 * @param Array $options
	 */
	public function config($options){
		foreach($options as $key => $value){
			$this->set($key, $value);
		}
		return $this;
	}

	/**
	 * Set a value to option
	 * @param String $key
	 * @param Mixed $value
	 */
	public function set($key, $value){
		if(array_key_exists($key, $this->options)){
			$this->options[$key] = $value;
		}
		return $this;
	}

	/**
	 * Get a value from option
	 * @param String $key
	 * @return Mixed|Null
	 */
	public function get($key){
		if(array_key_exists($key, $this->options)){
			return $this->options[$key];
		}
		return null;
	}

	/**
	 * Load data from cache or remote spreadsheet
	 * @param String $id
	 * @param Boolean $cache (optional)
	 * @return Array|Null
	 */
	public function load($id, $cache = null){
		$cache = is_null($cache) ? $this->get("cache") : $cache;
		$data = null;

		if($cache){
			if($data = $this->_getCache($id)){
				return $data;
			}
		} 
		if($data = $this->_fetch($id)){
			return $data;
		}
		return $this->_getCache($id, true);
	}

	/**
	 * Interface for XHR / JSONP access
	 * $input is $_GET as default
	 * $input must have "id", can have "cache", "callback" (if jsonp allowed)
	 * @param Array $input
	 */
	public function ajax($input = null){
		$input = is_null($input) ? $_GET : $input;

		$vars = array(
			"id" => $this->_filter("id", $input, "/^[a-z0-9\/]+$/i"),
			"cache" => ! preg_match("/^false$/i", $this->_filter("cache", $input, "/^(true|false)$/i")),
			"callback" => $this->_filter("callback", $input, "/^[a-z0-9_]+$/i")
		);
		$output = json_encode($this->load($vars["id"], $vars["cache"]));

		if($output === "null"){
			header("HTTP/1.0 500 Failed to load spreadsheet");
			die;
		}
		if($vars["callback"] && $this->get("jsonp")){
			$output = $vars["callback"] . "({$output})";
		}

		header("Content-Type: application/json; charset=utf-8");
		header("X-Content-Type-Options: nosniff");
		echo $output;
	}

	/**
	 * Remove cache file by id
	 * @param String $id
	 * @return Boolean
	 */
	public function clean($id = null){
		$path = $this->_getPath($id);
		if(file_exists($path)){
			return unlink($path);
		}
		return false;
	}

	/**
	 * (danger) Remove all files in cache directory
	 * @return Boolean
	 */
	public function cleanAll(){
		$dir = $this->get("cacheDir");
		$dp = opendir($dir);
		if(! $dp){
			return false;
		}
		while($file = readdir($dp)){
			$path = "{$dir}/{$file}";
			if(! preg_match("/^\./", $file) && is_file($path)){
				unlink($path);
			}
		}
		return true;
	}

	/**
	 * Parse a source of spreadsheet (json) and get data as array
	 * @param String $content
	 * @return Array|Null
	 */
	private function _parse($content){
		$source = json_decode($content, true);
		$items = array();
		$header = array();

		if(! is_array(@$source["feed"]["entry"])){
			return null;
		}

		foreach($source["feed"]["entry"] as $entry){
			preg_match("/^([A-Z]+)([0-9]+)$/i", $entry["title"]["\$t"], $m);
			$c = (string) $m[1];
			$r = ((integer) $m[2]) - 2;
			$value = $entry["content"]["\$t"];

			if($r < 0){
				$header[$c] = $this->_parseTitle($value);
				continue;
			}
			if(! array_key_exists($r, $items)){
				$items[$r] = array();
			}
			if(array_key_exists($c, $header)){
				$items[$r][$header[$c]["name"]] = $this->_juggle($value, $header[$c]["type"]);
			}
		}

		return array(
			"id" => $source["feed"]["id"]["\$t"],
			"updated" => $source["feed"]["updated"]["\$t"],
			"title" => $source["feed"]["title"]["\$t"],
			"items" => $items
		);
	}

	/**
	 * Juggle type of value
	 * @param Mixed $value
	 * @param String $type
	 * @return Mixed
	 */
	private function _juggle($value, $type){
		switch(true){
			case in_array($type, array("integer", "int")) :
				return (integer) $value;
			case in_array($type, array("boolean", "bool")) :
				return (boolean) $value;
			case in_array($type, array("float", "double", "real")) :
				return (float) $value;
			default :
				return (string) $value;
		}
	}

	/**
	 * Parse header column ("<name>:<type>")
	 * return array as array("type" => <type>, "name" => <name>)
	 * @param String $title
	 * @return Array
	 */
	private function _parseTitle($title){
		$types = implode("|", $this->types);
		if(preg_match("/^(.+?):({$types})$/", $title, $m)){
			return array(
				"type" => $m[2],
				"name" => $m[1]
			);
		}
		return array(
			"type" => "string",
			"name" => $title
		);
	}

	/**
	 * Try to get cache data
	 * If $force == true, ignore expires
	 * @param String $id
	 * @param Boolean $force
	 * @return Array|Null
	 */
	private function _getCache($id, $force = false){
		$path = $this->_getPath($id);
		$mtime = file_exists($path) ? filemtime($path) : 0;
		$expired = (time() - $mtime) > $this->get("expires");

		if($mtime !== 0 && ($force || ! $expired)){
			return unserialize(file_get_contents($path));
		}
		return null;
	}

	/**
	 * Save data to cache file
	 * @param String $id
	 * @param Array $content
	 */
	private function _saveCache($id, $content){
		file_put_contents(
			$this->_getPath($id),
			serialize($content)
		);
	}

	/**
	 * Get path by cache directory in option and id string
	 * @param String $id
	 */
	private function _getPath($id){
		return $this->get("cacheDir") . "/" . md5(urlencode($id));
	}

	/**
	 * Fetch json data from Google spreadsheet by id
	 * and save as cache file if success
	 * @param String $id
	 * @return Array|null
	 */
	private function _fetch($id){
		$data = null;
		$url = $this->get("prefix") . $id . $this->get("suffix");
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, $this->get("timeout"));
		$result = curl_exec($ch);
		$status = curl_getinfo($ch);
		$success = !! preg_match("/^2/", (string) $status["http_code"]);
		curl_close($ch);

		if($success && $result){
			$data = $this->_parse($result);
			$this->_saveCache($id, $data);
		}
		return $data;
	}

	/**
	 * Get filtered value from data by key
	 * $filter is string for preg_match
	 * @param String $key
	 * @param Array $data
	 * @param String $filter
	 */
	private function _filter($key, $data, $filter = null){
		$value = array_key_exists($key, $data) ? $data[$key] : "";
		if($filter){
			$value = preg_match($filter, $value, $m) ? $m[0] : "";
		}
		return $value;
	}
}