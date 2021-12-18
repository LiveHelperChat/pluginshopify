<?php

if (!isset($_GET['shop']) || empty($_GET['shop'])){
    die('Missing shop!');
}

$shop = $_GET['shop'];

$scopes = 'read_script_tags,write_script_tags';

$redirect_uri = erLhcoreClassBBCode::getHost() . erLhcoreClassDesign::baseurl('shopify/token') ;

$ext = erLhcoreClassModule::getExtensionInstance('erLhcoreClassExtensionPluginshopify');

$install_uri = 'https://' . $shop . '/admin/oauth/authorize?client_id=' . $ext->settings['app_settings']['api_key'] . '&scope=' . $scopes . '&redirect_uri=' . urlencode($redirect_uri);

header('Location: ' . $install_uri);

die();

?>