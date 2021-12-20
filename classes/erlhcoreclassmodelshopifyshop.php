<?php

class erLhcoreClassModelShopifyShop
{
    use erLhcoreClassDBTrait;

    public static $dbTable = 'lhc_shopify_shop';

    public static $dbTableId = 'id';

    public static $dbSessionHandler = 'erLhcoreClassExtensionPluginshopify::getSession';

    public static $dbSortOrder = 'DESC';

    public function getState()
    {
        return array(
            'id' => $this->id,
            'shop' => $this->shop,
            'access_token' => $this->access_token,
            'instance_id' => $this->instance_id
        );
    }

    public function __toString()
    {
        return $this->ctime;
    }

    public function __get($var)
    {
        switch ($var) {

            case 'ctime_front':
                $this->ctime_front = date('Ymd') == date('Ymd', $this->ctime) ? date(erLhcoreClassModule::$dateHourFormat, $this->ctime) : date(erLhcoreClassModule::$dateDateHourFormat, $this->ctime);
                return $this->ctime_front;
                break;

            default:
                ;
                break;
        }
    }

    public $id = null;
    public $shop = null;
    public $access_token = '';
    public $instance_id = 0;

}

?>