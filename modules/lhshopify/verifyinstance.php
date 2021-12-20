<?php

erLhcoreClassRestAPIHandler::setHeaders();
erTranslationClassLhTranslation::$htmlEscape = false;

$requestPayload = json_decode(file_get_contents('php://input'),true);

try {

    $ext = erLhcoreClassModule::getExtensionInstance('erLhcoreClassExtensionPluginshopify');

    if ($ext->settings['automated_hosting'] != true) {
        throw new Exception('Available only in automated hosting environment!');
    }

    if (!isset($requestPayload['ts']) || !is_numeric($requestPayload['ts'])){
        throw new Exception('Invalid arguments `ts` format!');
    }

    if (!isset($requestPayload['verify_token'])){
        throw new Exception('Missing `verify_token` argument!');
    }

    $secretHash = erConfigClassLhConfig::getInstance()->getSetting( 'site', 'seller_secret_hash' );

    if ($requestPayload['verify_token'] != sha1($secretHash.sha1($secretHash.'_external_login_' . $requestPayload['ts'])) || $requestPayload['ts'] < time() - 10) {
        throw new Exception('Invalid `verify_token` or it has expired!');
    }

    $currentUser = erLhcoreClassUser::instance();

    if ($currentUser->authenticate($requestPayload['username'],$requestPayload['password'])) {
        echo json_encode(['instance_id' => erLhcoreClassInstance::getInstance()->id]);
    } else {
        throw new Exception('Invalid logins!');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo erLhcoreClassRestAPIHandler::outputResponse(array(
        'error' => true,
        'result' => $e->getMessage()
    ));
}

exit();

?>