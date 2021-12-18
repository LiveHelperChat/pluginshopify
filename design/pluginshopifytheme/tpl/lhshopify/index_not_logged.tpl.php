<h5><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('plugin/shopify','Not logged')?></h5>

<p><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('plugin/shopify','Presently you are not logged to Live Helper Chat. Even if you are logged in another window please re-login using link below.')?></p>

<?php
    $ts = time();
    $secretHash = erConfigClassLhConfig::getInstance()->getSetting( 'site', 'secrethash' );
?>
<ol>
    <li><a target="_blank" href="<?php echo erLhcoreClassDesign::baseurl('user/login')?>?cookie=crossdomain&ts=<?php echo $ts?>&token=<?php echo sha1($secretHash.sha1($secretHash.'_external_login_' . $ts));?>" ><i class="material-icons">open_in_new</i><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('plugin/shopify','Please login first.')?></a></li>
    <li><a onclick="document.location.reload()" class="text-primary action-image" target="_blank"><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('plugin/shopify','Re-load this window after you did that.')?></a></li>
</ol>
