<?php

namespace AndrewSvirin\Ebics\Services\BankLetter\HashGenerator;

use AndrewSvirin\Ebics\Contracts\BankLetter\HashGeneratorInterface;
use AndrewSvirin\Ebics\Contracts\SignatureInterface;
use AndrewSvirin\Ebics\Services\DigestResolver;

/**
 * Generate hash for certificate.
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 *
 * @internal
 */
final class CertificateHashGenerator implements HashGeneratorInterface
{
    private DigestResolver $digestResolver;

    public function __construct(DigestResolver $digestResolver)
    {
        $this->digestResolver = $digestResolver;
    }

    /**
     * @inheritDoc
     */
    public function generate(SignatureInterface $signature): string
    {
        $digest = $this->digestResolver->digest($signature);

        return bin2hex($digest);
    }
}
