<?php
/*
 в .htaccess добавить
  RewriteRule ^sitemap\.xml$ /sitemap_xml.php [L] 
 в настройках iBlockов правильно прописаны пути к элементам и разделам

 */
error_reporting (E_ALL );
ini_set('error_reporting', E_ALL);

define("NO_KEEP_STATISTIC", true); 
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
if (!CModule::IncludeModule("iblock")) {
    $this->AbortResultCache();
    ShowError("IBLOCK_MODULE_NOT_INSTALLED");
    die();
};


class sitemap_xml {

    var $ignore = array();
    var $iBlocks = array();
    var $pages = array();
    function __construct($iBlocks = array(), $ignorePages = array()) {
        $this->iBlocks = $iBlocks;
        $this->ignore = $ignorePages;
    }
    /*******************************************************/
    function generate($home_url) {
        foreach ($this->iBlocks as $iBlock) {
            $this->AddPagesFromIBlock ($home_url, $iBlock);
        };  
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
            };
            if ($changefreq != '') {
                $page['changefreq'] = $changefreq;
            };
            $this->pages[] = $page;
        }
    }
    /***************************************/
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
    function AddPagesFromIBlock ($parentloc, $iBlock) {
        $pages = array();
        $i = 0;
        /* Вытащим все разделы */
    	$arSort = array(
    		"left_margin"=>"asc",
    	);      
        $arFilter = array(
      		'IBLOCK_ID' => $iBlock,
      		'ACTIVE' => 'Y'
      	);
    	$arSelect = array();
    	$res = CIBlockSection::GetList($arSort, $arFilter, true, $arSelect);
    	while($arSection = $res->GetNext()){
    		if(IntVal($arSection['ELEMENT_CNT']) == 0) continue;
            $page = array();
            $loc = $parentloc.$arSection['SECTION_PAGE_URL'];
            $prior = $this->CalcPriorBySlash($arSection['SECTION_PAGE_URL']);
            $this->AddPage($loc, $prior,  date('Y-m-01'));
    	}
                
        /* Вытащим все елементы */
        $arSelect = Array("*");
        $arFilter = Array("IBLOCK_ID"=>IntVal($iBlock), "ACTIVE_DATE"=>"Y", "ACTIVE"=>"Y");
        $res = CIBlockElement::GetList(Array(), $arFilter, false, Array("nPageSize"=>500000), $arSelect);
        $i = 0;
        while($ob = $res->GetNextElement()) {
            $arFields = $ob->GetFields();
            $page = array();
            $loc = $parentloc.$arFields['DETAIL_PAGE_URL'];
            $prior = $this->CalcPriorBySlash($arFields['DETAIL_PAGE_URL']);
            $this->AddPage($loc, $prior, date('Y-m-d', strtotime($arFields['TIMESTAMP_X'])));
            $i++;
        }
    }
    /******************************************************/
    function AddPagesFromMenuFile($filemenu, $home_url) {
        if (file_exists($filemenu)) {
            @include($filemenu);
            if (is_array($aMenuLinks )) {
                foreach ($aMenuLinks as $link) {
                    $prior = $this->CalcPriorBySlash($link[1]);
                    $url = $home_url.$link[1];
                    $this->AddPage($url, $prior, date('Y-m-01'));
                }
            }
        }
    }
    /******************************************************/
    function show() {
        $pages = $this->pages;
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
                    $result .= '</url>';
                }
            }
            $result .= '</urlset>';
        }
        return $result;
    }  
};
/*****************************************************/
/* */
$http = 'http';
if ($_SERVER['HTTPS']) {
    $http = 'https';
}
$http .= '://';
list($host, $port) = explode(':', $_SERVER['HTTP_HOST']);
$iBlock_array = array(3, 7, 8, 9, 16); /*Инфоблоки которые необходимо включить*/
$sitemap = new sitemap_xml($iBlock_array);
$sitemap->AddPage($http.$host);
$sitemap->AddPagesFromMenuFile('.top.menu.php', $http.$host);
$sitemap->AddPagesFromMenuFile('/about/.left.menu.php', $http.$host);
$sitemap->AddPage($http.$host.'/stock/');
$sitemap->generate($http.$host);
$sitemap->pages = $sitemap->DeleteIgnorePages();
header('Content-Type:text/xml');
echo $sitemap->show();
