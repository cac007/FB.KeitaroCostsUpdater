<?php
ini_set('display_errors','On');
require_once 'protect.php';
Protect\with('loginform.php', '1234567890abcdefg','fbcosts');
require('csvCRUD.php');
use FbCosts\csvCRUD;

$self = basename(__FILE__);

//////////////////////
// Define your CSV file
//////////////////////
$data_file = 'accounts.txt';

//////////////////////
// Define Table Headers:
// RowNum is automatically added by the class. It refers to the 0-indexed line number of the CSV.
// You can not manipulate the RowNum, but you can utilitze the column for admin actions such as delete and edit. These actions would be defined the in the template columns array.
// All other columns are referred to alphabetically like a spreadsheet.
//////////////////////
$tbl_headers = array('RowNum'=>'Запись','A'=>'Id кампании в Кейтаро','B'=>'Id рекламного кабинета FB','C'=>'Токен FB','D'=>'Адрес прокси','E'=>'Порт прокси','F'=>'Логин прокси','G'=>'Пароль прокси','H'=>'Комментарий');


//////////////////////
// Define columns you wish to hide from the HTML table display
// Note that these columns will NOT be hidden from edit forms, the set_hidden_columns method is only used for control over HTML display
//////////////////////
$hide_cols = array('C','E','F','G');

//////////////////////
// Define templates to be applied to the HTML output of a value:
// This is optional. By default a simple display of the value is rendered...but you can reference any columns value within the record by enclosing the column in brackets.
// This is very useful for creating hyperlinks to pass values to other scripts or pages.
// In this expample, we use the classes built-in 'RowNum' meta-column to provide admin links to edit or delete records from the CSV. Then we define column E as editable by applying a 'cell-edit' template link to it.
//////////////////////
$column_display_templates = array(
			'RowNum'=>"<a href=\"$self?action=edit&what=row&val=[RowNum]\">Редактировать</a>&nbsp;<a href=\"$self?action=delete&what=row&val=[RowNum]\">Удалить</a>"
		);


$csv = new csvCRUD($data_file,',');
$csv -> set_default_url($self);//This is used for the edit forms cancel button
$csv -> set_tbl_border(0);
$csv -> set_custom_tbl_headers($tbl_headers);
$csv -> set_hidden_cols($hide_cols);
$csv -> show_line_nums();//optional (this is different than 'RowNum') ...it only sets whether to display a line number in the HTML output for readability
$csv -> set_template_cols_array($column_display_templates);
$tbl = $csv -> data_dump('tbl');//after all desired options set, retrieve the reulting query HTML.

/*************************************************
 * Random Methods for bulk actions or debugging:
 * 
 * ////////////////////
 * // How to update multiple cells at once:
 * // In this example we look for all records where the 'C' column = 'DVD' & will set that records 'F' column to '$2.00'
 * // Note this method only changes the active array of data...to commit the change you must call the save_file() method
 * ////////////////////
 * $csv -> update_cells_where('C','DVD','F','$2.00');
 * 
 * ////////////////////
 * //How to view the contents of the data array:
 * ////////////////////
 * $csv -> print_rows_array();
 * 
 * ////////////////////
 * //How to add numeric values of a column:
 * //in this example, all prices will be added to get the total value
 * ////////////////////
 * $sum = $csv -> get_column_sum('F');
 * 
 * ////////////////////
 * //How to view the CSV text before committing it to the file (useful for debugging)
 * ////////////////////
 * $csv -> print_latest_text();
 * 
 * ////////////////////
 * // How to retreive the data array:
 * // This is useful if you want to create you own forms or tables...or simply access the data to import to other applications
 * ////////////////////
 * $result = $csv -> get_rows_array();
 * 
 * ////////////////////
 * // How to extract a single column from the CSV:
 * // There are 2 output formats that you can extract a column in...a comma separated string, or single dimension array of the columns values
 * ////////////////////
 * $values_a_array = $csv -> output_column('A','array');//outputs col A as an array of values
 * $values_b_string = $csv -> output_column('B','string');//outputs col B as a comma delim string of values
 *
 * ////////////////////
 * // How to create a CSV file from an imported array (such as mysql select result):
 * ////////////////////
 * $csv = new csvCRUD('my_New_CSV_file.txt');
 * $csv->set_rows_import_array($array);
 * $csv->save_file();
 * 
 * 
 **************************************************/

