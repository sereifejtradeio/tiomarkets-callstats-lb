<?php
include_once('/app/includes/config/database.config.php');
include_once('/app/includes/classes/database.class.php');

// Initialize the database class, and create an object
$db = new database(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME, 1);

$agents = array();

$calls_results = $db->getResults("select * from daily_calls_report order by Dials desc;", 'ASSOC');

for($i=0; $i < count($calls_results); $i++) {
    $agents[] = array(
        'Agent' => $calls_results[$i]['Agent'],
        'Dials' => $calls_results[$i]['Dials'],
        'Pickups' => $calls_results[$i]['Pickups'],
        'Effective_Calls_(180s)' => $calls_results[$i]['Effective Calls (180s)'],
        'Effective_Call_Ratio_(D/C)' => $calls_results[$i]['Effective Call Ratio (D/C)'],
        'Average_effective_call_duration_(180_seconds)' => $calls_results[$i]['Average effective call duration (180 seconds)'],
        'Total_EFFECTIVE_call_talk_time' => $calls_results[$i]['Total EFFECTIVE call talk time'],
    );
}
echo json_encode($agents);
?>