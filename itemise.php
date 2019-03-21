<?php

/**
 * Description of itemise
 *
 * @author gibson
 */
error_reporting(E_PARSE | E_COMPILE_ERROR | E_ERROR);
require_once('libraries/fpdf/fpdf.php');

class itemise extends FPDF {

    function ResetFillColor() {
        $this->SetFillColor(255, 255, 255);
    }

    function itemise($master_details, $data, $summary, $grandtotal, $output_method = 'I') {
        //print_r($data);
        $util = new helper();
        $dir = "F:/air/icontrol/bills/FEB2019/";
        $util->op_log($this->date, $master_details[MASTER_MSISDN] .'-'.$master_details[ACCOUNT_NAME], $logdetail = 'GENERATING ITEMISED BILL' );
        $pdf = new PDF();
        /*if (count($master_details) == 0 & count($vas == 0 & count($voice) == 0 && count($sms) == 0)) {
            $util->op_log($this->date,'ERROR', $logdetail = 'NO DATA HAS BEEN PASSED' );
            return date('Y-m-d H:i:s') . "Err : NO DATA HAS BEEN PASSED<br>";
        }*/
        $p = 0;
        $pdf->AliasNbPages();
        $pdf->AddPage();
        $pdf->SetMargins(10, 10, 10);
        //HEADER
        //print_r($data);
        $i = 0;
        foreach ($data as $msisdn => $elements) {
            $this->grand_summary($pdf, $msisdn, $grandtotal);
            //echo $msisdn." | ";
            foreach ($elements as $key => $items) {

                if ($key == 'VAS') {
                    if ($i > 0) {
                        $pdf->AddPage();
                    }
                    $this->Header($pdf, $master_details);
                    $p = 0;
                    //echo "VAS Item count" . $msisdn . "- " . count($elements) . "<br>";
                    $this->setVASHeader($pdf, $msisdn);
                    //print_r($elements);
                    $this->setVASDetailsHeader($pdf);
                    $charge_details_Y = 48;
                    foreach ($items as $date => $elements1) {
                        foreach ($elements1 as $service_usage_type => $elements2) {

                            foreach ($elements2 as $product_type => $elements3) {
                                ++$i;
                                foreach ($elements3 as $revenue => $volume) {

                                    //print_r($volume);
                                    //echo $msisdn . " | " . $date . "| " . $service_usage_type . "| " . $product_type . " | " . $revenue . " | " . $volume[0] . "<br>";
                                    $pdf->ResetFillColor();
                                    $pdf->SetFont('Helvetica', '', 6);
                                    $pdf->SetY($charge_details_Y);
                                    $pdf->Cell(20, 3, $i, 0, 0, L, true);
                                    $pdf->Cell(30, 3, $msisdn, 0, 0, L, true);
                                    $pdf->Cell(25, 3, $date, 0, 0, L, true);
                                    $pdf->Cell(35, 3, $service_usage_type, 0, 0, L, true);
                                    $pdf->Cell(30, 3, $product_type, 0, 0, L, true);
                                    $pdf->Cell(20, 3, number_format($volume[0], 2), 0, 0, R, true);
                                    $pdf->Cell(30, 3, number_format($revenue, 2), 0, 2, R, true);
                                    $pdf->Ln();

                                    $charge_details_Y += 3;
                                    $x = $charge_details_Y;
                                    if ($x % 276 == 0) {
                                        //$pdf->Footer();
                                        $pdf->AddPage();
                                        //$pdf->SetY($charge_details_Y);
                                        $this->NewPageHeader($pdf, $master_details);
                                        $this->setVASDetailsHeader($pdf);
                                        $charge_details_Y = 48;
                                        $x = $charge_details_Y;
                                    }
                                }
                            }
                        }
                    }
                    $this->charge_summary($pdf, $charge_details_Y, 'VAS', $summary[$msisdn]);
                } else if ($key == 'VOICE') {
                    if ($i > 0) {
                        $pdf->AddPage();
                    }
                    $i = 0;
                    //echo "p ".$p ."<br>";
                    //$pdf->AddPage();
                    if ($p == 1) {
                        //echo "p ".$p ."<br>";
                        $this->NewPageHeader($pdf, $master_details);
                    } else {
                        $this->Header($pdf, $master_details);
                        $p = 1;
                    }
                    $this->setVoiceHeader($pdf, $msisdn);
                    $this->setVoiceDetailsHeader($pdf);
                    $charge_details_Y = 48;
                    foreach ($items as $date => $elements1) {
                        foreach ($elements1 as $calledparty => $elements2) {
                            foreach ($elements2 as $leg => $elements3) {
                                ++$i;
                                foreach ($elements3 as $charged_amount => $mou) {
                                    //print_r($volume);
                                    //echo $msisdn . " | " . $date . "| " . $calledparty . "| " . $charged_amount . " | " . $charged_amount . " | " . $mou[0] . "<br>";
                                    $pdf->ResetFillColor();
                                    $pdf->SetFont('Helvetica', '', 6);
                                    $pdf->SetY($charge_details_Y);
                                    $pdf->Cell(20, 3, $i, 0, 0, L, true);
                                    $pdf->Cell(30, 3, $msisdn, 0, 0, L, true);
                                    $pdf->Cell(35, 3, $date, 0, 0, L, true);
                                    $pdf->Cell(35, 3, $calledparty, 0, 0, L, true);
                                    $pdf->Cell(40, 3, $leg, 0, 0, L, true);
                                    $pdf->Cell(20, 3, $mou[0], 0, 0, L, true);
                                    $pdf->Cell(10, 3, number_format($charged_amount, 2), 2, 0, 2, R, true);
                                    $pdf->Ln();
                                    $charge_details_Y += 3;
                                    $x = $charge_details_Y;
                                    //echo $i ."---- Y size". $charge_details_Y."<br>";
                                    if ($x % 276 == 0) {
                                        //$pdf->Footer();
                                        $pdf->AddPage();
                                        //$pdf->SetY($charge_details_Y);
                                        $this->NewPageHeader($pdf, $master_details);
                                        $this->setVoiceDetailsHeader($pdf);
                                        $charge_details_Y = 48;
                                        $x = $charge_details_Y;
                                    }
                                }
                            }
                        }
                    }
                    $this->charge_summary($pdf, $charge_details_Y, 'VOICE', $summary[$msisdn]);
                } else if ($key == 'SMS') {
                    if ($i > 0) {
                        $pdf->AddPage();
                    }
                    $i = 0;
                    // Know when to print a new header
                    if ($p == 1) {
                        $this->NewPageHeader($pdf, $master_details);
                    } else {
                        $this->Header($pdf, $master_details);
                        $p = 1;
                    }
                    //$this->NewPageHeader($pdf, $master_details);
                    $this->setSMSHeader($pdf, $msisdn);
                    $this->setSMSDetailsHeader($pdf);
                    $charge_details_Y = 48;
                    foreach ($items as $date => $elements1) {
                        foreach ($elements1 as $calledparty => $elements2) {
                            ++$i;
                            foreach ($elements2 as $leg => $charge_amount) {
                                //print_r($volume);
                                //echo $msisdn . " | " . $date . "| " . $calledparty . "| " . $charged_amount . " | " . $charged_amount . " | " . $mou[0] . "<br>";
                                $pdf->ResetFillColor();
                                $pdf->SetFont('Helvetica', '', 6);
                                $pdf->SetY($charge_details_Y);
                                $pdf->Cell(20, 3, $i, 0, 0, L, true);
                                $pdf->Cell(30, 3, $msisdn, 0, 0, L, true);
                                $pdf->Cell(45, 3, $date, 0, 0, L, true);
                                $pdf->Cell(35, 3, $calledparty, 0, 0, L, true);
                                $pdf->Cell(45, 3, $leg, 0, 0, L, true);
                                $pdf->Cell(15, 3, number_format($charge_amount[0], 2), 0, 0, 1, R, true);
                                $pdf->Ln();
                                $charge_details_Y += 3;
                                $x = $charge_details_Y;
                                if ($x % 276 == 0) {
                                    $pdf->AddPage();
                                    $this->NewPageHeader($pdf, $master_details);
                                    $this->setSMSDetailsHeader($pdf);
                                    $charge_details_Y = 48;
                                    $x = $charge_details_Y;
                                    $pdf->SetY($charge_details_Y);
                                }
                            }
                        }
                    }
                    $this->charge_summary($pdf, $charge_details_Y, 'SMS', $summary[$msisdn]);
                }
            }
            $file_name = $dir . str_replace(' ', '_', strtoupper($master_details[ACCOUNT_NAME])) . "_" . strtoupper($master_details[MASTER_MSISDN]) . "_" . $msisdn . ".pdf";
            $pdf->Output($file_name, 'F');
            $pdf = new PDF();
            //Open New Document
            $pdf->AliasNbPages();
            $pdf->AddPage();
            $pdf->SetMargins(10, 10, 10);
            //HEADER
            $this->Header($pdf, $master_details);
            $i = 0;
        }
    }

