<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$prodName = $product ?? 'product';
$link     = $productLink ?? '#';
$text     = $productText ?? 'Start GoTo'.ucfirst($prodName);
?>
<link rel="stylesheet" href="<?php echo $view['assets']->getUrl('plugins/LeuchtfeuerGoToBundle/Assets/css/citrix.css'); ?>" type="text/css"/>
<a class="citrix-start-button" href="<?php echo $link; ?>" target="_blank">
    <?php echo $text; ?>
</a>
<div style="clear:both"></div>
