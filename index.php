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
		<meta name="keywords" content="section links toolserver pietrodn" />
		<link rel="shortcut icon" href="/favicon.ico" />

		<title>Section Links - Wikimedia Tool Labs</title>
		<link rel="stylesheet" type="text/css" href="pietrodn.css" />
	</head>
<body>
	<div id="globalWrapper">
		<div id="column-content">
	<div id="content">
		<a id="top"></a>
		<h1 class="firstHeading">Section Links</h1>

		<div id="bodyContent">
			<h3 id="siteSub">Wikimedia Tool Labs - Pietrodn's tools.</h3>
			<!-- start content -->
			<p>This tool shows inexistent section links from or to a page. It follows redirects, but if the starting page is a redirect the tool doesn't follow it.</p>

<form id="ListaForm" action="<? echo $_SERVER['PHP_SELF']; ?>" method="get">
<fieldset>
<label id="wikiDb">Project:
<select name="wikiDb">
<?php
    projectChooser($_GET['wikiDb']); // $allWikis passed by reference!
?>
</select></label>
<div style="float:left; margin-right:5px;">
<?php
$directions = Array('from'=>'From: ', 'to'=>'To: ');
foreach($directions as $i=>$label)
{
    $selected='';
    if($_GET['wikiDirection']==$i || $i == 'from')
        $selected='checked ';
        
    // Disabling "to" option (MediaWiki bug!)
    if($i=='to')
        $disabled='disabled ';
    echo "<input type=\"radio\" name=\"wikiDirection\" value=\"$i\" $selected $disabled/> $label<br />";
}
?>
</div>
<input type="text" size="20" name="wikiPage" value="<? print htmlentities($_GET['wikiPage'], ENT_QUOTES, 'UTF-8'); ?>" />
<br style="clear:both;" />
</fieldset>
<input id="SubmitButton" type="submit" value="Show" />
</form>

