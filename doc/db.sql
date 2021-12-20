CREATE TABLE `lhc_shopify_shop` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `shop` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
    `access_token` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
    `instance_id` bigint(20) unsigned NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `shop` (`shop`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;