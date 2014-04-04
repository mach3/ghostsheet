# Ghostsheet

Simple Google Spreadsheet Loader for PHP and Ajax

## Feature

- Load Google Spreadsheet and parse it to easy-to-read object data
- Save it as cache file
- Having an interface for Ajax or JSONP

## Basic Usage

### Create Spreadsheet

Example :

<table>
	<thead>
		<tr>
			<th>name</th>
			<th>age:integer</th>
			<th>email</th>
			<th>active:bool</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td>john</td>
			<td>18</td>
			<td>john@example.com</td>
			<td>true</td>
		</tr>
		<tr>
			<td>tom</td>
			<td>21</td>
			<td>tom@example.com</td>
			<td>false</td>
		</tr>
	</tbody>
</table>

- First row must be header consists of field name
- If field name has data type (as `:string`), PHP try to juggle value to the type  
  (If not set, value will be output as string)

### Fetching Sheet Data

#### By Index or Name

Ghostsheet is able to fetch by index or name of sheet.
Pass worksheet's **key** and **index or name** of the sheet you want to fetch data of.

```
$gs = new Ghostsheet();
$data = $gs->get("XXxxxxXXXxxxXxXxxxxXxxxxxXXXxXXXxXxxXXXXXXXx", 0); // Get first sheet
$data = $gs->get("XXxxxxXXXxxxXxXxxxxXxxxxxXXXxXXXxXxxXXXXXXXx", "product"); // Get sheet named "product"
```

#### By Full ID

Full ID is an identifier string formatted as "[spreadsheet-key]/[sheet-id]".

This skips a process to fetch the sheet list by API,
it's much faster than fetching by index or name

```
$gs = new Ghostsheet();
$data = $gs->get("XXxxxxXXXxxxXxXxxxxXxxxxxXXXxXXXxXxxXXXXXXXx/od6");
```

#### With Mode

Four modes are available by specifying in third argument.

```
// Get "product" sheet with "load" mode
$gs->get("XXxxxxXXXxxxXxXxxxxXxxxxxXXXxXXXxXxxXXXXXXXx", "product", "load");
// Or Full ID
$data = $gs->get("XXxxxxXXXxxxXxXxxxxXxxxxxXXXxXXXxXxxXXXXXXXx/od6", null, "load");
```

- **"load"** (default) Check local cache, if it's expired fetch remote data, save it as cache.
- **"update"** Get remote data and save it as cache, in spite of cache's lifetime.
- **"cache"** Get local cache data in spite of its lifetime. If cache does not exist, return null.
- **"fetch"** Get remote data, doesn't save it as cache.


### Do Something on Data

Now, `$data` has an array consists of spreadsheet contents.

```php
array(
	"id" => "https://spreadsheets.google.com/feeds/cells/XXxxxxXXXxxxXxXxxxxXxxxxxXXXxXXXxXxxXXXXXXXx/yyY/public/basic",
	"title" => "mysheet", // your sheet's name
	"updated" => "2013-05-28T10:37:51.771Z",
	"items" => array(
		array("name" => "John", "age" => 18, "email" => "john@example.com", "active" => true),
		...
	)
);
```


## Ajax

Ghostsheet has an interface for AJAX request.

```php
# ajax.php
$gs = new Ghostsheet();
$gs->ajax($_GET);
```

This will respond with JSON for the passed parameters.
If no arguments, this uses $_GET as default.

Example for jQuery :

```javascript
$.getJSON("ajax.php", {
	key: "XXxxxxXXXxxxXxXxxxxXxxxxxXXXxXXXxXxxXXXXXXXx", // Spreadsheet Key or ID
	name: "product", // Sheet Index or Name
	mode: "load", // Load mode
})
.then(function(data){
	var items = data.items;
});
```

## Configure

Configure options with `config()`

```php
$gs->config(array("cache_dir", "./mycache"));
$gs->config("cache_dir", "./mycache/");
$gs->config("cache_dir"); // Returns "./mycache/"
$gs->config(); // Returns all options
```

## API Doc

&raquo; [Learn More About Ghostsheet](doc/api.md)

## Change Log

&raquo; [Change Log](doc/changelog.md)


-----

## Author

mach3

- [Website](http://www.mach3.jp)
- [Blog](http://blog.mach3.jp)
- [Twitter](http://twitter.com/mach3ss)
