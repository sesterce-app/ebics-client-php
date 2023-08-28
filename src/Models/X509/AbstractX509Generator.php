<?php

namespace AndrewSvirin\Ebics\Models\X509;

use AndrewSvirin\Ebics\Contracts\Crypt\RSAInterface;
use AndrewSvirin\Ebics\Contracts\Crypt\X509Interface;
use AndrewSvirin\Ebics\Contracts\X509GeneratorInterface;
use AndrewSvirin\Ebics\Exceptions\X509\X509GeneratorException;
use AndrewSvirin\Ebics\Factories\Crypt\X509Factory;
use AndrewSvirin\Ebics\Services\RandomService;
use AndrewSvirin\Ebics\Services\X509\X509ExtensionOptionsNormalizer;
use DateTime;
use DateTimeInterface;
use RuntimeException;

/**
 * Default X509 certificate generator @see X509GeneratorInterface.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Guillaume Sainthillier, Andrew Svirin
 */
abstract class AbstractX509Generator implements X509GeneratorInterface
{
    private DateTimeInterface $x509StartDate;
    private DateTimeInterface $x509EndDate;
    private string $serialNumber;
    private X509Factory $x509Factory;
    private RandomService $randomService;

    /**
     * @deprecated 2.1 No longer used by internal code and not recommended. Extend getCertificateOptions() method.
     */
    protected array $certificateOptions = [];

    public function __construct()
    {
        $this->x509Factory = new X509Factory();
        $this->x509StartDate = (new DateTime())->modify('-1 day');
        $this->x509EndDate = (new DateTime())->modify('+1 year');
        $this->randomService = new RandomService();
        $this->serialNumber = $this->generateSerialNumber();
    }

    /**
     * @param array $certificateOptions
     * @deprecated 2.1 No longer used by internal code and not recommended. Extend getCertificateOptions() method.
     */
    public function setCertificateOptions(array $certificateOptions): void
    {
        $this->certificateOptions = $certificateOptions;
    }

    /**
     * Get certificate options
     *
     * @return array the certificate options
     *
     * @see X509 options
     */
    protected function getCertificateOptions(): array
    {
        return $this->certificateOptions;
    }

    /**
     * @inheritDoc
     * @throws X509GeneratorException
     */
    public function generateAX509(RSAInterface $privateKey, RSAInterface $publicKey): X509Interface
    {
        return $this->generateX509($privateKey, $publicKey, [
            'extensions' => [
                'id-ce-keyUsage' => [
                    'value' => ['nonRepudiation'],
                    'critical' => true,
                ],
            ],
        ]);
    }

    /**
     * @inheritDoc
     * @throws X509GeneratorException
     */
    public function generateEX509(RSAInterface $privateKey, RSAInterface $publicKey): X509Interface
    {
        return $this->generateX509($privateKey, $publicKey, [
            'extensions' => [
                'id-ce-keyUsage' => [
                    'value' => ['keyEncipherment'],
                    'critical' => true,
                ],
            ],
        ]);
    }

    /**
     * @inheritDoc
     * @throws X509GeneratorException
     */
    public function generateXX509(RSAInterface $privateKey, RSAInterface $publicKey): X509Interface
    {
        return $this->generateX509($privateKey, $publicKey, [
            'extensions' => [
                'id-ce-keyUsage' => [
                    'value' => ['digitalSignature'],
                    'critical' => true,
                ],
            ],
        ]);
    }

    /**
     * Merge arrays recursively by substitution not assoc arrays.
     *
     * @param array $options1
     * @param array $options2
     *
     * @return array
     */
    private function mergeCertificateOptions(array $options1, array $options2): array
    {
        foreach ($options2 as $key => $value) {
            if (is_string($key) && array_key_exists($key, $options1) && is_array($value)) {
                $options1[$key] = $this->mergeCertificateOptions($options1[$key], $options2[$key]);
            } else {
                $options1[$key] = $value;
            }
        }

        return $options1;
    }

