<?php
// path to read archives from, no trailing slash
$rootPath = '.';

$archiveTypes = array(
	(object)array( 'command' => 'unrar',	'listParams' => 'lb',		'extractParams' => 'p -c- -idq',	'ext' => array('rar', 'cbr') ),
	(object)array( 'command' => 'unzip',	'listParams' => '-Z -1',	'extractParams' => '-p',			'ext' => array('zip', 'cbz') ),
	(object)array( 'command' => '7z',		'listParams' => 'l -slt',	'extractParams' => 'x -so',			'ext' => array('7z', 'cb7') ),
);

// used to cache archive contents
session_start();

// no query parameters means let user pick which archive to work with
if( count($_GET) == 0 ) {
	$archives = array();
	foreach($archiveTypes as $archKind) {
		foreach($archKind->ext as $ext) {
			$archPath = array(); // reset so exec doesn't keep appending
			exec("ls $rootPath" . DIRECTORY_SEPARATOR . "*.$ext", $archPath); // TODO add windows support
			foreach($archPath as $curArchPath) {
				$archives[] = (object)array(
					'path' => '"' . $curArchPath .'"',
					'name' => basename($curArchPath),
					'kind' => $archKind,
					'index' => count($archives),
					'items' => array()
				);
			}
		}
	}

	// reset session and store the archives we found, they will be referenced in later pages
	session_unset();
	$_SESSION['archives'] = $archives;
	
	drawPage_ArchiveList($archives);
} else {
	$archiveIndex = intval($_GET['archive']); // will be 0 if null or empty
	if( !array_key_exists('archives',$_SESSION) || $archiveIndex < 0 || $archiveIndex >= count($_SESSION['archives']) ) {
		drawPage_Error('must specify valid archive before doing anything');
		die();
	} else {
		$archive = $_SESSION['archives'][$archiveIndex];
		
		if( !isset($archive->path) || !isset($archive->kind) ) {
			drawPage_Error('invalid archive specified, something went horribly wrong X.X');
			die();
		}

		if( isset($_GET['doList']) ) {
			if( !isset($archive->items) || $archive->items == null ) {
				$archive->items = getItemsFromArchive($archive); // this updates session too
				//$_SESSION['archives'][$archiveIndex]->items = $archive->items; // is this necessary?
			}
			if($archive->items != null) {
				drawPage_ArchiveItems($archive);
			} else {
				drawPage_Error('can\'t load items for archive, something went horribly wrong X.X');
				die();
			}
		} else if( array_key_exists('item', $_GET) ) {
			$itemIndex = intval($_GET['item']);
			
			if( $itemIndex < 0 || $itemIndex >= count($archive->items) ) {
				drawPage_Error('invalid item specified');
				die();
			}

			if( isset($_GET['doView']) ) {
				drawPage_ItemView($archive, $itemIndex);
			} else {
				dumpItem($archive, $itemIndex);
				die(); // exit here so nothing can be accidentally printed after and corrupt the image
			}
		}
	}
}

function getItemsFromArchive($archive) {
	// dump file names in archive into $items
	exec("{$archive->kind->command} {$archive->kind->listParams} $archive->path", $items);
	
	// 7zip has no 'bare' list option, so dump detailed info and parse out file paths
	if($archive->kind->command == '7z') {
		$parsedItems = array();
		$skippedFirst = false;
		foreach($items as $item) {
			if( startsWith($item, 'Path = ') ) {
				if( !$skippedFirst ) {
					$skippedFirst = true; // first item in 7zip's detailed list is the archive name, we don't need that here
				} else {
					$parsedItems[] = substr($item, 7);
				}
			}
		}
		$items = $parsedItems;
	}

	return $items;
}

function startsWith($haystack, $needle)
{
    return strpos($haystack, $needle) === 0;
}
function endsWith($haystack, $needle)
{
    return substr($haystack, -strlen($needle)) == $needle;
}

function drawPage_Error($message) {
	echo $message;
}

function drawPage_ArchiveList($archives) {
	$archiveCount = count($archives);
	echo "Found $archiveCount items<br />\n";
	$curIndex = 0;
	foreach($archives as $file) {
		$htmlFile = htmlentities($file->name);
		echo "<a href='?archive=$curIndex&doList'>$htmlFile</a><br />\n";
		++$curIndex;
	}
}

