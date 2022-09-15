<?php

declare(strict_types=1);

use const MauticPlugin\MauticGoToBundle\Entity\STATUS_ACTIVE;

use MauticPlugin\MauticGoToBundle\Helper\GoToDetailKeywords;

/** @var array $mauticTemplateVars */
/** @var array $field */
/** @var string $inputAttr */
/** @var string $labelAttr */
$defaultInputFormClass = ' not-chosen';
$defaultInputClass     = 'selectbox';
$containerType         = 'select';

include __DIR__.'/field_helper.php';

if (!function_exists('buildProductTitle')) {
    function buildProductTitle(array $field, array $product)
    {
        $product_date = \DateTime::createFromFormat('Y-m-d H:i:s.u', $product['date']['date']);

        if (false === $product_date || STATUS_ACTIVE !== $product['status'] || $product_date < new DateTime()) {
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
}

//  some basic values
$productsShouldSplit = true;
$listType            = $field['customParameters']['listType'] ?? '';
$list                = $mauticTemplateVars['field']['customParameters']['product_choices'];
$selectedProducts    = array_intersect_key($list, array_flip($field['properties']['product_select']));
$recurrenceKeys      = array_column($selectedProducts, 'recurrence_key', 'product_key');

$refactored = [];

foreach ($list as $productKey => $product) {
    if (!in_array($product['recurrence_key'], $recurrenceKeys)) {
        continue;
    }
    $refactored[$productsShouldSplit ? $product['recurrence_key'] : false][$productKey] = $product;
}

if (!empty($properties['multiple'])) {
    $inputAttr .= ' multiple="multiple"';
}
$label = (!$field['showLabel']) ? '' : "<label $labelAttr>{$field['label']}</label>";
$help  = (empty($field['helpMessage'])) ? '' : "<span class=\"mauticform-helpmessage\">{$field['helpMessage']}</span>";

$emptyOption = '';
if (!empty($properties['empty_value']) || empty($field['defaultValue']) && empty($properties['multiple'])) {
    $emptyOption = "<option value=\"\">{$properties['empty_value']}</option>";
}

$html = '';

foreach ($refactored as $fieldGroup) {
    $optGroupLabel = array_values($fieldGroup)[0]['name'];

    $html .= count($refactored) > 1 ? sprintf('<optgroup label="%s">', $optGroupLabel) : '';

    foreach ($fieldGroup as $productKey => $product) {
        $selected = ($productKey === ($product['defaultValue'] ?? false)) ? ' selected="selected"' : '';
        $html .= "<option value=\"{$view->escape($productKey)}\"{$selected}>{$view->escape(buildProductTitle($field, $product))}</option>";
    }

    $html .= count($refactored) > 1 ? '</optgroup>' : '';
}
$html .= '</select>';

$description = '';

if (!empty($field['properties']['above_dropdown_details'])) {
    $details = $field['properties']['above_dropdown_details'];

    foreach ($selectedProducts as $key => $product) {
        if (null === $product) {
            continue;
        }

        $properties = [
            GoToDetailKeywords::GOTOTITLE       => sprintf('<span %s>%s</span>', $field['properties']['attribute_title'], $product['name']),
            GoToDetailKeywords::GOTOLANGUAGE    => sprintf('<span %s>%s</span>', $field['properties']['attribute_language'], locale_get_display_language($product['language'], 'en')),
            GoToDetailKeywords::GOTOAUTHOR      => sprintf('<span %s>%s</span>', $field['properties']['attribute_author'], $product['author']),
            GoToDetailKeywords::GOTODURATION    => sprintf('<span %s>%s</span>', $field['properties']['attribute_duration'], round(intval($product['duration'] / 60))),
            GoToDetailKeywords::GOTODATE        => sprintf('<span %s>%s</span>', $field['properties']['attribute_date'], DateTime::createFromFormat('Y-m-d H:i:s.u', $product['date']['date'])->format('d.m.Y H:i')),
            GoToDetailKeywords::GOTODESCRIPTION => sprintf('<span %s>%s</span>', $field['properties']['attribute_description'], $product['description']),
        ];

        $rowDescription = join('', array_intersect_key($properties, array_flip($details)));
        $description .= sprintf('<div %s>%s</div>', $field['properties']['attribute_container'], $rowDescription);
    }
}

$optionsHtml = $html;
$html        = <<<HTML

            <div $containerAttr>{$label}{$help}
                <div $containerAttr>{$description}</div>
                <select $inputAttr>$optionsHtml
                </select>
                <span class="mauticform-errormsg" style="display: none;">$validationMessage</span>
            </div>

HTML;
echo $html;
