<?php

$prodName = $product ?? 'product';
$link     = $productLink ?? '#';
$text     = $productText ?? 'Start GoTo'.ucfirst($prodName);
?>
<link rel="stylesheet" href="<?php echo $view['assets']->getUrl('plugins/LeuchtfeuerGoToBundle/Assets/css/citrix.css'); ?>" type="text/css"/>
<a class="citrix-start-button" href="<?php echo $link; ?>" target="_blank">
    <?php echo $text; ?>
</a>
<div style="clear:both"></div>
