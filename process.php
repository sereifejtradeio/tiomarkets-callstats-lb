<?php

include_once('includes/config/database.config.php');
include_once('includes/classes/database.class.php');

// Initialize the database class, and create an object
$db = new database(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME, 1);

$sales_agents_results = $db->getResults("select * from tableau_crm.Sales_LD_SummaryV4_AgentTracking_PAMM;", 'ASSOC');
$sales_agents_trades = $db->getResults("select * from tableau_crm.Sales_LD_Detailv4_AgentTracking_PAMM;", 'ASSOC');
$sales_agents_team_target = $db->getRow("select target from tableau_crm.activation_team_target where mnth=date_add(date_add(LAST_DAY(now()),interval 1 DAY),interval -1 MONTH);", 'ASSOC');

$agents_json = $sales_agents_results;
$agents_trades_json = $sales_agents_trades;

for($i=0; $i < count($agents_json); $i++) {
    for($j=0; $j < count($agents_trades_json); $j++) {
        if( $agents_json[$i]['Agent'] == $agents_trades_json[$j]['Agent'] ) {
            $agents_json[$i]['Todays_first_trades_list'][] = array(
                'Agent' => $agents_trades_json[$j]['Agent'],
                'MasterAccount' => $agents_trades_json[$j]['MasterAcccount'],
                'Name' => utf8_encode($agents_trades_json[$j]['name']),
                'RegistrationDate' => $agents_trades_json[$j]['RegistrationDate'],
                'FirstTradeDateMaster' => $agents_trades_json[$j]['FirstTradeDateMaster'],
                'Days' => $agents_trades_json[$j]['days'],
                'Points' => $agents_trades_json[$j]['Points'],
                'FTD_Check' => $agents_trades_json[$j]['FTD_Check'],
                'Moved_date' => $agents_trades_json[$j]['Moved_date'],
            );
        }
    }
}

$retention_agent_results = $db->getResults("select * from tableau_crm.Retention_LeaderBoardV5_tracking;", 'ASSOC');
$retention_agent_trades = $db->getResults("select * from tableau_crm.Retention_LeaderBoardV5_Detail_tracking;", 'ASSOC');

$retention_agent_json = $retention_agent_results;
$retention_agent_trades_json = $retention_agent_trades;

for($k=0; $k < count($retention_agent_json); $k++) {
    $agents_json[0]['Retention_agent_stats'][$k] = array(
        'AgentName' => $retention_agent_json[$k]['Agent'],
        'Contribution' => $retention_agent_json[$k]['Contribution'],
        'Deposit_Today' => $retention_agent_json[$k]['Deposit_Today'],
        'Withdrawal_Today' => $retention_agent_json[$k]['Withdrawal_Today'],
        'Deposit_Month' => $retention_agent_json[$k]['Deposit_Month'],
        'Withdrawal_Month' => $retention_agent_json[$k]['Withdrawal_Month'],
        'Net_Deposit_Month_Proteced' => $retention_agent_json[$k]['Net_Deposit_Month_Proteced'],
        'Retention_agent_todays_first_trades_list' => array()
    );
    for($l=0; $l < count($retention_agent_trades_json); $l++) {
        if( $retention_agent_json[$k]['Agent'] == $retention_agent_trades_json[$l]['agent'] ) {
            $agents_json[0]['Retention_agent_stats'][$k]['Retention_agent_todays_first_trades_list'][] = array(
                'Agent' => $retention_agent_trades_json[$l]['agent'],
                'Client_ID' => $retention_agent_trades_json[$l]['Client_ID'],
                'Registration_date' => $retention_agent_trades_json[$l]['Registration_date'],
                'Retention_date' => $retention_agent_trades_json[$l]['Retention_date'],
                'Deposit_Date' => $retention_agent_trades_json[$l]['Deposit_Date'],
                'Amount_USD' => $retention_agent_trades_json[$l]['Amount_USD'],
            );
        }
    }
}

if( $agents_json[0]['Agent'] == 'Grand Total' ) {
    $agents_json[0]['Team_target'] = $sales_agents_team_target['target'];
}
echo json_encode($agents_json);
?>