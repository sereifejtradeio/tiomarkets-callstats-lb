<?php
include_once('includes/config/database.config.php');
include_once('includes/classes/database.class.php');

// Initialize the database class, and create an object
$db = new database(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME, 1);

$agents = array();

$calls_results = $db->getResults("select * from heroku_00410929162a0f1.Daily_Calls_report order by  Dials desc;", 'ASSOC');

if(!empty($calls_results)) {
    for($i=0; $i < count($calls_results); $i++) {
        $agents[] = array(
            'Agent' => $calls_results[$i]['Agent'],
            'Dials' => $calls_results[$i]['Dials'],
            'Pickups' => $calls_results[$i]['Pickups'],
            'Effective_calls' => $calls_results[$i]['EFF.CALLS'],
            'Effective_Call_Ratio_D_C' => $calls_results[$i]['Effective Call Ratio (D/C)'],
            'Average_effective_call_duration_min' => $calls_results[$i]['AVG. EFF.CALL DURATION(MIN)'],
            'Total_EFFECTIVE_call_talk_time' => $calls_results[$i]['TOTAL EFF. TALK TIME'],
        );
    }
    echo json_encode($agents);
}
?>