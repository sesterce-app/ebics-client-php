<?php

namespace AndrewSvirin\Ebics\Builders\Request;

use Closure;

/**
 * Ebics 3.0 XmlBuilder.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
final class XmlBuilderV3 extends XmlBuilder
{
    public function createUnsecured(): XmlBuilder
    {
        $this->createH005(self::EBICS_UNSECURED_REQUEST);

        return $this;
    }

    public function createSecuredNoPubKeyDigests(): XmlBuilder
    {
        $this->createH005(self::EBICS_NO_PUB_KEY_DIGESTS, true);

        return $this;
    }

    public function createSecured(): XmlBuilder
    {
        $this->createH005(self::EBICS_REQUEST, true);

        return $this;
    }

    public function createUnsigned(): XmlBuilder
    {
        $this->createH005(self::EBICS_UNSIGNED_REQUEST);

        return $this;
    }

    private function createH005(string $container, bool $secured = false): XmlBuilder
    {
        $this->instance = $this->dom->createElementNS('urn:org:ebics:H005', $container);
        if ($secured) {
            $this->instance->setAttributeNS(
                'http://www.w3.org/2000/xmlns/',
                'xmlns:ds',
                'http://www.w3.org/2000/09/xmldsig#'
            );
        }
        $this->instance->setAttribute('Version', 'H005');
        $this->instance->setAttribute('Revision', '1');

        return $this;
    }

    public function addHeader(Closure $callback): XmlBuilder
    {
        $headerBuilder = new HeaderBuilderV3($this->dom);
        $header = $headerBuilder->createInstance()->getInstance();
        $this->instance->appendChild($header);

        call_user_func($callback, $headerBuilder);

        return $this;
    }

    public function addBody(Closure $callback = null): XmlBuilder
    {
        $bodyBuilder = new BodyBuilderV3($this->dom);
        $body = $bodyBuilder->createInstance()->getInstance();
        $this->instance->appendChild($body);

        if (null !== $callback) {
            call_user_func($callback, $bodyBuilder);
        }

        return $this;
    }
}
