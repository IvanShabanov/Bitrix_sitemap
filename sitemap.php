<?
include_once(__DIR__.'/class_sitemap.php');

CModule::IncludeModule("iblock");

function GenerateSitemapXmlExt () {
    $http = 'https://';
	
    //list($SERVER_HTTP_HOST, $port) = explode(':', $_SERVER['HTTP_HOST']);
    $SERVER_HTTP_HOST = $_SERVER['HTTP_HOST'];
	
    /*Инфоблоки которые необходимо включить*/
    $arBlocks[] = array(
            'IBLOCK_ID' => 13,
            'SECTION' => 'N', /* Y/N - влючать ли разделы инфоблока */
            'DETAIL' => 'Y'   /* Y/N - влючать ли страницы элементов инфоблока */
        );
	$arBlocks[] = array(
            'IBLOCK_ID' => 20,
            'SECTION' => 'N', /* Y/N - влючать ли разделы инфоблока */
            'DETAIL' => 'Y'   /* Y/N - влючать ли страницы элементов инфоблока */
        );
	$arBlocks[] = array(
            'IBLOCK_ID' => 9,
            'SECTION' => 'N', /* Y/N - влючать ли разделы инфоблока */
            'DETAIL' => 'Y'   /* Y/N - влючать ли страницы элементов инфоблока */
        );
	$arBlocks[] = array(
            'IBLOCK_ID' => 22,
            'SECTION' => 'Y', /* Y/N - влючать ли разделы инфоблока */
            'DETAIL' => 'Y'   /* Y/N - влючать ли страницы элементов инфоблока */
        );
    $sitemap = new sitemapXmlExt($arBlocks);
    /* Добавим главную страницу */
    $sitemap->AddPage($http.$SERVER_HTTP_HOST);
    $sitemap->AddPage($http.$SERVER_HTTP_HOST.'/delivery/');
    $sitemap->AddPage($http.$SERVER_HTTP_HOST.'/about/');
    $sitemap->AddPage($http.$SERVER_HTTP_HOST.'/otziv/');
    $sitemap->AddPage($http.$SERVER_HTTP_HOST.'/contacts/');
    $sitemap->AddPage($http.$SERVER_HTTP_HOST.'/sitemap/');
	
    /* Добавим страницы из файлов меню */
    $sitemap->AddPagesFromMenuFile($_SERVER['DOCUMENT_ROOT'].'/.top.menu.php', $http.$SERVER_HTTP_HOST);
    /* Сгенерируем остальные страницы */
    $sitemap->generate($http.$SERVER_HTTP_HOST);
	
    /* Запишем все в файл */
    $generate = $sitemap->show();
	
    return "GenerateSitemapXmlExt();";
}
