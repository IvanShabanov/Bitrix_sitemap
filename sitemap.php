<?
include_once(__DIR__.'/class_sitemap.php');

CModule::IncludeModule("iblock");

function GenerateSitemapXmlExt () {
    $http = 'https://';
    list($SERVER_HTTP_HOST, $port) = explode(':', $_SERVER['HTTP_HOST']);
    $home_url = $http.$SERVER_HTTP_HOST;
    $document_root = $_SERVER['DOCUMENT_ROOT'];
    if ($document_root == '') {
        /* Если $_SERVER['DOCUMENT_ROOT'] пустой,
            по проверим весь путь до текущего файла
            и путь до bitrix или local и будет $_SERVER['DOCUMENT_ROOT'] */
        $dirs = realpath(dirname(__FILE__));
        if (strpos($dirs, '/local/') !== false) {
            list($document_root, $trash) = explode('/local/', $dirs);
        } elseif (strpos($dirs, '/bitrix/') !== false) {
            list($document_root, $trash) = explode('/bitrix/', $dirs);
        };
    };


    /* Инфоблоки которые необходимо включить */
    $arBlocks[] = array(
        'IBLOCK_ID' => 1,
        'SECTION' => 'Y', /* Y/N - влючать ли разделы инфоблока */
        'DETAIL' => 'Y'   /* Y/N - влючать ли страницы элементов инфоблока */
    );
    /* Файлы менющек */
    $arMenus[] = $document_root.'/.top.menu.php';

    /* Максимально кол-во страниц в одном sitemap */
    $items_on_page = 50000; /* Максимальное кол-во по Google и Yandex - 50000 */

    $sitemap = new sitemapXmlExt($arBlocks);
    /* Добавим главную страницу */
    $sitemap->AddPage($home_url);
    /* Добавим страницы из файлов меню */
    if (is_array($arMenus)) {
        foreach ($arMenus as $menu) {
            $sitemap->AddPagesFromMenuFile($menu, $home_url);
        }
    }
    /* Сгенерируем остальные страницы */
    $sitemap->generate($home_url);


    /* Запишем все в файл/ы */
    $total_items = count($sitemap->pages);
    $maxfiles = max(1, ceil($total_items / $items_on_page));
    if ($maxfiles == 1) {
        file_put_contents($document_root.'/sitemap.xml', $sitemap->Show());
    } else {
        while ($maxfiles >= 0) {
            $fileSitemap = $document_root.'/sitemap_'.$maxfiles.'.xml';
            $sitemap->AddToSitemapIndex($home_url.'/sitemap_'.$maxfiles.'.xml');
            file_put_contents($fileSitemap, $sitemap->Show($maxfiles * $items_on_page, $items_on_page));
            $maxfiles--;
        }
        file_put_contents($document_root.'/sitemap.xml', $sitemap->ShowSitemapIndex());
    }
    return "GenerateSitemapXmlExt();";
}
