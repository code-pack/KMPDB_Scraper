<?php
session_start();

ini_set('max_execution_time', 0); // to get unlimited php script execution time

$doctors = Array();

function scrapeData(){
	require_once('simple_html_dom.php');
	include_once("doctor.php");

	$first_page = 1;
	$last_page = 288;
	$docs = array();
	$file = 'doctors.txt';
	$count = 0;

	for($i=$first_page; $i<=$last_page; $i++){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'http://medicalboard.co.ke/online-services/retention/?currpage='.$i);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$response = curl_exec($ch);
		curl_close($ch);

		$html = new simple_html_dom();
		$html->load($response);

		foreach($html->find('div#main table tbody tr') as $tablerow){
			$doc = array();	
			foreach($tablerow->find('td') as $data){
				if(trim($data->plaintext) != "View/Print"){
					$doc[] = trim($data->plaintext);
				}
			}
			if(!empty($doc)){
				$docs[] = $doc;
			}		
			reset($doc);
		}
		
		$percent = intval($count/($last_page - $first_page) * 100)."%";   
	
		//sleep(1); // Here call your time taking function like sending bulk sms etc.
		
        /*
		echo '<script>
		parent.document.getElementById("progressbar").innerHTML="<div style=\"width:'.$percent.';background:linear-gradient(to bottom, rgba(125,126,125,1) 0%,rgba(14,14,14,1) 100%); ;height:35px;\">&nbsp;</div>";
		parent.document.getElementById("information").innerHTML="<div style=\"text-align:center; font-weight:bold\">page '.$first_page.' through to '.$last_page.'<br>'.$percent.' is processed.</div>";</script>';
        */

		//ob_flush(); 
		//flush(); 
		
		$count++;

	}


	for($k=0; $k<count($docs); $k++){
		$name = $docs[$k][0];
		$regDate = $docs[$k][1];
		$regID = $docs[$k][2];
		$address = $docs[$k][3];
		$qualifications = $docs[$k][4];
		$specialty = $docs[$k][5];
		$subSpecialty = $docs[$k][6];
		$doctor = new Doctor($name, $regDate, $regID, $address, $qualifications, $specialty, $subSpecialty);

		//echo ($k+1)." - ".$doctor->name." - ".$doctor->regID." - ".$doctor->regDate."<br>";
        
        /*

		$current = file_get_contents($file);
		$current .= ($k+1)." - ".$doctor->name." (".$doctor->regID.")\n";
		file_put_contents($file, $current);
        */
        $dArray = Array();
        $dArray["#"] = ($k+1);
        $dArray["Names"] = $doctor->name;
        $dArray["Registration ID"] = $doctor->regID;
        $dArray["Registration Date"] = $doctor->regDate;
        $dArray["Address"] = $doctor->address;
        $dArray["Qualifications"] = $doctor->qualifications;
        $dArray["Specialty"] = $doctor->specialty;
        $dArray["Sub Specialty"] = $doctor->subSpecialty;
        
        $doctors[] = $dArray;
	}
	
	/*
    echo '<script>parent.document.getElementById("information").innerHTML="<div style=\"text-align:center; font-weight:bold\">Process completed</div>"</script>';
    */
    
    @ob_start();
	session_start();
    $filename = "KMPDB_Doctors_Retention_List_".date("Y-m-d-Gis").".xls";     
    //$filename = "KMPDB_Doctors_Retention_List.xls";     
    ob_end_clean();  
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    ExportFile($doctors);
}


function ExportFile($records) {
    $heading = false;
    if(!empty($records)){
        foreach($records as $row) {
            if(!$heading) {
                // display field/column names as a first row
                echo implode("\t", array_keys($row)) . "\n";
                $heading = true;
            }
            $row = preg_replace("/\t/", "\\t", $row);
            $row = preg_replace("/\r?\n/", "\\n", $row);	

            echo implode("\t", array_values($row)) . "\n";
        }
        exit;
    }
}

scrapeData();
session_destroy(); 