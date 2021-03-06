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
echo "Начинаем загрузку расходов!\r\n";
$acc_parser=new AccParser();
$accounts = $acc_parser->parse_accounts();

//
// начинем идти по списку аккаунтов и отрабатывать каждый
//
$dead_account_indexes=array();

foreach($accounts as $account) {
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
		// если в ответ прилетает ошибка, то пропускаем аккаунт
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

		//Получаем временную зону и валюту аккаунта
		$account_info = $fb->getFacebookApi('/act_'.$account['cabinet'], 
			['access_token' => $account['access_token'],
			 'fields'=>'currency,timezone_name,timezone_offset_hours_utc']);

		//
		// получаем список адсетов текущего кабинета
		//
		$adsets = [];
		$adsets_dirty = $fb->getFacebookApi('/act_'.$account['cabinet'].'/adsets', ['access_token' => $account['access_token']]);
		//echo json_encode($adsets_dirty)."\r\n";
		if (!isset($adsets_dirty))
		{
			echo "Какая-то неведомая хуйня! Не смогли получить адсеты!!!\r\n";
			continue;
		}
		
		$adsets = array_merge($adsets_dirty['data'], $adsets);
		echo "Получили список адсетов...\r\n";
		echo "Временная зона акка:".$account_info['timezone_offset_hours_utc']."\r\n";
		//
		// после того, как сформировали список адсетов, запрашиваем стату по каждому
		// и посылаем стату по трекерам
		//
		echo "Валюта аккаунта:".$account_info['currency']."\r\n";
		foreach($adsets as $adset) {
			$timezone = $account_info['timezone_offset_hours_utc'];
			$insights = [];
			$params = [
				'access_token' => $account['access_token'],
				'fields' => 'spend',
				'time_range' => ['since' => '2019-01-01', 'until' => gmdate("Y-m-d", time() + 3600 * ($timezone + date("I")))],
				'time_increment' => '1'
			];
			$insights_dirty = $fb->getFacebookApi('/'.$adset['id'].'/insights', $params);
			$insights = array_merge($insights_dirty['data'], $insights);

			//
			// стату по дням получили
			// теперь идем по каждому дню этой статы и отправляем ее в нужные трекеры
			//
			
			foreach($insights as $insight) {
				echo "Грузим расход за ".$insight['date_start']." - ".$insight['spend']."\r\n";

				$keitaro_data = [
					'start_date' => $insight['date_start'].' 00:00:00',
					'end_date' => $insight['date_start'].' 23:59:59',
					'cost' => $insight['spend'],
					'currency' => $account_info['currency'],
					'timezone' => $account_info['timezone_name'],
					'filters' => ['sub_id_'.$keitaro_subid => $adset['id']]
				];
							
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, 'http://'.$keitaro_domain.'/admin_api/v1/campaigns/'.$account['campaign'].'/update_costs');
				curl_setopt($ch, CURLOPT_HTTPHEADER, array('Api-Key: '.$keitaro_api_key));
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($keitaro_data));
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				$curl_res = curl_exec($ch);
			}
		}
		echo "Закончили работу с акком! \r\n";
	} catch (Exception $e) {
		echo 'Caught exception: ',  $e->getMessage(), "\n";
	}
}
/*echo "Удаляем все мёртвые записи...\r\n";
$acc_parser->remove_records($dead_account_indexes);
echo "Мёртвые записи удалены!\r\n";*/