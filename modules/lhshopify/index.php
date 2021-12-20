<?php

$ext = erLhcoreClassModule::getExtensionInstance('erLhcoreClassExtensionPluginshopify');

if ($ext->settings['automated_hosting'] == true) {

    $shop = erLhcoreClassModelShopifyShop::findOne(['filter' => ['shop' => $_GET['shop']]]);

    $data = array();

    if (!($shop instanceof erLhcoreClassModelShopifyShop)) {
        $shop = new erLhcoreClassModelShopifyShop();
    }

    if ($shop instanceof erLhcoreClassModelShopifyShop && $shop->access_token != '') {
        $data['shops'][$_GET['shop']]['access_token'] = $shop->access_token;
    }

} else {
    $shopifyOptions = erLhcoreClassModelChatConfig::fetch('shopify_options');
    $data = (array) $shopifyOptions->data;
}

if (!isset($_GET['shop']) || !isset($_GET['hmac'])) {
    die('Missing required parameters [shop or hmac]');
}

if (isset($data['shops'][$_GET['shop']]['access_token']) && !empty($data['shops'][$_GET['shop']]['access_token'])) {

    header('X-Frame-Options: ALLOWALL');

    @ini_set('session.cookie_samesite', 'None');
    @ini_set('session.cookie_secure', true);

    $ext->setAccessData([
        'shop' => $_GET['shop'],
        'access_token' => $data['shops'][$_GET['shop']]['access_token']]);

    try {
        $ext->verifyAccessToken($data['shops'][$_GET['shop']]['access_token'], $_GET['shop']);
    } catch (Exception $e) {
        if (isset($_SERVER['HTTP_SEC_FETCH_DEST']) && $_SERVER['HTTP_SEC_FETCH_DEST'] == 'iframe' ) {
            $tpl = new erLhcoreClassTemplate('lhshopify/reinstall.tpl.php');
            $Result['content'] = $tpl->fetch();
            $Result['pagelayout'] = 'popup';
            header("Refresh:0");
            return;
        } else {
            erLhcoreClassModule::redirect('shopify/install', '?shop=' .$_GET['shop']);
            die();
        }
    }

    // If not iframe redirect to iframe
    if (!(isset($_SERVER['HTTP_SEC_FETCH_DEST']) && $_SERVER['HTTP_SEC_FETCH_DEST'] == 'iframe' )) {
        header('Location: https://' . $_GET['shop'] . '/admin/apps');
        exit();
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

    if (isset($shop) && $shop instanceof erLhcoreClassModelShopifyShop) {
        $tpl = erLhcoreClassTemplate::getInstance('lhshopify/index_automated_hosting.tpl.php');
    } else {
        $tpl = erLhcoreClassTemplate::getInstance('lhshopify/index.tpl.php');
    }

    if (!hash_equals($hmac, $computed_hmac)) {
        $tpl = new erLhcoreClassTemplate('lhkernel/validation_error.tpl.php');
        $tpl->set('errors',[erTranslationClassLhTranslation::getInstance()->getTranslation('plugin/shopify', 'We could not verify this request. Please reload this window.')]);

        // Log invalid request params
        erLhcoreClassLog::write('Shopify : TOKEN_MISMATCH '.print_r($paramsAll, true));
    } else {

        if (isset($shop) && $shop instanceof erLhcoreClassModelShopifyShop) {

            // Means we still don't know to what this instance belongs to
            if ($shop->instance_id == 0) {

                $tpl = new erLhcoreClassTemplate('lhshopify/index_not_logged_automated_hosting.tpl.php');

                if (ezcInputForm::hasPostData() && isset($_POST['Action'])) {
                    $definition = array(
                        'address' => new ezcInputFormDefinitionElement(
                            ezcInputFormDefinitionElement::OPTIONAL, 'unsafe_raw'
                        ),
                        'username' => new ezcInputFormDefinitionElement(
                            ezcInputFormDefinitionElement::OPTIONAL, 'unsafe_raw'
                        ),
                        'password' => new ezcInputFormDefinitionElement(
                            ezcInputFormDefinitionElement::OPTIONAL, 'unsafe_raw'
                        )
                    );

                    $form = new ezcInputForm( INPUT_POST, $definition );
                    $Errors = array();

                    $paramsValidation = [];

                    if ($form->hasValidData( 'address' ) && $form->address != '') {
                        $paramsValidation['address'] = $form->address;
                        $tpl->set('address', $form->address);
                    } else {
                        $Errors[] = 'Please enter an address!';
                    }

                    if ($form->hasValidData( 'username' ) && $form->username != '') {
                        $paramsValidation['username'] = $form->username;
                        $tpl->set('username', $form->username);
                    } else {
                        $Errors[] = 'Please enter a username!';
                    }

                    if ($form->hasValidData( 'password' ) && $form->password != '') {
                        $paramsValidation['password'] = $form->password;
                        $tpl->set('password', $form->password);
                    } else {
                        $Errors[] = 'Please enter a password!';
                    }

                    if (empty($Errors)) {

                        $responseValidation = $ext->validateAutomatedHosting([
                            'address' => $form->address,
                            'username' => $form->username,
                            'password' => $form->password,
                        ]);

                        if (isset($responseValidation['valid']) && $responseValidation['valid'] == true) {
                            $shop->instance_id = $responseValidation['response']['instance_id'];
                            $shop->updateThis();
                            // Reload current page with all arguments as we have instance ID now.
                            header("Refresh: 6;");
                            exit;
                        } else {
                            $tpl->set('errors',['Please check your login details!']);
                            if ($ext->settings['enable_debug'] == true) {
                                erLhcoreClassLog::write(print_r($responseValidation, true));
                            }
                        }

                    } else {
                        $tpl->set('errors',$Errors);
                    }
                }

                $Result['content'] = $tpl->fetch();
                $Result['path'] = array(
                    array(
                        'title' => erTranslationClassLhTranslation::getInstance()->getTranslation('plugin/shopify', 'Shopify')
                    )
                );
                $Result['pagelayout'] = 'popup';
                return;
            }

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
        }

        $tpl->set('ext',$ext);

        if (isset($shop) && $shop instanceof erLhcoreClassModelShopifyShop) {

            $ext->setAccessData([
                'shop' => $shop->shop,
                'access_token' => $shop->access_token]
            );

            $departments = [];
            $themes = [];

            $responseValidation = $ext->getIntegrationSettings([
                'address' => erLhcoreClassModelInstance::fetch($shop->instance_id)->address,
                'shop' => $shop->shop
            ]);

            $departments = $responseValidation['response']['departments'];
            $themes = $responseValidation['response']['themes'];

            $data['shops'][$_GET['shop']]['embed_script'] = $responseValidation['response']['embed_script'];
            $data['shops'][$_GET['shop']]['custom_script'] = $responseValidation['response']['custom_script'];

            $tpl->setArray([
                'seller_domain' => erConfigClassLhConfig::getInstance()->getSetting( 'site', 'seller_domain' ),
                'instance' => erLhcoreClassModelInstance::fetch($shop->instance_id),
                'departments' => $departments,
                'themes' => $themes,
            ]);

        } else {
            $ext->setAccessData([
                'shop' => $_GET['shop'],
                'access_token' => $data['shops'][$_GET['shop']]['access_token']]);
            $departmentParams = array();
            $userDepartments = erLhcoreClassUserDep::parseUserDepartmetnsForFilter($currentUser->getUserID(), $currentUser->cache_version);
            if ($userDepartments !== true) {
                $departmentParams['filterin']['id'] = $filter['filterin']['dep_id'] = $userDepartments;
            }
            $departmentParams['limit'] = false;

            $tpl->set('departmentParams',$departmentParams);
        }

        if (ezcInputForm::hasPostData()) {

            if (!(isset($shop) && $shop instanceof erLhcoreClassModelShopifyShop) && (!isset($_POST['csfr_token']) || !$currentUser->validateCSFRToken($_POST['csfr_token']))) {
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
                    ezcInputFormDefinitionElement::OPTIONAL, 'string', null, FILTER_REQUIRE_ARRAY
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
                    $tpl->set('errors',[erTranslationClassLhTranslation::getInstance()->getTranslation('plugin/shopify', 'Missing `script_tag_id`')]);
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

                        if (isset($shop) && $shop instanceof erLhcoreClassModelShopifyShop) {
                            $src = 'https://' . erLhcoreClassModelInstance::fetch($shop->instance_id)->address . '.' . erConfigClassLhConfig::getInstance()->getSetting( 'site', 'seller_domain' )  . erLhcoreClassDesign::baseurl('shopify/script') . '/' . $ext->getAccessAttribute('shop');
                        } else {
                            $src = erLhcoreClassBBCode::getHost() . erLhcoreClassDesign::baseurl('shopify/script') . '/' . $ext->getAccessAttribute('shop');
                        }

                        $response = $ext->shopifyCall("/admin/api/2021-10/script_tags.json", json_encode([
                            'script_tag' => [
                                'src' => $src,
                                'event' => 'onload'
                            ]
                        ]), 'POST');

                        if (!is_array($response)) {
                            $tpl->set('errors',[$response]);
                        }
                    }

                    if (isset($shop) && $shop instanceof erLhcoreClassModelShopifyShop) {
                        $ext->setIntegrationSettings([
                            'address' => erLhcoreClassModelInstance::fetch($shop->instance_id)->address,
                            'shop' => $shop->shop,
                            'params_integration' => $data['shops'][$_GET['shop']]
                        ]);
                    } else {
                        $shopifyOptions->value = serialize($data);
                        $shopifyOptions->saveThis();
                    }

                    $tpl->set('updated', true);
                    $tpl->set('msg', erTranslationClassLhTranslation::getInstance()->getTranslation('plugin/shopify', 'Chat script was installed or updated! Refresh your store page to see it. Have in mind it might take upto a minute for it to appear.'));
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

    if (isset($shop) && $shop instanceof erLhcoreClassModelShopifyShop) {
        $Result['pagelayout'] = 'popup';
    } else {
        $Result['pagelayout'] = 'chattabs';
    }

} else {
    erLhcoreClassModule::redirect('shopify/install', '?shop=' .$_GET['shop']);
    die();
}



?>