    function Header($pdf, $master_details) {
        $pdf->Image('images/logo.png', 10, 10, 20);
        $pdf->SetY(28);
        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->SetTextColor(0, 0, 0);
        //$pdf->Ln();
        $pdf->Cell(90, 4, strtoupper($master_details[ACCOUNT_NAME]), 0, 1, L);
        $pdf->SetFont('Helvetica', 'B', 6);
        $pdf->Cell(90, 4, strtoupper($master_details[MASTER_MSISDN]), 0, 1, L);
        return $pdf;
    }

    function grand_summary($pdf, $msisdn, $grandtotal) {
        //print_r($grandtotal);
        $pdf->SetY(36);
        $pdf->SetFont('Helvetica', 'B', 6);
        $pdf->ResetFillColor();
        $pdf->SetFont('Helvetica', 'B', 6);
        $pdf->Cell(35, 3, 'TOTAL CHARGEABLE AMOUNT:', 0, 0, L, true);
        $pdf->Cell(20, 3, number_format($grandtotal[$msisdn][TOTAL], 2), 0, 1, L, true);
        //$pdf->Cell(50, 4, 'TOTAL CHARGEABLE AMOUNT', 0, 1, L,true);
        //$pdf->SetFont('Helvetica', 'B', 6);
        //$pdf->Cell(20, 4, 'testing', 0, 1, L,true);
        //$pdf->Cell(20, 3, 'Call Duration', 0, 0, L, true);
        //$pdf->Cell(20, 3, 'Charge Amount', 0, 1, R, true);
        //$pdf->Cell(90, 4, number_format($grandtotal[$msisdn][TOTAL], 2), 0, 1, L);
        $pdf->Ln();
        return $pdf;
    }

