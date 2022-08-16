<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

use MauticPlugin\MauticGoToBundle\Helper\GoToDetailKeywords;

if (!function_exists('buildTitle')) {
    function buildTitle($list, $products, $field)
    {
        $new_list = [];
        foreach ($list as $key => $product) {
            $title        = '';
            $product_date = DateTime::createFromFormat('Y-m-d H:i:s.u', $products[$key]['date']['date']);

            if ($product_date && (\MauticPlugin\MauticGoToBundle\Entity\STATUS_ACTIVE === $products[$key]['status'] && $product_date->getTimestamp() > time())) {
                foreach ($field['properties']['in_dropdown_details'] as $setting) {
                    switch ($setting) {
                        case GoToDetailKeywords::GOTOTITLE:
                            $title .= $products[$key]['name'];
                            break;
                        case GoToDetailKeywords::GOTODATE:
                            if (false !== $product_date) {
                                $title .= $product_date->format('d.m.Y H:i');
                            }

                            break;
                        case GoToDetailKeywords::GOTOAUTHOR:
                            $title .= $products[$key]['author'];
                            break;
                        case GoToDetailKeywords::GOTOLANGUAGE:
                            $title .= $products[$key]['language'];
                            break;
                    }

                    $title .= ' ';
                }
                $new_list[$key] = $title;
            }
        }

        return $new_list;
    }
}

$listType = '';
if (isset($field['customParameters']['listType'])) {
    $listType = $field['customParameters']['listType'];
}

$list = $mauticTemplateVars['field']['customParameters']['product_choices'];

$new_list             = [];
$without_session_list = [];
$not_separate_list    = [];
$recurrences          = [];
foreach ($list as $key => $entry) {
    if (in_array($key, $field['properties']['product_select'], true) && null !== $entry['recurrence_key']) {
        $recurrences[] = $entry['recurrence_key'];
    }
}

foreach ($list as $key => $entry) {
    if (in_array($key, $field['properties']['product_select'], true) && !isset($entry['recurrence_key'])) {
        $new_list[$key][$key]    = $list[$key]['name'];
        $not_separate_list[$key] = $list[$key]['name'];
    }

    if (in_array($entry['recurrence_key'], $recurrences, true)) {
        $new_list[$entry['recurrence_key']][$key] = $list[$key]['name'];
        $not_separate_list[$key]                  = $list[$key]['name'];
    }
}

$field    = $field;
$inForm ??= false;
$list     = $new_list;
$id       = $id;
$formId ??= 0;
$formName ??= '';

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$defaultInputFormClass = ' not-chosen';
$defaultInputClass     = 'selectbox';
$containerType         = 'select';

include __DIR__.'/field_helper.php';

if (!empty($properties['multiple'])) {
    $inputAttr .= ' multiple="multiple"';
}

$label = ($field['showLabel']) ? <<<HTML

                <label {$labelAttr}>{$field['label']}</label>
HTML : '';

$help = (empty($field['helpMessage'])) ? '' : <<<HTML

                <span class="mauticform-helpmessage">{$field['helpMessage']}</span>
HTML;

$emptyOption = '';
if (!empty($properties['empty_value']) || empty($field['defaultValue']) && empty($properties['multiple'])):
    $emptyOption = <<<HTML

                    <option value="">{$properties['empty_value']}</option>
HTML;
endif;

$optionBuilder = static function (array $list, $emptyOptionHtml = '') use (&$optionBuilder, $field, $view) {
    $html      = $emptyOptionHtml;
    foreach ($list as $listValue => $listLabel):
        if (is_array($listLabel)) {
            // This is an option group
            $html .= <<<HTML

                    <optgroup label="{$listValue}">
                    {$optionBuilder($listLabel)}
                    </optgroup>

HTML;

            continue;
        }

        $selected = ($listValue === $field['defaultValue']) ? ' selected="selected"' : '';
        $html     .= sprintf('<option value="%s"%s>%s</option>', $view->escape($listValue), $selected, $view->escape($listLabel));
    endforeach;

    return $html;
};

$description = '';
$products    = $field['customParameters']['product_choices'];
if (empty($without_session_list)) {
    $without_session_list = $not_separate_list;
}

if (!empty($field['properties']['above_dropdown_details'])) {
    $details = $field['properties']['above_dropdown_details'];

    foreach ($without_session_list as $key => $product) {
        if (null === $product) {
            continue;
        }

        $description .= sprintf('                <div %s>', $field['properties']['attribute_container']);
        if (in_array(GoToDetailKeywords::GOTOTITLE, $details, false)) {
            $description .= sprintf('                <span %s>%s</span>', $field['properties']['attribute_title'], $products[$key]['name']);
        }

        if (in_array(GoToDetailKeywords::GOTOLANGUAGE, $details, false)) {
            $lang = locale_get_display_language($products[$key]['language'], 'en');
            $description .= sprintf('                <span %s>%s</span>', $field['properties']['attribute_language'], $lang);
        }

        if (in_array(GoToDetailKeywords::GOTOAUTHOR, $details, false)) {
            $description .= sprintf('                <span %s>%s</span>', $field['properties']['attribute_author'], $products[$key]['author']);
        }

        if (in_array(GoToDetailKeywords::GOTODURATION, $details, false)) {
            $duration = $products[$key]['duration'] / 60;
            $description .= sprintf('                <span %s>%s</span>', $field['properties']['attribute_duration'], $duration);
        }

        if (in_array(GoToDetailKeywords::GOTODATE, $details, false)) {
            $date = DateTime::createFromFormat('Y-m-d H:i:s.u', $products[$key]['date']['date']);
            if (false !== $date) {
                $description .= sprintf('                <span %s>%s</span>', $field['properties']['attribute_date'], $date->format('d.m.Y H:i'));
            }
        }

        if (in_array(GoToDetailKeywords::GOTODESCRIPTION, $details, false)) {
            $description .= sprintf('                <span %s>%s</span>', $field['properties']['attribute_description'], $products[$key]['description']);
        }

        $description .= <<<HTML
                </div>
HTML;

        if ($field['properties']['separate']) {
            $testList = [];
            if (isset($list[$products[$key]['recurrence_key']])) {
                $testList = buildTitle($list[$products[$key]['recurrence_key']], $products, $field);
            } else {
                $testList = buildTitle($list[$products[$key]['product_key']], $products, $field);
            }

            if (null !== $testList) {
                $optionsHtml = $optionBuilder($testList, $emptyOption);

                $html = <<<HTML
            <div {$containerAttr}>
                <div {$containerAttr}>{$description}</div>
                <select {$inputAttr} multiple="multiple">{$optionsHtml}
                </select>
                <span class="mauticform-errormsg" style="display: none;">{$validationMessage}</span>
            </div>

HTML;

                echo $html;
                $description = '';
            }
        }
    }
}

if (0 === $field['properties']['separate']) {
    $optionsHtml = $optionBuilder(buildTitle($not_separate_list, $products, $field), $emptyOption);
    $html        = <<<HTML

            <div {$containerAttr}>{$label}{$help}
                <div {$containerAttr}>{$description}</div>
                <select {$inputAttr}>{$optionsHtml}
                </select>
                <span class="mauticform-errormsg" style="display: none;">{$validationMessage}</span>
            </div>

HTML;
    echo $html;
}
