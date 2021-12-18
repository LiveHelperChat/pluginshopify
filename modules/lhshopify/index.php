<?php

$shopifyOptions = erLhcoreClassModelChatConfig::fetch('shopify_options');
$data = (array) $shopifyOptions->data;

if (!isset($_GET['shop']) || !isset($_GET['hmac'])) {
    die('Missing required parameters [shop or hmac]');
}

if (isset($data['shops'][$_GET['shop']]['access_token']) && !empty($data['shops'][$_GET['shop']]['access_token'])) {

    header('X-Frame-Options: ALLOWALL');

    @ini_set('session.cookie_samesite', 'None');
    @ini_set('session.cookie_secure', true);

    $ext = erLhcoreClassModule::getExtensionInstance('erLhcoreClassExtensionPluginshopify');

    $ext->setAccessData([
        'shop' => $_GET['shop'],
        'access_token' => $data['shops'][$_GET['shop']]['access_token']]);

    try {
        $ext->verifyAccessToken($data['shops'][$_GET['shop']]['access_token'], $_GET['shop']);
    } catch (Exception $e) {
        erLhcoreClassModule::redirect('shopify/install', '?shop=' .$_GET['shop']);
        die();
    }

    // Set variables for our request
    $api_key = $ext->settings['app_settings']['api_key'];
    $shared_secret = $ext->settings['app_settings']['api_secret_key'];
    $params = $paramsAll = $_GET; // Retrieve all request parameters
    $hmac = $_GET['hmac']; // Retrieve HMAC request parameter

    $validKeys = ['code','host','shop','timestamp','locale','session'];

    foreach ($params as $key => $value){
        if (!in_array($key,$validKeys)) {
            unset($params[$key]);
        }
    }

    ksort($params); // Sort params lexographically

    $computed_hmac = hash_hmac('sha256', http_build_query($params), $shared_secret);

    $tpl = erLhcoreClassTemplate::getInstance('lhshopify/index.tpl.php');

    if (!hash_equals($hmac, $computed_hmac)) {
        $tpl = new erLhcoreClassTemplate('lhkernel/validation_error.tpl.php');
        $tpl->set('errors',[erTranslationClassLhTranslation::getInstance()->getTranslation('plugin/shopify', 'We could not verify this request. Please reload this window.')]);

        // Log invalid request params
        erLhcoreClassLog::write('Shopify : TOKEN_MISMATCH '.print_r($paramsAll, true));
    } else {

        $currentUser = erLhcoreClassUser::instance();

        // Further actions are allowed only if logged
        if (!$currentUser->isLogged()) {
            $tpl = new erLhcoreClassTemplate('lhshopify/index_not_logged.tpl.php');
            $Result['content'] = $tpl->fetch();
            $Result['path'] = array(
                array(
                    'title' => erTranslationClassLhTranslation::getInstance()->getTranslation('plugin/shopify', 'Shopify')
                )
            );
            $Result['pagelayout'] = 'popup';
            header("refresh: 6;");
            return;
        }

        if (!$currentUser->hasAccessTo('lhshopify','use')) {
            $tpl = new erLhcoreClassTemplate('lhkernel/validation_error.tpl.php');
            $tpl->set('errors',[erTranslationClassLhTranslation::getInstance()->getTranslation('plugin/shopify', 'You do not have permission to modify embed script!')]);
            $Result['pagelayout'] = 'popup';
            return;
        }

        $ext = erLhcoreClassModule::getExtensionInstance('erLhcoreClassExtensionPluginshopify');

        $ext->setAccessData([
            'shop' => $_GET['shop'],
            'access_token' => $data['shops'][$_GET['shop']]['access_token']]);

        $tpl->set('ext',$ext);

        $departmentParams = array();
        $userDepartments = erLhcoreClassUserDep::parseUserDepartmetnsForFilter($currentUser->getUserID(), $currentUser->cache_version);
        if ($userDepartments !== true) {
            $departmentParams['filterin']['id'] = $filter['filterin']['dep_id'] = $userDepartments;
        }
        $departmentParams['limit'] = false;

        $tpl->set('departmentParams',$departmentParams);

        if (ezcInputForm::hasPostData()) {

            if (!isset($_POST['csfr_token']) || !$currentUser->validateCSFRToken($_POST['csfr_token'])) {
                die('CSFR Token is missing');
            }

            $definition = array(
                'embed_script' => new ezcInputFormDefinitionElement(
                    ezcInputFormDefinitionElement::OPTIONAL, 'unsafe_raw'
                ),
                'custom_script' => new ezcInputFormDefinitionElement(
                    ezcInputFormDefinitionElement::OPTIONAL, 'unsafe_raw'
                ),
                'script_tag_id' => new ezcInputFormDefinitionElement(
                    ezcInputFormDefinitionElement::OPTIONAL, 'unsafe_raw'
                ),
                'custom_dep_id' => new ezcInputFormDefinitionElement(
                    ezcInputFormDefinitionElement::OPTIONAL, 'string', array('min_range' => 1), FILTER_REQUIRE_ARRAY
                ),
                'custom_theme_id' => new ezcInputFormDefinitionElement(
                    ezcInputFormDefinitionElement::OPTIONAL, 'int', array('min_range' => 1)
                )
            );

            $form = new ezcInputForm( INPUT_POST, $definition );
            $Errors = array();

            if (isset($_POST['RemoveScript'])) {

                if ( $form->hasValidData( 'script_tag_id' ) && $form->script_tag_id != '') {
                    $response = $ext->shopifyCall("/admin/api/2021-10/script_tags/" . $form->script_tag_id . ".json", [], 'DELETE');

                    if (!is_array($response)) {
                        $tpl->set('errors',[$response]);
                    } else {
                        $tpl->set('updated', true);
                        $tpl->set('msg', erTranslationClassLhTranslation::getInstance()->getTranslation('plugin/shopify', 'Chat script was removed!'));
                    }

                } else {
                    $tpl->set('errors',[erTranslationClassLhTranslation::getInstance()->getTranslation('plugin/shopify', 'Missing script_tag_id')]);
                }

            } else if (isset($_POST['InstallScript'])) {

                if ( $form->hasValidData( 'embed_script' ) && $form->embed_script != '') {
                    $data['shops'][$_GET['shop']]['embed_script'] = $form->embed_script;
                } else {
                    $Errors[] = erTranslationClassLhTranslation::getInstance()->getTranslation('plugin/shopify', 'Embed script is missing!');
                }

                if ( $form->hasValidData( 'custom_theme_id' )) {
                    $data['shops'][$_GET['shop']]['custom_theme_id'] = $form->custom_theme_id;
                } else {
                    $data['shops'][$_GET['shop']]['custom_theme_id'] = 0;
                }

                if ( $form->hasValidData( 'custom_dep_id' )) {
                    $data['shops'][$_GET['shop']]['custom_dep_id'] = $form->custom_dep_id;
                } else {
                    $data['shops'][$_GET['shop']]['custom_dep_id'] = [];
                }

                if ( $form->hasValidData( 'custom_script' ) && $form->custom_script != '') {
                    $data['shops'][$_GET['shop']]['custom_script'] = $form->custom_script;
                } else {
                    $data['shops'][$_GET['shop']]['custom_script'] = '';
                }

                if (empty($Errors)) {
                    $embedParams = json_decode($data['shops'][$_GET['shop']]['embed_script'], true);
                    if (!is_array($embedParams)) {
                        $Errors[] = erTranslationClassLhTranslation::getInstance()->getTranslation('plugin/shopify', 'You embed arguments does not seems to be a valid JSON!');
                        $data['shops'][$_GET['shop']]['embed_script'] = '';
                    }
                }

                if (empty($Errors)) {

                    // We execute request only if we don't have a tag yet
                    if (!($form->hasValidData( 'script_tag_id' ) && $form->script_tag_id != '')) {
                        $response = $ext->shopifyCall("/admin/api/2021-10/script_tags.json", json_encode([
                            'script_tag' => [
                                'src' => erLhcoreClassBBCode::getHost() . erLhcoreClassDesign::baseurl('shopify/script') . '/' . $ext->getAccessAttribute('shop'),
                                'event' => 'onload'
                            ]
                        ]), 'POST');
                        if (!is_array($response)) {
                            $tpl->set('errors',[$response]);
                        }
                    }

                    $shopifyOptions->value = serialize($data);
                    $shopifyOptions->saveThis();
                    $tpl->set('updated', true);
                    $tpl->set('msg', erTranslationClassLhTranslation::getInstance()->getTranslation('plugin/shopify', 'Chat script was installed or updated! Click F5 in your store to see it.'));
                } else {
                    $tpl->set('errors',$Errors);
                }
            }
        }

        $tpl->set('ext_settings',$data['shops'][$_GET['shop']]);
    }

    $Result['content'] = $tpl->fetch();
    $Result['path'] = array(
        array(
            'title' => erTranslationClassLhTranslation::getInstance()->getTranslation('plugin/shopify', 'Shopify')
        )
    );

    $Result['pagelayout'] = 'chattabs';

} else {
    erLhcoreClassModule::redirect('shopify/install', '?shop=' .$_GET['shop']);
    die();
}



?>