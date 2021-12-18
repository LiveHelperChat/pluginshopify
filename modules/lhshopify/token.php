<?php

$ext = erLhcoreClassModule::getExtensionInstance('erLhcoreClassExtensionPluginshopify');

try {

    // Set variables for our request
    $api_key = $ext->settings['app_settings']['api_key'];
    $shared_secret = $ext->settings['app_settings']['api_secret_key'];

    if (empty($api_key) || empty($shared_secret)){
        throw new Exception(erTranslationClassLhTranslation::getInstance()->getTranslation('plugin/shopify', 'API Key or API Secret Key is not defined in the settings file'));
    }

    if (!isset($_GET['hmac'])) {
        throw new Exception(erTranslationClassLhTranslation::getInstance()->getTranslation('plugin/shopify', 'HMAC is not passed!'));
    }

    $params = $paramsAll = $_GET; // Retrieve all request parameters

    if (!isset($_GET['hmac'])) {
        throw new Exception(erTranslationClassLhTranslation::getInstance()->getTranslation('plugin/shopify', 'HMAC is not passed!'));
    }

    $hmac = $_GET['hmac']; // Retrieve HMAC request parameter

    $validKeys = ['code','host','shop','timestamp'];

    foreach ($params as $key => $value) {
        if (!in_array($key,$validKeys)) {
            unset($params[$key]);
        }
    }

    ksort($params); // Sort params lexographically

    $computed_hmac = hash_hmac('sha256', http_build_query($params), $shared_secret);


    // Use hmac data to check that the response is from Shopify or not
    if (hash_equals($hmac, $computed_hmac)) {
        // Set variables for our request
        $query = array(
            "client_id" => $api_key, // Your API key
            "client_secret" => $shared_secret, // Your app credentials (secret key)
            "code" => $params['code'] // Grab the access key from the URL
        );

        // Generate access token URL
        $access_token_url = "https://" . $params['shop'] . "/admin/oauth/access_token?";

        // Configure curl client and execute request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $access_token_url);
        curl_setopt($ch, CURLOPT_POST, count($query));
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($query));
        $resultRequest = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if (curl_errno($ch)) {
            $http_error = curl_error($ch);
        }

        if ($httpcode == 200) {
            // Store the access token
            $resultAccess = json_decode($resultRequest, true);
            if (isset($resultAccess['access_token'])) {

                $shopifyOptions = erLhcoreClassModelChatConfig::fetch('shopify_options');
                $data = (array) $shopifyOptions->data;
                $data['shops'][$params['shop']]['access_token'] = $resultAccess['access_token'];
                $shopifyOptions->explain = '';
                $shopifyOptions->type = 0;
                $shopifyOptions->hidden = 1;
                $shopifyOptions->identifier = 'shopify_options';
                $shopifyOptions->value = serialize($data);
                $shopifyOptions->saveThis();

                header('Location: https://' . $params['shop'] . '/admin/apps');
                exit();

            } else {
                throw new Exception(erTranslationClassLhTranslation::getInstance()->getTranslation('plugin/shopify', 'Request success but access_token was not found!'));
            }
        } else {
            throw new Exception(erTranslationClassLhTranslation::getInstance()->getTranslation('plugin/shopify', 'Request failed!'));
        }
    } else {
        throw new Exception(erTranslationClassLhTranslation::getInstance()->getTranslation('plugin/shopify', 'Hashes mismatch'));
    }

} catch (Exception $e) {

    header('X-Frame-Options: ALLOWALL');

    $tpl = erLhcoreClassTemplate::getInstance('lhkernel/validation_error.tpl.php');
    $tpl->set('errors',array($e->getMessage()));
    $Result['content'] = $tpl->fetch();

    erLhcoreClassLog::write($e->getMessage());

    if ($ext->settings['enable_debug'] == true) {

        $paramsLog = ['exception' => $e->getMessage()];

        if (isset($resultRequest)) {
            $paramsLog['result'] = $resultRequest;
        }

        if (isset($httpcode)) {
            $paramsLog['http_code'] = $httpcode;
        }

        if (isset($query)) {
            $paramsLog['query'] = $query;
        }

        if (isset($http_error)) {
            $paramsLog['http_error'] = $http_error;
        }

        if (isset($paramsAll)) {
            $paramsLog['params_all'] = $paramsAll;
        }

        if (isset($access_token_url)) {
            $paramsLog['access_token_url'] = $access_token_url;
        }

        erLhcoreClassLog::write(print_r($paramsLog, true));
    }
}






?>