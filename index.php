<?php
require('Test.php');

/**
 * Params: Database bilgileri bulunan .ini dosyasi
 *  
 */
$db_settings = ['driver' => 'mysql','host' => 'localhost','port' => '3306',
'schema' => 'test','username' => 'root','password' => ''];

$test_obj = new Test($db_settings);

/**
 * 
 * Challenge 1.Soru:
 * Sonsuz döngü şeklinde istenilen sayfa için aşağıdaki sonucu verir:
 * $page parametresi ne kadar artarsa artsın sürekli sonuç verir, 
 * $page değeri eğer max_pages ' i geçerse $page=1 e eşitler ilk sayfadan devam eder
 * 
 * Return olarak aşağıdaki gibi bir JSON objesi return eder:
 * 	 {
 *   "0":{10 posts},
 *	 "2":{4 users},
 *	 "3":{10 posts},
 *	 "4":{1 ad},
 *	 "5":{10 posts},
 *	 "6":{1 survey},
 *	  } (total 36 items)
 *   Aşağıdaki şekilde $page=3 parametresi verildiği için 3.sayfadaki sonuçarı verir.
 */
$test_obj->getPaginationResults(3);

/**
 * Challenge 2.Soru:
 * Test.php line 322.de bulunan 2.sorudaki listelemeyi yapan method, Userlari location updated_at ' e göre azalan şekilde sıralar.
 */
$test_obj->listUserByLocationUpdated();

?>