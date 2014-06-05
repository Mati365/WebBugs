<?php
error_reporting(0);

$URL = 'http://baginski.mateusz.2lo.pl/';
$dir = '';
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
	foreach (new DirectoryIterator($dir) as $file)
		if(strcmp($file, 'index.php') == 0 || 
			strcmp($file, 'index.html') == 0)
			return true;
	return false;
}
function gotoURL($url) {
	header('Location: '.$url);
}
function getURL() {
	global $URL;
	return $URL;
}
function isPageFile($file) {
	$type = $file->getExtension();
	return strcmp($type, "html") == 0 || 
			strcmp($type, "php") == 0;
}

$columns = array(0, 5, 10, 4);
$file_count = 0;
$folder_count = 0;

function previousDir($dir) {
	$__dir = "/";
	if(isset($_GET['LAST_DIR']))
		$__dir = $_GET['LAST_DIR'];
	return $__dir;
}

$filter = array('config.php');
function isInFilter($word) {
	global $filter;
	if(strrpos($word, '.php') != false && strcmp($word, 'index.php') != 0)
		return true;
	foreach($filter as $obj)
		if(strcmp($obj, $word) == 0)
			return true;
	return false;
}

function printList($dir_print, $summary) {
	global $dir, $columns, $file_count, $folder_count;
	
	$file_count = $folder_count = 0;

	date_default_timezone_set('UTC');
	if($dir_print)
		insertString(true, 30, '<img src="./folder.png" /><a class="link" href="http://baginski.mateusz.2lo.pl?DIR='.previousDir($dir).'">..</a><br>');
	if(empty($dir))
		$dir = "/";
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
		
		// Link do pliku!!!
		$url = getURL();
		$filename = $file->getFilename();
		$fullpath = $file->getPath().'/'.$filename;
		if(isInFilter($filename))
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
		
		if($is_dir) {
			$filename = $filename.'/';
			$url = $url.'?DIR='.$fullpath.'/';
		} else if(isPageFile($file))
			$url = $url.'?PAGE='.$fullpath;
		else
			$url = $url.$fullpath;
		if(strpos($url, "DIR") != false || strpos($url, "PAGE"))
			$url = $url.'&LAST_DIR='.$dir;

		$folder_icon = '<img src="./folder.png" />';	
		insertString(true, $columns[3], 
					($is_dir ? $folder_icon : '').
					'<a class="'.(!$is_dir ? 'link' : 'folder_link').'" href="'.$url.'">'.$filename.'</a><br>');
	}
	// Podsumowanie!
	if($summary == false)
		return;
	echo '<br>File count:<b>'.$file_count.'</b>  ';
	echo 'Folder count:<b>'.$folder_count.'</b>  ';
	echo 'Total size:<b>'.$folder_size.'MB</b>';
}
?>
<html lang="pl">
	<head>
		<meta charset="UTF-8" />
		<title> File browser </title>
		<style>
			@font-face {
    				font-family: 'Joystix';
    				src: url('joystix.ttf');
			}
			<?php 
if(!isset($_GET['PAGE'])) {
			?>
			body, div#file_list, pre {
				font-family: 'Joystix';		
			}
			body {
				overflow:		hidden;
				background-color:	black;
				color:			white;
				font-weight:		bold;
			}
			<?php
}
			?>
			#iframe_style {
				position:	fixed; 
				top:		0px; 
				left:		0px; 
				bottom:		0px; 
				right:		0px; 
				width:		100%; 
				height:		100%; 
				border:		none; 
				margin:		0; 
				padding:	0; 
				overflow:	hidden; 
				z-index:	999999;
			}
			a.link, a.folder_link {
				text-decoration:	none;				
			}
			a.link {
				color:			#4DBD33;
			}
			a.link:hover, a.folder_link:hover {
				background-color:	black;
				color:			white;

				margin-left:	5px;
			}
			a.folder_link {
				background-color:	#4DBD33;
				color:			black;
			}
			div#file_title_bar {
				background-color:	white;
				color:			black;

				height:	20px;
				font-weight:	bold;
				text-shadow:	1px 1px lightgray;
			}
			div#file_list {
				width:		600px;
				margin:		0 auto;
				
				overflow:		hidden;
				white-space: 		nowrap;
			}
			div#content, div.shadow {
				display:	inline-block;
			}
			div.shadow {
				height:	100%;
				width:	6px;
				background-image:	url('./kwiat.gif');
				margin:	0;
				padding:	0;
			}
			.lolink:hover {
				margin-left:	10px;
			}
		</style>
	</head>
	<?php 
if(!isset($_GET['PAGE'])) {
	?>
	<body>
		<div id="file_list">
			<div style="font-size: 11px; margin-bottom: 3px;">Path: <?php echo $_GET['DIR']; ?></div>
			
			<div style="border: 1px solid gray; background-image:	url('./file_background.png');">
				<pre style="padding: 0px; margin: 0px; font-size: 11px"><div id="file_title_bar">Last modified         Size    Name</div><br><?php loadPage(); ?></pre>
			</div>
		</div>
	</body>
	<?php
} else {
	$page = $_GET['PAGE'];
	?>
	<iframe id="iframe_style" src='<?php echo getURL().$page; ?>'> </iframe>
	<?php 
}
	 ?>
</html>
