<?php
require_once __DIR__.'/accparser.php';
require_once __DIR__.'/fbapi.php';
require_once __DIR__.'/config.php';

use FbCosts\AccParser;
use FbCosts\FbApiExecutor;

ini_set('display_errors','On');
error_reporting(E_ALL);
$isCLI = ( php_sapi_name() == 'cli' );
if( !$isCLI )
    die("Эмм, я сдох, сори. Я не такой. В браузере не работаю, фу на вас!");
//
// получаем список аккаунтов
//
$acc_parser=new AccParser();
$accounts = $acc_parser->parse_accounts();

//
// начинем идти по списку аккаунтов и отрабатывать каждый
//
$dead_account_indexes=array();

foreach($accounts as $account) 
{
	echo "Начинаем проверку аккаунта ".$account['comment']."\r\n";
	try {
		//
		// если какой-то косяк с токеном, то пропускаем аккаунт
		//
		if (!@$account['access_token'])
			continue;

		//
		// если не задан рекламный кабинет, то пропускаем аккаунт
		//
		if (!@$account['cabinet'])
			continue;

		//
		// посылаем тестовый запрос API Facebook, чтобы проверить работоспособность токена
		// если в ответ прилетает ошибка, то помечаем аккаунт
		//
		$proxy=['proxy_address' =>$account['proxy_address'],
				'proxy_port' =>$account['proxy_port'],
				'proxy_user' =>$account['proxy_user'],
				'proxy_password' =>$account['proxy_password']];
		$fb = new FbApiExecutor($proxy);
		$me = $fb->getFacebookApi('/me', ['access_token' => $account['access_token']]);
		if (isset($me['error']))
		{
			echo "Запись ".$account['comment']." мертва. Будет удалена: ".$account['index']."!\r\n";
			array_push($dead_account_indexes,(int)$account['index']);
			continue;
		}
		echo "Чекнули ФБ, акк: ".$account['cabinet'].". Ол гут!\r\n";
	} catch (Exception $e) {
		echo 'Caught exception: ',  $e->getMessage(), "\n";
	}
}
echo "Удаляем все мёртвые записи...\r\n";
$acc_parser->remove_records($dead_account_indexes);
echo "Мёртвые записи удалены!\r\n";