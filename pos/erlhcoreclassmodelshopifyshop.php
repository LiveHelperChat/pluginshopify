<?php

$def = new ezcPersistentObjectDefinition();
$def->table = "lhc_shopify_shop";
$def->class = "erLhcoreClassModelShopifyShop";

$def->idProperty = new ezcPersistentObjectIdProperty();
$def->idProperty->columnName = 'id';
$def->idProperty->propertyName = 'id';
$def->idProperty->generator = new ezcPersistentGeneratorDefinition(  'ezcPersistentNativeGenerator' );

$def->properties['shop'] = new ezcPersistentObjectProperty();
$def->properties['shop']->columnName   = 'shop';
$def->properties['shop']->propertyName = 'shop';
$def->properties['shop']->propertyType = ezcPersistentObjectProperty::PHP_TYPE_STRING;

$def->properties['access_token'] = new ezcPersistentObjectProperty();
$def->properties['access_token']->columnName   = 'access_token';
$def->properties['access_token']->propertyName = 'access_token';
$def->properties['access_token']->propertyType = ezcPersistentObjectProperty::PHP_TYPE_STRING;

$def->properties['instance_id'] = new ezcPersistentObjectProperty();
$def->properties['instance_id']->columnName   = 'instance_id';
$def->properties['instance_id']->propertyName = 'instance_id';
$def->properties['instance_id']->propertyType = ezcPersistentObjectProperty::PHP_TYPE_INT;

return $def;

?>