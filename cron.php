<?php
require_once __DIR__.'/accparser.php';
require_once __DIR__.'/fbapi.php';
require_once __DIR__.'/config.php';

use FbCosts\AccParser;
use FbCosts\FbApiExecutor;

ini_set('display_errors','On');
error_reporting(E_ALL);

//
// получаем список аккаунтов
//
$acc_parser=new AccParser();
$accounts = $acc_parser->parse_accounts();

//
// начинем идти по списку аккаунтов и отрабатывать каждый
//
foreach($accounts as $account) {


	//
	// если какой-то косяк с токеном, то пропускаем аккаунт
	//
	if (!@$account['access_token'])
		continue;

	//
	// если не заданы рекламный кабинет, то пропускаем аккаунт
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
		continue;
	echo 'Чекнули ФБ, ол гут<br/>';

	//Получаем временную зону и валюту аккаунта
	$account_info = $fb->getFacebookApi('/act_'.$account['cabinet'], 
		['access_token' => $account['access_token'],
		 'fields'=>'currency,timezone_name,timezone_offset_hours_utc']);

	//
	// получаем список адсетов текущего кабинета
	//
	$adsets = [];
	$adsets_dirty = $fb->getFacebookApi('/act_'.$account['cabinet'].'/adsets', ['access_token' => $account['access_token']]);
	$adsets = array_merge($adsets_dirty['data'], $adsets);
	while (isset($adsets_dirty['paging']['next'])) {
		$adsets_dirty = json_decode(file_get_contents($adsets_dirty['paging']['next']), TRUE);
		$adsets = array_merge($adsets_dirty['data'], $adsets);
		usleep(500000);
	}
	echo 'Получили адсеты...<br/>';
	//
	// после того, как сформировали список адсетов, запрашиваем стату по каждому
	// и посылаем стату по трекерам
	//

	foreach($adsets as $adset) {
		echo $adset['id'].'<br/>';

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
		while (isset($insights_dirty['paging']['next'])) {
			$insights_dirty = json_decode(file_get_contents($insights_dirty['paging']['next']), TRUE);
			$insights = array_merge($insights_dirty['data'], $insights);
			usleep(500000);
		}

		//
		// стату по дням получили
		// теперь идем по каждому дню этой статы и отправляем ее в нужные трекеры
		//

		foreach($insights as $insight) {

			$keitaro_data = [
				'start_date' => $insight['date_start'].' 00:00:00',
				'end_date' => $insight['date_start'].' 23:59:59',
				'cost' => $insight['spend'],
				'currency' => $account_info['currency'],
				'timezone' => $account_info['timezone_name'],
				'only_campaign_uniques' => 1,
				'filters' => ['sub_id_'.$keitaro_subid => $adset['id']]
			];
			print_r($keitaro_data.'<br/>');
			
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, 'http://'.$keitaro_domain.'/admin_api/v1/campaigns/'.$account['campaign'].'/update_costs');
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Api-Key: '.$keitaro_api_key));
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($keitaro_data));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$curl_res = curl_exec($ch);
			print_r($curl_res.'<br/>');
		}
	}
}