<?php
ini_set('auto_detect_line_endings', TRUE);
ini_set('memory_limit', '2048M');
require(__DIR__ . '/../commonDirLocation.php'); // COMMON_PHP_DIR 
require(__DIR__ . '/get_last_semiannum_end_date_str.php'); // COMMON_PHP_DIR 
require_once (COMMON_PHP_DIR . '/toolkit/soapclient/SforceEnterpriseClient.php');
require(COMMON_PHP_DIR . '/phpgeo/autoloader.php');
require(COMMON_PHP_DIR . '/SlackMessagePost.php');
require(COMMON_PHP_DIR . '/production.userAuth.php');
require(COMMON_PHP_DIR . '/creds.php');
require_once(COMMON_PHP_DIR . '/csv.php');

use Location\Coordinate;
use Location\Distance\Vincenty;






//////////////////////////      FUNCTIONS       /////////////////////////////


/*
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
*/


function append($record){  
//function used to make a record of query results
  file_put_contents(__DIR__ . '/sfreport/SFREPORT', print_r($record, true), FILE_APPEND);
}


function sfVLookup($api_response,$srch_val,$lkp_col,$ret_col){
// returns first value in $ret_col where value of $srch_val matches the value of $lkp_col
  $result = '';
  foreach($api_response->records as $record) {
    if ($record->$lkp_col == $srch_val){
      $result = $record->$ret_col;
      return $result;
    }
  }
}

function arrVLookup($arr,$srch_val,$lkp_col,$ret_col){
// returns first value in $ret_col where value of $srch_val matches the value of $lkp_col
  $result = '';
  foreach($arr as $record) {
    if (isset($record->$lkp_col) and ($record->$lkp_col == $srch_val)){
      $result = $record->$ret_col;
      return $result;
    }
  }
}


function sfVLookupAll($api_response,$srch_val,$lkp_col,$ret_col){
// returns array of all values in $ret_col where value of $srch_val matches the value of $lkp_col
  $result = [];
  foreach($api_response->records as $record) {
//  if (preg_match("+$srch_val+", $record->$lkp_col)){  // optional for wildcard search
    if ($srch_val == $record->$lkp_col) {
      $result[] = $record->$ret_col;
    }
  }
  return $result;
}


//////////////////////////       EXEC CODE      //////////////////////////////


