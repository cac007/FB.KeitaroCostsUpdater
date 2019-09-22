<?php

use FbCosts\AccParser;
use FbCosts\FbApiExecutor;

error_reporting(E_ALL);
require_once __DIR__ . '/accparser.php';
require_once __DIR__ . '/fbapi.php';
require_once __DIR__ . '/config.php';
$acc_parser=new AccParser();
$accounts = $acc_parser->parse_accounts();

foreach ($accounts as $account) {
	$proxy=['proxy_address' =>$account['proxy_address'],
			'proxy_port' =>$account['proxy_port'],
			'proxy_user' =>$account['proxy_user'],
            'proxy_password' =>$account['proxy_password']];
    $fb = new FbApiExecutor($proxy);
	$me = $fb->getFacebookApi('/me', ['access_token' => $account['access_token']]);
	if ( isset($me['error']) ) {
		echo $account['comment'] . ' - <span style="fotn-weight:bold;color:red;">ОШИБКА</span><br/>';
	} else {
		echo $account['comment'] . ' - <span style="fotn-weight:bold;color:green;">OK</span><br/>';
	}
	flush();
}