    /**
     * Generate X509.
     *
     * @param RSAInterface $privateKey
     * @param RSAInterface $publicKey
     * @param array $typeCertificateOptions
     *
     * @return X509Interface
     * @throws X509GeneratorException
     */
    private function generateX509(
        RSAInterface $privateKey,
        RSAInterface $publicKey,
        array $typeCertificateOptions = []
    ): X509Interface {
        $defaultCertificateOptions = [
            'subject' => [
                'domain' => null,
                'DN' => [],
            ],
            'issuer' => [
                'DN' => [], // Same as subject, means self-signed.
            ],
            'extensions' => [
                'id-ce-basicConstraints' => [
                    'value' => [
                        'CA' => false,
                    ],
                ],
                'id-ce-extKeyUsage' => [
                    'value' => ['id-kp-emailProtection'],
                ],
            ],
        ];

        $options = array_merge_recursive($defaultCertificateOptions, $typeCertificateOptions);
        $options = $this->mergeCertificateOptions($options, $this->getCertificateOptions());

        $signatureAlgorithm = 'sha256WithRSAEncryption';

        $subject = $this->generateSubject($publicKey, $options['subject']);
        $issuer = $this->generateIssuer($privateKey, $publicKey, $subject, $options['issuer']);

        $x509 = $this->x509Factory->create();
        $x509->setStartDate($this->x509StartDate->format('YmdHis'));
        $x509->setEndDate($this->x509EndDate->format('YmdHis'));
        $x509->setSerialNumber($this->serialNumber);

        // Sign subject to allow add extensions.
        if (!($x509Signed = $x509->sign($issuer, $subject, $signatureAlgorithm))) {
            throw new RuntimeException('X509 was not signed.');
        }
        $signedSubject = $x509->saveX509($x509Signed);
        $x509->loadX509($signedSubject);

        foreach ($options['extensions'] as $id => $extension) {
            $extension = X509ExtensionOptionsNormalizer::normalize($extension);
            $isSetExtension = $x509->setExtension(
                $id,
                $extension['value'],
                $extension['critical'],
                $extension['replace']
            );
            if (false === $isSetExtension) {
                throw new X509GeneratorException(sprintf(
                    'Unable to set "%s" extension with value: %s',
                    $id,
                    var_export($extension['value'], true)
                ));
            }
        }

        // Sign extensions.
        $signedX509 = $x509->saveX509($x509->sign($issuer, $x509, $signatureAlgorithm));
        $x509->loadX509($signedX509);

        return $x509;
    }

    /**
     * @param RSAInterface $publicKey
     * @param array $options
     *
     * @return X509Interface
     */
    protected function generateSubject(RSAInterface $publicKey, array $options): X509Interface
    {
        $subject = $this->x509Factory->create();
        $subject->setPublicKey($publicKey); // $pubKey is Crypt_RSA object

        if (!empty($options['DN'])) {
            if (!$subject->setDN($options['DN'])) {
                throw new RuntimeException('Can not set Subject DN.');
            }
        }

        if (!empty($options['domain'])) {
            $subject->setDomain($options['domain']); // @phpstan-ignore-line
        }
        $subject->setKeyIdentifier($subject->computeKeyIdentifier($publicKey)); // id-ce-subjectKeyIdentifier

        return $subject;
    }

    /**
     * @param RSAInterface $privateKey
     * @param RSAInterface $publicKey
     * @param X509Interface $subject
     * @param array $options
     *
     * @return X509Interface
     */
    protected function generateIssuer(
        RSAInterface $privateKey,
        RSAInterface $publicKey,
        X509Interface $subject,
        array $options
    ): X509Interface {
        $issuer = $this->x509Factory->create();
        $issuer->setPrivateKey($privateKey); // $privKey is Crypt_RSA object

        if (!empty($options['DN'])) {
            if (!$issuer->setDN($options['DN'])) {
                throw new RuntimeException('Can not set Issuer DN.');
            }
        } else {
            $issuer->setDN($subject->getDN());
        }
        $issuer->setKeyIdentifier($subject->computeKeyIdentifier($publicKey));

        return $issuer;
    }

    /**
     * Random Number of maximum 20 Bytes if self-signed.
     *
     * @return string
     */
    protected function generateSerialNumber(): string
    {
        return $this->randomService->digits(20);
    }

    /**
     * @param DateTimeInterface $x509StartDate
     */
    public function setX509StartDate(DateTimeInterface $x509StartDate): void
    {
        $this->x509StartDate = $x509StartDate;
    }

    /**
     * @param DateTimeInterface $x509EndDate
     */
    public function setX509EndDate(DateTimeInterface $x509EndDate): void
    {
        $this->x509EndDate = $x509EndDate;
    }

    /**
     * @param string $serialNumber
     */
    public function setSerialNumber(string $serialNumber): void
    {
        $this->serialNumber = $serialNumber;
    }
}
