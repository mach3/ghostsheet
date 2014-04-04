
# Ghostsheet

## Options

- **cache_dir** :String ("./cache") - Directory to save cache files
- **cache_extension** :String (".cache") - Extension for cache files
- **cache_expires** :integer (3600) - Cache lifetime as seconds
- **cache_list** :Boolean (true) - Cache list data or not
- **url_list** :String - URL template for sheet list
- **url_sheet** :String - URL template for sheet data
- **mode_default** :String - Default mode
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


### getSheets($key, [$cache = $this->options["cache_list"] [, $force = false]]) :Array

Get sheet list by key.

- If $cache is TRUE, try to get from local cache data
- If $cache and $force is TRUE, forcely get local cache data in spite of its life time
- If $cache is FALSE, fetch data from remote

```php
$sheet_list = $gs->getSheets($mySpreadSheetKey);
```

### getSheetId($name, $key [,$cache [,$force]]) :String

Get sheet id from the list by name or index.

- $cache and $force are passed to `getSheets`

```php
$sheet_id = $gs->getSheetId(0, $mySpreadSheetKey);
```

### get($key[, $name[, $mode]]) :Array

Get spreadsheet data by id with name(index) and mode.  
Specify mode from "load", "cache", "update" and "fetch".

If $key string is formatted as "[spreadsheet key]/[sheet ID]", $name is ignored.

```php
$data = $gs->get($mySpreadSheetKey, "products", "load");
$data = $gs->get($mySpreadSheetKey, 0, "load");
$data = $gs->get($mySpreadSheetId,);
```

### ajax([$input = null]) :Boolean

Interface for Ajax. Pass the values as $input (default is $_GET), then response with JSON or JSONP.
If 'jsonp' option is FALSE, respond as JSON even if 'callback' parameter is sent.

$input consists of parameters below:

- key :String - Spreadsheet key
- name :String|Integer - Sheet name or index
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


