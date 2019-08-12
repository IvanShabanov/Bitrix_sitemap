# Bitrix_sitemap
Sitemap.xml для 1С Битрикс

1) Скопировать файл sitemap_xml.php на хост в корень сайта

2) Настроить файл
   
- Настройте все инфоблоки, чтобы в них правильно формировались ссылки на страницы.
   
- Включите Все инфоблоки которые надо вывести в файл
$iBlock_array = array(3, 7, 8, 9, 16);
   
-Добавьте все файлы меню используемые на сайте
    $sitemap->AddPagesFromMenuFile('.top.menu.php', $http.$host); 
    
- Добавьте страницы
$sitemap->AddPage($http.$host.'/stock/');
   
- Добавьте игнорируемые страницы (страницы которые не должны попасть в файл)
$sitemap->AddIgnorePage($http.$host.'/hidden/');


3) Проверитть правильность формирования sitemat вызвав файл из браузера

4) в .htaccess добавить

  RewriteRule ^sitemap\.xml$ /sitemap_xml.php [L] 
