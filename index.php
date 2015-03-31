<?php
/*
    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
    include_once 'pietrodnUtils.php';
    include_once 'SectionLinkList.php';
    include_once 'SectionStore.php';
?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="UTF-8">
		<meta name="keywords" content="section links wmflabs pietrodn" />
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link rel="shortcut icon" href="/favicon.ico" />

		<title>Section Links - Wikimedia Tool Labs</title>
		<link href="//tools-static.wmflabs.org/static/bootstrap/3.2.0/css/bootstrap.min.css" rel="stylesheet">
        <link href="pietrodn.css" rel="stylesheet">
	</head>
<body>
	<!-- Fixed navbar -->
	<div class="navbar navbar-inverse navbar-fixed-top" role="navigation">
	  <div class="container">
		<div class="navbar-header">
		  <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
			<span class="sr-only">Toggle navigation</span>
			<span class="icon-bar"></span>
			<span class="icon-bar"></span>
			<span class="icon-bar"></span>
		  </button>
		  <a class="navbar-brand" href="//tools.wmflabs.org/">WMF Tool Labs</a>
		</div>
		<div class="navbar-collapse collapse">
		  <ul class="nav navbar-nav">
			<li class="active"><a href=".">Section Links</a></li>
			<li><a href="//github.com/pietrodn/section-links">Source (GitHub)</a></li>
			<li><a href="//wikitech.wikimedia.org/wiki/User:Pietrodn">Pietrodn</a></li>
			<li><a href="../intersect-contribs/">Intersect Contribs</a></li>
		  </ul>
		</div><!--/.nav-collapse -->
	  </div>
	</div>

	<div class="jumbotron">
		<div class="container">
			<div class="media">
				<a class="pull-left" href="#">
					<img id="wikitech-logo" class="media-object" src="images/WikitechLogo.png" alt="Wikitech Logo">
				</a>
				<div class="media-body">
					<h1>Section Links<br />
					<small>Wikimedia Tool Labs — Pietrodn's tools.</small>
					</h1>
				</div>
			</div>
			<!-- start content -->
			<p>This tool shows inexistent section links from or to a page.</p>
			<p>Known issues:</p>
			<ul>
				<li>Highly experimental and not fully developed tool.</li>
				<li>The tool uses the MediaWiki Web API and not the replicated databases in order to get revision full texts.</li>
				<li><em>To</em> option is quite slow and expensive: it needs to retrieve the text of every reference to the given page (it frequently hits the API call limit).</li>
				<li>Redirect handling is inconsistent.</li>
			</ul>
		
			<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="get">
				<div class="form-group">
					<label for="wikiDb">Project</label>:
					<select class="form-control" name="wikiDb" id="wikiDb" required>
					<?php
						/* Generates the project chooser dropdown */
						$selectedProject = (isset($_GET['wikiDb']) ? $_GET['wikiDb'] : NULL);
						projectChooser($selectedProject);
					?>
					</select>
				</div>
				<?php
					$directions = Array('from'=>'From: ', 'to'=>'To: ');
					foreach($directions as $i=>$label)
					{
						$selected='';
						if(isset($_GET['wikiDirection']) && $_GET['wikiDirection']==$i) {
							$selected='checked ';
						}
						echo '<div class="radio">';
						echo "<input type=\"radio\" name=\"wikiDirection\" required value=\"$i\" $selected/> $label<br />";
						echo '</div>';
					}
				?>
				<div class="form-group">
				<input class="form-control" type="text" placeholder="Page title" required name="wikiPage" value="<?php 
					if(isset($_GET['wikiPage']))
					print htmlentities($_GET['wikiPage'], ENT_QUOTES, 'UTF-8'); ?>" />
				</div>
				<input class="btn btn-default" id="SubmitButton" type="submit" value="Show" />
			</form>
		</div>
	</div>

	<div class="container">

