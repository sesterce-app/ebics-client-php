<?php

namespace AndrewSvirin\Ebics\Services;

use DOMNode;
use DOMNodeList;
use RuntimeException;

/**
 * @internal
 */
final class DOMHelper
{
    /**
     * @param DOMNodeList|false $domNodeList
     *
     * @return DOMNode
     */
    public static function safeItem($domNodeList): DOMNode
    {
        if (false === $domNodeList) {
            throw new RuntimeException('DOM Node List should not be empty.');
        }
        $domNode = $domNodeList->item(0);
        if ($domNode === null) {
            throw new RuntimeException('DOM Node List should have an item.');
        }

        return $domNode;
    }

    /**
     * @param DOMNodeList|false $domNodeList
     *
     * @return string
     */
    public static function safeItemValue($domNodeList): string
    {
        $domNode = self::safeItem($domNodeList);

        return $domNode->nodeValue;
    }

    /**
     * @param DOMNodeList|false $domNodeList
     *
     * @return string
     */
    public static function safeItemValueOrNull($domNodeList): ?string
    {
        if ($domNodeList === false) {
            throw new RuntimeException('DOM Node List should not be empty.');
        }

        $domNode = $domNodeList->item(0);

        if ($domNode === null) {
            return null;
        }

        return $domNode->nodeValue;
    }
}
