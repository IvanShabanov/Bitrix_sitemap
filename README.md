# Bitrix_sitemap
Sitemap.xml для 1С Битрикс

1) Скопировать файлы в php_interface

2) Настроить файл sitemap.php
   
- Настройте все инфоблоки, чтобы в них правильно формировались ссылки на страницы.
   
- Включите Все инфоблоки которые надо вывести в файл

      $arBlocks[] = array(
            'IBLOCK_ID' => 1, 
            'SECTION' => 'Y', 
            'DETAIL' => 'Y'   
        );
        
        
- Добавьте все файлы меню используемые на сайте
   
      $sitemap->AddPagesFromMenuFile('.top.menu.php', $http.$host); 
    
- Добавьте страницы
   
      $sitemap->AddPage($http.$host.'/stock/');
   
- Добавьте игнорируемые страницы (страницы которые не должны попасть в файл)
   
      $sitemap->AddIgnorePage($http.$host.'/hidden/');

3) в init.php добавить строчку 
      
      include_once('sitemap.php');


4) Добавить агента 

      GenerateSitemapXmlExt();