<?php
	define('MAX_API_CALLS', 40);
	define('ALL_PARAMS_MISSING_EXC', 1);
	define('SOME_PARAMS_MISSING_EXC', 2);
	define('NONEXISTENT_WIKI_EXC', 3);
	define('DIRECTION_EXC', 4);
	define('API_EXC', 5);

	$apiCalls = 0;

	try {

		if(empty($_GET['wikiDb']) and empty($_GET['wikiDirection']) and empty($_GET['wikiPage']))
			throw new Exception("", ALL_PARAMS_MISSING_EXC);
		if(empty($_GET['wikiDb']) or empty($_GET['wikiDirection']) or empty($_GET['wikiPage']))
			throw new Exception('Some parameters are missing.', SOME_PARAMS_MISSING_EXC);
		if(!($wikiHost = getWikiHost($_GET['wikiDb'])))
			throw new Exception('You tried to select a non-existent wiki!', NONEXISTENT_WIKI_EXC);
		if(!in_array($_GET['wikiDirection'], array_keys($directions)))
			throw new Exception('Error specifying the direction.', DIRECTION_EXC);
	
		$list = new SectionLinkList($wikiHost);
		$ss = new SectionStore($wikiHost);
	
		$pageForUrl = rawurlencode($_GET['wikiPage']);
		$pageText = getPageText($_GET['wikiPage'], FALSE);
		if($pageText===FALSE)
			throw new Exception("The page that you entered doesn't exist in this wiki.");
		
		if($_GET['wikiDirection'] == 'from') {            
			preg_match_all('/\[\[([^\]|]*)#([^\]|]+)(\|[^\]]+)?\]\]/', $pageText, $linkedMatches, PREG_SET_ORDER);
			foreach($linkedMatches as $linkedMatch) {
				$linkedPage = $linkedMatch[1];
				$linkedSect = str_replace('_', ' ', rawurldecode($linkedMatch[2]));
				if($linkedPage == '') {
					$linkedPage = $_GET['wikiPage'];
				}
			
				$flags = 0;
			
				$result = $ss->hasSection($linkedSect, $linkedPage);
				if($result != SectionStore::SECTION_YES) {
					if($result == SectionStore::RED_LINK) {
						$flags |= SectionLinkList::NOPAGE_LINK;
					} else if($linkedPage == $_GET['wikiPage'] && hasAnchor($linkedSect, $pageText)) {
						$flags |= SectionLinkList::ANCHOR_LINK;
					}
					$list->add($_GET['wikiPage'], $linkedPage, $linkedSect, $flags);
				}
			}
		
		} else if($_GET['wikiDirection'] == 'to') {
			// Links pointing to the specified page
			echo '<ul>';
		
			// Sections of specified page
			$mySections = $ss->getSections($_GET['wikiPage']);
		
			// Get references
			$references = getReferences($_GET['wikiPage']);
			$references[] = array('title' => $_GET['wikiPage'], 'revisions' => array(0 => array('*' => $pageText))); // Adding the start page page as possible reference, as it's not included in backreferences.
		
			foreach($references as $row) {
				$refPage = $row['title'];
				$refText = $row['revisions'][0]['*'];
				$pageForRegexp = preg_replace('/[ _]/', '[ _]', preg_quote($_GET['wikiPage'], '/'));
				preg_match_all('/\[\[(' . $pageForRegexp . ')' . 
					($refPage == $_GET['wikiPage'] ? '?' : '') . // Destination page can be omitted only if source = dest.
					'#([^\]|]+)(\|[^\]]+)?\]\]/',
					$refText, $refMatches, PREG_SET_ORDER);  
			
				foreach($refMatches as $refMatch) {
					$flags = 0;
					$refSect = str_replace('_', ' ', rawurldecode($refMatch[2]));
				
					if(!in_array($refSect, $mySections) || $_GET['wikiPage'] == $refPage)
					{
						if(hasAnchor($refSect, $pageText))
						{
							$flags |= SectionLinkList::ANCHOR_LINK;
						}
						$list->add($refPage, $_GET['wikiPage'], $refSect, $flags);
					}
				}
			}
		}
	
		$count = $list->countList();
		if($count === 0) {
			echo '<div class="alert alert-info">';
			echo 'No results found.';
			echo '</div>';
		} else {
			echo '<div class="alert alert-success">';
			if($_GET['wikiDirection'] == 'from') {
				echo "Links to inexistent sections in the specified <a href=\"//$wikiHost/wiki/$pageForUrl\">page</a> (<a href=\"//$wikiHost/w/index.php?title=$pageForUrl&amp;action=edit\">edit</a>):\n";
			} else if($_GET['wikiDirection'] == 'to') {
				echo "Links to inexistent sections pointing to the specified <a href=\"//$wikiHost/wiki/$pageForUrl\">page</a> (<a href=\"//$wikiHost/w/index.php?title=$pageForUrl&amp;action=edit\">edit</a>):\n";
			}
			echo $count . ' results found.';
			echo '</div>';	
			$list->printList();
		}
	
	} catch (Exception $e) {
		if(!($e->getCode() == ALL_PARAMS_MISSING_EXC))
			printError($e->getMessage());
		if($e->getCode() == API_EXC) {
			printError("The following list is incomplete.");
			$list->printList();
		}
	}

	function getReferences($page, $queryContinue='')
	{
		global $wikiHost, $apiCalls;
		if(++$apiCalls > MAX_API_CALLS)
			throw new Exception('Max. API call limit exceeded (' . MAX_API_CALLS . ')', API_EXC);
	
		$pageForUrl = rawurlencode($page);
		$url = "https://$wikiHost/w/api.php?action=query&prop=revisions&generator=backlinks&gbltitle=$pageForUrl$queryContinue&rvprop=content&format=php";
		$req = curl_init($url);
		curl_setopt($req, CURLOPT_RETURNTRANSFER, 1);
		$ser = curl_exec($req);
		$unser = unserialize($ser);
		$references = $unser['query']['pages'];
		// Recursive function for query-continue
		if(isset($unser['query-continue']))
		{
			$qc = '&gblcontinue=' . $unser['query-continue']['backlinks']['gblcontinue'];
			return array_merge($references, getReferences($page, $qc));
		} else {
		
			return $references;
		}
	}

	function hasSection($sectName, $text)
	{
		return preg_match('/(=+) *' . preg_quote($sectName, '/') . ' *\1/', $text);
	}

	function hasAnchor($anchorName, $text)
	{
		return preg_match('/id="[^"]*\b' . preg_quote($anchorName, '/') . '\b[^"]*/', $text);
	}

	function getPageText($page, $followRedirects=TRUE)
	{
		global $wikiHost, $apiCalls;
		static $textCache = array();
	
		if(array_key_exists($page, $textCache))
		{
			return $textCache[$page];
		}
	
		if(++$apiCalls > MAX_API_CALLS)
			throw new Exception('Max. API call limit exceeded (' . MAX_API_CALLS . ')', API_EXC);
		
		$redirects = '';
		if($followRedirects)
			$redirects='&redirects'; 
		$pageForUrl = rawurlencode($page);
		$req = curl_init("https://$wikiHost/w/api.php?action=query&prop=revisions&titles=$pageForUrl&rvprop=content$redirects&format=php");
		curl_setopt($req, CURLOPT_RETURNTRANSFER, 1);
		$ser = curl_exec($req);
		$unser = unserialize($ser);
		$singlePage = array_shift($unser['query']['pages']);
		if(isset($singlePage['missing'])) {
			return FALSE;
		}
		$pageText = $singlePage['revisions'][0]['*'];
		$textCache[$page] = $pageText;
		return $pageText;
	}
	?>
	</div>

	<div id="footer">
		<div class="container">
			<a href="//tools.wmflabs.org/"><img id="footer-icon" src="//tools-static.wmflabs.org/static/logos/powered-by-tool-labs.png" title="Powered by Wikimedia Tool Labs" alt="Powered by Wikimedia Tool Labs" /></a>
			<p class="text-muted credit">
			Made by <a href="//wikitech.wikimedia.org/wiki/User:Pietrodn">Pietro De Nicolao (Pietrodn)</a>.
			Licensed under the
			<a href="//www.gnu.org/licenses/gpl.html">GNU GPL</a> license.
			</p>
		</div>
	</div>
	
	<!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script src="//tools-static.wmflabs.org/static/jquery/2.1.0/jquery.min.js"></script>
    <!-- Include all compiled plugins (below), or include individual files as needed -->
    <script src="//tools-static.wmflabs.org/static/bootstrap/3.2.0/js/bootstrap.min.js"></script>
    
	</body>
</html>