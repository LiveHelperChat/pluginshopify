<?php

/**
 * Direct integration with os-ticket
 * */
class erLhcoreClassExtensionPluginshopify
{

    public $configData = false;
    private $accessData = [];

    public function __construct()
    {

    }

    public function run()
    {
        $this->registerAutoload ();
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
            
        );

        if (key_exists ( $className, $classesArray )) {
            include_once $classesArray [$className];
        }
    }

    public function getConfig()
    {
        if ($this->configData === false) {
            $osTicketOptions = erLhcoreClassModelChatConfig::fetch('osticket_options');
            $data = (array) $osTicketOptions->data;
            $this->configData = $data;
        }
    }

    public function setAccessData($params) {
        $this->accessData = $params;
    }

    public function getAccessAttribute($attr) {
        return $this->accessData[$attr];
    }

    public function verifyAccessToken($access_token, $shop) {

        $response = $this->shopifyCall("/admin/api/2021-10/script_tags.json", array('src' => erLhcoreClassBBCode::getHost() . erLhcoreClassDesign::baseurl('shopify/script') . '/' . $shop) , 'GET');

        if (!is_array($response) || $response['http_code'] == 401) {
            erLhcoreClassLog::write('Shopify : ACCESS_TOKEN_INVALID '.print_r($response, true));
            throw new Exception('Access token is invalid!');
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
