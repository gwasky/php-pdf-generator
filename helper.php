<?php

/**
 * Description of helper
 *
 * @author gibson
 */
error_reporting(E_PARSE | E_WARNING);
require_once("PDOConnection.php");
require_once("helper.php");

class helper {

    //put your code here

    function generate_childnumbers($master_msisdn, $name) {
        $util = new helper();
        if (!$master_msisdn) {
            return "No Master number supplied";
        }
        $dbh = new PDOConnection();
        $util->op_log($this->date, $name, $logdetail = 'GENERATING CHILD LIST');
        //echo date('Y-m-d H:i:s'). " Generating ".$master_msisdn ." Child List<br>";
        $child_sql = "SELECT substr(msisdn,4,9) msisdn FROM icontrol_subs "
                . "WHERE master_msisdn = '" . $master_msisdn . "'";
        //$child_sql = "SELECT substr(msisdn,4,9) msisdn FROM icontrol_subs "
        //        . "WHERE master_msisdn = '" . $master_msisdn ."' AND substr(msisdn,4,9) IN ('750325776','750325778')";
        $child_rows = $dbh->select($child_sql);
        $x = count($child_rows);
        $i = 0;
        //echo date('Y-m-d H:i:s')." Total Child Numbers Found :" .$x. "<br>";
        $util->op_log($this->date, $name, $logdetail = 'CHILD NUMBERS FOUND .' . $x);
        if ($x > 0) {
            $tuple = "(";
            foreach ($child_rows as $key => $msisdn) {
                //echo $msisdn[MSISDN]."<br>";
                if ($i != $x - 1) {
                    ++$i;
                    $tuple .= "'" . $msisdn[MSISDN] . "',";
                } else {
                    $tuple .= "'" . $msisdn[MSISDN] . "')";
                }
            }
        } else {
            $tuple .= "''";
        }
        return $tuple;
    }

    function generate_vas_usage($tuple, $startdate, $billdate, $name) {
        $dbh = new PDOConnection();
        //echo date('Y-m-d H:i:s'). " Generating VAS Data<br>";
        $util = new helper();
        $util->op_log($this->date, $name, $logdetail = 'GENERATING VAS DATA');
        $vas_query = "SELECT DATED,call_start_time,msisdn,service_usage_type_name,bundle_product_type_name,volume,revenue
                        FROM
                        (
                        SELECT TO_CHAR(TO_DATE ( TO_CHAR(DATED, 'DD-MON-YYYY') || call_start_time,'DD-MON-YYYY HH24:MI:SS'),'DD-MON-YYYY HH24:MI:SS')
                                                    DATED,
                                                     call_start_time,
                                                     msisdn,
                                                     service_usage_type_name,
                                                     bundle_product_type_name,
                                                     0 volume,
                                                     CASE WHEN service_usage_type_name = 'Data' THEN ROUND(revenue/1.18,2) ELSE ROUND(revenue/1.3216,2) END revenue,
                                                     ROW_NUMBER() OVER(PARTITION BY msisdn,bundle_product_type_name  ORDER BY 1,2,3 DESC ) rnk
                        FROM icontrol_vas_usage
                        WHERE UPPER(service_usage_type_name) = 'VOICE'
                        AND bundle_product_type_name IN ('IControl_5K','IControl_10K','Icontrol CMB','IControl_25K','CMB_25K_Icontrol_x000D_')
                        AND TRUNC(DATED) BETWEEN  '" . $startdate . "'  AND '" . $billdate . "' AND msisdn in " . $tuple . "
                        ) WHERE rnk = 1
                        UNION ALL
                        SELECT TO_CHAR(TO_DATE ( TO_CHAR(DATED, 'DD-MON-YYYY') || call_start_time,'DD-MON-YYYY HH24:MI:SS'),'DD-MON-YYYY HH24:MI:SS')
                                                    DATED,call_start_time,
                                                     msisdn,
                                                     service_usage_type_name,
                                                     bundle_product_type_name,
                                                     0 volume,
                                                     CASE WHEN service_usage_type_name = 'Data' THEN ROUND(revenue/1.18,2) ELSE ROUND(revenue/1.3216,2) END revenue
                        FROM icontrol_vas_usage
                        WHERE UPPER(service_usage_type_name) = 'VOICE'
                        AND bundle_product_type_name NOT IN ('IControl_5K','IControl_10K','Icontrol CMB','IControl_25K','CMB_25K_Icontrol_x000D_')
                        AND TRUNC(DATED) BETWEEN  '" . $startdate . "'  AND '" . $billdate . "' AND msisdn in " . $tuple . "
                        UNION ALL
                        SELECT TO_CHAR(TO_DATE ( TO_CHAR(DATED, 'DD-MON-YYYY') || call_start_time,'DD-MON-YYYY HH24:MI:SS'),'DD-MON-YYYY HH24:MI:SS')
                                                    DATED,call_start_time,
                                                     msisdn,
                                                     service_usage_type_name,
                                                     bundle_product_type_name,
                                                     0 volume,
                                                     CASE WHEN service_usage_type_name = 'Data' THEN ROUND(revenue/1.18,2) ELSE ROUND(revenue/1.3216,2) END revenue
                        FROM icontrol_vas_usage
                        WHERE UPPER(service_usage_type_name) <> 'VOICE' AND TRUNC(DATED) BETWEEN  '" . $startdate . "'  AND '" . $billdate . "' AND msisdn in " . $tuple . "
                       UNION ALL
                       SELECT TO_CHAR(DATETIME,'DD-MON-YYYY HH24:MI:SS') DATED,'',SUBSTR(msisdn,4,9) msisdn,'Data' service_type,BUNDLE,0,
                       ROUND(PRICE/1.18,2) CHARGED_AMOUNT
                       FROM ICONTROL_DATA_SUBSCRIPTIONS
                        WHERE TRUNC(DATETIME) BETWEEN  '" . $startdate . "'  AND '" . $billdate . "' AND SUBSTR(msisdn,4,9) in " . $tuple . "
                       ORDER BY 1";
        //$vas_query = "SELECT TO_CHAR(TO_DATE ( TO_CHAR(DATED, 'DD-MON-YYYY') || call_start_time,'DD-MON-YYYY HH24:MI:SS'),'DD-MON-YYYY HH24:MI:SS') DATED,call_start_time,msisdn,service_usage_type_name,bundle_product_type_name, "
        //        . "CASE WHEN service_usage_type_name = 'Data' THEN ROUND(revenue/1.18,2)"
        //        ." ELSE ROUND(revenue/1.3216,2) END revenue FROM icontrol_vas_usage "
        //        . "WHERE TRUNC(DATED) BETWEEN '" . $startdate . "'  AND '" . $billdate . "'"
        //        . "AND  service_usage_type_name <> '(null)' AND msisdn in ".$tuple;
        //echo $vas_query;
        $vas_items = $dbh->select($vas_query);
        return $vas_items;
    }