function drawPage_ArchiveItems($archive) {
	$index = 0;
	foreach($archive->items as $item) {
		$htmlItem = htmlentities($item);
		echo "<a href='?archive=$archive->index&item=$index&doView'>$htmlItem</a><br />\n";
		++$index;
	}
}

function drawPage_ItemView($archive, $itemIndex) {
	$baseURL = "{$_SERVER[SCRIPT_NAME]}?archive=$archive->index&item=";
	$imageNames = json_encode(array_map('htmlentities',$archive->items));
	?>
	<!DOCTYPE html>
	<html>
		<head>
			<meta http-equiv="X-UA-Compatible" content="IE=10">
			<style>
			html, body {
				margin: 0px;
				padding: 0px;
				width: 100%;
				height: 100%;
			}
			img.view {
				margin: 0px;
				padding: 0px;
				width: 100%;
				cursor: pointer;
				display: none;
				visibility: visible;
			}
			</style>
			<title><?php echo $htmlItemName; ?></title>
		</head>
		<body onLoad="showInitialImage()">
			<script language="JavaScript">
			var baseURL = '<?php echo $baseURL; ?>';
			var imageNames = <?php echo $imageNames; ?>;
			var imageCount = imageNames.length;
			var curIndex = <?php echo $itemIndex; ?>;

			function showNextImage(lastView, currViewId, nextViewId) {
				curIndex = (curIndex + 1) % imageCount; // update global counter

				document.title = imageNames[curIndex];

				var nextIndex = (curIndex + 1) % imageCount; // used locally to preload next

				var currView = document.getElementById(currViewId);
				var nextView = document.getElementById(nextViewId);
				
				setBorderColor('1px solid red');
				
				lastView.style.display = 'none';
				lastView.src = 'transparent.gif'; // http://engineering.linkedin.com/linkedin-ipad-5-techniques-smooth-infinite-scrolling-html5
				lastView.alt = '';
				
				currView.style.display = 'inline'; // should already be loaded, just show it

				nextView.style.display = 'none';
				nextView.src = baseURL + nextIndex; // start loading next one
				nextView.alt = imageNames[nextIndex];
			}

			function showInitialImage() {
				// begin loading first image into view A
				var viewA = document.getElementById('viewA');
				viewA.src = baseURL + curIndex;
				viewA.alt = imageNames[curIndex];

				// will set this one to transparent
				var viewC = document.getElementById('viewC');

				--curIndex; // showNext will increment
				showNextImage(viewC, 'viewA', 'viewB'); // free up C, show A, load next image into B
			}
			
			function loaded(image) {
				console.log('loaded '+image.src);
				if(image.src.indexOf('transparent.gif') < 0 && image.style.display == 'none') {
					setBorderColor('1px solid lime');
				}
			}
			
			function setBorderColor(color) {
				console.log('setting border to '+color);
				document.getElementById('viewA').style.borderTop = color;
				document.getElementById('viewB').style.borderTop = color;
				document.getElementById('viewC').style.borderTop = color;
			}
			</script>
			<img id="viewA" class="view" onClick="showNextImage(this, 'viewB', 'viewC')" onLoad="loaded(this)" />
			<img id="viewB" class="view" onClick="showNextImage(this, 'viewC', 'viewA')" onLoad="loaded(this)" />
			<img id="viewC" class="view" onClick="showNextImage(this, 'viewA', 'viewB')" onLoad="loaded(this)" />
		</body>
	</html>
	<?php
}

function dumpItem($archive, $itemIndex) {
	$item = $archive->items[$itemIndex];
	if(endsWith($item, '.png')) {
		header('Content-Type: image/png');
	} else if(endsWith($item, '.jpg') || endsWith($item, '.jpeg')) {
		header('Content-Type: image/jpeg');
	} else {
		// TODO: properly handle dumping unknown file types and add more here (txt, nfo, etc)
	}
	
	passthru("{$archive->kind->command} {$archive->kind->extractParams} $archive->path \"$item\"");
}
?>