<?php
    define('MAX_API_CALLS', 20);
    $apiCalls = 0;
    
    $wikiDb = addslashes($_GET['wikiDb']); // A little more security
    
    if(!$_GET['wikiDb'] and !$_GET['wikiDirection'] and !$_GET['wikiPage'])
        print "";
    else if(!$_GET['wikiDb'] or !$_GET['wikiDirection'] or !$_GET['wikiPage'])
        printError('Some parameters are missing.');
    else if(!in_array($_GET['wikiDb'], $wikiProjects))
        printError('You tried to select a non-existent wiki!');
    else if(!in_array($_GET['wikiDirection'], array_keys($directions)))
        printError('Error specifying the direction.');
    else
    {
        $wikiHost = getWikiHost($wikiDb);
        
        $ss = new SectionStore($wikiHost);
        
        $pageForUrl = rawurlencode($_GET['wikiPage']);
        $pageText = getPageText($_GET['wikiPage'], FALSE);
        if($pageText=='')
        {
            printError("The page that you entered doesn't exist in this wiki.");
        } else if($_GET['wikiDirection'] == 'from') {
            echo "Links to inexistent sections in the specified <a href=\"//$wikiHost/wiki/$pageForUrl\">page</a> (<a href=\"//$wikiHost/w/index.php?title=$pageForUrl&action=edit\">edit</a>):\n";
            $linkList = new SectionLinkList($wikiHost);
            preg_match_all('/\[\[([^\]|]*)#([^\]|]+)(\|[^\]]+)?\]\]/', $pageText, $linkedMatches, PREG_SET_ORDER);
            foreach($linkedMatches as $linkedMatch)
            {
                $linkedPage = $linkedMatch[1];
                $linkedSect = str_replace('_', ' ', rawurldecode($linkedMatch[2]));
                if($linkedPage == '')
                {
                    $linkedPage = $_GET['wikiPage'];
                }
                
                $flags = 0;
                
                if(!$ss->hasSection($linkedSect, $linkedPage))
                {
                    /*if(hasAnchor($linkedSect, $linkedText))
                    {
                        $flags |= SectionLinkList::ANCHOR_LINK;
                    }*/
                    $linkList->add($_GET['wikiPage'], $linkedPage, $linkedSect, $flags);
                }
            }
            $linkList->printList();
        } else if($_GET['wikiDirection'] == 'to') {
            // Links pointing to the specified page
            echo "Links to inexistent sections pointing to the specified <a href=\"//$wikiHost/wiki/$pageForUrl\">page</a> (<a href=\"//$wikiHost/w/index.php?title=$pageForUrl&action=edit\">edit</a>):\n";
            echo '<ul>';
            
            // Sections of specified page
            $mySections = $ss->getSections($_GET['wikiPage']);
            
            // Get references
            $references = getReferences($_GET['wikiPage']);
            $references[] = array('title' => $_GET['wikiPage'], 'revisions' => array(0 => array('*' => $pageText))); // Adding the start page page as possible reference, as it's not included in backreferences.
            //var_dump($references);
            $refList = new SectionLinkList($wikiHost);
            foreach($references as $row)
            {
                $refPage = $row['title'];
                $refText = $row['revisions'][0]['*'];
                $pageForRegexp = preg_replace('/[ _]/', '[ _]', preg_quote($_GET['wikiPage'], '/'));
                preg_match_all('/\[\[(' . $pageForRegexp . ')' . 
                    ($refPage == $_GET['wikiPage'] ? '?' : '') . // Destination page can be omitted only if source = dest.
                    '#([^\]|]+)(\|[^\]]+)?\]\]/',
                    $refText, $refMatches, PREG_SET_ORDER);  
                //var_dump($refMatches);
                foreach($refMatches as $refMatch)
                {
                	
                    $flags = 0;
                    $refSect = str_replace('_', ' ', rawurldecode($refMatch[2]));
                    echo $refSect;
                    if(!in_array($refSect, $mySections) || $_GET['wikiPage'] == $refPage)
                    {
                    	/*
                        if(hasAnchor($refSect, $pageText))
                        {
                            $flags |= SectionLinkList::ANCHOR_LINK;
                        }*/
                        $refList->add($refPage, $_GET['wikiPage'], $refSect, $flags);
                    }
                }
            }
            $refList->printList();
        }
    }
    
    function getReferences($page, $queryContinue='')
    {
        global $wikiHost, $apiCalls;
        if(++$apiCalls > MAX_API_CALLS)
        {
            printError('Max. API call limit exceeded (' . MAX_API_CALLS . ')');
            exit();
        }
        
        $pageForUrl = rawurlencode($page);
        $url = "https://$wikiHost/w/api.php?action=query&prop=revisions&generator=backlinks&gbltitle=$pageForUrl$queryContinue&rvprop=content&redirects&format=php";
        $req = curl_init($url);
        curl_setopt($req, CURLOPT_RETURNTRANSFER, 1);
        $ser = curl_exec($req);
        $unser = unserialize($ser);
        $references = $unser['query']['pages'];
        //echo $url . "\n\n";
        // Recursive function for query-continue
        if(isset($unser['query-continue']))
        {
            $qc = '&gblcontinue=' . $unser['query-continue']['backlinks']['gblcontinue'];
            //echo $qc . "\n";
            //echo $url . "\n";
            return array_merge($references, getReferences($page, $qc));
        } else {
        	
        	//var_dump($unser);
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
        {
            printError('Max. API call limit exceeded (' . MAX_API_CALLS . ')');
            exit();
        }
        $redirects = '';
        if($followRedirects)
            $redirects='&redirects'; 
        $pageForUrl = rawurlencode($page);
        $req = curl_init("https://$wikiHost/w/api.php?action=query&prop=revisions&titles=$pageForUrl&rvprop=content$redirects&format=php");
        curl_setopt($req, CURLOPT_RETURNTRANSFER, 1);
        $ser = curl_exec($req);
        $unser = unserialize($ser);
        $singlePage = array_shift($unser['query']['pages']);
        $pageText = $singlePage['revisions'][0]['*'];
        $textCache[$page] = $pageText;
        return $pageText;
    }
?>
			</div><!-- end content -->
						<div class="visualClear"></div>

	</div>
		</div>
		<div id="column-one">
	<div id="p-cactions" class="portlet">
		<h3>Visite</h3>
		<div class="pBody">
			<ul>
	
				 <li id="ca-nstab-project" class="selected"><a href="<? echo $_SERVER['PHP_SELF']; ?>" title="The tool [t]" accesskey="t">tool</a></li>
				 <li id="ca-source"><a href="//github.com/pietrodn/section-links" title="See the source code of this tool [s]" accesskey="s">source</a></li>
			</ul>
		</div>
	</div>

	<div class="portlet" id="p-logo">
		<a style="background-image: url(//wikitech.wikimedia.org/w/images/thumb/6/60/Wikimedia_labs_logo.svg/120px-Wikimedia_labs_logo.svg.png);" href="//wikitech.wikimedia.org" title="Wikimedia Tool Labs" accesskey="w"></a>
	</div>
		<div class='generated-sidebar portlet' id='p-navigation'>
		<h3>Navigation</h3>
		<div class='pBody'>
			<ul>
				<li id="n-pietrodn"><a href="//wikitech.wikimedia.org/wiki/User:Pietrodn">Pietrodn</a></li>
				<li id="n-svn"><a href="//github.com/pietrodn/section-links">Git repository</a></li>
			</ul>
		</div>
	</div>
	
	<div class='generated-sidebar portlet' id='p-tools'>

		<h3>Tools</h3>
		<div class='pBody'>
			<ul>
				<li id="t-intersectcontribs"><a href="/intersect-contribs">Intersect Contribs</a></li>
				<li id="t-sectionlinks"><a href="/section-links">Section Links</a></li>
			</ul>
		</div>
	</div>
	
		</div>
			<div class="visualClear"></div>
			<div id="footer">
			<div id="f-copyrightico">
<!-- Creative Commons License -->
<a href="//www.gnu.org/licenses/gpl.html">
<img alt="CC-GNU GPL 2.0" src="images/cc-GPL-a.png" height="50" /></a>
<!-- /Creative Commons License -->
</div>
				<div id="f-poweredbyico"><a href="http://validator.w3.org/check?uri=referer"><img src="images/valid-xhtml10.png" alt="Valid XHTML 1.0 Strict" height="31" width="88" /></a></div>
			<ul id="f-list">
				<li id="about"><a href="//wikitech.wikimedia.org/wiki/User:Pietrodn" title="User:Pietrodn">About Pietrodn</a></li>
				<li id="email"><a href="mailto:pietrodn@toolserver.org" title="Mail">e-mail</a></li>
			</ul>
		</div>

</div>
</body></html>
