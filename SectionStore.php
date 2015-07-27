<?php
class SectionStore
{
	private $arr = Array();
	public $wikiHost = '';
	
	const SECTION_YES = 1;
	const SECTION_NO = 2;
	const RED_LINK = 3;
	
	public function __construct($wh)
	{
		$this->wikiHost = $wh;
	}
	
	/* Returns an array of sections, or false if the page doesn't exists. */
	public function getSections($page)
	{
		if(!isset($this->arr[$page])) {
			$this->addPage($page);
		}
		return $this->arr[$page];
	}
	
	public function hasSection($section, $page)
	{
		$sects = $this->getSections($page);
		if($sects === FALSE)
			return self::RED_LINK;
		if(in_array($section, $sects))
			return self::SECTION_YES;
		else
			return self::SECTION_NO;
	}
	
	private function addPage($page)
	{
		/* Calls MW API */
		$pageForUrl = rawurlencode($page);
        $req = curl_init("https://" . $this->wikiHost . "/w/api.php?action=parse&prop=sections&page=$pageForUrl&format=json");
        curl_setopt($req, CURLOPT_RETURNTRANSFER, 1);
        $ser = curl_exec($req);
        $unser = json_decode($ser, TRUE);
		
		if(isset($unser['error']) && $unser['error']['code'] == 'missingtitle') {
			$this->arr[$page] = FALSE;
		}
		
		$this->arr[$page] = Array();
		if(isset($unser['parse']['sections'])) {
			$sections = $unser['parse']['sections'];
		
			foreach($sections as $s) {
				$this->arr[$page][$s['index']] = $s['line'];
			}
		}
	}
};
?>