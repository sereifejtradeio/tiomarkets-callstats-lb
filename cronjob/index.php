<?php
require 'vendor/autoload.php';

include_once('includes/config/database.config.php');
include_once('includes/classes/database.class.php');

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
        'VisibilityTimeout' => 90
    ));

    $queueSize = $client->getQueueAttributes(array(
        'AttributeNames' => array('ApproximateNumberOfMessages'),
        'QueueUrl' => $queueUrl
    ));

    $db->query("DELETE FROM heroku_00410929162a0f1.sales_call_stats WHERE FROM_UNIXTIME(start_time, '%Y-%m-%d') < DATE_FORMAT(NOW(), '%Y-%m-%d');", 'ASSOC');

    $messages = $result->get('Messages');

    foreach ($messages as $message) {

        $messageBody = json_decode($message['Body'], true);
        $type = $messageBody['type'];
        $uuid = $messageBody['uuid'];
        $agent_name = $messageBody['agent_name'];
        $duration = $messageBody['duration'];
        $team_names = $messageBody['team_names'];
        $talk_time = $messageBody['talk_time'];
        $callcenter_uuid = $messageBody['callcenter_uuid'];
        $agent_extension = $messageBody['agent_extension'];
        $disposition = $messageBody['disposition'];
        $hangup_reason = $messageBody['hangup_reason'];
        $start_time = $messageBody['start_time'];
        $end_time = $messageBody['end_time'];
        $dnis = $messageBody['dnis'];
        $ani = $messageBody['ani'];

        $deleteParams = [
            'QueueUrl' => $queueUrl,
            'ReceiptHandle' => $message['ReceiptHandle']
        ];

        //if( date("Y-m-d", $start_time) >= date("Y-m-d") ) {
            $call_exists = $db->getRow("SELECT * FROM sales_call_stats WHERE type = '$type' AND uuid = '$uuid' AND agent_name = '$agent_name' AND duration = '$duration' AND team_names = '$team_names' AND talk_time = '$talk_time' AND callcenter_uuid = '$callcenter_uuid' AND agent_extension = '$agent_extension' AND disposition = '$disposition' AND hangup_reason = '$hangup_reason' AND start_time = '$start_time' AND end_time = '$end_time' AND dnis = '$dnis' AND ani = '$ani'", 'ASSOC');

            if(empty($call_exists)) {
                $db->query("INSERT INTO sales_call_stats (type, uuid, agent_name, duration, team_names, talk_time, callcenter_uuid, agent_extension, disposition, hangup_reason, start_time, end_time, dnis, ani) VALUES ('$type','$uuid','$agent_name','$duration','$team_names','$talk_time','$callcenter_uuid','$agent_extension','$disposition','$hangup_reason','$start_time','$end_time','$dnis', '$ani')", 'ASSOC');
                $deleteResult = $client->deleteMessage($deleteParams);
            }
        //}

    }

} catch (AwsException $e) {
    // output error message if fails
    error_log($e->getMessage());
}

?>