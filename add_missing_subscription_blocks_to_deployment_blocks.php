<?php

ini_set('auto_detect_line_endings', TRUE);
ini_set('memory_limit', '2048M');
require_once(__DIR__ . '/../commonDirLocation.php');
require_once(COMMON_PHP_DIR . '/csv.php');
require_once(__DIR__ . '/get_last_semiannum_end_date_str.php');

$report_end_date = $report_end_date = get_last_semiannum_end_date_str();

$cpuc_deployment_data_csv_temp = 'cpuc_deployment_temp.csv';
$cpuc_deployment_new = 'CPUC_deployment_including_subscriptions_' . $report_end_date . '.csv';
$fcc_deployment_data_csv_temp = 'fcc_deployment_temp.csv';
$fcc_deployment_new = 'FCC_deployment_including_subscriptions_' . $report_end_date . '.csv';


// add any GeoIDs where customers are, according to subscription data
  $cpuc_subscription_data_csv_dir = realpath(__DIR__ . '/../../../pythoncode/scheduled/CPUC/');
  $fcc_subscription_data_csv_dir = realpath(__DIR__ . '/../../../pythoncode/scheduled/FCC/');
  //print_r("\n$cpuc_subscription_data_csv_dir\n");
  //print_r("\n$fcc_subscription_data_csv_dir\n");
  $cpuc_subscription_data_csv_path = $cpuc_subscription_data_csv_dir . '/Subscription_' . $report_end_date . '.csv';
  $fcc_subscription_data_csv_path = $fcc_subscription_data_csv_dir . '/Subscription_Blocks_' . $report_end_date . '.csv';
  print_r($cpuc_subscription_data_csv_path);
  print_r($fcc_subscription_data_csv_path);
  sleep(1);
  $cpuc_subscription_data_arr = csv_to_array($cpuc_subscription_data_csv_path,',');
  $fcc_subscription_data_arr = csv_to_array($fcc_subscription_data_csv_path,',');
  print_r(array_slice($cpuc_subscription_data_arr, 1, 10));
  sleep(1);
  print_r(array_slice($fcc_subscription_data_arr, 1, 10));
  sleep(1);
  $cpuc_deployment_data_csv = 'CPUC_deployment_' . $report_end_date . '.csv';
  $fcc_deployment_data_csv = 'FCC_deployment_' . $report_end_date . '.csv';
  $cpuc_deployment_header_string = 'DBA Name,FRN,Block Code,TechCode,ConsumerFlag,MaxAdDn,MaxAdUp,BusinessFlag,CIRdn,CIRup' . "\n";
  $fcc_deployment_header_string = 'Block Code,DBA,TechCode,ConsumerFlag,MaxAdDn,MaxAdUp,BusinessFlag' . "\n";
  $cpuc_deployment_file_contents = file_get_contents($cpuc_deployment_data_csv);
  $fcc_deployment_file_contents = file_get_contents($fcc_deployment_data_csv);
  $cpuc_deployment_file_contents_w_header = $cpuc_deployment_header_string.$cpuc_deployment_file_contents;
  $fcc_deployment_file_contents_w_header = $fcc_deployment_header_string.$fcc_deployment_file_contents;
  file_put_contents($cpuc_deployment_data_csv_temp, $cpuc_deployment_file_contents_w_header);
  file_put_contents($fcc_deployment_data_csv_temp, $fcc_deployment_file_contents_w_header);
  $cpuc_deployment_data_arr = csv_to_array($cpuc_deployment_data_csv_temp,',');
  $fcc_deployment_data_arr = csv_to_array($fcc_deployment_data_csv_temp,',');
  print_r(array_slice($cpuc_deployment_data_arr, 1, 10));
  sleep(1);
  print_r(array_slice($fcc_deployment_data_arr, 1, 10));
  sleep(1);

  $dba = 'unWired Broadband Inc';
  $frn = '0009957838';
  $technology = '70';
  $consumerFlag = '1';
  $maxAdDn = '30';
  $maxAdUp = '7.5';
  $enterpriseFlag = '1';
  $CIRdn = '30';
  $CIRup = '15';
  $cpuc_deployment_block_codes = array_column($cpuc_deployment_data_arr,'Block Code');
  $fcc_deployment_block_codes = array_column($fcc_deployment_data_arr,'Block Code');
  //print_r($deployment_block_codes);
  foreach ($cpuc_subscription_data_arr as $record) {
    //print_r("\n" . $record['block']);
    if (!in_array($record['Block Code'], $cpuc_deployment_block_codes, TRUE )) {
      
      $row = "$dba,$frn," . $record['Block Code'] . ",$technology,$consumerFlag,$maxAdDn,$maxAdUp,$enterpriseFlag,$CIRdn,$CIRup" . "\n";
      file_put_contents($cpuc_deployment_data_csv_temp,$row,FILE_APPEND);
      //print_r($row);
      $cpuc_deployment_file_contents_w_header = $cpuc_deployment_file_contents_w_header . $row;
    }
  }
  foreach ($fcc_subscription_data_arr as $record) {
    //print_r("\n" . $record['block']);
    if (!in_array($record['Block Code'], $fcc_deployment_block_codes, TRUE )) {
      
      $row = $record['Block Code'] . ",$dba,$technology,$consumerFlag,$maxAdDn,$maxAdUp,$enterpriseFlag" . "\n";
      file_put_contents($fcc_deployment_data_csv_temp,$row,FILE_APPEND);
      //print_r($row);
      $fcc_deployment_file_contents_w_header = $fcc_deployment_file_contents_w_header . $row;
    }
  }
  print_r(substr($cpuc_deployment_file_contents_w_header, -1000));
  sleep(10);
  print_r(substr($cpuc_deployment_file_contents_w_header, 1000));
  sleep(10);
  print_r(substr($fcc_deployment_file_contents_w_header, -1000));
  sleep(10);
  print_r(substr($fcc_deployment_file_contents_w_header, 1000));
  file_put_contents($cpuc_deployment_new, $cpuc_deployment_file_contents_w_header);
  file_put_contents($fcc_deployment_new, $fcc_deployment_file_contents_w_header);
