<?php
class SectionStore
{
	private $arr = Array();
	public $wikiHost = '';
	
	public function __construct($wh)
	{
		$this->wikiHost = $wh;
	}
	
	public function getSections($page)
	{
		if(!isset($this->arr[$page])) {
			$this->addPage($page);
		}
		return $this->arr[$page];
	}
	
	public function hasSection($section, $page)
	{
		return in_array($section, $this->getSections($page));
	}
	
	private function addPage($page)
	{
		/* Calls MW API */
		$pageForUrl = rawurlencode($page);
        $req = curl_init("https://" . $this->wikiHost . "/w/api.php?action=parse&prop=sections&page=$pageForUrl&format=php");
        curl_setopt($req, CURLOPT_RETURNTRANSFER, 1);
        $ser = curl_exec($req);
        $unser = unserialize($ser);
		
		$sections = $unser['parse']['sections'];
		//var_dump($sections);
		$this->arr[$page] = Array();
		foreach($sections as $s) {
			$this->arr[$page][$s['index']] = $s['line'];
		}
	}
};
?>