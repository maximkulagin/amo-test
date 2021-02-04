<?php

use League\OAuth2\Client\Token\AccessTokenInterface;
use AmoCRM\Client\AmoCRMApiClient;

include_once __DIR__ . '/../../../vendor/autoload.php';
include_once __DIR__ . '/token_actions.php';
include_once __DIR__ . '/HookHandler.php';

$clientId = "90e00d71-1f00-45ef-961c-507a65254830";
$clientSecret = "awZG7SAWwTrVxXTKPmYFkNxgPPmZii51DFGVAxLaoO5qPVbSs5B8OAEdSFvBgCU8";
$redirectUri = "http://maks.cookebooks.ru/src/get_token.php";
$logFile = "log.txt";
$fieldOneId = 1127443;
$fieldTwoId = 1127445;
$fieldOneName = "Поле 1";
$fieldTwoName = "Поле 2";

$apiClient = new AmoCRMApiClient($clientId, $clientSecret, $redirectUri);

$accessToken = getToken();
$apiClient->setAccessToken($accessToken)
    ->setAccountBaseDomain($accessToken->getValues()['baseDomain'])
    ->onAccessTokenRefresh(
        function (AccessTokenInterface $accessToken, string $baseDomain) {
            saveToken(
                [
                    'accessToken' => $accessToken->getToken(),
                    'refreshToken' => $accessToken->getRefreshToken(),
                    'expires' => $accessToken->getExpires(),
                    'baseDomain' => $baseDomain,
                ]
            );
        }
    );

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $session = uniqid();
    saveLog("New POST request", $logFile, $session);
    try {
        $hookHandler = new HookHandler($apiClient, $_POST);
        saveLog("Handled lead is " . $hookHandler->getHandledLead()->getId(), $logFile, $session);
        if ($hookHandler->getHandledLead()) {
            if ($hookHandler->isContainsField($fieldOneId)) {
                if ($hookHandler->isCalculated($fieldOneId, $fieldTwoId)) {
                    saveLog("Fields are calculated. Nothing to do", $logFile, $session);
                } else {
                    $updatedLead = $hookHandler->doubleField($fieldOneId, $fieldTwoId, $fieldTwoName);
                    saveLog("Updated values is " . $updatedLead->getCustomFieldsValues(), $logFile, $session);
                    $hookHandler->sendLead();
                }
            } else {
                saveLog("No fields found", $logFile, $session);
            }
        }
    } catch (Exception $e) {
        saveLog("ERROR: " . $e, $logFile, $session);
    }
}

function saveLog($info, $logFileName, $session)
{
    date_default_timezone_set('Europe/Moscow');
    $date = date('m/d/Y h:i:s a', time());
    $fp = fopen($logFileName, "a+");
    $text = "$date uid: $session " . var_export($info, true) . "\n";
    fwrite($fp, $text);
    fclose($fp);
}
