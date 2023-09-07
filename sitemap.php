<?
include_once(__DIR__ . '/class_sitemap.php');


function GenerateSitemapXmlExtConfig($siteId)
{
	$arResult = [];

	if ($siteId == 's1') {
		/* Инфоблоки */
		$arResult['IBLOCK'][] = [
			'IBLOCK_ID'      => 1,   /* ID инфоблока */
			'SECTION'        => 'Y', /* Y/N - влючать ли разделы инфоблока */
			'SECTION_FILTER' => [],  /* Фильтр для разделов */
			'DETAIL'         => 'Y', /* Y/N - влючать ли страницы элементов инфоблока */
			'DETAIL_FILTER'  => []   /* Фильтр для элементов инфоблока */
		];

		/* Меню сайта */
		$arResult['MENU'] = [
			'/.top.menu.php',
			'/.bottom.menu.php'
		];

		/* Добавить урлы */
		$arResult['URLS'] = [
			'https://site.ru/my_urs/',
			'https://site.ru/my_urs2/'
		];

		/* Игнорируемые урлы */
		$arResult['IGNORE'] = [
			'https://site.ru/ignore_urs/',
			'https://site.ru/ignore_urs2/'
		];

		/* Максимально кол-во страниц в одном sitemap */
		/* Максимальное кол-во по Google и Yandex - 50000 */
		$arResult['MAX_URLS_IN_SITEMAP'] = 1000;
	}
	return $arResult;
}

function GenerateSitemapXmlExt(string $siteId = "s1", bool $useHttps = true)
{
	$result = 'GenerateSitemapXmlExt("' . $siteId . '", ' . ($useHttps ? 'true' : 'false') . ');';

	if (!\Bitrix\Main\Loader::includeModule("iblock")) {
		\CEventLog::Add([
			"SEVERITY" => "ERROR",
			"AUDIT_TYPE_ID" => "GenerateSitemapXmlExt",
			"MODULE_ID" => "",
			"ITEM_ID" => "",
			"DESCRIPTION" => "Не удалось загрудить модуль iblock",
		]);
		return $result;
	}

	$site = \Bitrix\Main\SiteTable::getById($siteId)->fetch();

	$http = $useHttps ? 'https://' : 'http://';

	$SERVER_HTTP_HOST = $site['SERVER_NAME'];

	$home_url = $http . $SERVER_HTTP_HOST;

	$document_root = trim($site['DOC_ROOT']);
	if ($document_root == '') {
		$document_root = \Bitrix\Main\Application::getDocumentRoot();
	};
	if ($document_root == '') {
		$dirs = realpath(dirname(__FILE__));
		if (strpos($dirs, '/local/') !== false) {
			list($document_root, $trash) = explode('/local/', $dirs);
		} elseif (strpos($dirs, '/bitrix/') !== false) {
			list($document_root, $trash) = explode('/bitrix/', $dirs);
		};
	};
	$document_root = $document_root.$site['DIR'];

	$arConfig = GenerateSitemapXmlExtConfig($siteId);

	$arBlocks = $arConfig['IBLOCK'] ? $arConfig['IBLOCK'] : [];
	$arMenus = $arConfig['MENU'] ? $arConfig['MENU'] : [];
	$items_on_page = $arConfig['MAX_URLS_IN_SITEMAP'] ? $arConfig['MAX_URLS_IN_SITEMAP'] : 50000;
	$arUrl = $arConfig['URLS'] ? $arConfig['URLS'] : [];
	$arIgnore = $arConfig['IGNORE'] ? $arConfig['IGNORE'] : [];

	$sitemap = new sitemapXmlExt($home_url, $arBlocks);

	$sitemap->AddPage($home_url);
	if (is_array($arMenus)) {
		foreach ($arMenus as $menu) {
			$sitemap->AddPagesFromMenuFile(str_replace('//', '/', $document_root . $menu), $home_url);
		}
	}
	if (is_array($arUrl)) {
		foreach ($arUrl as $url) {
			$sitemap->AddPage($url);
		}
	}
	if (is_array($arIgnore)) {
		foreach ($arIgnore as $ignore) {
			$sitemap->AddIgnorePage($ignore);
		}
	}

	$sitemap->generate();

	$total_items = count($sitemap->pages);
	$maxfiles = max(1, ceil($total_items / $items_on_page));
	if ($maxfiles == 1) {
		file_put_contents($document_root . '/sitemap.xml', $sitemap->Show());
	} else {
		while ($maxfiles > 0) {
			$maxfiles--;
			$fileSitemap = $document_root . '/sitemap_' . $maxfiles . '.xml';
			$sitemap->AddToSitemapIndex($home_url . '/sitemap_' . $maxfiles . '.xml');
			file_put_contents($fileSitemap, $sitemap->Show($maxfiles * $items_on_page, $items_on_page));
		}
		file_put_contents($document_root . '/sitemap.xml', $sitemap->ShowSitemapIndex());
	}

	\CEventLog::Add([
		"SEVERITY" => "INFO",
		"AUDIT_TYPE_ID" => "GenerateSitemapXmlExt",
		"MODULE_ID" => "",
		"ITEM_ID" => "",
		"DESCRIPTION" => "Sitemap.xml сформирован $total_items страниц",
	]);

	return $result;
}
