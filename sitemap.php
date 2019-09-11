<?
include_once('class_sitemap.php');

CModule::IncludeModule("iblock");

function GenerateSitemapXmlExt () {
    $http = 'https://';
    list($SERVER_HTTP_HOST, $port) = explode(':', $_SERVER['HTTP_HOST']);
    /*Инфоблоки которые необходимо включить*/
    $arBlocks[] = array(
            'IBLOCK_ID' => 1,
            'SECTION' => 'Y', /* Y/N - влючать ли разделы инфоблока */
            'DETAIL' => 'Y'   /* Y/N - влючать ли страницы элементов инфоблока */
        );
    $sitemap = new sitemapXmlExt($arBlocks);
    /* Добавим главную страницу */
    $sitemap->AddPage($http.$SERVER_HTTP_HOST);
    /* Добавим страницы из файлов меню */
    $sitemap->AddPagesFromMenuFile($_SERVER['DOCUMENT_ROOT'].'/.top.menu.php', $http.$SERVER_HTTP_HOST);
    /* Сгенерируем остальные страницы */
    $sitemap->generate($http.$SERVER_HTTP_HOST);
    /* Запишем все в файл */
    file_put_contents($_SERVER['DOCUMENT_ROOT'].'/sitemap.xml', $sitemap->show());
    return "GenerateSitemapXmlExt();";
}
