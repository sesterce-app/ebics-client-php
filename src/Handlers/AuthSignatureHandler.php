<?php

namespace AndrewSvirin\Ebics\Handlers;

use AndrewSvirin\Ebics\Exceptions\EbicsException;
use AndrewSvirin\Ebics\Handlers\Traits\C14NTrait;
use AndrewSvirin\Ebics\Handlers\Traits\XPathTrait;
use AndrewSvirin\Ebics\Models\KeyRing;
use AndrewSvirin\Ebics\Services\CryptService;
use AndrewSvirin\Ebics\Services\DOMHelper;
use DOMDocument;
use DOMNode;
use DOMXPath;

/**
 * Class AuthSignatureHandler manage body DOM elements.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 *
 * @internal
 */
abstract class AuthSignatureHandler
{
    use C14NTrait;
    use XPathTrait;

    private KeyRing $keyRing;
    private CryptService $cryptService;

    public function __construct(KeyRing $keyRing)
    {
        $this->keyRing = $keyRing;
        $this->cryptService = new CryptService();
    }

    abstract protected function prepareH00XXPath(DOMDocument $request): DOMXPath;

    /**
     * Add body and children elements to request.
     * Sign all elements with attribute authenticate=true.
     * Add Authenticate signature after Header section.
     *
     * @param DOMDocument $request
     * @param DOMNode|null $xmlRequestHeader
     *
     * @throws EbicsException
     */
    public function handle(DOMDocument $request, DOMNode $xmlRequestHeader = null): void
    {
        $canonicalizationPath = '//AuthSignature/*';
        $signaturePath = "//*[@authenticate='true']";
        $signatureMethodAlgorithm = 'sha256';
        $digestMethodAlgorithm = 'sha256';
        $canonicalizationMethodAlgorithm = 'REC-xml-c14n-20010315';
        $digestTransformAlgorithm = 'REC-xml-c14n-20010315';

        // Add AuthSignature to request.
        $xmlAuthSignature = $request->createElement('AuthSignature');

        // Find Header element to insert after.
        if (null === $xmlRequestHeader) {
            $xpath = $this->prepareXPath($request);
            $headerList = $xpath->query('//header');
            $xmlRequestHeader = DOMHelper::safeItem($headerList);
        }

        $this->insertAfter($xmlAuthSignature, $xmlRequestHeader);

        // Add ds:SignedInfo to AuthSignature.
        $xmlSignedInfo = $request->createElementNS('http://www.w3.org/2000/09/xmldsig#', 'ds:SignedInfo');
        $xmlAuthSignature->appendChild($xmlSignedInfo);

        // Add ds:CanonicalizationMethod to ds:SignedInfo.
        $xmlCanonicalizationMethod = $request->createElement('ds:CanonicalizationMethod');
        $xmlCanonicalizationMethod->setAttribute(
            'Algorithm',
            sprintf('http://www.w3.org/TR/2001/%s', $canonicalizationMethodAlgorithm)
        );
        $xmlSignedInfo->appendChild($xmlCanonicalizationMethod);

        // Add ds:SignatureMethod to ds:SignedInfo.
        $xmlSignatureMethod = $request->createElement('ds:SignatureMethod');
        $xmlSignatureMethod->setAttribute(
            'Algorithm',
            sprintf('http://www.w3.org/2001/04/xmldsig-more#rsa-%s', $signatureMethodAlgorithm)
        );
        $xmlSignedInfo->appendChild($xmlSignatureMethod);

        // Add ds:Reference to ds:SignedInfo.
        $xmlReference = $request->createElement('ds:Reference');
        $xmlReference->setAttribute('URI', sprintf('#xpointer(%s)', $signaturePath));
        $xmlSignedInfo->appendChild($xmlReference);

        // Add ds:Transforms to ds:Reference.
        $xmlTransforms = $request->createElement('ds:Transforms');
        $xmlReference->appendChild($xmlTransforms);

        // Add ds:Transform to ds:Transforms.
        $xmlTransform = $request->createElement('ds:Transform');
        $xmlTransform->setAttribute('Algorithm', sprintf('http://www.w3.org/TR/2001/%s', $digestTransformAlgorithm));
        $xmlTransforms->appendChild($xmlTransform);

        // Add ds:DigestMethod to ds:Reference.
        $xmlDigestMethod = $request->createElement('ds:DigestMethod');
        $xmlDigestMethod->setAttribute(
            'Algorithm',
            sprintf('http://www.w3.org/2001/04/xmlenc#%s', $digestMethodAlgorithm)
        );
        $xmlReference->appendChild($xmlDigestMethod);

        // Add ds:DigestValue to ds:Reference.
        $xmlDigestValue = $request->createElement('ds:DigestValue');
        $canonicalizedHeader = $this->calculateC14N(
            $this->prepareH00XXPath($request),
            $signaturePath,
            $canonicalizationMethodAlgorithm
        );
        $canonicalizedHeaderHash = $this->cryptService->hash($canonicalizedHeader, $digestMethodAlgorithm);
        $digestValueNodeValue = base64_encode($canonicalizedHeaderHash);

        $xmlDigestValue->nodeValue = $digestValueNodeValue;
        $xmlReference->appendChild($xmlDigestValue);

        // Add ds:SignatureValue to AuthSignature.
        $xmlSignatureValue = $request->createElement('ds:SignatureValue');
        $canonicalizedSignedInfo = $this->calculateC14N(
            $this->prepareH00XXPath($request),
            $canonicalizationPath,
            $canonicalizationMethodAlgorithm
        );
        $canonicalizedSignedInfoHash = $this->cryptService->hash(
            $canonicalizedSignedInfo,
            $signatureMethodAlgorithm
        );
        $canonicalizedSignedInfoHashEncrypted = $this->cryptService->encrypt(
            $this->keyRing->getUserSignatureX()->getPrivateKey(),
            $this->keyRing->getPassword(),
            $canonicalizedSignedInfoHash
        );
        $signatureValueNodeValue = base64_encode($canonicalizedSignedInfoHashEncrypted);

        $xmlSignatureValue->nodeValue = $signatureValueNodeValue;
        $xmlAuthSignature->appendChild($xmlSignatureValue);
    }
}
