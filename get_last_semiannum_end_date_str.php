<?php
function get_last_semiannum_end_date_str() {
	$t = date("Y-m-d H:i:s");
//    print_r("\nt:" );
//    print_r($t);
	$curr_year = date("Y");
//    print_r("\ncurr_year:" );
//    print_r($curr_year);
	$jun30_curr_year = strtotime($curr_year . '-06-30');
//    print_r("\njun30_curr_year:" );
//    print_r($jun30_curr_year);
//    print_r("\n" );
	if (strtotime($t) <= $jun30_curr_year) {
	  return ($curr_year - 1) . '-12-31';
	} else {
	  return $curr_year . '-06-30';
	}
}

