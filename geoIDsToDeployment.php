<?php

ini_set('auto_detect_line_endings', TRUE);
ini_set('memory_limit', '2048M');
require(__DIR__ . '/../../commonDirLocation.php'); // COMMON_PHP_DIR
require(COMMON_PHP_DIR . '/phpgeo/autoloader.php');


function csv_to_array($filename='', $delimiter=',')
{
    if(!file_exists($filename) || !is_readable($filename))
        return FALSE;

    $header = NULL;
    $data = array();
    if (($handle = fopen($filename, 'r')) !== FALSE)
    {
        while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE)
        {
            if(!$header)
                $header = $row;
            else
                $data[] = array_combine($header, $row);
        }
        fclose($handle);
    }
    return $data;
}



function list_file_to_array($filename) {
// Open the file
  $fp = @fopen($filename, 'r'); 

// Add each line to an array
  if ($fp) {
    $array = explode("\n", fread($fp, filesize($filename)));
  }
  return $array;
}

function removeM($file) {
  $fixed_file = str_ireplace("\x0D", "", $file);
  return $fixed_file;
}

  $report_end_date = '12312018';
  $CPUCdeploymentCSV = "CPUC_Deployment_" . $report_end_date . ".csv"; //the end product
  $FCCdeploymentCSV = "FCC_Deployment_" . $report_end_date . ".csv";

  file_put_contents(__DIR__ . '/' . $CPUCdeploymentCSV, print_r('', true));
  file_put_contents(__DIR__ . '/' . $FCCdeploymentCSV, print_r('', true));
  $dba = 'unWired Broadband, Inc.';
  $frn = '0009957838';
  $technology = '70';
  $consumerClass = '1';
  $maxCsmrDownMbps = '100';
  $maxCsmrUpMbps = '100';
  $enterpriseClass = '1';
  $maxContractDownMbps = '100';
  $maxContractUpMbps = '100';

  $CPUCheaderString = 'DBA Name,FRN,Block Code,TechCode,ConsumerFlag,MaxAdDn,MaxAdUp,BusinessFlag,CIRdn,CIRup' . "\n";
  $geoIDfile = __DIR__ . "/Deployment_edited__123118.csv";

  $geoIDArray = list_file_to_array($geoIDfile);


//array_slice: array_slice ( array $array , int $offset [, int $length = NULL [, bool $preserve_keys = FALSE ]] )
  print_r("\ngeoIDArray: ");
  print_r(array_slice($geoIDArray, 1, 10));
//  print_r($geoIDfile);

//  sort results
  sort($geoIDArray,SORT_NUMERIC);
  print_r("\nsortedReachableGeoIDs: ");
  print_r(array_slice($geoIDArray, 1, 10));

  $uniqueSortedReachableGeoIDs = $geoIDArray;
  array_shift($uniqueSortedReachableGeoIDs);
//  final product CPUC
  file_put_contents(__DIR__ . '/' . $CPUCdeploymentCSV, print_r($CPUCheaderString, true), FILE_APPEND);
  foreach ($uniqueSortedReachableGeoIDs as $geoID) {
    $geoID = str_ireplace("\x0D", "", $geoID);
    if(!empty($geoID)) {
      $line = "$dba,$frn,$geoID,$technology,$consumerClass,$maxCsmrDownMbps,$maxCsmrUpMbps,$enterpriseClass,$maxContractDownMbps,$maxContractUpMbps\n";
      file_put_contents(__DIR__ . '/' . $CPUCdeploymentCSV, print_r($line, true), FILE_APPEND);
    }
  }
  foreach ($uniqueSortedReachableGeoIDs as $geoID) {
    $geoID = str_ireplace("\x0D", "", $geoID);
    if(!empty($geoID)) {
      $line = "$geoID,$dba,$technology,$consumerClass,$maxCsmrDownMbps,$maxCsmrUpMbps,$enterpriseClass,$maxContractDownMbps,$maxContractUpMbps\n";
      file_put_contents(__DIR__ . '/' . $FCCdeploymentCSV, print_r($line, true), FILE_APPEND);
    }
  }
    $ct = count($uniqueSortedReachableGeoIDs);
    print_r("Count: ");
    print_r($ct);
  
