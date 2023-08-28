<?php

namespace AndrewSvirin\Ebics\Exceptions;

/**
 * InvalidUserOrUserStateException used for 091002 EBICS error
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Guillaume Sainthillier
 */
final class InvalidUserOrUserStateException extends EbicsResponseException
{
    public function __construct(?string $responseMessage = null)
    {
        parent::__construct(
            '091002',
            $responseMessage,
            'Error that results from an invalid combination of user ID or an invalid subscriber state.'
        );
    }
}