/////////////////////
// Begin Admin Action Control Section
// Do not edit below: this code is for the admin actions for creating forms and editing records
/////////////////////
$action = (isset($_GET['action'])) ? $_GET['action'] : false;
$what = (isset($_GET['what'])) ? $_GET['what'] : false;
$qryVal = (isset($_GET['val'])) ? $_GET['val'] : false;

if($action){
	
	switch($action){
		case 'edit':
			switch ($what){
				case 'row':
					$form = $csv -> get_row_edit_form($qryVal);
				break;
				case 'cell':
					$form = $csv -> get_cell_edit_form_table($qryVal);
				break;
			}
		break;
		case 'add':
			$form = $csv -> get_add_record_form();
		break;
		case 'delete':
			$form = $csv -> get_row_delete_form($qryVal);			
		break;
	}
}

if($_POST){
	$updated = false;
    $msg = '';
		switch($_POST['action']){
			case 'edit':
				foreach($_POST['update_cells'] as $cell => $val){
					$csv -> update_cell($cell,$val);
				}
			break;
			case 'add':
				$csv->add_record($_POST['add_cells']);
				break;
			case 'delete':
				$csv -> delete_record($qryVal);
				break;
		}
		
		if($csv->save_file()){
			header('Location: '.$self);
		}else{
			echo "<h3>Error saving to data file ($data_file) ...check permissions.</h3>";
			exit;
		}

    echo $msg.'<br><br>';
    exit;
}

/////////////////////
// End Admin Action control section
/////////////////////


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN">
<html>

<head>
    <title>Keitaro Facebook integration</title>
    <script language="Javascript" type="text/javascript" src="sorttable.js"></script>
    <style>

body {
 font-family: Arial, Helvetica, sans-serif;
 font-size: 13px;
}
th{
 text-align:left;
}
#form_wrapper { 
 margin: 0 auto;
 width: 372px;
}
#form_content { 
 width: 350px;
 color: #333;
 border: 1px solid #ccc;
 background: #F2F2E6;
 margin: 10px 0px 10px 0px;
 padding: 10px;
 /*
 height: 300px;
 */
}

#tbl_wrapper { 
 margin: 0 auto;
 width: 90%;
}
#tbl_content { 
 color: #333;
 border: 1px solid #ccc;
 background: #F2F2E6;
 margin: 10px 0px 10px 0px;
 padding: 10px;
 /*
 height: 300px;
 */
}
        <?php
        echo isset($_GET['show_val']) ?  '#'.$_GET['show_val'].' {background-color: #e0e0e0;}'. "\n":'';
        ?>
        #csvtbl{font-family: arial,helvetica,verdana,sans-serif; font-size:12pt;}
        #frm_div{width:325px; margin-right:auto; margin-left:auto;}
        #tbl_div{margin-right:auto; margin-left:auto;}
        .frm_lbl{text-align:center; font-weight:bold;}
        .frm_input{text-align:center;}
        .csvtbl_row_odd{}
        .csvtbl_row_even{background-color:#D4DBE6;}
        .td_4{font-weight:bold;}
    </style>
</head>
<body>
<div id="container">
    <?php
    if($action == 'edit' || $action == 'add' || $action == 'delete'){
		echo "
<div id=\"form_wrapper\">
	 <div id=\"form_content\">
		   $form
	 </div>
</div>
		";
        //echo "<div id=\"frm_div\">\n".$form."</div>\n";
    }else{
		
		echo "
<div id=\"tbl_wrapper\">
	 <div id=\"tbl_content\">
		   <h3>
		   [<a href=\"$self?action=add&what=row\">&nbsp;Добавить запись&nbsp;</a>]
		   [<a href=\"tokens.php\">&nbsp;Проверка токенов&nbsp;</a>]</h3>
		   \n<hr>
			<div id=\"tbl_div\">\n".$tbl."</div>
	 </div>
</div>
";
    }

    ?>
</div>
</body>
</html>

