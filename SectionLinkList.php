<?php
class SectionLinkList
{
	private $arr = Array();
	public $wikiHost = '';
	
	const NOPAGE_LINK = 1;
	const ANCHOR_LINK = 2;
	
	function __construct($wh)
	{
		$this->wikiHost = $wh;
	}
	
	function add($from, $to, $section, $flags=0)
	{
		$hash = md5($from . $to . $section);
		if(array_key_exists($hash, $this->arr))
		{
			$this->arr[$hash]['count'] += 1;
		} else {
			$this->arr[$hash]['from'] = str_replace('_', ' ', rawurldecode($from));
			$this->arr[$hash]['to'] = str_replace('_', ' ', rawurldecode($to));
			$this->arr[$hash]['section'] = str_replace('_', ' ', rawurldecode($section));
			$this->arr[$hash]['flags'] = $flags;
			$this->arr[$hash]['count'] = 1;
		}
	}
	
	function printList()
	{
		if(count($this->arr)==0)
		{
			echo "<p>No results.</p>";
		} else {
			echo '<ol>';
			foreach($this->arr as $hash=>$row)
			{
				$linkUrl = rawurlencode(str_replace(' ', '_', $row['to'] . '#' . $row['section']));
				$fromUrl = rawurlencode(str_replace(' ', '_', $row['from']));
				$from = htmlspecialchars($row['from'], ENT_NOQUOTES);
				$to = htmlspecialchars($row['to'], ENT_NOQUOTES);
				$sect = htmlspecialchars($row['section'], ENT_NOQUOTES);
				$nopage = $anchor = $samepage = $doublespace = '';
				if(($row['flags'] & self::NOPAGE_LINK) == self::NOPAGE_LINK)
				{
					$nopage = ' nopage';
				}
				if(($row['flags'] & self::ANCHOR_LINK) == self::ANCHOR_LINK)
				{
					$anchor = ' (Warning: there is an anchor with this name!)';
				}
				if($from == $to)
				{
					$samepage = ' samepage';
				}
				if(strpos($row['section'], '  ') !== FALSE)
				{
					$doublespace = ' (Warning: double space in section link!)';
				}
				$count = $row['count'];
				$wh = $this->wikiHost;
				echo "<li><a href=\"//$wh/w/index.php?title=$fromUrl\">$from</a> → <a href=\"//$wh/w/index.php?title=$linkUrl\"><span class=\"$nopage$samepage\">$to</span><span class=\"canc\">#</span>$sect</a> ($count)$anchor$doublespace</li>";
			}
			echo '</ol>';
		}
	}
};
?>