<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerGoToBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;

/**
 * Class TokenGenerateEvent.
 */
class TokenGenerateEvent extends CommonEvent
{
    /**
     * TokenGenerateEvent constructor.
     *
     * @param mixed[] $params
     */
    public function __construct(private array $params)
    {
    }

    /**
     * Returns the params array.
     *
     * @return mixed[]
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * @param mixed[] $params
     */
    protected function setParams(array $params): void
    {
        $this->params = $params;
    }

    public function getProduct(): string
    {
        return array_key_exists('product', $this->params) ? $this->params['product'] : '';
    }

    public function setProduct(string $product): void
    {
        $this->params['product'] = $product;
    }

    public function getProductLink(): string
    {
        return array_key_exists('productLink', $this->params) ? $this->params['productLink'] : '';
    }

    public function setProductLink(string $productLink): void
    {
        $this->params['productLink'] = $productLink;
    }

    public function getProductText(): string
    {
        return array_key_exists('productText', $this->params) ? $this->params['productText'] : '';
    }

    public function setProductText(string $productText): void
    {
        $this->params['productText'] = $productText;
    }
}