    function generate_voice_usage($tuple, $startdate, $billdate, $name) {
        $dbh = new PDOConnection();
        $util = new helper();
        $util->op_log($this->date, $name, $logdetail = 'GENERATING VOICE DATA');
        //echo date('Y-m-d H:i:s'). " Generating Voice Data<br>";
        $voice_query = "SELECT TO_CHAR(TO_DATE ( TO_CHAR(DATED, 'DD-MON-YYYY') || call_start_time,'DD-MON-YYYY HH24:MI:SS'),'DD-MON-YYYY HH24:MI:SS') DATED,call_start_time,MSISDN,CALLEDPARTYNUMBER,LEG, "
                . "ROUND(CHARGED_AMOUNT/1.3216,2) CHARGED_AMOUNT,MOU "
                . "FROM icontrol_voice_usage "
                . "WHERE TRUNC(DATED) BETWEEN '" . $startdate . "'  AND '" . $billdate . "'"
                . "AND msisdn IN " . $tuple . " ORDER BY 1";
        $voice_items = $dbh->select($voice_query);
        //print_r($voice_items);
        return $voice_items;
    }

    function generate_sms_usage($tuple, $startdate, $billdate, $name) {
        $dbh = new PDOConnection();
        $util = new helper();
        $util->op_log($this->date, $name, $logdetail = 'GENERATING SMS DATA');
        //echo date('Y-m-d H:i:s'). " Generating SMS Data<br>";
        $sms_query = "SELECT TO_CHAR(TO_DATE ( TO_CHAR(DATED, 'DD-MON-YYYY') || call_start_time,'DD-MON-YYYY HH24:MI:SS'),'DD-MON-YYYY HH24:MI:SS') DATED,call_start_time,MSISDN,CALLEDPARTYNUMBER,LEG,"
                . "ROUND(CHARGED_AMOUNT/1.3216,2) CHARGED_AMOUNT "
                . "FROM icontrol_sms_usage "
                . "WHERE TRUNC(DATED) BETWEEN '" . $startdate . "'  AND '" . $billdate . "'"
                . "AND msisdn in " . $tuple . " ORDER BY 1";
        $sms_items = $dbh->select($sms_query);
        return $sms_items;
    }

    function generate_paygo_usage($tuple, $startdate, $billdate, $name) {
        $dbh = new PDOConnection();
        // echo date('Y-m-d H:i:s'). " Generating PAY GO Data USAGE<br>";
        $util = new helper();
        $util->op_log($this->date, $name, $logdetail = 'GENERATING PAYGO DATA');
        $paygo_query = "SELECT TO_CHAR(TO_DATE ( TO_CHAR(DATED, 'DD-MON-YYYY') || call_start_time,'DD-MON-YYYY HH24:MI:SS'),'DD-MON-YYYY HH24:MI:SS') DATED,call_start_time,MSISDN,VOLUME, "
                . "ROUND(CHARGED_AMOUNT/1.18,2) CHARGED_AMOUNT "
                . "FROM icontrol_data_usage "
                . "WHERE TRUNC(DATED) BETWEEN '" . $startdate . "'  AND '" . $billdate . "'"
                . "AND msisdn in " . $tuple . " ORDER BY 1";
        //echo $paygo_query;
        $paygo_items = $dbh->select($paygo_query);
        return $paygo_items;
    }

    function mem_usage($unit = 'M') {
        switch ($unit) {
            case 'K':
            case 'k':
                return $this->format_n(memory_get_usage(true) / (1024), 1) . " KB";
                break;
            case 'G':
            case 'g':
                return $this->format_n(memory_get_usage(true) / (1024 * 1024 * 1024), 1) . " GB";
                break;
            case 'M':
            case 'm':
            default:
                return $this->format_n(memory_get_usage(true) / (1024 * 1024), 1) . " MB";
        }
    }

    function format_n($input, $dec_places = 0) {
        if (is_numeric($input) or $input == '') {
            return number_format($input, $dec_places);
        } else {
            return $input;
        }
    }

    function op_log($opdate = '', $op = '', $detail = '', $echo = TRUE) {

        $util = new helper();
        $logdate = date('Y-m-d H:i:s');
        if ($echo) {
            echo $logdate . " : " . $op . " " . ($opdate != '' ? "[" . $opdate . "]" : "") . " :[" . $util->mem_usage() . "]:" . $detail . " ...\n";
        }
    }

}
