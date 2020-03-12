<?php

error_reporting(E_PARSE | E_COMPILE_ERROR | E_ERROR);
//error_reporting(0);
ini_set('max_execution_time', 1800);

require_once("PDOConnection.php");
//require_once("BillPDF.php");
require_once("billpdf2.php");
require_once("helper.php");

$dbh = new PDOConnection();
$pdf = new PDF();
$util = new helper();
//echo($argv[1]);
//exit();
if($argc > 0){
  $billdate = $argv[1];
}else {
  $billdate = '31-MAR-2019';
}
$d = new DateTime($billdate);
$d->modify('first day of this month');
$startdate = $d->format('d-M-Y');

//Get all active accounts
$active_accounts_sql = "select master_msisdn,account_name from icontrol_masters where master_msisdn = '256752738641' and status = 1";
// $active_accounts_sql = "select master_msisdn,account_name from icontrol_masters WHERE status = 1";
$results = $dbh->select($active_accounts_sql);

$util->op_log('', 'END OF MONTH BILL RUN', $logdetail = 'ADDENDUM GENERATION FOR MONTH ENDING - ' .$billdate );
foreach ($results as $key => $value) {
    $util->op_log('', $value[MASTER_MSISDN] .'-'.$value[ACCOUNT_NAME], $logdetail = 'BEGINNING GENERATION ' . $value[MASTER_MSISDN] );
    $pdf->billPDF($value, $startdate, $billdate);
}

?>
