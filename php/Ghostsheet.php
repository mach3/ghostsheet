<?php

/**
 * Ghostsheet
 * ----------
 * Load Google spreadsheet, parse it, output data or response as JSON
 *
 * @version 0.9.3
 * @author mach3 <http://github.com/mach3>
 * @url http://github.com/mach3/ghostsheet
 */

class Ghostsheet {

	/**
	 * Options:
	 * - {String} cache_dir ... Directory to save cache file
	 * - {String} cache_extension ... Extention string for cache file
	 * - {Integer} cache_expires ... Cache lifetime as seconds
	 * - {String} prefix ... Prefix string for spreadsheet id
	 * - {String} suffix ... Suffix string for spreadsheet id
	 * - {Integer} timeout ... Timeout seconds for curl
	 * - {Boolean} nullfill ... Fill empty column as `Null` or not
	 * - {Boolean} debug ... Save logs or not
	 * - {Boolean} jsonp ... Allow jsonp access or not
	 */
	private $options = array(
		"cache_dir" => "./cache",
		"cache_extension" => ".cache",
		"cache_expires" => 3600, 
		"prefix" => "http://spreadsheets.google.com/feeds/cells/",
		"suffix" => "/public/basic?alt=json",
		"timeout" => 30,
		"nullfill" => true,
		"debug" => false,
		"jsonp" => false
	);

	/**
	 * Types for juggling type of column values
	 */
	private $types = array(
		"int", "integer",
		"bool", "boolean",
		"float", "double", "real",
		"array", "json",
		"string"
	);

	/**
	 * Modes list for get() method
	 */
	private $modes = array(
		"load", "update", "fetch", "cache"
	);

	/**
	 * Container for logs
	 */
	public $logs = array();

	/**
	 * Constructor
	 * - Configure options if having arguments
	 */
	public function __construct($options = null){
		if(null !== $options){
			$this->config($options);
		}
	}

	/**
	 * Interfaces
	 * ----------
	 */

	/**
	 * Configure options
	 * - config(key, value);
	 * - config(key);
	 * - config(vars);
	 * - config();
	 * @param {Mixed} Args...
	 * @return {Mixed}
	 */
	public function config(){
		$args = func_get_args();
		if(! count($args)){
			return $this->options;
		}
		switch(gettype($args[0])){
			case "array":
				foreach($args[0] as $key => $value){
					$this->config($key, $value);
				}
				return $this;
			case "string":
				if(count($args) === 1){
					return $this->options[$args[0]];
				}
				$this->options[$args[0]] = $args[1];
				return $this;
			default: break;
		}
		return $this;
	}

	/**
	 * Get spreadsheet data from cache or remote
	 * - Wrapper for `load`, `update`, `fetch`, `cache`
	 * @param {String} $id 
	 * @param {String} $mode (optional)
	 * @return {Array}
	 */
	public function get($id, $mode = "load"){
		if(! in_array($mode, $this->modes)){
			$this->_log("Invalid mode: {$mode}");
			throw new Exception("Invalid mode @ get(): {$mode}");
		}
		return $this->$mode($id);
	}

	/**
	 * Ajax interface
	 * - id: Spreadsheet ID
	 * - mode: Load mode
	 * - callback: Callback function name for JSONP
	 *   (If 'jsonp' in options is FALSE, response as JSON)
	 * @param {Array} $input
	 */
	public function ajax($input = null){
		$input = !! $input ? $input : $_GET;
		$vars = array(
			"id" => $this->_filter("id", $input, null),
			"mode" => $this->_filter("mode", $input, "load"),
			"callback" => $this->_filter("callback", $input, null)
		);
		$data = $this->get($vars["id"], $vars["mode"]);

		if(! $data){
			header("HTTP/1.1 500 Internal Server Error");
			return false;
		}

		if(!! $vars["callback"] && $this->config("jsonp")){
			header("Content-Type: text/javascript");
			header("X-Content-Type-Options: nosniff");
			printf("%s(%s);", $vars["callback"], json_encode($data));
			return true;
		}

		header("Content-Type: application/json");
		header("X-Content-Type-Options: nosniff");
		echo json_encode($data);
		return true;
	}

	/**
	 * Load spreadsheet data normally
	 * - Firstly try to get local cache
	 * - If cache is valid (not expired), return cache data
	 * - If not, try to get remote data, save it as cache
	 * - Then return
	 * @param {String} $id
	 * @return {Array}
	 */
	public function load($id){
		$this->_validateId($id);
		$data = $this->_getLocal($id);
		if(! $data){
			$data = $this->update($id);
		}
		return $data;
	}

	/**
	 * Update local cache by remote data
	 * - Returns latest data
	 * @param {String} $id
	 * @return {Array}
	 */
	public function update($id){
		$this->_validateId($id);
		$data = $this->_parse($this->_getRemote($id));
		if($data){
			$this->_saveLocal($id, $data);
		}
		return $data;
	}

