<?php
header('P3P:CP="IDC DSP COR ADM DEVi TAIi PSA PSD IVAi IVDi CONi HIS OUR IND CNT"');
header('Content-type: text/javascript');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', time() + 60 * 60 * 8) . ' GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

$shopifyOptions = erLhcoreClassModelChatConfig::fetch('shopify_options');
$data = (array) $shopifyOptions->data;

if (isset($data['shops'][$_GET['shop']]['access_token']) && !empty($data['shops'][$_GET['shop']]['access_token'])) {

$args = json_decode($data['shops'][$_GET['shop']]['embed_script'],true);

if (isset($data['shops'][$_GET['shop']]['custom_dep_id']) && is_array($data['shops'][$_GET['shop']]['custom_dep_id']) && !empty($data['shops'][$_GET['shop']]['custom_dep_id'])) {
    $args['department'] = $data['shops'][$_GET['shop']]['custom_dep_id'];
}

if (isset($data['shops'][$_GET['shop']]['custom_theme_id']) && !empty($data['shops'][$_GET['shop']]['custom_theme_id'])) {
    $args['theme'] = $data['shops'][$_GET['shop']]['custom_theme_id'];
}

$args = json_encode($args);

if (isset($data['shops'][$_GET['shop']]['custom_script'])) {
    echo $data['shops'][$_GET['shop']]['custom_script'];
}

?>

var LHC_API = LHC_API||{};
LHC_API.args = <?php echo $args?>;
(function() {
var po = document.createElement('script'); po.type = 'text/javascript'; po.setAttribute('crossorigin','anonymous'); po.async = true;
var date = new Date();
po.src = '//<?php echo $_SERVER['HTTP_HOST']?><?php echo erLhcoreClassDesign::design('js/widgetv2/index.js')?>?'+(""+date.getFullYear() + date.getMonth() + date.getDate());
var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(po, s);
})();

<?php } else { ?>console.log('LiveHelperCat: Invalid shop');<?php  } exit;?>

