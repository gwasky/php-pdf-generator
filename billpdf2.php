<?php

/**
 * Description of BillPDF
 *
 * @author gibson
 */
error_reporting(E_PARSE | E_COMPILE_ERROR | E_ERROR);

require_once('libraries/fpdf/fpdf.php');
//include 'fpdf17/font/';
require_once("PDOConnection.php");
require_once("helper.php");
require_once("itemise.php");

class PDF extends FPDF {

    //put your code here
    function BasicTable($header, $data, $width) {
        // Header
        if (is_array($header) and count($header) > 0) {
            foreach ($header as $col) {
                $this->Cell(40, 7, $col, 1);
            }
            $this->Ln();
        }
        // Data
        foreach ($data as $row) {
            foreach ($row as $col) {
                $this->Cell(40, 6, $col, 1);
            }
            $this->Ln();
        }
    }

    function ResetFillColor() {
        $this->SetFillColor(255, 255, 255);
    }

    function billPDF($value, $startdate, $billdate, $output_method = 'I') {
        $pdf = new PDF();
        $dir = "F:/air/icontrol/bills/FEB2019/";
        if (count($value) == 0) {
            return "NO MASTER NUMBER PASSED<br>";
        }

        $dbh = new PDOConnection();
        $util = new helper();
        //$value[MASTER_MSISDN] = '256702278217';

        $select_sql = "SELECT a.msisdn,b.master_msisdn,b.account_name,a.service_type,a.bundle_product_type_name,
                        CASE WHEN a.service_type = 'DATA' THEN ROUND(a.CHARGED_AMOUNT/1.18,2) 
                            ELSE ROUND(a.CHARGED_AMOUNT/1.3216,2) END CHARGED_AMOUNT
                        FROM (
                            SELECT msisdn,service_type,bundle_product_type_name,SUM(CHARGED_AMOUNT) CHARGED_AMOUNT
                     FROM (
                        SELECT msisdn,service_type,bundle_product_type_name,CHARGED_AMOUNT
                         FROM(
                            SELECT msisdn,UPPER(service_usage_type_name) service_type,bundle_product_type_name,revenue CHARGED_AMOUNT,
                            ROW_NUMBER() OVER(PARTITION BY msisdn,bundle_product_type_name  ORDER BY 1,2,3 DESC ) rnk
                            FROM icontrol_vas_usage
                            WHERE UPPER(service_usage_type_name) = 'VOICE'
							AND bundle_product_type_name IN ('IControl_5K','IControl_10K','Icontrol CMB','IControl_25K','CMB_25K_Icontrol_x000D_')
							AND trunc(DATED) BETWEEN  '" . $startdate . "'  AND '" . $billdate . "'
                        ) WHERE rnk = 1
						UNION ALL
							SELECT msisdn,UPPER(service_usage_type_name) SERVICE_TYPE,bundle_product_type_name,revenue CHARGED_AMOUNT
								   FROM icontrol_vas_usage
							 WHERE UPPER(service_usage_type_name) = 'VOICE'
							 AND trunc(DATED) BETWEEN  '" . $startdate . "'  AND '" . $billdate . "'
						AND bundle_product_type_name NOT IN ('IControl_5K','IControl_10K','Icontrol CMB','IControl_25K','CMB_25K_Icontrol_x000D_')
                        UNION ALL
                        SELECT msisdn,
                            CASE WHEN UPPER(service_usage_type_name) = 'COMBO' THEN 'VOICE' ELSE UPPER(service_usage_type_name) END
                            service_type,bundle_product_type_name,revenue CHARGED_AMOUNT
                        FROM icontrol_vas_usage
                        WHERE UPPER(service_usage_type_name) <> 'VOICE' AND trunc(DATED) BETWEEN  '" . $startdate . "'  AND '" . $billdate . "'
                       UNION ALL
                        SELECT msisdn,UPPER('VOICE') service_type, 'VOICE USAGE' bundle_product_type_name,CHARGED_AMOUNT
                        FROM icontrol_voice_usage
                        WHERE trunc(DATED) BETWEEN  '" . $startdate . "'  AND '" . $billdate . "'
                        UNION ALL
                        SELECT msisdn,'SMS' service_type,'SMS USAGE' bundle_product_type_name, CHARGED_AMOUNT
                        FROM icontrol_sms_usage
                        WHERE TRUNC(DATED) BETWEEN  '" . $startdate . "'  AND '" . $billdate . "'
                        UNION ALL
                        SELECT msisdn,'DATA' service_type ,'PAY GO',CHARGED_AMOUNT
                        FROM icontrol_data_usage
                        WHERE TRUNC(DATED) BETWEEN  '" . $startdate . "'  AND '" . $billdate . "'
                        UNION ALL
                        SELECT substr(msisdn,4,9) msisdn,'DATA' service_type,BUNDLE,PRICE
                        FROM ICONTROL_DATA_SUBSCRIPTIONS
                        WHERE trunc(DATETIME) BETWEEN  '" . $startdate . "'  AND '" . $billdate . "'
                    ) GROUP BY msisdn,service_type,bundle_product_type_name
                    ) a LEFT OUTER JOIN (
                 SELECT t.master_msisdn,t.msisdn,z.imsi,z.account_name
                 FROM icontrol_subs t LEFT OUTER JOIN icontrol_masters z
                 ON t.master_msisdn = z.master_msisdn
                ) b ON a.msisdn = SUBSTR(b.msisdn,4,9) 
                 WHERE b.master_msisdn = '" . $value[MASTER_MSISDN] . "' ORDER BY 1";
        //echo $select_sql;
        //exit;
        //echo "GENERATING SUMMARY BILL FOR ".strtoupper($value[ACCOUNT_NAME])."<br>";
        $util->op_log($this->date,  $value[MASTER_MSISDN] .'-'.$value[ACCOUNT_NAME], $logdetail = 'GENERATING SUMMARY BILL');
        $pdf->AliasNbPages();
        $pdf->AddPage();
        //$pdf->SetMargins(15,15,15);
        $pdf->SetMargins(10, 10, 10);

        //HEADER
        //$pdf->Image('images/header.png',10,10,190);
        $pdf->Image('images/footer.png', 10, 10, 190);
        $pdf->SetY(17);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Helvetica', '', 9);
        $pdf->Cell(90, 3, 'ICONTORL BILLING', 0, 1, L);
        $pdf->SetFont('Helvetica', '', 6);
        $pdf->Cell(90, 3, $billdate, 0, 1, L);

        //CLIENT DETAILS
        $pdf->SetY(30);
        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(90, 4, strtoupper($value[ACCOUNT_NAME]), 0, 1, L);
        $pdf->SetFont('Helvetica', 'B', 6);
        $pdf->Cell(90, 4, strtoupper($value[MASTER_MSISDN]), 0, 1, L);

        // GET TOTAL VOLUMES
        $items = $dbh->select($select_sql);
        //print_r($items);
        foreach ($items as $key => $item) {
            if ($item[SERVICE_TYPE] == 'VOICE') {
                $TOTAL['VOICE']+=$item[CHARGED_AMOUNT];
            } else if ($item[SERVICE_TYPE] == 'SMS') {
                $TOTAL['SMS']+=$item[CHARGED_AMOUNT];
            } else if ($item[SERVICE_TYPE] == 'DATA') {
                $TOTAL['DATA']+=$item[CHARGED_AMOUNT];
            }
            $TOTAL[TOTAL]+=$item[CHARGED_AMOUNT];
        }


        //print_r($TOTAL);
        //ACCOUNT SUMMARY
        $w = 190 / 4;
        $h = 4;
        $i = 1;
        $pdf->Ln();
        $pdf->SetFont('Times', 'B', 10);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFillColor(209, 210, 212);
        $pdf->Cell(190, $h + 1, 'Bill Summary', 0, 1, C, true);
        $pdf->SetFont('Times', 'U', 10);
        $pdf->Cell($w, $h, 'VOICE', 0, 0, C, true);
        $pdf->Cell($w, $h, 'SMS', 0, 0, C, true);
        $pdf->Cell($w, $h, 'DATA', 0, 0, C, true);
        $pdf->Cell($w, $h, 'Chargeable Amount', 0, 1, C, true);
        $pdf->SetFont('Helvetica', 'B', 10);
        $pdf->Cell($w, $h, number_format($TOTAL[VOICE]), 0, 0, C, true);
        $pdf->Cell($w, $h, number_format($TOTAL[SMS]), 0, 0, C, true);
        $pdf->Cell($w, $h, number_format($TOTAL[DATA]), 0, 0, C, true);
        $pdf->Cell($w, $h, number_format($TOTAL[TOTAL]), 0, 0, C, true);

        $pdf->SetLineWidth(0.2);
        //CHARGES DETAILS BORDER
        $charge_details_Y = 72;
        $pdf->SetY($charge_details_Y);
        $pdf->SetFont('Helvetica', 'B', 11);
        $pdf->SetFillColor(237, 28, 36);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetLineWidth(0.05);
        $pdf->Cell($w1, 7, 'This Month\'s Charge Details', TB, 2, L, true);
        $pdf->Ln();
        //$pdf->SetLineWidth(0.1);
        $pdf->SetTextColor(0, 0, 0);


        $h = 4;
        //overall_max_items z`29; //+3
        //$pdf->SetXY($charge_details_X, $charge_details_Y + 7 + 0.5);
        $charge_details_Y += 8;
        $pdf->SetY($charge_details_Y);
        $pdf->SetFont('Helvetica', 'B', 8);
        $pdf->SetFillColor(209, 210, 212);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(10, 3, 'No', 0, 0, L, true);
        $pdf->Cell(45, 3, 'Mobile', 0, 0, L, true);
        $pdf->Cell(35, 3, 'Service Type', 0, 0, L, true);
        $pdf->Cell(70, 3, 'Product', 0, 0, L, true);
        $pdf->Cell(30, 3, 'Cost', 0, 1, R, true);
        $pdf->Ln();

        $charge_details_Y += 4;
        foreach ($items as $key => $item) {
            $pdf->ResetFillColor();
            $pdf->SetFont('Helvetica', '', 6);
            $pdf->SetY($charge_details_Y);
            $pdf->Cell(10, 3, $i, 0, 0, L, true);
            $pdf->Cell(45, 3, $item[MSISDN], 0, 0, L, true);
            $pdf->Cell(35, 3, $item[SERVICE_TYPE], 0, 0, L, true);
            $pdf->Cell(70, 3, $item[BUNDLE_PRODUCT_TYPE_NAME], 0, 0, L, true);
            $pdf->Cell(30, 3, number_format($item[CHARGED_AMOUNT], 2), 0, 2, R, true);
            $pdf->Ln();
            $charge_details_Y += 3;
            $x = $charge_details_Y;
            //echo $i . " " . $charge_details_Y . "<br>";
            ++$i;
            if ($i % 61 == 0) {
                //$pdf->Footer();
                $pdf->AddPage();
                //$pdf->SetY($charge_details_Y);
                $pdf->SetFont('Helvetica', 'B', 8);
                $pdf->SetFillColor(209, 210, 212);
                $pdf->SetTextColor(0, 0, 0);
                $pdf->Cell(10, 3, 'No', 0, 0, L, true);
                $pdf->Cell(45, 3, 'Mobile', 0, 0, L, true);
                $pdf->Cell(35, 3, 'Service Type', 0, 0, L, true);
                $pdf->Cell(70, 3, 'Product', 0, 0, L, true);
                $pdf->Cell(30, 3, 'Cost', 0, 1, R, true);
                $pdf->Ln();
                $charge_details_Y = 17;
                $x = $charge_details_Y;
                $pdf->SetY($charge_details_Y);
            }
        }

        $pdf->SetAuthor('Airtel Enterprise Billing');
        $pdf->SetCreator('Airtel Icontrol');

        if ($output_method != 'S') {
            if (count($ids) == 1) {
                $file_name = '';
            } else {
                //$file_name = 'testfile.pdf';
                $file_name = $dir . str_replace(' ', '_', strtoupper($value[ACCOUNT_NAME])) . "_" . strtoupper($value[MASTER_MSISDN]) . ".pdf";
            }
        } else {
            $file_name = $dir . str_replace(' ', '_', strtoupper($value[ACCOUNT_NAME])) . "_" . strtoupper($value[MASTER_MSISDN]);
            return $pdf->Output($file_name, $output_method);
        }

        //$pdf->Output($file_name.'.pdf',$output_method);
        if ($pdf->Output($file_name, 'F')) {
            //echo "BILL GENERATION COMPLETE...<br>";
            $util->op_log($this->date, '', $logdetail = 'BILL GENERATION COMPLETE');
        }

        //$util = new helper();
        $child_tuple = $util->generate_childnumbers($value[MASTER_MSISDN],$value[MASTER_MSISDN].'-'.$value[ACCOUNT_NAME]);
        $vas = $util->generate_vas_usage($child_tuple, $startdate, $billdate,$value[MASTER_MSISDN].'-'.$value[ACCOUNT_NAME]);
        $voice = $util->generate_voice_usage($child_tuple, $startdate, $billdate,$value[MASTER_MSISDN].'-'.$value[ACCOUNT_NAME]);
        $sms = $util->generate_sms_usage($child_tuple, $startdate, $billdate,$value[MASTER_MSISDN].'-'.$value[ACCOUNT_NAME]);
        $paygo = $util->generate_paygo_usage($child_tuple, $startdate, $billdate,$value[MASTER_MSISDN].'-'.$value[ACCOUNT_NAME]);
        //print_r($vas);
        foreach ($vas as $key => $pair) {
            $data[$pair[MSISDN]][VAS][$pair[DATED]][$pair[SERVICE_USAGE_TYPE_NAME]][$pair[BUNDLE_PRODUCT_TYPE_NAME]][$pair[REVENUE]] = [$pair[VOLUME]];
            $summary[$pair[MSISDN]][VAS][$pair[BUNDLE_PRODUCT_TYPE_NAME]]+=$pair[REVENUE];
            $grandtotal[$pair[MSISDN]][TOTAL]+=$pair[REVENUE];
            //echo $value[MSISDN]."<br>";
        }

        foreach ($voice as $key => $pair) {
            $data[$pair[MSISDN]][VOICE][$pair[DATED]][$pair[CALLEDPARTYNUMBER]][$pair[LEG]][$pair[CHARGED_AMOUNT]] = [$pair[MOU]];
            $summary[$pair[MSISDN]][VOICE][$pair[LEG]]+=$pair[CHARGED_AMOUNT];
            $grandtotal[$pair[MSISDN]][TOTAL]+=$pair[CHARGED_AMOUNT];
            //echo $value[MSISDN]."<br>";
        }

        foreach ($sms as $key => $pair) {
            $data[$pair[MSISDN]][SMS][$pair[DATED]][$pair[CALLEDPARTYNUMBER]][$pair[LEG]] = [$pair[CHARGED_AMOUNT]];
            $summary[$pair[MSISDN]][SMS][$pair[LEG]]+=$pair[CHARGED_AMOUNT];
            $grandtotal[$pair[MSISDN]][TOTAL]+=$pair[CHARGED_AMOUNT];
            //echo $value[MSISDN]."<br>";
        }

        foreach ($paygo as $key => $pair) {
            $data[$pair[MSISDN]][PAYGO][$pair[DATED]][$pair[VOLUME]] = [$pair[CHARGED_AMOUNT]];
            $summary[$pair[MSISDN]][PAYGO]+=$pair[CHARGED_AMOUNT];
            $grandtotal[$pair[MSISDN]][TOTAL]+=$pair[CHARGED_AMOUNT];
            //echo $value[MSISDN]."<br>";
        }

        /*
          foreach ($data as $msisdn => $elements) {
          //echo $msisdn." | ";
          foreach ($elements as $key => $items) {
          if ($key == 'VAS') {
          foreach ($items as $date => $elements1) {
          foreach ($elements1 as $service_usage_type => $elements2) {
          foreach ($elements2 as $product_type => $elements3) {
          foreach ($elements3 as $revenue => $volume) {
          //print_r($volume);
          echo $msisdn . " | " . $date . "| " . $service_usage_type . "| " . $product_type . " | " . $revenue . " | " . $volume[0] . "<br>";
          }
          }
          }
          }
          print_r($summary[$msisdn][VAS]);
          foreach($summary[$msisdn][VAS] as $k => $v){
          echo $k. "-" . $v. "<br>";
          }
          } else if ($key == 'VOICE') {
          foreach ($items as $date => $elements1) {
          foreach ($elements1 as $calledparty => $elements2) {
          foreach ($elements2 as $leg => $elements3) {
          foreach ($elements3 as $charged_amount => $mou) {
          //print_r($volume);
          echo $msisdn . " | " . $date . "| " . $calledparty . "| " . $charged_amount . " | " . $charged_amount . " | " . $mou[0] . "<br>";
          }
          }
          }
          }
          foreach($summary[$msisdn][VOICE] as $k1 => $v1){
          echo $k1. "-" . $v1. "<br>";
          }
          } else if ($key == 'SMS') {
          foreach ($items as $date => $elements1) {
          foreach ($elements1 as $calledparty => $elements2) {
          foreach ($elements2 as $leg => $charged_amount) {
          echo $msisdn . " | " . $date . "| " . $calledparty . "| " . $leg . " | " . $charged_amount[0] . "<br>";
          }
          }
          }
          foreach($summary[$msisdn][SMS] as $k2 => $v2){
          echo $k2. "-" . $v2. "<br>";
          }
          }

          }
          }

         */
        /*
          foreach ($data[VOICE] as $msisdn => $elements) {
          //echo $msisdn." | ";
          foreach ($elements as $date => $elements1) {
          foreach ($elements1 as $calledparty => $elements2) {
          foreach ($elements2 as $leg => $elements3) {
          foreach ($elements3 as $charged_amount => $mou) {
          //print_r($volume);
          echo $msisdn . " | " . $date . "| " . $calledparty . "| " . $charged_amount . " | " . $charged_amount . " | " . $mou[0] . "<br>";
          }
          }
          }
          }
          }

          foreach ($data[SMS] as $msisdn => $elements) {
          //echo $msisdn." | ";
          foreach ($elements as $date => $elements1) {
          foreach ($elements1 as $calledparty => $elements2) {
          foreach ($elements2 as $leg => $charged_amount) {
          echo $msisdn . " | " . $date . "| " . $calledparty . "| " . $leg . " | " . $charged_amount[0]. "<br>";
          }
          }
          }
          }

          foreach ($data[VAS] as $msisdn => $elements) {
          //echo $msisdn." | ";
          foreach ($elements as $date => $elements1) {
          foreach ($elements1 as $service_usage_type => $elements2) {
          foreach ($elements2 as $product_type => $elements3) {
          foreach ($elements3 as $revenue => $volume) {
          //print_r($volume);
          echo $msisdn . " | " . $date . "| " . $service_usage_type . "| " . $product_type . " | " . $revenue ." | " . $volume[0] ."<br>";
          }
          }
          }
          }
          }
         */
        //print_r($data);
        //exit();
        $itemise_pdf = new itemise();
        $itemise_pdf->itemise($value, $data, $summary, $grandtotal);
    }

    function setFooter() {
        $footer_txt_Y = 268;
        $footer_txt_X = 10;
        $pdf->SetXY($footer_txt_X, $footer_txt_Y);
        $pdf->SetFont('courier', '', 8);
        $footer_text = 'Airtel Uganda Limited : Forest Mall business centre, Lugogo and Plaza Kampala Road';
        $pdf->Cell(0, 4, $footer_text, 0, 1, C);
        $footer_text = 'P.O.B0x 6771 Kampala : business.support@ug.airtel.com : (256) 700777776 : http://africa.airtel.com/uganda/';
        $pdf->Cell(0, 4, $footer_text, 0, 1, C);
        return x;
    }

    function Footer() {
        // Go to 1.5 cm from bottom
        $this->SetY(-15);
        // Select Arial italic 8
        $this->SetFont('Arial', '', 8);
        // Print centered page number
        $this->Cell(0, 10, 'Page ' . $this->PageNo(), 0, 0, 'C');
    }

}

?>
