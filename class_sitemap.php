<?php
class sitemapXmlExt
{

	public $ignore = [];
	public $iBlocks = [];
	public $pages = [];
	public $sitemap = [];
	public $HomeUrl = '/';

	function __construct($home_url, $iBlocks = [], $ignorePages = [])
	{
		$this->iBlocks = $iBlocks;
		$this->ignore = $ignorePages;
		$this->HomeUrl = $home_url;
	}
	/***************************************/
	public function generate()
	{
		$this->AddPagesFromIBlock();
		$this->pages = $this->DeleteIgnorePages();
	}
	/***************************************/
	public function DeleteIgnorePages()
	{
		$pages = $this->pages;
		/*Не включаем игнорируемые страницы и дубли */
		$result =  [];
		$allready = [];
		if (!is_array($pages)) {
			return $result;
		};

		foreach ($pages as $val) {
			if (in_array($val['loc'], $this->ignore)) {
				continue;
			}
			if (in_array($val['loc'], $allready)) {
				continue;
			}
			$allready[] = trim($val['loc'], '/');
			$allready[] = trim($val['loc'], '/') . '/';
			$result[] = $val;
		}

		return $result;
	}
	/***************************************/
	public function AddPage($url, $priority = 1, $lastmod = '', $changefreq = 'monthly')
	{
		$url = str_replace('/index.php', '/', $url);
		if (in_array($url, $this->ignore)) {
			return false;
		};

		$page = [];
		$page['loc'] = $this->prepareLocation($url);
		$page['priority'] = $priority;
		if ($lastmod != '') {
			$page['lastmod'] = date('Y-m-d', strtotime($lastmod));
		} else {
			$page['lastmod'] = date('Y-m-01');
		}
		if ($changefreq != '') {
			$page['changefreq'] = $changefreq;
		};
		$this->pages[] = $page;

		return $page;
	}
	/***************************************/
	public function AddIgnorePage($url)
	{
		$this->ignore[] = $url;
	}
	/***************************************/
	public function CalcPriorBySlash($url)
	{
		$url = str_replace($this->HomeUrl, '', $url);
		$prior = 0.8 - (substr_count(trim($url, '/'), '/') * 0.1);
		if ($prior < 0.4) {
			$prior = 0.4;
		}
		return $prior;
	}
	/***************************************/
	public function prepareLocation($url)
	{
		if (mb_strpos($url, $this->HomeUrl) === false) {
			if (mb_substr($url, 0, 1) != '/') {
				$url = '/' . $url;
			};
			$url = $this->HomeUrl . $url;
		};
		return $url;
	}
	/***************************************/
	public function AddPagesFromIBlock()
	{
		$arIBlocks = $this->iBlocks;
		$arSectionIBlockFilter = [];
		$arSectionIBlockIDs = [];
		$arDetailIBlockFilter = [];
		$arDetailIBlockIDs = [];
		if (is_array($arIBlocks)) {
			foreach ($arIBlocks as $iBlock) {
				if ($iBlock['SECTION'] != 'N') {
					if ((isset($iBlock['SECTION_FILTER'])) && (is_array($iBlock['SECTION_FILTER'])) && (count($iBlock['SECTION_FILTER']) > 0)) {
						$arSectionIBlockFilter[$iBlock['IBLOCK_ID']] = $iBlock['SECTION_FILTER'];
					} else {
						$arSectionIBlockIDs[] = $iBlock['IBLOCK_ID'];
					}
				};
				if ($iBlock['DETAIL'] != 'N') {
					if ((isset($iBlock['DETAIL_FILTER'])) && (is_array($iBlock['DETAIL_FILTER'])) && (count($iBlock['DETAIL_FILTER']) > 0)) {
						$arDetailIBlockFilter[$iBlock['IBLOCK_ID']] = $iBlock['DETAIL_FILTER'];
					} else {
						$arDetailIBlockIDs[] = $iBlock['IBLOCK_ID'];
					}
				}
			}

			/* Вытащим все разделы */
			if (count($arSectionIBlockIDs) > 0) {
				$arSort = ["left_margin" => "asc"];
				$arFilter = [
					'IBLOCK_ID' => $arSectionIBlockIDs,
					'ACTIVE' => 'Y'
				];
				$arSelect = [];
				$res = \CIBlockSection::GetList($arSort, $arFilter, true, $arSelect);
				while ($arSection = $res->GetNext()) {
					$loc = $arSection['SECTION_PAGE_URL'];
					$prior = $this->CalcPriorBySlash($loc);
					$this->AddPage($loc, $prior, date('Y-m-01'), 'monthly');
				}
			}
			if (count($arSectionIBlockFilter) > 0) {
				foreach ($arSectionIBlockFilter as $IBlockID => $arFilter) {
					$arSort = ["left_margin" => "asc"];
					$arFilter['IBLOCK_ID'] = $IBlockID;
					$arFilter['ACTIVE'] = 'Y';
					$arSelect = ["*"];
					$res = \CIBlockSection::GetList($arSort, $arFilter, true, $arSelect);
					while ($arSection = $res->GetNext()) {
						$loc = $arSection['SECTION_PAGE_URL'];
						$prior = $this->CalcPriorBySlash($loc);
						$this->AddPage($loc, $prior, date('Y-m-01'), 'monthly');
					}
				}
			}
			/* Вытащим все елементы */
			$arSelect = ["*"];
			$arFilter = [
				"IBLOCK_ID" => $arDetailIBlockIDs,
				"ACTIVE_DATE" => "Y",
				"ACTIVE" => "Y"
			];
			$res = \CIBlockElement::GetList([], $arFilter, false, array("nPageSize" => 500000), $arSelect);
			while ($ob = $res->GetNextElement()) {
				$arFields = $ob->GetFields();
				$loc = $arFields['DETAIL_PAGE_URL'];
				$prior = $this->CalcPriorBySlash($loc);;
				$this->AddPage($loc, $prior, date('Y-m-d', strtotime($arFields['TIMESTAMP_X'])), 'monthly');
			}

			if (count($arDetailIBlockFilter) > 0) {
				foreach ($arDetailIBlockFilter as $IBlockID => $arFilter) {
					$arSelect = ["*"];
					$arFilter["IBLOCK_ID"] = $IBlockID;
					$arFilter["ACTIVE_DATE"] = "Y";
					$arFilter["ACTIVE"] = "Y";
					$res = \CIBlockElement::GetList([], $arFilter, false, array("nPageSize" => 500000), $arSelect);
					while ($ob = $res->GetNextElement()) {
						$arFields = $ob->GetFields();
						$loc = $arFields['DETAIL_PAGE_URL'];
						$prior = $this->CalcPriorBySlash($loc);;
						$this->AddPage($loc, $prior, date('Y-m-d', strtotime($arFields['TIMESTAMP_X'])), 'monthly');
					}
				}
			}
		}
	}
	/******************************************************/
	public function AddPagesFromMenuFile($filemenu, $home_url)
	{
		if (file_exists($filemenu)) {
			@include($filemenu);
			if (is_array($aMenuLinks)) {
				foreach ($aMenuLinks as $link) {
					$loc = $home_url . $link[1];
					$prior = $this->CalcPriorBySlash($loc);
					$this->AddPage($loc, $prior, date('Y-m-01'), 'monthly');
				}
			}
		}
	}
	/******************************************************/
	public function Show($offset = 0, $length = 0)
	{
		$pages = $this->pages;
		if (($offset > 0) || ($length > 0)) {
			$pages = array_slice($pages, $offset, $length);
		};
		$result = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		$result .= '<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">' . "\n";
		if (is_array($pages)) {
			foreach ($pages as $key => $val) {
				if ($val['loc'] != '') {
					$result .= '<url>';
					foreach ($val as $valkey => $valvalue) {
						$valkey = htmlspecialchars($valkey, ENT_XML1);
						$valvalue = htmlspecialchars($valvalue, ENT_XML1);
						if ((trim($valvalue) != '') && ($valkey != 'title')) {
							$result .= '<' . $valkey . '>' . $valvalue . '</' . $valkey . '>';
						}
					}
					$result .= '</url>' . "\n";
				}
			}
		}
		$result .= '</urlset>';
		return $result;
	}

	/*********************************** */
	public function AddToSitemapIndex($linkToSitemap)
	{
		$this->sitemap[] = $linkToSitemap;
	}

	/*********************************** */
	public function ShowSitemapIndex()
	{
		$sitemaps = $this->sitemap;
		$result = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		$result .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
		if (is_array($sitemaps)) {
			foreach ($sitemaps as $sitemap) {
				$result .= '<sitemap>';
				$result .= '<loc>' . $sitemap . '</loc>';
				$result .= '<lastmod>' . date('Y-m-d') . '</lastmod>';
				$result .= '</sitemap>';
			}
		}
		$result .= '</sitemapindex>';
		return $result;
	}
};
