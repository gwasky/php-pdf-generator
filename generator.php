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
$billdate = '28-FEB-2019';
$d = new DateTime($billdate);
$d->modify('first day of this month');
$startdate = $d->format('d-M-Y');
$util = new helper();
//Get all active accounts
//$active_accounts_sql = "select master_msisdn,account_name from icontrol_masters where master_msisdn = '256755402269'";
$active_accounts_sql = "select master_msisdn,account_name from icontrol_masters";
$results = $dbh->select($active_accounts_sql);

foreach ($results as $key => $value) {
    //echo "key --> ". $key ."<br>";
   //echo "key --> ". print_r($value) ."<br>";
    //$report = $value[MASTER_MSISDN];
    $util->op_log(date, $value[MASTER_MSISDN] .'-'.$value[ACCOUNT_NAME], $logdetail = 'BEGINNING GENERATION ' . $value[MASTER_MSISDN] );
    //echo "ACCOUNT_NAME --> ".strtoupper($value[ACCOUNT_NAME]). "<br>";
    $pdf->billPDF($value, $startdate, $billdate);

    /*
      $select_sql = "SELECT a.created_date,a.master_msisdn,b.account_name,
      CASE
      WHEN SUBSTR(a.resource_type,1,7) = 'AIRTIME' THEN 'Voice Usage'
      WHEN SUBSTR(a.resource_type,1,3) = 'CMB' THEN 'Corporate Megabonus Megabonus'
      WHEN SUBSTR(a.resource_type,1,3) = 'CUG' THEN 'Closed User Group' ELSE resource_type END resource_type,a.msisdn,
      CASE WHEN SUBSTR(a.resource_type,1,7) = 'AIRTIME' OR SUBSTR(a.resource_type,1,3) = 'CMB' OR SUBSTR(a.resource_type,1,3) = 'CUG'
      THEN ROUND(a.price/1.3216,2)
      ELSE ROUND(a.price/1.18,2) END price
      FROM hybrid_subs a LEFT OUTER JOIN icontrol_masters b
      ON a.master_msisdn = b.master_msisdn
      WHERE TRUNC(created_date) >= '01-FEB-2017' AND TRUNC(created_date) >= '01-FEB-2017' a.ocs_resp_code IN ('405000000','0') AND a.master_msisdn ='" . $value[MASTER_MSISDN] . "'";

      $items = $dbh->select($select_sql);
      //print_r($items);
      $TOTAL['AIRTIME']+=0;
      $TOTAL['CMB']+=0;
      $TOTAL['CUG']+=0;
      $TOTAL['DATA']+=0;
      foreach ($items as $key => $item) {
      // echo ++$i." &nbsp;".$item[CREATED_DATE]. "&nbsp;".$item[MSISDN]. "&nbsp;".$item[RESOURCE_TYPE]. "&nbsp;".$item[PRICE]. "<br>";
      if (substr($item[RESOURCE_TYPE], 0, 5) == 'Voice') {
      $TOTAL['AIRTIME']+=$item[PRICE];
      } else if (substr($item[RESOURCE_TYPE], 0, 9) == 'Corporate') {
      $TOTAL['CMB']+=$item[PRICE];
      } else if (substr($item[RESOURCE_TYPE], 0, 6) == 'Closed') {
      $TOTAL['CUG']+=$item[PRICE];
      } else {
      $TOTAL['DATA']+=$item[PRICE];
      }

      $TOTAL[TOTAL]+=$item[PRICE];
      //$TOTAL[$item[RESOURCE_TYPE]]+=$item[PRICE];
      // echo substr($item[RESOURCE_TYPE],0, 7);
      }
      print_r($TOTAL);
     */
    //echo "........................................................................................................ ...<br>";
}
//$dbh->insert($insert_sql);
?>
