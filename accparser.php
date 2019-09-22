<?php
namespace FbCosts;
require('csvCRUD.php');
class AccParser
{
	var $data_file="";
	function __construct()
	{
		$this->data_file=__DIR__ . '/accounts.txt';
	}
	
    function parse_accounts()
    {
        $accounts_dirty = explode("\n", file_get_contents($this->data_file));
        $accounts = [];
		$i=0;
        foreach ($accounts_dirty as $acc) {
            $split = explode(',', $acc);
            $accounts[] = [
				'index' => $i,
                'campaign' => $split[0],
                'cabinet' => $split[1],
                'access_token' => $split[2],
                'proxy_address' => $split[3],
                'proxy_port' => $split[4],
                'proxy_user' => $split[5],
                'proxy_password' => $split[6],
                'comment' => $split[7]
            ];
			$i++;
        }
        return $accounts;
    }
	
	function remove_records($line_indexes)
	{
		// Read file into memory
		$lines = file($this->data_file);

		// Filter lines based on line number
		$lines = array_filter($lines, function($lineNumber) use ($line_indexes) {
			return !in_array($lineNumber, $line_indexes);
		}, ARRAY_FILTER_USE_KEY);

		// Re-assemble the output (the lines already have a line-break at the end)
		$output = implode('', $lines);

		// Write back to file
		file_put_contents($this->data_file, $output);
	}
}