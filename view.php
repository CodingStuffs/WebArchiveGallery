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
			if( !is_array($_SESSION[$fullPath]) || !in_array($itemName, $_SESSION[$fullPath]) ) {
				die("error: invalid file name [$htmlItemName] for archive [$htmlFullPath], <a href='?'>start from beginning</a>");
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
				// first figure which image we clicked on
				$currentIndex = 0;
				foreach($items as $item) {
					if($item == $itemName) {
						break;
					} else {
						++$currentIndex;
					}
				}

				$safeFileName = htmlentities($archiveName);
				$baseURL = "{$_SERVER[SCRIPT_NAME]}?archive=$safeFileName&item=";
				$imagePaths = json_encode($items);
				
				$htmlItemName = htmlentities($itemName);
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
						<script language="javascript">
						var baseURL = '<?php echo $baseURL; ?>';
						var imagePaths = <?php echo $imagePaths; ?>;
						var imageCount = imagePaths.length;
						var curIndex = <?php echo $currentIndex; ?>;

						function showNextImage(lastView, currViewId, nextViewId) {
							console.log('showNextImage '+lastView.id+' '+currViewId+' '+nextViewId);

							curIndex = (curIndex + 1) % imageCount; // update global counter
							
							document.title = imagePaths[curIndex];

							var nextIndex = (curIndex + 1) % imageCount; // used locally to preload next

							var currView = document.getElementById(currViewId);
							var nextView = document.getElementById(nextViewId);
							
							lastView.style.display = 'none';
							lastView.src = 'transparent.gif'; // http://engineering.linkedin.com/linkedin-ipad-5-techniques-smooth-infinite-scrolling-html5
							
							currView.style.display = 'inline'; // should already be loaded
							console.log(currView.style.display +' '+ currView.style.visibility +' '+ currView.src);

							nextView.style.display = 'none';
							nextView.src = baseURL + imagePaths[nextIndex];
						}

						function showInitialImage() {
							// begin loading first image into view A
							var viewA = document.getElementById('viewA');
							viewA.src = baseURL + imagePaths[curIndex];

							// will set this one to transparent
							var viewC = document.getElementById('viewC');
							
							--curIndex; // nextImage will up this back to where it should be for initial run
							showNextImage(viewC, 'viewA', 'viewB'); // "free up" C, show A, load next image into B
						}
						</script>
						<img id="viewA" class="view" onClick="showNextImage(this, 'viewB', 'viewC')" />
						<img id="viewB" class="view" onClick="showNextImage(this, 'viewC', 'viewA')" />
						<img id="viewC" class="view" onClick="showNextImage(this, 'viewA', 'viewB')" />
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