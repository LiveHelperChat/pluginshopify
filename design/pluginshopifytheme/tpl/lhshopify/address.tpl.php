<div class="form-group">
    <label><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('plugin/shopify','Live Helper Chat instance')?>&nbsp;<b><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('plugin/shopify','address')?></b>. https://<b>address</b>.livehelperchat.com</label>
    <div class="input-group input-group-sm mb-3">
        <div class="input-group-prepend">
            <span class="input-group-text">https://</span>
        </div>
        <input type="text" required name="address" value="<?php if (isset($address)) : ?><?php echo htmlspecialchars($address)?><?php endif;?>" placeholder="Put here only address part" class="form-control form-control-sm" />
        <div class="input-group-append">
            <span class="input-group-text">.livehelperchat.com</span>
        </div>
    </div>
</div>