try {
  //$today = date("Y-m-d");
  $report_end_date = get_last_semiannum_end_date_str();
  print_r($report_end_date);
  $CPUCdeploymentCSV = "CPUC_deployment_" . $report_end_date . ".csv"; //the end product
  $FCCdeploymentCSV = "FCC_deployment_" . $report_end_date . ".csv";

  $allCaBlocksCSV = "allCaBlocks.csv";
  $allCaCountiesCSV = "allCaCounties.csv";
  $reachable_json_filename = "reachable_blocks_" . $report_end_date . ".json";
  $maxDistanceFromTower = 19312.1;

//get Tower report from Salesforce using the correct report end date. 


///// BUILD QUERY ///////

  if (!file_exists($reachable_json_filename)) {
    $obj = 'Tower_Site__c'; // salesforce object to query
    $get_fields = 'Site_Code__c,Name,GPS_Coordinates__Latitude__s,GPS_Coordinates__Longitude__s'; // comma-delimited string: fields we want to query (salesforce)
    //$where_clause_elements = 'Start_Date__c >= 2019-07-01 AND Start_Date__c <= 2019-12-31';
    $where_clause_elements = 'Start_Date__c <= ' . $report_end_date;
    if (!empty($where_clause_elements)){
      $where_clause = ' WHERE ' . $where_clause_elements;
    } else {
      $where_clause = '';
    }
    $query_return_limit = '20000'; //total number of devices to work on ("LIMIT" in soql query)
    $query = "SELECT $get_fields FROM $obj$where_clause LIMIT $query_return_limit"; //soql query
  
  ///// MAKE CONNECTION AND EXECUTE QUERY /////
    $mySforceConnection = new SforceEnterpriseClient();
    $mySoapClient = $mySforceConnection->createConnection(COMMON_PHP_DIR . '/wsdl/production.enterprise.wsdl.xml');
    $mylogin = $mySforceConnection->login(SF_USER, SF_PW);
  
    $options = new QueryOptions(2000);  //Set query to return results in chunks
    $mySforceConnection->setQueryOptions($options);
  
    $done = false;
    $response = $mySforceConnection->query(($query));
    echo "Size of records:  " . $response->size."\n";
  
  
    $record_arr=array();
    if ($response->size > 0) {
      while (!$done) {
        foreach ($response->records as $record) {
          $record_arr[] = $record;
        } 
        if ($response->done == true) {
          $done = true;
        } else {
  //      echo "***** Get Next Chunk *****\n";
          $response = $mySforceConnection->queryMore($response->queryLocator);
  //      append($response); //this logs the query response into a file
  //  print_r($mySforceConnection->getLastRequest());
        }  
      }
    }
  
                                                                                                  print_r($record_arr);
  //                                                                                              print_r("\nOutput record count: " . count($record_arr) . "\n\n");
  
  
  
  /*
  $twrLat = arrVLookup($record_arr,'mdwy','Site_Code__c','GPS_Coordinates__Latitude__s');
  print_r("\n\n");
  print_r($twrLat);
  */
  
  
  
  
  //print_r($allCaBlocksArray);
    $allCaCountiesArray = csv_to_array($allCaCountiesCSV,',');
  //  print_r($allCaCountiesArray);
  
  //create covered counties arrays (Names and codes)
    $coveredCountiesArray = [ 'Amador County','Calaveras County','Fresno County',
                              'Imperial County','Inyo County','Kern County',
                              'Kings County','Madera County','Mariposa County',
                              'Merced County','Sacramento County','San Joaquin County',
                              'Stanislaus County','Tulare County','Tuolumne County' ]; 
    $coveredCountyCodesArray = [];
    foreach ($allCaCountiesArray as $c) {
      if (in_array($c['CtyName'], $coveredCountiesArray)){
        $coveredCountyCodesArray[] = $c['StCode'] . $c['CtyCode'];
      }
    }  
    print_r($coveredCountyCodesArray);
    unset($allCaCountiesArray);
  
  
  //convert allCaBlocksCSV and allCaCountiesCSV to arrays
    $allCaBlocksArray = csv_to_array($allCaBlocksCSV,',');
  
    $coveredCountyBlocksArray = [];
    
    foreach ($allCaBlocksArray as $b) {
      $stateCountyCode = substr($b['GEOID10'], 0, 5);
  //print_r($stateCountyCode . "\n");
      if (in_array( $stateCountyCode,$coveredCountyCodesArray )) {
        $coveredCountyBlocksArray[] = $b; 
      }
    }
    print_r("\nCovered County Blocks Count: ");
    print_r(count($coveredCountyBlocksArray));
  
    unset($allCaBlocksArray);
    unset($coveredCountyCodesArray);
  
  
    $reachableGeoIDs = [];
  
    $tower_arr = $record_arr;
  //$ct = 0;
    foreach ($tower_arr as $twr) {
      $twrLat = '';
      $twrLon = '';
      if ((isset($twr->GPS_Coordinates__Latitude__s)) and (isset($twr->GPS_Coordinates__Longitude__s))) {
        $twrLat = (float) $twr->GPS_Coordinates__Latitude__s;
        $twrLon = (float) $twr->GPS_Coordinates__Longitude__s;
      }
  //    print_r($twrLat . ', ' . $twrLon . "\n");
      
      foreach ($coveredCountyBlocksArray as $blk) {
        $geoID = $blk['GEOID10'];
        $blkLat = '';
        $blkLon = '';
        if (isset($blk['INTPTLAT10']) and isset($blk['INTPTLON10'])) {
          $blkLat = (float) $blk['INTPTLAT10'];    
          $blkLon = (float) $blk['INTPTLON10'];
          if (!(empty($twrLat) or empty($twrLon) or empty($blkLat) or empty($blkLon))) { 
            $coordinate1 = new Coordinate($twrLat, $twrLon); //tower location
            $coordinate2 = new Coordinate($blkLat, $blkLon); //block location
  
            $calculator = new Vincenty();
            $distance = $calculator->getDistance($coordinate1, $coordinate2); // returns 128130.850 (meters; 128 kilometers)
            if ($distance < $maxDistanceFromTower) {
              if(!in_array($geoID,$reachableGeoIDs)) {
                $reachableGeoIDs[] = $geoID;
  //              $ct++;
              }
            }
          }
        }
      }
    }
    unset($tower_arr); 
    unset($coveredCountyBlocksArray);
  //array_slice: array_slice ( array $array , int $offset [, int $length = NULL [, bool $preserve_keys = FALSE ]] )
    //print_r("\nreachableGeoIDs: ");
    //print_r(array_slice($reachableGeoIDs, 1, 10));
    //print_r("\ncount_reachableGeoIDs: ");
    //print_r(count($reachableGeoIDs));
  
  //  sort results
    sort($reachableGeoIDs,SORT_NUMERIC);
    print_r("\nsortedReachableGeoIDs: ");
    print_r(array_slice($reachableGeoIDs, 1, 10));
  //  remove duplicates
    $uniqueSortedReachableGeoIDs = array_unique($reachableGeoIDs);
    unset($reachableGeoIDs);  
  
  // save results in json format
    $reachable_json = json_encode($uniqueSortedReachableGeoIDs);
    file_put_contents($reachable_json_filename, $reachable_json);
  } else {     // If a json file was found for this time period, use it.
    $loaded_json = file_get_contents($reachable_json_filename);
    $uniqueSortedReachableGeoIDs = json_decode($loaded_json);
  }


//  final product CPUC
  file_put_contents(__DIR__ . '/' . $CPUCdeploymentCSV, print_r('', true));
  file_put_contents(__DIR__ . '/' . $FCCdeploymentCSV, print_r('', true));
  //static variables 
    $dba = 'unWired Broadband Inc';
    $frn = '0009957838';
    $technology = '70';
    $consumerFlag = '1';
    $maxAdDn = '30';
    $maxAdUp = '7.5';
    $enterpriseFlag = '1';
    $maxCIRDn = '30';
    $maxCIRUp = '15';  

    // header for CSV (FCC does not want a header string)
    $CPUCheaderString = 'DBA Name,FRN,Block Code,TechCode,ConsumerFlag,MaxAdDn,MaxAdUp,BusinessFlag,CIRdn,CIRup' . "\n";
    file_put_contents(__DIR__ . '/' . $CPUCdeploymentCSV, print_r($CPUCheaderString, true), FILE_APPEND);

    // write files
  foreach ($uniqueSortedReachableGeoIDs as $geoID) {
    $line = "$dba,$frn,$geoID,$technology,$consumerFlag,$maxAdDn,$maxAdUp,$enterpriseFlag,$maxCIRDn,$maxCIRUp\n";
    file_put_contents(__DIR__ . '/' . $CPUCdeploymentCSV, print_r($line, true), FILE_APPEND);
  }
  foreach ($uniqueSortedReachableGeoIDs as $geoID) {
    $line = "$geoID,$dba,$technology,$consumerFlag,$maxAdDn,$maxAdUp,$enterpriseFlag\n";
    file_put_contents(__DIR__ . '/' . $FCCdeploymentCSV, print_r($line, true), FILE_APPEND);
  }
  $ct = count($uniqueSortedReachableGeoIDs);
  print_r("\nCount of geo IDs before addition of subscriber blocks: $ct ");

  //add missing subscription blocks to deployment blocks
  $command = '/usr/local/bin/php add_missing_subscription_blocks_to_deployment_blocks.php';
  exec($command);
  
  $ct = count(file($CPUCdeploymentCSV));
  print_r("\nCount of lines in CPUC file after addition of subscriber blocks: $ct");
  $ct = count(file($FCCdeploymentCSV));
  print_r("\nCount of lines in FCC file after addition of subscriber blocks: $ct");
  
} catch (Exception $e) {
  echo $e->faultstring;
}
