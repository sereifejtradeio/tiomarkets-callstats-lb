<?php
require 'vendor/autoload.php';

include_once('/app/includes/config/database.config.php');
include_once('/app/includes/classes/database.class.php');

// Initialize the database class, and create an object
$db = new database(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME, 1);

use Aws\Sqs\SqsClient;
use Aws\Exception\AwsException;

$queueUrl = "https://sqs.eu-central-1.amazonaws.com/707765823363/voiso";

$client = new SqsClient([
    'version'     => 'latest',
    'region'      => 'eu-central-1',
    'credentials' => [
        'key'    => 'AKIA2JSRBM6BUJYXEM4T',
        'secret' => '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',
    ],
]);

$agents = array();
$agents_formatted = array();

try {
    $result = $client->receiveMessage(array(
        'AttributeNames' => ['SentTimestamp'],
        'MaxNumberOfMessages' => 10,
        'MessageAttributeNames' => ['All'],
        'QueueUrl' => $queueUrl, // REQUIRED
        'WaitTimeSeconds' => 20,
    ));

    $queueSize = $client->getQueueAttributes(array(
        'AttributeNames' => array('ApproximateNumberOfMessages'),
        'QueueUrl' => $queueUrl
    ));

    $db->query("DELETE FROM sales_call_stats;", 'ASSOC');

    while ($queueSize['Attributes']['ApproximateNumberOfMessages'] > 0) {

        $messages = $result->get('Messages');

        for ($j = 0; $j < count($messages); $j++) {

            $entries = [];
            foreach ($messages as $key => $value) {
                $entries[] = ["Id" => $value['MessageId'], "ReceiptHandle" => $value['ReceiptHandle']];
            }

            $deletedResults = $client->deleteMessageBatch(["QueueUrl" => $queueUrl, "Entries" => $entries]);

            if ($deletedResults['Successful']) {
                foreach ($deletedResults['Successful'] as $success) {
                    $success_msg = sprintf("Deleting message succeeded id = %s ", $success['Id']);
                    echo $success_msg . '<br/>';
                }
            }

            if ($deletedResults['Failed']) {
                foreach ($deletedResults['Failed'] as $failed) {
                    $myfile = fopen("not_deleted_messages.txt", "w");
                    $txt = sprintf("Deleting message failed, code = %s, id = %s, msg = %s, senderfault = %s", $failed['Code'], $failed['Id'], $failed['Message'], $failed['SenderFault']);
                    fwrite($myfile, $txt);
                    fclose($myfile);

                }
                throw new \RuntimeException("Cannot delete some messages, consult log for more info!");
            }

            $agents[$j] = json_decode($messages[$j]['Body'], true);

            $type = $agents[$j]['type'];
            $uuid = $agents[$j]['uuid'];
            $agent_name = $agents[$j]['agent_name'];
            $duration = $agents[$j]['duration'];
            $team_names = $agents[$j]['team_names'];
            $talk_time = $agents[$j]['talk_time'];
            $callcenter_uuid = $agents[$j]['callcenter_uuid'];
            $agent_extension = $agents[$j]['agent_extension'];
            $disposition = $agents[$j]['disposition'];
            $hangup_reason = $agents[$j]['hangup_reason'];
            $start_time = $agents[$j]['start_time'];
            $end_time = $agents[$j]['end_time'];
            $dnis = $agents[$j]['dnis'];
            $ani = $agents[$j]['ani'];

            if( date("Y-m-d", $agents[$j]['start_time']) >= date("Y-m-d") ) {
                $call_exists = $db->getRow("SELECT * FROM sales_call_stats WHERE type = '$type' AND uuid = '$uuid' AND agent_name = '$agent_name' AND duration = '$duration' AND team_names = '$team_names' AND talk_time = '$talk_time' AND callcenter_uuid = '$callcenter_uuid' AND agent_extension = '$agent_extension' AND disposition = '$disposition' AND hangup_reason = '$hangup_reason' AND start_time = '$start_time' AND end_time = '$end_time' AND dnis = '$dnis' AND ani = '$ani'", 'ASSOC');
                
                if(empty($call_exists)) {
                    $db->query("INSERT INTO sales_call_stats (type, uuid, agent_name, duration, team_names, talk_time, callcenter_uuid, agent_extension, disposition, hangup_reason, start_time, end_time, dnis, ani) VALUES ('$type','$uuid','$agent_name','$duration','$team_names','$talk_time','$callcenter_uuid','$agent_extension','$disposition','$hangup_reason','$start_time','$end_time','$dnis', '$ani')", 'ASSOC');
                }
            }

//            if (!array_key_exists($agents[$j]['agent_name'], $agents_formatted) && $agents[$j]['agent_name'] != '') {
//                $agents_formatted[$agents[$j]['agent_name']][] = json_decode($messages[$j]['Body'], true);
//            } else {
//                if ($agents[$j]['agent_name'] != '') {
//                    $agents_formatted[$agents[$j]['agent_name']][$j + 1] = json_decode($messages[$j]['Body'], true);
//                }
//            }
        }

    }

} catch (AwsException $e) {
    // output error message if fails
    error_log($e->getMessage());
}

?>