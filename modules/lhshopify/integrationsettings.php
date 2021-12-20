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

    if (!isset($requestPayload['verify_token'])) {
        throw new Exception('Missing `verify_token` argument!');
    }

    if (!isset($requestPayload['action'])) {
        throw new Exception('Missing `action` argument!');
    }

    if (!isset($requestPayload['shop'])) {
        throw new Exception('Missing `shop` argument!');
    }

    $secretHash = erConfigClassLhConfig::getInstance()->getSetting( 'site', 'seller_secret_hash' );

    if ($requestPayload['verify_token'] != sha1($secretHash.sha1($secretHash.'_external_login_' . $requestPayload['ts'])) || $requestPayload['ts'] < time() - 10) {
        throw new Exception('Invalid `verify_token` or it has expired!');
    }

    $shopifyOptions = erLhcoreClassModelChatConfig::fetch('shopify_options');
    $data = (array) $shopifyOptions->data;

    if ($requestPayload['action'] == 'set') {
        $data['shops'][$requestPayload['shop']] = $requestPayload['params_integration'];
        $shopifyOptions->explain = '';
        $shopifyOptions->type = 0;
        $shopifyOptions->hidden = 1;
        $shopifyOptions->identifier = 'shopify_options';
        $shopifyOptions->value = serialize($data);
        $shopifyOptions->saveThis();
        echo json_encode(['result' => true]);
        exit;
    }

    $departments = [];
    foreach (erLhcoreClassModelDepartament::getList(['limit' => false]) as $department) {
        $departments[] = ['name' => $department->name, 'id' => $department->alias != '' ? $department->alias : $department->id];
    }

    $themes = [];
    foreach (erLhAbstractModelWidgetTheme::getList(array('limit' => 1000)) as $theme) {
        $themes[] = ['name' => $theme->name, 'id' => $theme->alias != '' ? $theme->alias : $theme->id];
    }

    echo json_encode([
        'departments' => $departments,
        'themes' => $themes,
        'embed_script' => (isset($data['shops'][$requestPayload['shop']]['embed_script']) ? $data['shops'][$requestPayload['shop']]['embed_script'] : ''),
        'custom_script' => (isset($data['shops'][$requestPayload['shop']]['custom_script']) ? $data['shops'][$requestPayload['shop']]['custom_script'] : '')
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo erLhcoreClassRestAPIHandler::outputResponse(array(
        'error' => true,
        'result' => $e->getMessage()
    ));
}

exit();

?>