<?php
namespace FbCosts;
class AccParser
{
    function parse_accounts()
    {
        $accounts_dirty = explode("\n", file_get_contents(__DIR__ . '/accounts.txt'));
        $accounts = [];
        foreach ($accounts_dirty as $acc) {
            $split = explode(',', $acc);
            $accounts[] = [
                'campaign' => $split[0],
                'cabinet' => $split[1],
                'access_token' => $split[2],
                'proxy_address' => $split[3],
                'proxy_port' => $split[4],
                'proxy_user' => $split[5],
                'proxy_password' => $split[6],
                'comment' => $split[7]
            ];
        }
        return $accounts;
    }
}