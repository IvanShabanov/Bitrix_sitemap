# Bitrix_sitemap
Sitemap.xml для 1С Битрикс

1) Скопировать файлы в php_interface

2) В настройках сайта проверить настроено ли

   - URL сервера (без http://)

3) В настроках инфоблоков проверить настройки

   - URL страницы раздела
   - URL страницы детального просмотра

4) Настроить файл sitemap.php

   - Включите Все инфоблоки которые надо вывести в файл

      $arResult['IBLOCK'][] = array(
            'IBLOCK_ID' => 1,
            'SECTION' => 'Y',
            'SECTION_FILTER' => [],
            'DETAIL' => 'Y'
            'DETAIL_FILTER' => [],
      );


   - Добавьте все файлы меню используемые на сайте

      $arResult['MENU']

   - Добавьте страницы

      $arResult['URLS'];

   - Добавьте игнорируемые страницы (страницы которые не должны попасть в файл)

      $arResult['IGNORE'];

5) в init.php добавить строчку

      include_once('sitemap.php');


4) Добавить агента

      GenerateSitemapXmlExt(siteId, useHttps);

      siteId -  id сайта, по умолчанию "s1"
      useHttps - true/false используется ли hhtps


