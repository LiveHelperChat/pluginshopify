<?php

$Module = array( "name" => "Shopify",
    'variable_params' => true );

$ViewList = array();

$ViewList['token'] = array(
    'params' => array(),
    'uparams' => array(),
);

$ViewList['install'] = array(
    'params' => array(),
    'uparams' => array(),
);

$ViewList['index'] = array(
    'params' => array(),
);

$ViewList['script'] = array(
    'params' => array('shop'),
);

$ViewList['verifyinstance'] = array(
    'params' => array(),
);

$ViewList['integrationsettings'] = array(
    'params' => array(),
);

$FunctionList = array();
$FunctionList['use'] = array('explain' => 'Allow operator to configure shopify widget');
