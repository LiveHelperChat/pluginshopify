<div id="myModal">
    <div class="modal-dialog modal-xl">
        <div class="modal-content"><div class="modal-body">
            <h5><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('plugin/shopify','Not logged')?></h5>
            <p><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('plugin/shopify','Please provide your instance address and logins to finish setup.')?></p>
            <form action="" method="post" autocomplete="off" onsubmit="$(this).find('button[type=\'submit\']').addClass('disabled');">

                <?php if (isset($errors)) : ?>
                    <?php include(erLhcoreClassDesign::designtpl('lhkernel/validation_error.tpl.php'));?>
                <?php endif; ?>

                <?php include(erLhcoreClassDesign::designtpl('lhshopify/address.tpl.php'));?>

                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('plugin/shopify','Username OR E-mail')?></label>
                            <input type="text" required name="username" value="<?php if (isset($username)) : ?><?php echo htmlspecialchars($username)?><?php endif;?>" class="form-control form-control-sm" />
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('plugin/shopify','Password')?></label>
                            <input type="password" required name="password" value="<?php if (isset($password)) : ?><?php echo htmlspecialchars($password)?><?php endif;?>" class="form-control form-control-sm" />
                        </div>
                    </div>
                </div>

                <input type="hidden" name="Action" value="RegisterInstance" />

                <div class="row">
                    <div class="col-6">
                        <button type="submit" class="btn btn-sm btn-primary" name="loginAction"><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('plugin/shopify','Login')?></button>
                    </div>
                    <div class="col-6 text-right">
                        <?php include(erLhcoreClassDesign::designtpl('lhshopify/notice_register.tpl.php'));?>
                    </div>
                </div>

            </form>
            </div>
        </div>
    </div>
</div>