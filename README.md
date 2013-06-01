
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


### Get Spreadsheet ID

Get your spreadsheet id from URL

1. Open "File" > "Publish to the web" dialog
2. In "Get a link to the published data" section, select "RSS" and "Cells"
3. You can get url just like below

```
https://spreadsheets.google.com/feeds/cells/XXXXXXXXXXXXXXXXXXXXXX/YYY/public/basic?alt=rss
```

This `"XXXXXXXXXXXXXXXXXXXXXX/YYY"` is your sheet's ID

### Load it

```php
require "the/path/to/Ghostsheet.php";
$gs = new Ghostsheet(array(
	"cacheDir" => "./gscache/"
));
$data = $gs->load("XXXXXXXXXXXXXXXXXXXXXX/YYY");
```

Now, `$data` has array consists of spreadsheet contents.

```php
array(
	"id" => ""https://spreadsheets.google.com/feeds/cells/XXXXXXXXXXXXXXXXXXXXXX/YYY/public/basic"",
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

This will response JSON for the passed parameter.
If no arguments, this uses $_GET as default.

Example for jQuery :

```
$.getJSON("ajax.php", {id : "XXXXXXXXXX/YYY", cache : false})
.then(function(data){
	var items = data.items;
});
```

## Configure

Configure options with `config()` or `set()`.

```php
$gs->set("cacheDir", "./mycache/");
$gs->config(array("timeout", 60));
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
