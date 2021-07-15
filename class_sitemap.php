<?php
class sitemapXmlExt {

    var $ignore = array();
    var $iBlocks = array();
    var $pages = array();
    function __construct($iBlocks = array(), $ignorePages = array()) {
        $this->iBlocks = $iBlocks;
        $this->ignore = $ignorePages;
    }
    /*******************************************************/
    function generate($home_url) {
        $this->AddPagesFromIBlock($home_url, $this->iBlocks);
        $this->pages = $this->DeleteIgnorePages();
        return $result;

    }
    /***************************************/
    function array_merge($array, $arrayAdd) {
        if (is_array($arrayAdd)) {
            foreach ($arrayAdd as $val) {
                $array[] = $val;
            }
        }
        return $array;
    }
    /***************************************/
    function DeleteIgnorePages()  {
        $pages = $this->pages;
        /*Не включаем игнорируемые страницы и дубли */
        $result =  array();
        $allready = array();
        if (is_array($pages)) {
            foreach ($pages as $val) {
                if (!in_array($val['loc'], $this->ignore)) {
                    if (!in_array($val['loc'], $allready)) {
                        $allready[] = $val['loc'];
                        $result[] = $val;
                    }
                }
            }
        }
        return $result;
    } 
    /***************************************/
    function AddPage($url, $priority = 1, $lastmod = '', $changefreq = 'monthly'){
        if (!in_array($url, $this->ignore)) {
            $page = array();
            $page['loc'] = $url;
            $page['priority'] = $priority;
            if ($lastmod != '') {
                $page['lastmod'] = $lastmod;
            } else {
                $page['lastmod'] = date('Y-m-01');
            }
            if ($changefreq != '') {
                $page['changefreq'] = $changefreq;
            };
            $this->pages[] = $page;    
        }
    }/***************************************/
    function AddIgnorePage($url){
        $this->ignore[] = $url;    
    }  
    /***************************************/
    function CalcPriorBySlash($link) {
        $link = str_replace(array('http://','https://'), '', $link);
        list($host, $port) = explode(':', $_SERVER['HTTP_HOST']);
        $link = str_replace($host, '', $link);
        $prior = 0.8 - (substr_count (trim($link, '/'), '/') * 0.1);
        if ($prior < 0.4) {
            $prior = 0.4;
        }
        return $prior;
    }    
    /***************************************/
    function AddPagesFromIBlock ($parentloc, $arIBlocks) {
        if (is_array($arIBlocks)) {
            foreach ($arIBlocks as $iBlock) {
                if ($iBlock['SECTION'] != 'N') {
                    $arSectionIBlockIDs[] = $iBlock['IBLOCK_ID'];
                };
                if ($iBlock['DETAIL'] != 'N') {
                    $arDetailIBlockIDs[] = $iBlock['IBLOCK_ID'];
                }
            }

            $i = 0;
            /* Вытащим все разделы */
            if (count($arSectionIBlockIDs) > 0) {
                $arSort = array(
                    "left_margin"=>"asc",
                );
                $arFilter = array(
                    'IBLOCK_ID' => $arSectionIBlockIDs,
                    'ACTIVE' => 'Y'
                );
                $arSelect = array();
                $res = CIBlockSection::GetList($arSort, $arFilter, true, $arSelect);
                while ($arSection = $res->GetNext()) {
                    $page = array();
                    $loc = $parentloc.$arSection['SECTION_PAGE_URL'];
                    $prior = $this->CalcPriorBySlash($loc);
                    $this->AddPage($loc, $prior, date('Y-m-01'), 'monthly');
                }
            }
            /* Вытащим все елементы */
            $arSelect = array("*");
            $arFilter = array(
                "IBLOCK_ID"=>$arDetailIBlockIDs,
                "ACTIVE_DATE"=>"Y",
                "ACTIVE"=>"Y"
            );
            $res = CIBlockElement::GetList(array(), $arFilter, false, array("nPageSize"=>500000), $arSelect);
            $i = 0;
            while ($ob = $res->GetNextElement()) {
                $arFields = $ob->GetFields();
                $page = array();
                $loc = $parentloc.$arFields['DETAIL_PAGE_URL'];
                $prior = $this->CalcPriorBySlash($loc);;
                $this->AddPage($loc, $prior, date('Y-m-d', strtotime($arFields['TIMESTAMP_X'])), 'monthly');
                $i++;
            }
        }
    }
    /******************************************************/
    function AddPagesFromMenuFile($filemenu, $home_url) {
        if (file_exists($filemenu)) {
            @include($filemenu);
            if (is_array($aMenuLinks )) {
                foreach ($aMenuLinks as $link) {
                    $loc = $home_url.$link[1];
                    $prior = $this->CalcPriorBySlash($loc);
                    $this->AddPage($loc, $prior, date('Y-m-01'), 'monthly');
                }
            }
        }
    }
    /******************************************************/
    function show() {
		
        $pages = $this->pages;
		$result = '';
		
		if (is_array($pages)){
			if(count($pages) > 2000){
				$this->generate_multi($pages);
			}else{
				$this->generate_one($pages, true);
			}
		}
        return $result;

    }  
	
	function generate_one($pages, $simple=true, $num = 1) {
        if (is_array($pages)){
            $result = '<?xml version="1.0" encoding="UTF-8"?>'."\n".
                        '<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">';
            foreach ($pages as $key=>$val) {
                if ($val['loc'] != '') {
                    $result .= '<url>';
                    foreach ($val as $valkey=>$valvalue) {
                        if ((trim($valvalue) != '') && ($valkey != 'title')) {
                            $valkey = htmlspecialchars($valkey, ENT_XML1);
                            $valvalue = htmlspecialchars($valvalue, ENT_XML1);
                            $result .= '<'.$valkey.'>'.$valvalue.'</'.$valkey.'>';
                        }
                    }
                    $result .= '</url>'."\n";
                }
            }
            $result .= '</urlset>';
        }
		
		if($simple === true){
			@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/sitemap.xml', $result);
		}else{
			@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/sitemap_'.$num.'.xml', $result);
		}
	}

	function generate_multi($pages) {
		$http = 'https://';
		$SERVER_HTTP_HOST = $_SERVER['HTTP_HOST'];
		
		$file_num = 1;
        if (is_array($pages)){
			$count = 0;
			$pages_arr = array();
			// перебираем массив страниц, подсчитываем, сколько файлов будем создавать
			foreach($pages as $key=>$page){
				$pages_arr[$file_num][$key] = $page;
				$count++;
				if($count >= 2000){
					$file_num++;
					$count = 0;
				}
			}
			
			// создаем файлы
			foreach($pages_arr as $key=>$page){
				$this->generate_one($page, false, $key);
			}
			
			//создаем общий файл sitemap.xml
			$result = '<?xml version="1.0" encoding="UTF-8"?>'."\n".
			'<sitemapindex xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">';
				for($i=1; $i<=count($pages_arr); $i++){
					$result .= '<sitemap>
						<loc>'.$http.$SERVER_HTTP_HOST.'/sitemap_'.$i.'.xml</loc>
						<lastmod>'.date('Y-m-01').'</lastmod>
					</sitemap>';
				}
			$result .= '</sitemapindex>';
			@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/sitemap.xml', $result);
        }
	}
};
?>