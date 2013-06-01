
# Ghostsheet

## Options

- cacheDir : String ("./cache/") - Directory to save cache
- cache : Boolean (true) - Use cache or not
- prefix : String ("http://spreadsheets.google.com/feeds/cells/") - Prefix for Spreadsheet URL
- suffix : String ("/public/basic?alt=json") - Suffix for Spreadsheet URL
- timeout : Integer (30) - Timeout secs for curl request
- expires : Integer (3600) - Expire sec for cache
- jsonp : Boolean (true) - Allow JSONP request or not
- devel : Boolean (false) - Run on development mode or not

*Note* : On development mode, cache is to be ignored and not to be saved.

## Methods

### config(options:Array) : Ghostsheet

Set options by array

- options : Array set of option's key and value

### set(key:String, value:Mixed) : Ghostsheet

Setter for options

- key : Key name of options
- value : Value to set

### get(key:String) : Mixed

Getter for options

- key : Key name of options

### load(id:String) : Array

Load spreadsheet data by id, then return data as array or Null on failure.

- id : Spreadsheet ID

### getLogs() : Array

Get log messages for debug

### ajax([input:Array]) : void

Interface to repond to Ajax or JSONP request.  
If arguments not set, this uses `$_GET` data.  
To request as JSONP, `jsonp` in options must be set as true.

- input : 
	- id : Spreadsheet ID
	- cache : "true|false" ("true") - Use cache or not 
	- devel : "true|false" ("false") - Run on development mode or not
	- callback : Callback function's name for JSONP

### clean(id:String) : Boolean

Remove cache file by Spreadsheet ID, then return succeeded or not.

- id : Spreadsheet ID

### cleanAll() : Boolean

Remove all the files in cache directory. Not recommennded to use.

