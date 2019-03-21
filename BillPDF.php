<?php

/**
 * Description of BillPDF
 *
 * @author gibson
 */
error_reporting(E_PARSE | E_ERROR | E_WARNING);
require_once('libraries/fpdf/fpdf.php');
//include 'fpdf17/font/';
require_once("PDOConnection.php");
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
    
    function billPDF($value,$output_method='I'){
	$pdf = new PDF();
	if(count($value) == 0){
		return "NO MASTER NUMBER PASSED<br>";
	}
        
        $dbh = new PDOConnection();
        
        $select_sql = "SELECT a.created_date,a.master_msisdn,b.account_name,a.resource_type,a.msisdn,a.price
                       FROM hybrid_subs a LEFT OUTER JOIN icontrol_masters b
                       ON a.master_msisdn = b.master_msisdn 
                       WHERE TRUNC(created_date) >= '01-FEB-2017' AND a.ocs_resp_code IN ('405000000','0') 
                       AND a.master_msisdn ='".$value[MASTER_MSISDN]."'";
        
        $items = $dbh->select($select_sql);
	
	//foreach($master_msisdns as $id_key=>$id){
	
		$i = 0;
                
                    
                //}
	
		$pdf->AliasNbPages();
		$pdf->AddPage();
		//$pdf->SetMargins(15,15,15);
		$pdf->SetMargins(10,10,10);
		
		//HEADER
		//$pdf->Image('images/header.png',10,10,190);
		$pdf->SetY(17);	
		$pdf->SetFont('Helvetica','',9);
		$pdf->Cell(90,3,'AIRTEL UGANDA LTD',0,1,L);
		$pdf->SetFont('Helvetica','',9);
		$pdf->Cell(90,3,'VAT No : 1000027779; TIN : 1000027779',0,1,L);
		
		//CLIENT DETAILS
		$pdf->SetY(30);
		$pdf->SetFont('Helvetica','B',9);
		//$pdf->Cell(90,4,strtoupper(remove_account_suffix($invoice->details[Other_details][account_name])),0,1,L);
		$pdf->SetFont('Helvetica','',9);
		//$address = explode("<br>",$invoice->details[Other_details][physical_address]);
		//foreach($address as $row){ $pdf->Cell(90,4,strtoupper($value[ACCOUNT_NAME]),0,1,L); }
                
                $pdf->Cell(90,4,strtoupper($value[ACCOUNT_NAME]),0,1,L);
                $pdf->Cell(90,4,strtoupper($value[MASTER_MSISDN]),0,1,L);
		/*
		//BILL DETAILS
		$pdf->SetFont('Helvetica','',8);
		$bill_detail_left_pad = 131;
		$pdf->SetXY($bill_detail_left_pad,30);
		$bill_details = array(
			array('Account Number',': '.$invoice->details[Other_details][account_number]),
			array('Invoice Number',': '.$invoice->invoice_number),
			array('Service Type',': Broadband - '.$invoice->details[Other_details][service_type]),
			array('Invoice Currency',': '.$invoice->details[Other_details][invoice_currency]),
			array('Invoice Date',': '.date_reformat($invoice->details[Other_details][invoice_date],'')),
			array('Invoice Period',': '.date_reformat($invoice->details["Other_details"]["invoice_start"],'').' to '.date_reformat($invoice->details["Other_details"]["invoice_end"],'')),
			array('Due Date',': '.date_reformat($invoice->details["Other_details"]["invoice_due_date"],''))
		);
		foreach($bill_details as $row){
			foreach($row as $colindex=>$col){
				if($colindex == 0) { $width = 30; }else{ $width = 58; }
				$pdf->Cell($width,3.5,$col,0,0,L);
			}
			$pdf->Ln();
			$pdf->SetX($bill_detail_left_pad);
			unset($colindex,$width);
		}
	*/
		//ACCOUNT SUMMARY
		$w = 190/5; $h = 4;
		$pdf->Ln(); //$pdf->Ln();
		$pdf->SetFont('Times','B',10);
		$pdf->SetFillColor(209,210,212);
		$pdf->Cell(190,$h+1,'Your Account Summary',0,1,C,true);
		
		$pdf->SetFont('Times','U',10);
		$pdf->Cell($w, $h,'CUG Charge',0,0,C,true);
		$pdf->Cell($w, $h,'CMB Charge',0,0,C,true);
		$pdf->Cell($w, $h,'GPRS Charge',0,0,C,true);
		$pdf->Cell($w, $h,'Voice Usage Charge',0,0,C,true);
		$pdf->Cell($w, $h,'Amount Payable',0,1,C,true);
		$pdf->SetFont('Helvetica','B',10);
		$pdf->Cell($w, $h,number_format(1),0,0,C,true);
		$pdf->Cell($w, $h,number_format(2),0,0,C,true);
		$pdf->Cell($w, $h,number_format(3),0,0,C,true);
		$pdf->Cell($w, $h,number_format(4),0,0,C,true);
		$pdf->Cell($w, $h,number_format(5),0,1,C,true);
		
                /*
		//CHARGES SUMMARY
		$break_down = $invoice->details['Break Down'];
		$charge_summary_Y = 72;
		$pdf->SetY($charge_summary_Y);
		$pdf->SetLineWidth(0.05);
		//81
		$w = 65; $w1 = 45.5; $w2 = $w - $w1; $h = 4.5;
		//CHARGE SUMMARY BORDER
		$pdf->Cell($w,153,'',1,1,L);
		
		$charge_summary_Y = 73;
		$pdf->SetY($charge_summary_Y);
		$pdf->SetFont('Helvetica','B',11);
		$pdf->SetFillColor(237,28,36);
		$pdf->SetTextColor(255,255,255);
		$pdf->SetLineWidth(0.1);
		$pdf->Cell($w,7,'This Period\'s Charge Summary',TB,1,L,true);
		$pdf->SetY($charge_summary_Y+8);
		$pdf->SetFont('Helvetica','',9);
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFillColor(255,255,255);
		$pdf->Cell($w1,$h,'One time charges (Taxable)',0,0,L); $pdf->Cell($w2,$h,number_format(-$break_down[Charges],2),0,1,R);
		$pdf->Cell($w1,$h,'Monthly service charges',0,0,L); $pdf->Cell($w2,$h,number_format(-$break_down[Services],2),0,1,R);
		$pdf->Cell($w1,$h,'Prorating Adjustments',0,0,L); $pdf->Cell($w2,$h,number_format(-$break_down[prorate_adjustments_sum],2),0,1,R);
		$pdf->SetFillColor(209,210,212);
		$pdf->SetFont('Helvetica','B',9);
		$pdf->Cell($w1,6,'Subtotal',0,0,L,true); $pdf->Cell($w2,6,number_format(-($break_down[Charges] + $break_down[Services] + $break_down[prorate_adjustments_sum]),2),0,1,R,true);
		$pdf->SetFont('Helvetica','',9);
		$pdf->ResetFillColor();
		$pdf->Cell($w1,$h,'Tax (VAT 18%)',0,0,L); $pdf->Cell($w2,$h,number_format(-($break_down[Charges] + $break_down[Services] + $break_down[prorate_adjustments_sum]) * 0.18,2),0,1,R);
		$pdf->Cell($w1,$h,'One time charges (Non Taxable)',0,0,L); $pdf->Cell($w2,$h,number_format(-$break_down[untaxed_total],2),0,1,R);
		$pdf->Cell($w1,$h,'Adjustments (Non Taxable)',0,0,L); $pdf->Cell($w2,$h,number_format($break_down[notax_adjustments],2),0,1,R);
		$pdf->SetFillColor(209,210,212);
		$pdf->SetFont('Helvetica','B',9);
		$pdf->Cell($w1,$h+1,'Total Charges for Period',0,0,L,true); $pdf->Cell($w2,$h+1,number_format(-((($break_down[Charges] + $break_down[Services] + $break_down[prorate_adjustments_sum]) * 1.18) + $break_down[untaxed_total] + $break_down[notax_adjustments]),2),0,1,R,true);
		$pdf->SetFont('Helvetica','',9);
		$pdf->ResetFillColor();
		$pdf->Image('images/advert.png',10,140,$w);
		
		$pdf->SetLineWidth(0.2);
		*/	
		//CHARGES DETAILS START
                
		$charge_details_X = 78;
		$charge_details_Y = 72;
		$w = 122;
		$pdf->SetXY($charge_details_X,$charge_details_Y);
		$pdf->SetLineWidth(0.05);
		//CHARGES DETAILS BORDER
		$pdf->Cell($w,153,'',1,1,L);
		
		$charge_details_Y += 1;
		$pdf->SetXY($charge_details_X,$charge_details_Y);
		$pdf->SetFont('Helvetica','B',11);
		$pdf->SetFillColor(237,28,36);
		$pdf->SetTextColor(255,255,255);
		$pdf->SetLineWidth(0.1);
		//81
		$pdf->Cell($w,7,'This Period\'s Charge Details',TB,2,L,true);
		//$pdf->SetLineWidth(0.1);
		$pdf->SetTextColor(0,0,0);
		

			$h = 4;
			//overall_max_items z`29; //+3
		

			
			$col1of3 = $w*100/697;
			$col2of3 = $w*517/697;
			$col3of3 = $w*80/697;
			
				$pdf->SetXY($charge_details_X,$charge_details_Y + 7 + 0.5);
				$pdf->SetFont('Helvetica','B',8);
				$pdf->SetFillColor(209,210,212);
				$pdf->SetTextColor(0,0,0);
				$pdf->Cell($w,$h,'Taxable Onetime charges and Monthly services',0,2,L,true);
				$pdf->Cell($col1of3,$h,'Transaction Date',0,0,L,true);
                                $pdf->Cell($col2of3,$h+1,'Mobile Number',0,0,L,true);
                                $pdf->Cell($col3of3,$h+2,'Resource Type',0,2,C,true);
                               foreach ($items as $key => $item) { 
				$pdf->ResetFillColor();
				$pdf->SetFont('Helvetica','',7);
				$item_details_Y = $charge_details_Y + 7 + 0.5 + (2 * ($h));
				$pdf->SetXY($charge_details_X,$item_details_Y);
                                $pdf->Cell($col1of3,$h,$item[CREATED_DATE],0,0,L,true);
                                $pdf->Cell($col2of3,$h,$item[MSISDN],0,0,L,true);
                                $pdf->Cell($col3of3,$h,$item[RESOURCE_TYPE],0,2,R,true);
	
			//TOTAL CHARGES
			$charge_details_total_charges_X = $charge_details_X;
			$charge_details_total_charges_Y = 219;
			$pdf->SetXY($charge_details_total_charges_X,$charge_details_total_charges_Y);
			$pdf->SetFont('Helvetica','B',9);
			$pdf->SetFillColor(209,210,212);
			$pdf->SetTextColor(0,0,0);
			$pdf->Cell($w - 32,$h+1,'Total Charges',0,0,L,true);
			//$pdf->Cell(32,$h+1,number_format(-((($break_down[Charges] + $break_down[Services] + $break_down[prorate_adjustments_sum]) * 1.18) + $break_down[untaxed_total] + $break_down[notax_adjustments]),2),0,0,R,true);
		
		//CHARGES DETAILS END
		/*
		//TEAR OFF HERE
		$pdf->SetLineWidth(0.1);
		$pdf->SetTextColor(0,0,0);
		$pdf->Image('images/tear_here.png',10,225,190);
		$pdf->SetY(227);
		$pdf->SetFont('Helvetica','',8);
		$pdf->Write('5','Please detach this slip & return with payment. Make payments to ');
		$pdf->SetFont('Helvetica','U',8);
		$pdf->SetTextColor(255,0,0);
		$pdf->Write('5','Airtel Uganda Limited');
		$pdf->SetFont('Helvetica','',8);
		$pdf->SetTextColor(0,0,0);
		$pdf->Write('5',' Standard Chartered Bank Uganda Limited ');
		$pdf->SetFont('Helvetica','U',8);
		$pdf->SetTextColor(255,0,0); 
		$pdf->Write('5','Account No '.$pay_to_bank_account['Standard Chartered Bank Uganda Limited'][$invoice->details[Other_details][invoice_currency]]);
		
		$pdf->SetFont('Helvetica','',8);
		$pdf->SetTextColor(0,0,0);
		$pdf->Write('5',' OR  Stanbic Bank Uganda Limited ');
		$pdf->SetFont('Helvetica','U',8);
		$pdf->SetTextColor(255,0,0); 
		$pdf->Write('5','Account No '.$pay_to_bank_account['Stanbic Bank Uganda Limited'][$invoice->details[Other_details][invoice_currency]]);
			
		//BILL SUMMARY
		$bill_summary_Y = 238;
		$bill_summary_left_pad = 30;
		$pdf->SetXY($bill_summary_left_pad,$bill_summary_Y);
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('Helvetica','',8);
		$bill_summary = array(
			array('Account Number :',$invoice->details[Other_details][account_number]),
			array('Bill Number :',$invoice->invoice_number),
			array('Account Balance :',$invoice->details[Other_details][invoice_currency].' '.number_format(-$invoice->details[Other_details][acct_bal],2))
		);
		foreach($bill_summary as $row){
			foreach($row as $colkey=>$col){
				if($colkey == 0) { $align = 'R'; $pdf->SetFont('Helvetica','B',8); }else{ $align = 'L'; $pdf->SetFont('Helvetica','',8); }
				$pdf->Cell(35,3.5,$col,0,0,$align);
			}
			$pdf->Ln();
			$pdf->SetX($bill_summary_left_pad);
			unset($colkey,$width);
		}
		$pdf->SetXY(110,$bill_summary_Y);
		$bill_summary_left_pad = 110;
		$bill_summary = array(
			array('Bill Date :',date_reformat($invoice->details[Other_details][invoice_date],'')),
			array('Amount Payable :',$invoice->details[Other_details][invoice_currency].' '.number_format(-$invoice->amount_payable,2)),
			array('Due Date :', date_reformat($invoice->details[Other_details][invoice_due_date],''))
		);
		foreach($bill_summary as $row){
			foreach($row as $colkey=>$col){
				if($colkey == 0) { $align = 'R'; $pdf->SetFont('Helvetica','B',8); }else{ $align = 'L'; $pdf->SetFont('Helvetica','',8);}
				$pdf->Cell(35,3.5,$col,0,0,$align);
			}
			$pdf->Ln();
			$pdf->SetX($bill_summary_left_pad);
			unset($colkey,$width);
		}
		*/
		//PAYMENT TABLE
		/*
                $payment_table_Y = 250;
		$payment_table_X = 24;
		$w = 31; $h = 3;
		$pdf->SetXY($payment_table_X,$payment_table_Y);
		$pdf->SetFont('Helvetica','B',7);
		$pdf->Cell($w+5,$h,'Payment Mode',1,0,C);
		$pdf->Cell($w,$h,'Amount',1,0,C);
		$pdf->Cell($w,$h,'Date',1,0,C);
		$pdf->Cell($w,$h,'Cheque Number',1,0,C);
		$pdf->Cell($w,$h,'Bank/Branch',1,1,C);
		$pdf->SetX($payment_table_X);
		$pdf->Cell($w+5,$h,'Cheque / DD / Pay Order',1,0,C);
		$pdf->Cell($w,$h,'',1,0,C);
		$pdf->Cell($w,$h,'',1,0,C);
		$pdf->Cell($w,$h,'',1,0,C);
		$pdf->Cell($w,$h,'',1,1,C);
		$pdf->SetX($payment_table_X);
		$pdf->Cell($w+5,$h,'Cash',1,0,C);
		$pdf->Cell($w,$h,'',1,0,C);
		$pdf->Cell($w,$h,'',1,1,C);
		*/
		//FOOTER BANNER
		//$pdf->Image('images/footer.png',10,253,190);
		
		//FOOTER TEXT
		$footer_txt_Y = 268;
		$footer_txt_X = 10;
		$pdf->SetXY($footer_txt_X,$footer_txt_Y);
		$pdf->SetFont('courier','',8);
		$footer_text = 'Airtel Uganda Limited : Forest Mall business centre, Lugogo and Plaza Kampala Road';
		$pdf->Cell(0,4,$footer_text,0,1,C);
		$footer_text = 'P.O.B0x 6771 Kampala : business.support@ug.airtel.com : (256) 700777776 : http://africa.airtel.com/uganda/';
		$pdf->Cell(0,4,$footer_text,0,1,C);
		
		//ACTUAL PAGE FOOTER
		$pdf->SetY(-10);
		$pdf->SetFont('Helvetica','B',7);
		$pdf->Cell(0,4,"Page ".$pdf->PageNo()." of 1",0,0,'R');
                
	}
	
	$pdf->SetAuthor('Airtel Enterprise Billing');
	$pdf->SetCreator('Airtel Icontrol');
	
	if($output_method != 'S'){
		if(count($ids) == 1){
			$file_name = $invoice->details["Other_details"]["invoice_end"]." - ".$invoice->details["Other_details"]["invoice_start"]."_".$invoice->details[Other_details][account_number]." ".strtoupper(remove_account_suffix($invoice->details[Other_details][account_name]));
		}else{
			$file_name = 'testfile.pdf';
		}
	}else{
		$file_name = 'testfile.pdf';
		return $pdf->Output($file_name,$output_method);
	}
	
	//$pdf->Output($file_name.'.pdf',$output_method);
	$pdf->Output($file_name,'F');
    }

}