    function NewPageHeader($pdf, $master_details) {
        $pdf->Image('images/logo.png', 10, 10, 20);
        $pdf->SetY(20);
        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln();
        $pdf->Cell(90, 4, strtoupper($master_details[ACCOUNT_NAME]), 0, 1, L);
        $pdf->SetFont('Helvetica', 'B', 6);
        $pdf->Cell(90, 4, strtoupper($master_details[MASTER_MSISDN]), 0, 1, L);
        return $pdf;
    }

    function setVASHeader($pdf, $msisdn) {
    $charge_details_Y = 40;
    $pdf->SetY($charge_details_Y);
    $pdf->SetFont('Helvetica', 'B', 8);
    $pdf->SetFillColor(237, 28, 36);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetLineWidth(0.05);
    $pdf->Cell(190, 4, "VAS Charges and Transactions for : 0" . $msisdn, TB, 2, L, true);
    $pdf->Ln();
    //$pdf->SetLineWidth(0.1);
    $pdf->SetTextColor(0, 0, 0);
    return $pdf;
    }

    function setPAYGOHeader($pdf, $msisdn) {
        $charge_details_Y = 40;
        $pdf->SetY($charge_details_Y);
        $pdf->SetFont('Helvetica', 'B', 8);
        $pdf->SetFillColor(237, 28, 36);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetLineWidth(0.05);
        $pdf->Cell(190, 4, "DATA PAYGO Charges and Transactions for : 0" . $msisdn, TB, 2, L, true);
        $pdf->Ln();
        //$pdf->SetLineWidth(0.1);
        $pdf->SetTextColor(0, 0, 0);
        return $pdf;
    }

    function setVoiceHeader($pdf, $msisdn) {
        $charge_details_Y = 40;
        $pdf->SetY($charge_details_Y);
        $pdf->SetFont('Helvetica', 'B', 8);
        $pdf->SetFillColor(237, 28, 36);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetLineWidth(0.05);
        $pdf->Cell(190, 4, "Voice Call Charges and Transactions for : 0" . $msisdn, TB, 2, L, true);
        $pdf->Ln();
        //$pdf->SetLineWidth(0.1);
        $pdf->SetTextColor(0, 0, 0);
        return $pdf;
    }

