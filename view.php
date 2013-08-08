<?php
// path to read archives from, no trailing slash
$rootPath = '.';

// used to cache archive contents
session_start();

// no query parameters means let user pick which archive to work with
if( count($_GET) == 0 ) {
	exec("ls $rootPath", $archives);
	$archiveCount = count($archives);
	echo "Found $archiveCount items<br />\n";
	foreach($archives as $file) {
		$encFile = urlencode($file);
		$htmlFile = htmlentities($file);
		echo "<a href='?archive=$encFile&doList'>$htmlFile</a><br />\n";
	}
	echo "<br /><br /><a href='?reset'>reset session</a>";
} else {
	if( isset($_GET['reset']) ) {
		// this works but will be useful later
		session_unset();
		session_destroy();
		header("Location: {$_SERVER[SCRIPT_NAME]}");
	}
	else if( isset($_GET['archive']) ) {
		// attempting to prevent directory traversal, surely there is a better way?
		$archiveName = str_replace(array('\\','/'), '', $_GET['archive']);

		$fullPath =  '"' . $rootPath . DIRECTORY_SEPARATOR . $archiveName . '"';
		$htmlFullPath = htmlentities($fullPath);

		if( isset($_SESSION[$fullPath]) ) {
			$items = $_SESSION[$fullPath];
		}
		
		if( isset($_GET['doList']) ) {
			echo "Showing archive $htmlFullPath:<br />\n";

			if( !isset($items) ) {
				// dump file names in archive into $items
				if(endsWith($fullPath,'.rar"') || endsWith($fullPath,'.cbr"')) {
					exec("unrar lb $fullPath", $items);
				} else if(endsWith($fullPath,'.zip"') || endsWith($fullPath,'.cbz"')) {
					exec("unzip -Z -1 $fullPath", $items);
				} else {
					// TODO: add 7z and whatever other formats
					$items = null;
				}
				$_SESSION[$fullPath] = $items;
			}

			if($items == null) {
				echo("unknown archive $htmlFullPath");
			} else {
				foreach($items as $item) {
					$htmlItem = htmlentities($item);
					$encItem = urlencode($item);
					$encFile = urlencode($archiveName);
					echo "<a href='?archive=$encFile&item=$encItem&doView'>$htmlItem</a><br />\n";
				}
			}
		} else if( isset($_GET['item']) ) {
			// this comes from inside archive and can be anything
			$itemName = $_GET['item'];
			$htmlItemName = htmlentities($itemName);
			
			// make sure it did indeed come from archive
			// TODO: quotes inside archive names will probably break stuff, should fix it here
			if( !in_array($itemName, $_SESSION[$fullPath]) ) {
				die("error: invalid file name [$htmlItemName] for archive [$htmlFullPath]");
			}
			
			if( !isset($_GET['doView']) ) {
				if(endsWith($itemName, '.png')) {
					header('Content-Type: image/png');
				} else if(endsWith($itemName, '.jpg') || endsWith($itemName, '.jpeg')) {
					header('Content-Type: image/jpeg');
				} else {
					// TODO: properly handle dumping unknown file types and add more here (txt, nfo, etc)
				}
				
				if(endsWith($fullPath,'.rar"') || endsWith($fullPath,'.cbr"')) {
					passthru("unrar p -c- -idq $fullPath \"$itemName\"");
				} else if(endsWith($fullPath,'.zip"') || endsWith($fullPath,'.cbz"')) {
					passthru("unzip -p $fullPath \"$itemName\"");
				}
				
				die();
			} else {
				$next = '';
				$prev = '';
				foreach($items as $item) {
					if($next == $itemName) {
						$next = $item;
						break;
					}
					
					if($item == $itemName) {
						$next = $item;
					} else {
						$prev = $item;
					}
				}
				if($next == $itemName) {
					$next = $items[0];
				}
				if($prev == '') {
					$prev = $items[count($items)-1];
				}
				
				$safeFileName = urlencode($archiveName);
				$safeItemName = urlencode($itemName);
				$safeNextName = urlencode($next);
				$safePrevName = urlencode($prev);

				$currURL = "?archive=$safeFileName&item=$safeItemName";
				$nextURL = "?archive=$safeFileName&item=$safeNextName&doView";
				$prevURL = "?archive=$safeFileName&item=$safePrevName&doView";
				
				$htmlItemName = htmlentities($itemName);
				?>
				<html>
					<head>
						<style>
						html { 
							background: url(<?php echo $currURL; ?>) no-repeat center center fixed;
							-webkit-background-size: contain;
							-moz-background-size: contain;
							-o-background-size: contain;
							background-size: contain;
						}
						body {
							margin: 0px;
							padding: 0px;
							width: 100%;
							height: 100%;
						}
						div.prev {
							margin: 0px;
							padding: 0px;
							width:50%;
							height:100%;
							float:left;
						}
						
						div.next {
							margin: 0px;
							padding: 0px;
							width:50%;
							height:100%;
							float:right;
						}
						</style>
						<title><?php echo $htmlItemName; ?></title>
					</head>
					<body>
						<div onClick="location.href='<?php echo $prevURL; ?>'" class="prev">&nbsp;</div>
						<div onClick="location.href='<?php echo $nextURL; ?>'" class="next">&nbsp;</div>
					</body>
				</html>
				<?php
			}
		}
	}
}

function startsWith($haystack, $needle)
{
    return strpos($haystack, $needle) === 0;
}
function endsWith($haystack, $needle)
{
    return substr($haystack, -strlen($needle)) == $needle;
}
?>
