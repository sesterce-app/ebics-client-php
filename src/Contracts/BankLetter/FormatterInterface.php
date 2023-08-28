<?php

namespace AndrewSvirin\Ebics\Contracts\BankLetter;

use AndrewSvirin\Ebics\Models\BankLetter;

/**
 * EBICS formatter for bank letter.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
interface FormatterInterface
{

    /**
     * Format bank letter to printable.
     *
     * @param BankLetter $bankLetter
     *
     * @return mixed
     */
    public function format(BankLetter $bankLetter);
}