    function setSMSHeader($pdf, $msisdn) {
        $charge_details_Y = 40;
        $pdf->SetY($charge_details_Y);
        $pdf->SetFont('Helvetica', 'B', 8);
        $pdf->SetFillColor(237, 28, 36);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetLineWidth(0.05);
        $pdf->Cell(190, 4, "SMS Charges and Transactions for : 0" . $msisdn, TB, 2, L, true);
        $pdf->Ln();
        //$pdf->SetLineWidth(0.1);
        $pdf->SetTextColor(0, 0, 0);
        return $pdf;
    }

    function setVASDetailsHeader($pdf) {
        $h = 4;
        //overall_max_items z`29; //+3
        //$pdf->SetXY($charge_details_X, $charge_details_Y + 7 + 0.5);
        $charge_details_Y += 45;
        $pdf->SetY($charge_details_Y);
        $pdf->SetFont('Helvetica', '', 8);
        $pdf->SetFillColor(209, 210, 212);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(20, 3, 'No', 0, 0, L, true);
        $pdf->Cell(30, 3, 'Mobile', 0, 0, L, true);
        $pdf->Cell(25, 3, 'Transaction Date', 0, 0, L, true);
        $pdf->Cell(35, 3, 'Service Usage Type', 0, 0, L, true);
        $pdf->Cell(30, 3, 'Product Type', 0, 0, L, true);
        $pdf->Cell(20, 3, 'Volume (KB)', 0, 0, L, true);
        $pdf->Cell(30, 3, 'Charged Amount', 0, 1, R, true);
        $pdf->Ln();
        return $pdf;
    }

    function setVoiceDetailsHeader($pdf) {
        $h = 4;
        //overall_max_items z`29; //+3
        //$pdf->SetXY($charge_details_X, $charge_details_Y + 7 + 0.5);
        $charge_details_Y += 45;
        $pdf->SetY($charge_details_Y);
        $pdf->SetFont('Helvetica', '', 8);
        $pdf->SetFillColor(209, 210, 212);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(20, 3, 'No', 0, 0, L, true);
        $pdf->Cell(30, 3, 'Mobile', 0, 0, L, true);
        $pdf->Cell(25, 3, 'Transaction Date', 0, 0, L, true);
        $pdf->Cell(35, 3, 'Called Party Number', 0, 0, L, true);
        $pdf->Cell(30, 3, 'Operator', 0, 0, L, true);
        $pdf->Cell(20, 3, 'Call Duration', 0, 0, L, true);
        $pdf->Cell(30, 3, 'Charge Amount', 0, 1, R, true);
        $pdf->Ln();
        return $pdf;
    }

    function setSMSDetailsHeader($pdf) {
        $h = 4;
        //overall_max_items z`29; //+3
        //$pdf->SetXY($charge_details_X, $charge_details_Y + 7 + 0.5);
        $charge_details_Y += 45;
        $pdf->SetY($charge_details_Y);
        $pdf->SetFont('Helvetica', '', 8);
        $pdf->SetFillColor(209, 210, 212);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(20, 3, 'No', 0, 0, L, true);
        $pdf->Cell(30, 3, 'Mobile', 0, 0, L, true);
        $pdf->Cell(45, 3, 'Transaction Date', 0, 0, L, true);
        $pdf->Cell(35, 3, 'Called Party Number', 0, 0, L, true);
        $pdf->Cell(20, 3, 'Operator', 0, 0, L, true);
        $pdf->Cell(40, 3, 'Charge Amount', 0, 1, R, true);
        $pdf->Ln();
        return $pdf;
    }

    function charge_summary($pdf, $charge_details_total_charges_Y, $service_type, $summary) {
        //echo $charge_details_total_charges_Y;
        $pdf->Ln();
        $pdf->SetY($charge_details_total_charges_Y);
        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->SetFillColor(209, 210, 212);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(190, 3, 'Charge Summary', 0, 0, L, true);
        $pdf->Ln();
        $summ = 0;
        foreach ($summary[$service_type] as $type => $charge) {
            $summ += $charge;
            $pdf->ResetFillColor();
            $pdf->SetFont('Helvetica', '', 5);
            $pdf->Cell(50, 3, $type, 0, 0, L, true);
            $pdf->Cell(20, 3, number_format($charge, 2), 0, 0, R, true);
            $pdf->Ln();
        }
        $pdf->SetFillColor(209, 210, 212);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(50, 3, 'Sub Total', 0, 0, L, true);
        $pdf->Cell(20, 3, number_format($summ, 2), 0, 0, R, true);
        $pdf->Ln();
        return $pdf;
    }

}
