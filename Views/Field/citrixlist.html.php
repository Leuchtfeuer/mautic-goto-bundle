<?php
include __DIR__ . '/field_helper.php';

use MauticPlugin\MauticGoToBundle\Helper\GoToDetailKeywords;
use const MauticPlugin\MauticGoToBundle\Entity\STATUS_ACTIVE;

$debug = true;

/** @var array $mauticTemplateVars */
/** @var array $field */
/** @var string $inputAttr */
/** @var string $labelAttr */

function buildProductTitle(array $field, array $product)
{
    $product_date = DateTime::createFromFormat('Y-m-d H:i:s.u', $product['date']['date']);

    if (false === $product_date || $product['status'] !== STATUS_ACTIVE || $product_date < new DateTime()) {
        return;
    }

    $parts = [];

    foreach ($field['properties']['in_dropdown_details'] as $setting) {
        switch ($setting) {
            case GoToDetailKeywords::GOTOTITLE:
                $parts[] = $product['name'];
                break;
            case GoToDetailKeywords::GOTODATE:
                $parts[] = $product_date->format('d.m.Y H:i');
                break;
            case GoToDetailKeywords::GOTOAUTHOR:
                $parts[] = $product['author'];
                break;
            case GoToDetailKeywords::GOTOLANGUAGE:
                $parts[] = $product['language'];
                break;
        }
    }

    return join(' ', $parts);
}

//  some basic values
$productsShouldSplit = true;
$listType = $field['customParameters']['listType'] ?? '';
$list = $mauticTemplateVars['field']['customParameters']['product_choices'];
$selectedProducts = array_intersect_key($list, array_flip($field['properties']['product_select']));
$recurrenceKeys = array_column($selectedProducts, 'recurrence_key', 'product_key');

$refactored = [];

foreach ($list as $productKey => $product) {
    if (!in_array($product['recurrence_key'], $recurrenceKeys)) {
        continue;
    }
    $refactored[$productsShouldSplit ? $product['recurrence_key'] : false][$productKey] = $product;
}

$defaultInputFormClass = ' not-chosen';
$defaultInputClass = 'selectbox';
$containerType = 'select';


if (!empty($properties['multiple'])) {
    $inputAttr .= ' multiple="multiple"';
}
$label = (!$field['showLabel']) ? '' : "<label $labelAttr>{$field['label']}</label>";
$help = (empty($field['helpMessage'])) ? '' : "<span class=\"mauticform-helpmessage\">{$field['helpMessage']}</span>";

$emptyOption = '';
if ((!empty($properties['empty_value']) || empty($field['defaultValue']) && empty($properties['multiple']))) {
    $emptyOption = "<option value=\"\">{$properties['empty_value']}</option>";
}

foreach ($refactored as $fieldGroup) {
    $optGroupLabel = array_values($fieldGroup)[0]['name'];

    $html .= count($refactored) > 1 ? sprintf('<optgroup label="%s">', $optGroupLabel) : '';


    foreach ($fieldGroup as $productKey => $product) {
        $selected = ($productKey === $product['defaultValue']) ? ' selected="selected"' : '';
        $html .= "<option value=\"{$view->escape($productKey)}\"{$selected}>{$view->escape(buildProductTitle($field, $product))}</option>";
    }

    $html .= count($refactored) > 1 ? '</optgroup>' : '';
}
$html .= '</select>';

$description = '';

if (false && !empty($field['properties']['above_dropdown_details'])) {
    $details = $field['properties']['above_dropdown_details'];

    foreach ($without_session_list as $key => $product) {
        if ($product === null) {
            continue;
        }

        $description .= sprintf('<div %s>', $field['properties']['attribute_container']);

        if (in_array(GoToDetailKeywords::GOTOTITLE, $details, false)) {
            $description .= sprintf(
                '<span %s>%s</span>',
                $field['properties']['attribute_title'],
                $products[$key]['name']
            );
        }
        if (in_array(GoToDetailKeywords::GOTOLANGUAGE, $details, false)) {
            $lang = locale_get_display_language($products[$key]['language'], 'en');
            $description .= <<<HTML
                <span {$field['properties']['attribute_language']}>{$lang}</span>
HTML;
        }
        if (in_array(GoToDetailKeywords::GOTOAUTHOR, $details, false)) {
            $description .= <<<HTML
                <span {$field['properties']['attribute_author']}>{$products[$key]['author']}</span>
HTML;
        }
        if (in_array(GoToDetailKeywords::GOTODURATION, $details, false)) {
            $duration = $products[$key]['duration'] / 60;
            $description .= <<<HTML
                <span {$field['properties']['attribute_duration']}>{$duration}</span>
HTML;
        }
        if (in_array(GoToDetailKeywords::GOTODATE, $details, false)) {
            $date = DateTime::createFromFormat('Y-m-d H:i:s.u', $products[$key]['date']['date']);
            if ($date !== false) {
                $description .= <<<HTML
                <span {$field['properties']['attribute_date']}>{$date->format('d.m.Y H:i')}</span>
HTML;
            }
        }
        if (in_array(GoToDetailKeywords::GOTODESCRIPTION, $details, false)) {
            $description .= <<<HTML
                <span {$field['properties']['attribute_description']}>{$products[$key]['description']}</span>
HTML;
        }
        $description .= <<<HTML
                </div>
HTML;
        break;
    }
}


    $optionsHtml = $html;
    $html = <<<HTML

            <div $containerAttr>{$label}{$help}
                <div $containerAttr>{$description}</div>
                <select $inputAttr>$optionsHtml
                </select>
                <span class="mauticform-errormsg" style="display: none;">$validationMessage</span>
            </div>

HTML;
    echo $html;

