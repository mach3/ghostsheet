
# Ghostsheet

## Options

- **cache_dir** :String ("./cache") - Directory to save cache files
- **cache_extension** :String (".cache") - Extension for cache files
- **cache_expires** :integer (3600) - Cache lifetime as seconds
- **prefix** :String ("http://spreadsheets.google.com/feeds/cells/") - Prefix string for spreadsheet URL
- **suffix** :String ("/public/basic?alt=json") - Suffix string for spreadsheet URL
- **timeout** :Integer (30) - Timeout for CURL to get remote data as seconds
- **nullfill** :Boolean (true) - Fill the empty columns with `Null` or not
- **debug** :Boolean (false) - Save log or not
- **jsonp** :Boolean (false) - Allow JSONP access or not

## Properties

### logs :Array

Notice logs are saved as `logs`. If 'debug' option is false, this does not work.

## Methods

### config([$key|$options] [, $value]) :Mixed

Configure options, set or get values.

```php
$gs->config("debug", true); // Set by key and value
$gs->config(array("debug" => true)); // Set by array
$gs->config("debug"); // Returns a value
$gs->config(); // Returns all options
```

### get($id [, $mode="load"]) :Array

Get spreadsheet data by id with mode ("load", "cache", "update", "fetch")

```php
$data = $gs->get($mySpreadSheetId, "load");
```

### ajax([$input = null]) :Boolean

Interface for Ajax. Pass the values as $input (default is $_GET), then response with JSON or JSONP.
If 'jsonp' option is FALSE, respond as JSON even if 'callback' parameter is sent.

$input consists of parameters below:

- id :String - Spreadsheet ID
- mode :String - Mode name
- callback :String - Callback function name for JSONP

```php
$gs->ajax($_GET);
```

### load($id) :Array

Get data with 'load' mode.  

- Firstly try to get cache file
- If cache file does not exist or is expired, fetch remote data and save it as cache file

```php
$data = $gs->load($mySpreadSheetId);
```

### update($id) :Array

Get data with 'update' mode.

- Fetch remote data in spite of cache's lifetime
- Save it as cache file

```php
$data = $gs->update($mySpreadSheetId);
```

### cache($id) :Array

Get data with 'cache' mode

- Get local cache data in spite of its lifetime

```php
$data = $gs->cache($mySpreadSheetId);
```

### fetch($id) :Array

Get data with 'fetch' mode

- Get remote data
- Do nothing about cache

```php
$data = $gs->fetch($mySpreadSheetId);
```


