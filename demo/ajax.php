<?php

require "../php/Ghostsheet.php";

$gs = new Ghostsheet();
$gs->set("cacheDir", "./cache/");
$gs->ajax($_GET);