	/**
	 * Get local cache data in spite of its lifetime
	 * - Does not touch remote data at all
	 * @param {String} $id
	 */
	public function cache($id){
		$this->_validateId($id);
		$data = $this->_getlocal($id, true);
		return $data;
	}

	/**
	 * Get remote data in spite of cache existing
	 * - Does not save it as local cache
	 * @param {String} $id
	 */
	public function fetch($id){
		$this->_validateId($id);
		$json = $this->_getRemote($id);
		$data = $this->_parse($json);
		return $data;
	}

	/**
	 * Utilities
	 * ---------
	 */

	/**
	 * Validate id string, update it as URL
	 * @param {String} &$id
	 * @return {String}
	 */
	private function _validateId(&$id){
		if(! preg_match("/^http(s)?:\/\//", $id)){
			$id = $this->config("prefix") . $id . $this->config("suffix");
		}
	}

	/**
	 * Parse the spreadsheet JSON string as array with columns
	 * @param {String} $json
	 */
	private function _parse($json){
		$source = json_decode($json, true);
		$items = array();
		$header = array();

		if(! is_array(@$source["feed"]["entry"])){
			$this->_log("Parse Error : {$json}");
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

		if($this->config("nullfill")){
			$this->_nullfill($items, $header);
		}

		return array(
			"id" => $source["feed"]["id"]["\$t"],
			"updated" => $source["feed"]["updated"]["\$t"],
			"title" => $source["feed"]["title"]["\$t"],
			"items" => $items
		);
	}

	/**
	 * Parse value in header columns
	 * - Returns array which consists of title name and type string
	 * @param {String} $title
	 * @return {Array}
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
	 * Juggle the value as the type
	 * @param {String} $value
	 * @param {String} $type
	 * @return {Mixed}
	 */
	private function _juggle($value, $type){
		switch(true){
			case in_array($type, array("integer", "int")) :
				return (integer) $value;
			case in_array($type, array("boolean", "bool")) :
				if(preg_match("/^(true|false)$/i", $value)){
					return json_decode($value);
				} else {
					return null;
				}
			case in_array($type, array("float", "double", "real")) :
				return (float) $value;
			case in_array($type, array("array")) :
				$value = explode(",", str_replace("\\,", "&comma;", $value));
				foreach($value as $i=>$v){
					$value[$i] = trim(str_replace("&comma;", ",", $v));
				}
				return $value;
			case $type === "json" :
				return json_decode($value);
			default :
				return (string) $value;
		}
	}

	/**
	 * If column value is empty, fill it as `Null`
	 * @param {Array} &$items
	 * @param {Array} $header
	 */
	private function _nullfill(&$items, $header){
		foreach($items as &$item){
			foreach($header as $field){
				$name = $field["name"];
				$item[$name] = array_key_exists($name, $item) ? $item[$name] : null;
			}
		}
	}

	/**
	 * Get remote data with curl
	 * @param {String} $url
	 * @return {String}
	 */
	private function _getRemote($url){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, $this->config("timeout"));
		$result = curl_exec($ch);
		$status = curl_getinfo($ch);
		$success = !! preg_match("/^2/", (string) $status["http_code"]);
		curl_close($ch);
		if($success && $result){
			return $result;
		} else {
			$this->_log("Failed to fetch remote data @ getRemote() : {$url}");
		}
		return null;
	}

	/**
	 * Get local cache data
	 * - If cache file does not exists or is expired, return null
	 * - Set $force as TRUE to forcely get cache data in spite of its lifetime
	 * @param {String} $id
	 * @param {Boolean} $force (optional)
	 * @return {Array}
	 */
	private function _getLocal($id, $force = false){
		$file = $this->_getCacheFileName($id);
		if(! file_exists($file)){
			$this->_log("Cache file not found @ getLocal() : {$id}");
			return null;
		}
		$expire = (time() - filemtime($file)) > $this->config("cache_expires");
		if($expire && ! $force){
			$this->_log("Cache file is expired @ getLocal() : {$id}");
			return null; 
		}
		return json_decode(file_get_contents($file), true);
	}

	/**
	 * Save the $data as cache named $id
	 * @param {String} $id
	 * @param {Array} $data
	 */
	private function _saveLocal($id, $data){
		$done = file_put_contents($this->_getCacheFileName($id), json_encode($data));
		if($done){
			$this->_log("Cache file saved @ saveLocal() : {$id}");
		}
		return $done;
	}

	/**
	 * Get cache file name by id
	 * @param {String} $id
	 * @return {String}
	 */
	private function _getCacheFileName($id){
		return $this->config("cache_dir") . "/" . md5(urlencode($id)) . $this->config("cache_extension");
	}

	/**
	 * Save the log
	 * @param {String} $message
	 */
	private function _log($message){
		if(! $this->config("debug")){
			return;
		}
		array_push($this->logs, array(
			"time" => date(DATE_RFC822),
			"message" => $message
		));
	}

	private function _filter($key, $vars, $default = null){
		if(array_key_exists($key, $vars)){
			return $vars[$key];
		}
		return $default;
	}
}

