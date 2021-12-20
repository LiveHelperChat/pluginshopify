<?php

/**
 * Shopify integration
 * */
class erLhcoreClassExtensionPluginshopify
{

    public $configData = false;
    private $accessData = [];
    private static $persistentSession;
    
    public function __construct()
    {

    }

    public function run()
    {
        $this->registerAutoload ();

    }

    public static function getSession() {
        if (! isset ( self::$persistentSession )) {
            self::$persistentSession = new ezcPersistentSession ( ezcDbInstance::get (), new ezcPersistentCodeManager ( './extension/pluginshopify/pos' ) );
        }
        return self::$persistentSession;
    }

    public function registerAutoload() {
        spl_autoload_register ( array (
            $this,
            'autoload'
        ), true, false );
    }

    public function __get($var) {
        switch ($var) {
            case 'is_active' :
                return true;
                break;

            case 'settings' :
                $this->settings = include ('extension/pluginshopify/settings/settings.ini.php');
                return $this->settings;
                break;

            default :
                ;
                break;
        }
    }

    public function autoload($className) {

        $classesArray = array (
            'erLhcoreClassModelShopifyShop' => 'extension/pluginshopify/classes/erlhcoreclassmodelshopifyshop.php'
        );

        if (key_exists ( $className, $classesArray )) {
            include_once $classesArray [$className];
        }
    }

    public function setAccessData($params) {
        $this->accessData = $params;
    }

    public function getAccessAttribute($attr) {
        return $this->accessData[$attr];
    }

    public function verifyAccessToken($access_token, $shop)
    {
        $response = $this->shopifyCall("/admin/api/2021-10/script_tags.json", array('src' => erLhcoreClassBBCode::getHost() . erLhcoreClassDesign::baseurl('shopify/script') . '/' . $shop) , 'GET');

        if (!is_array($response) || $response['http_code'] == 401) {
            erLhcoreClassLog::write('Shopify : ACCESS_TOKEN_INVALID '.print_r($response, true));
            throw new Exception('Access token is invalid!');
        }
    }

    public function getIntegrationSettings($params)
    {
        $url = 'https://' . $params['address'] . '.' . erConfigClassLhConfig::getInstance()->getSetting( 'site', 'seller_domain' ) . erLhcoreClassDesign::baseurl('shopify/integrationsettings');
        $ts =  time();
        $secretHash = erConfigClassLhConfig::getInstance()->getSetting( 'site', 'seller_secret_hash' );

        $payloadData = [
            'verify_token' => sha1($secretHash.sha1($secretHash.'_external_login_' . $ts)),
            'ts' => $ts,
            'shop' => $params['shop'],
            'action' => 'get'
        ];

        $response = $this->makeInstanceCall([
            'url' => $url,
            'payload_data' => $payloadData,
        ]);

        return $response;
    }

    public function setIntegrationSettings($params)
    {
        $url = 'https://' . $params['address'] . '.' . erConfigClassLhConfig::getInstance()->getSetting( 'site', 'seller_domain' ) . erLhcoreClassDesign::baseurl('shopify/integrationsettings');
        $ts =  time();
        $secretHash = erConfigClassLhConfig::getInstance()->getSetting( 'site', 'seller_secret_hash' );

        $payloadData = [
            'verify_token' => sha1($secretHash.sha1($secretHash.'_external_login_' . $ts)),
            'ts' => $ts,
            'shop' => $params['shop'],
            'action' => 'set',
            'params_integration' => $params['params_integration']
        ];

        $response = $this->makeInstanceCall([
            'url' => $url,
            'payload_data' => $payloadData,
        ]);

        return $response;
    }

    public function validateAutomatedHosting($params)
    {

        $url = 'https://' . $params['address'] . '.' . erConfigClassLhConfig::getInstance()->getSetting( 'site', 'seller_domain' ) . erLhcoreClassDesign::baseurl('shopify/verifyinstance');
        $ts =  time();
        $secretHash = erConfigClassLhConfig::getInstance()->getSetting( 'site', 'seller_secret_hash' );

        $payloadData = [
            'verify_token' => sha1($secretHash.sha1($secretHash.'_external_login_' . $ts)),
            'ts' => $ts,
            'username' => $params['username'],
            'password' => $params['password'],
        ];

        $response = $this->makeInstanceCall([
            'url' => $url,
            'payload_data' => $payloadData,
        ]);

        return $response;

    }

    public function makeInstanceCall($params) {
        $curl = curl_init($params['url']);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($curl, CURLOPT_MAXREDIRS, 3);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_USERAGENT, 'Live Helper Chat v.1');
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');

        // Setup headers
        $request_headers[] = "Content-Type: application/json";
        curl_setopt($curl, CURLOPT_HTTPHEADER, $request_headers);
        curl_setopt ($curl, CURLOPT_POSTFIELDS, json_encode($params['payload_data']));

        $response = curl_exec($curl);
        $error_number = curl_errno($curl);
        $error_message = curl_error($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        // Close cURL to be nice
        curl_close($curl);

        if ($error_number) {
            return $error_message;
        } else {
            $responseData = json_decode($response, true);
            if ($http_code == 200) {
                return [
                    'response' => $responseData,
                    'valid' => true
                ];
            } else {
                return [
                    'http_code' => $http_code,
                    'error_message' => $error_message,
                    'response' => $response,
                    'response_data' => $responseData,
                    'valid' => false
                ];
            }
        }
    }

    public function shopifyCall($api_endpoint, $query = array(), $method = 'GET', $request_headers = array()) {

        if (is_array($query)) {
            $query['Content-type'] = "application/json"; // Tell Shopify that we're expecting a response in JSON format
        }

        // Build URL
        $url = "https://" . $this->accessData['shop'] . $api_endpoint;
        if (!is_null($query) && in_array($method, array('GET', 'DELETE'))) $url = $url . "?" . http_build_query($query);

        // Configure cURL
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_HEADER, TRUE);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($curl, CURLOPT_MAXREDIRS, 3);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        // curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 3);
        // curl_setopt($curl, CURLOPT_SSLVERSION, 3);
        curl_setopt($curl, CURLOPT_USERAGENT, 'Live Helper Chat v.1');
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);

        // Setup headers
        $request_headers[] = "Content-Type: application/json";
        if (!is_null($this->accessData['access_token'])) $request_headers[] = "X-Shopify-Access-Token: " . $this->accessData['access_token'];
        curl_setopt($curl, CURLOPT_HTTPHEADER, $request_headers);

        if ($method != 'GET' && in_array($method, array('POST', 'PUT'))) {
            if (is_array($query)) $query = http_build_query($query);
            curl_setopt ($curl, CURLOPT_POSTFIELDS, $query);
        }

        // Send request to Shopify and capture any errors
        $response = curl_exec($curl);
        $error_number = curl_errno($curl);
        $error_message = curl_error($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        // Close cURL to be nice
        curl_close($curl);

        // Return an error is cURL has a problem
        if ($error_number) {
            return $error_message;
        } else {

            // No error, return Shopify's response by parsing out the body and the headers
            $response = preg_split("/\r\n\r\n|\n\n|\r\r/", $response, 2);

            // Convert headers into an array
            $headers = array();
            $header_data = explode("\n",$response[0]);
            $headers['status'] = $header_data[0]; // Does not contain a key, have to explicitly set
            array_shift($header_data); // Remove status, we've already set it above
            foreach($header_data as $part) {
                $h = explode(":", $part);
                $headers[trim($h[0])] = trim($h[1]);
            }

            // Return headers and Shopify's response
            return array('http_code' => $httpcode, 'headers' => $headers, 'response' => $response[1]);

        }
    }
}
