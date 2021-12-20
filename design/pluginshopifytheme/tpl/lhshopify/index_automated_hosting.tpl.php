<h5><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('plugin/shopify','Please configure your widget settings.')?></h5>

<?php

$response = $ext->shopifyCall("/admin/api/2021-10/script_tags.json", array('src' => 'https://' . $instance->address . '.' . $seller_domain  . erLhcoreClassDesign::baseurl('shopify/script') . '/' . $ext->getAccessAttribute('shop')) , 'GET');

if (is_array($response)) : $responseJSON = json_decode($response['response'], true); ?>
    <form action="" method="post" ng-non-bindable onsubmit="$(this).find('button[type=\'submit\']').addClass('disabled');">

        <?php include(erLhcoreClassDesign::designtpl('lhkernel/csfr_token.tpl.php'));?>

        <?php if (isset($errors)) : ?>
            <?php include(erLhcoreClassDesign::designtpl('lhkernel/validation_error.tpl.php'));?>
        <?php endif; ?>

        <?php if (isset($updated)) : ?>
            <?php include(erLhcoreClassDesign::designtpl('lhkernel/alert_success.tpl.php'));?>
        <?php endif; ?>

        <div class="form-group">
            <label><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('plugin/shopify','Department. If you do not choose any department we will show all available departments in the widget.')?></label>
            <select name="custom_dep_id[]" multiple="multiple" size="5" class="form-control form-control-sm">
                <?php foreach ($departments as $departament) : $value = $departament['id']?>
                    <option <?php if (isset($ext_settings['custom_dep_id']) && is_array($ext_settings['custom_dep_id']) && in_array($value, $ext_settings['custom_dep_id'])) : ?>selected="selected"<?php endif;?> value="<?php echo htmlspecialchars($value)?>"><?php echo htmlspecialchars($departament['name'])?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('plugin/shopify','Default theme')?></label>
            <select name="custom_theme_id" class="form-control form-control-sm">
                <option value="0"><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('system/htmlcode','Default');?></option>
                <?php foreach ($themes as $theme) : $value = $theme['id']?>
                    <option <?php if (isset($ext_settings['custom_theme_id']) && $value == $ext_settings['custom_theme_id'] ? 'selected="selected"' : '') : ?>selected="selected"<?php endif;?> value="<?php echo htmlspecialchars($value)?>"><?php echo htmlspecialchars($theme['name'])?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="button" onclick="$('#shopify-advanced').toggleClass('hide')" class="btn btn-xs btn-outline-secondary my-2"><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('plugin/shopify','Show advanced options')?></button>

        <div class="row hide" id="shopify-advanced">
            <div class="col-6">
                <div class="form-group">
                    <label><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('plugin/shopify','You can modify embed script arguments.')?> <button id="reset-to-default" type="button" class="btn btn-outline-secondary btn-xs"><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('plugin/shopify','Reset to default')?></button> </label>
                    <textarea class="form-control form-control-sm fs12" rows="10" id="id_embed_script" name="embed_script"><?php
                        $paramsEmbed = $paramsEmbedDefault = [
                            'mode' => 'widget',
                            'lhc_base_url' => '//' .  $instance->address . '.' . $seller_domain . '/',
                            'wheight' => 450,
                            'wwidth' => 350,
                            'pwidth' => 500,
                            'leaveamessage' => true,
                            'check_messages' => false
                        ];
                        if (isset($ext_settings['embed_script']) && !empty($ext_settings['embed_script'])) {
                            $paramsEmbed = json_decode($ext_settings['embed_script'],true);
                        }
                        echo htmlspecialchars(json_encode($paramsEmbed,JSON_PRETTY_PRINT));
                        ?></textarea>
                </div>
            </div>
            <div class="col-6">
                <div class="form-group">
                    <label><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('plugin/shopify','Custom javascript before embed code. E.g Here you can write a script to pass custom variables.')?></label>
                    <textarea class="form-control form-control-sm fs12" rows="10" name="custom_script"><?php echo htmlspecialchars(isset($ext_settings['custom_script']) ? $ext_settings['custom_script'] : ''); ?></textarea>
                </div>
            </div>
        </div>

        <script>
            $('#reset-to-default').click(function(){
                $('#id_embed_script').val(<?php echo json_encode(json_encode($paramsEmbedDefault, JSON_PRETTY_PRINT))?>);
            });
        </script>

        <hr>

        <div class="btn-group" role="group" aria-label="...">
            <button type="submit" name="InstallScript" class="btn btn-sm btn-primary">
                <?php if (count($responseJSON['script_tags']) == 1) : ?>
                    <?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('plugin/shopify','Update');?>
                <?php else : ?>
                    <?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('plugin/shopify','Install');?>
                <?php endif; ?>
            </button>
            <?php if (count($responseJSON['script_tags']) == 1) : ?>
                <input type="hidden" name="script_tag_id" value="<?php echo htmlspecialchars($responseJSON['script_tags'][0]['id'])?>" />
                <button type="submit" name="RemoveScript" class="btn btn-sm btn-warning"><?php echo erTranslationClassLhTranslation::getInstance()->getTranslation('plugin/shopify','Remove');?></button>
            <?php endif; ?>
        </div>
    </form>

<?php else : ?>
    <p ng-non-bindable><?php echo htmlspecialchars($response)?></p>
<?php endif;?>

