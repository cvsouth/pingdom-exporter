<?php

namespace Exporters\Pingdom;

use Exception;
use Symfony\Component\Dotenv\Dotenv;

include __DIR__ . '/../vendor/autoload.php';

$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/../.env');

header('Content-Type: text/plain; version=0.0.4');

/**
 * @throws Exception
 */
function pingdomApi($endpoint)
{
    $curl = curl_init('https://api.pingdom.com/api/3.1/' . $endpoint);

    $token = $_ENV['PINGDOM_API_TOKEN'];

    if(empty($token)) {
        throw new Exception('Pingdom API token missing');
    }
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);

    $response = json_decode(curl_exec($curl), true);
    if (isset($response['error'])) {
        throw new Exception($response['error']['errormessage']);
    }
    curl_close($curl);

    return $response;
}
function pingdomChecks()
{
    try {
        $response = pingdomApi('checks');
    } catch(Exception $e) {
        return false;
    }
    $pingdom_check_status = [];
    $pingdom_check_response_time = [];

    foreach($response['checks'] as $check) {
        $host = $check['hostname'];
        $pingdom_check_status[] = 'pingdom_check_status{host="' . $host . '"} ' . ($check['status'] === 'up' ? '1' : '0');
        $pingdom_check_response_time[] = 'pingdom_check_response_time{host="' . $host . '"} ' . $check['lastresponsetime'];
    }
    echo '# TYPE pingdom_check_status gauge' . "\n";
    foreach($pingdom_check_status as $metric) {
        echo $metric . "\n";
    }
    echo '# TYPE pingdom_check_response_time gauge' . "\n";
    foreach($pingdom_check_response_time as $metric) {
        echo $metric . "\n";
    }
    return true;
}
function pingdomTmsChecks()
{
    try {
        $response = pingdomApi('tms/check');
    } catch(Exception $e) {
        return false;
    }
    $pingdom_tms_check_active = [];
    $pingdom_tms_check_status = [];
    $pingdom_tms_check_last_downtime_start = [];
    $pingdom_tms_check_last_downtime_end = [];

    foreach($response['checks'] as $check) {
        $transaction = $check['name'];
        $pingdom_tms_check_active[] = 'pingdom_tms_check_active{transaction="' . $transaction . '"} ' . ($check['active'] ? '1' : '0');
        $pingdom_tms_check_status[] = 'pingdom_tms_check_status{transaction="' . $transaction . '"} ' . ($check['status'] === 'successful' ? '1' : '0');
        $pingdom_tms_check_last_downtime_start[] = 'pingdom_tms_check_last_downtime_start{transaction="' . $transaction . '"} ' . $check['last_downtime_start'];
        $pingdom_tms_check_last_downtime_end[] = 'pingdom_tms_check_last_downtime_end{transaction="' . $transaction . '"} ' . $check['last_downtime_end'];
    }
    echo '# TYPE pingdom_tms_check_active gauge' . "\n";
    foreach($pingdom_tms_check_active as $metric) {
        echo $metric . "\n";
    }
    echo '# TYPE pingdom_tms_check_status gauge' . "\n";
    foreach($pingdom_tms_check_status as $metric) {
        echo $metric . "\n";
    }
    echo '# TYPE pingdom_tms_check_last_downtime_start gauge' . "\n";
    foreach($pingdom_tms_check_last_downtime_start as $metric) {
        echo $metric . "\n";
    }
    echo '# TYPE pingdom_tms_check_last_downtime_end gauge' . "\n";
    foreach($pingdom_tms_check_last_downtime_end as $metric) {
        echo $metric . "\n";
    }

    return true;
}
pingdomChecks();
pingdomTmsChecks();
