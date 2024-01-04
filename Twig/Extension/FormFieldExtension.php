<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerGoToBundle\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

final class FormFieldExtension extends AbstractExtension
{
    /**
     * @return TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('pushProduct', [$this, 'pushProduct']),
        ];
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('array_intersect_key', [$this, 'arrayIntersectKey']),
            new TwigFunction('array_flip', [$this, 'arrayFlip']),
        ];
    }

    /**
     * @param mixed[] $array1
     * @param mixed[] $array2
     *
     * @return mixed[]
     */
    public function arrayIntersectKey(array $array1, array $array2): array
    {
        return array_intersect_key($array1, $array2);
    }

    /**
     * @param mixed[] $array
     *
     * @return mixed[]
     */
    public function arrayFlip(array $array): array
    {
        return array_flip($array);
    }

    /**
     * @param mixed[] $array1
     * @param mixed[] $array2
     *
     * @return mixed[]
     */
    public function pushProduct(array $array1, array $array2, string $index): array
    {
        $array1[$index][$array2['product_key']] = $array2;

        return $array1;
    }
}
