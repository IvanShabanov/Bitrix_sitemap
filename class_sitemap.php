<?php
class sitemapXmlExt {

    var $ignore = array();
    var $iBlocks = array();
    var $pages = array();
    var $sitemap = array();
    function __construct($iBlocks = array(), $ignorePages = array()) {
        $this->iBlocks = $iBlocks;
        $this->ignore = $ignorePages;
    }
    /*******************************************************/
    function SetDocumentRoot($DocRoot) {
        $this->DOCUMENT_ROOT = $DocRoot;
    }
    /*******************************************************/
    function generate($home_url) {
        $this->HomeUrl = $home_url;
        $this->AddPagesFromIBlock($home_url, $this->iBlocks);
        $this->pages = $this->DeleteIgnorePages();
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
    function Show($offset = 0, $length = 0) {
        $pages = $this->pages;
        if (($offset > 0) || ($length > 0)) {
            $pages = array_slice($pages, $offset, $length);
        };
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
        return $result;
    }

    function AddToSitemapIndex($linkToSitemap) {
        $this->sitemap[] = $linkToSitemap;
    }

    function ShowSitemapIndex() {
        $sitemaps = $this->sitemap;
        if (is_array($sitemaps)){
            $result = '<?xml version="1.0" encoding="UTF-8"?>
                    <sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
                    ';
            foreach ($sitemaps as $sitemap) {
                $result .= '<sitemap>';
                $result .= '<loc>'.$sitemap.'</loc>';
                $result .= '<lastmod>'.date().'</lastmod>';
                $result .= '</sitemap>';
            }
        }
        $result .= '</sitemapindex>';
        return $result;
    }
};
