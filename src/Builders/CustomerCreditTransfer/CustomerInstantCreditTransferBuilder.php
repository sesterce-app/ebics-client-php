<?php

namespace AndrewSvirin\Ebics\Builders\CustomerCreditTransfer;

use AndrewSvirin\Ebics\Handlers\Traits\XPathTrait;
use AndrewSvirin\Ebics\Models\CustomerCreditTransfer;
use AndrewSvirin\Ebics\Services\DOMHelper;
use AndrewSvirin\Ebics\Services\RandomService;
use DateTime;

/**
 * Class CustomerInstantCreditTransferBuilder builder for model
 * @see \AndrewSvirin\Ebics\Models\CustomerInstantCreditTransfer
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Linus Holtstiege
 */
final class CustomerInstantCreditTransferBuilder
{
    use XPathTrait;

    private RandomService $randomService;
    private ?CustomerCreditTransfer $instance;

    public function __construct()
    {
        $this->randomService = new RandomService();
    }

    /**
     * @param string $debtorFinInstBic
     * @param string $debttorIban
     * @param string $debitorName
     * @param bool $batchBooking By deactivating the batch booking procedure,
     * you request your credit institution to book each transaction within this order separately.
     * @param string|null $msgId
     * @param string|null $paymentReference
     * @return CustomerInstantCreditTransferBuilder
     */
    public function createInstance(
        string $debtorFinInstBic,
        string $debttorIban,
        string $debitorName,
        bool $batchBooking = true,
        string $msgId = null,
        string $paymentReference = null
    ): CustomerInstantCreditTransferBuilder {
        $this->instance = new CustomerCreditTransfer();
        $now = new DateTime();

        $xmDocument = $this->instance->createElementNS(
            'urn:iso:std:iso:20022:tech:xsd:pain.001.001.03',
            'Document'
        );
        $xmDocument->setAttributeNS(
            'http://www.w3.org/2000/xmlns/',
            'xmlns:xsi',
            'http://www.w3.org/2001/XMLSchema-instance'
        );
        $xmDocument->setAttributeNS(
            'http://www.w3.org/2001/XMLSchema-instance',
            'xsi:schemaLocation',
            'urn:iso:std:iso:20022:tech:xsd:pain.001.001.03 pain.001.001.03.xsd'
        );
        $this->instance->appendChild($xmDocument);

        $xmlCstmrCdtTrfInitn = $this->instance->createElement('CstmrCdtTrfInitn');
        $xmDocument->appendChild($xmlCstmrCdtTrfInitn);

        $xmlGrpHdr = $this->instance->createElement('GrpHdr');
        $xmlCstmrCdtTrfInitn->appendChild($xmlGrpHdr);

        $xmlMsgId = $this->instance->createElement('MsgId');
        $xmlMsgId->nodeValue = $this->randomService->uniqueIdWithDate('msg');
        if ($msgId) {
            $xmlMsgId->nodeValue = $msgId;
        }
        $xmlGrpHdr->appendChild($xmlMsgId);

        $xmlMsgId = $this->instance->createElement('CreDtTm');
        $xmlMsgId->nodeValue = $now->format('Y-m-d\TH:i:s\.vP');
        $xmlGrpHdr->appendChild($xmlMsgId);

        $xmlNbOfTxs = $this->instance->createElement('NbOfTxs');
        $xmlNbOfTxs->nodeValue = '0';
        $xmlGrpHdr->appendChild($xmlNbOfTxs);

        $xmlCtrlSum = $this->instance->createElement('CtrlSum');
        $xmlCtrlSum->nodeValue = '0';
        $xmlGrpHdr->appendChild($xmlCtrlSum);

        $xmlInitgPty = $this->instance->createElement('InitgPty');
        $xmlGrpHdr->appendChild($xmlInitgPty);

        $xmlNm = $this->instance->createElement('Nm');
        $xmlNm->nodeValue = $debitorName;
        $xmlInitgPty->appendChild($xmlNm);

        $xmlPmtInf = $this->instance->createElement('PmtInf');
        $xmlCstmrCdtTrfInitn->appendChild($xmlPmtInf);

        $xmlPmtInfId = $this->instance->createElement('PmtInfId');
        $xmlPmtInfId->nodeValue = $this->randomService->uniqueIdWithDate('pmt');
        if ($paymentReference) {
            $xmlPmtInfId->nodeValue = $paymentReference;
        }
        $xmlPmtInf->appendChild($xmlPmtInfId);

        $xmlPmtMtd = $this->instance->createElement('PmtMtd');
        $xmlPmtMtd->nodeValue = 'TRF';
        $xmlPmtInf->appendChild($xmlPmtMtd);

        $xmlBtchBookg = $this->instance->createElement('BtchBookg');
        $xmlBtchBookg->nodeValue = $batchBooking ? 'true' : 'false';
        $xmlPmtInf->appendChild($xmlBtchBookg);

        $xmlNbOfTxs = $this->instance->createElement('NbOfTxs');
        $xmlNbOfTxs->nodeValue = '0';
        $xmlPmtInf->appendChild($xmlNbOfTxs);

        $xmlCtrlSum = $this->instance->createElement('CtrlSum');
        $xmlCtrlSum->nodeValue = '0';
        $xmlPmtInf->appendChild($xmlCtrlSum);

        $xmlPmtTpInf = $this->instance->createElement('PmtTpInf');
        $xmlPmtInf->appendChild($xmlPmtTpInf);

        $xmlSvcLvl = $this->instance->createElement('SvcLvl');
        $xmlPmtTpInf->appendChild($xmlSvcLvl);

        $xmlCd = $this->instance->createElement('Cd');
        $xmlCd->nodeValue = 'SEPA';
        $xmlSvcLvl->appendChild($xmlCd);

        $xmlLclInstrm = $this->instance->createElement('LclInstrm');
        $xmlPmtTpInf->appendChild($xmlLclInstrm);

        $xmlCd = $this->instance->createElement('Cd');
        $xmlCd->nodeValue = 'INST';
        $xmlLclInstrm->appendChild($xmlCd);

        $xmlReqdExctnDt = $this->instance->createElement('ReqdExctnDt');
        $xmlReqdExctnDt->nodeValue = $now->format('Y-m-d');
        $xmlPmtInf->appendChild($xmlReqdExctnDt);

        $xmlDbtr = $this->instance->createElement('Dbtr');
        $xmlPmtInf->appendChild($xmlDbtr);

        $xmlNm = $this->instance->createElement('Nm');
        $xmlNm->nodeValue = $debitorName;
        $xmlDbtr->appendChild($xmlNm);

        $xmlDbtrAcct = $this->instance->createElement('DbtrAcct');
        $xmlPmtInf->appendChild($xmlDbtrAcct);

        $xmlId = $this->instance->createElement('Id');
        $xmlDbtrAcct->appendChild($xmlId);

        $xmlIBAN = $this->instance->createElement('IBAN');
        $xmlIBAN->nodeValue = $debttorIban;
        $xmlId->appendChild($xmlIBAN);

        $xmlDbtrAgt = $this->instance->createElement('DbtrAgt');
        $xmlPmtInf->appendChild($xmlDbtrAgt);

        $xmlFinInstnId = $this->instance->createElement('FinInstnId');
        $xmlDbtrAgt->appendChild($xmlFinInstnId);

        $xmlBIC = $this->instance->createElement('BIC');
        $xmlBIC->nodeValue = $debtorFinInstBic;
        $xmlFinInstnId->appendChild($xmlBIC);

        $xmlChrgBr = $this->instance->createElement('ChrgBr');
        $xmlChrgBr->nodeValue = 'SLEV';
        $xmlPmtInf->appendChild($xmlChrgBr);

        return $this;
    }

    /**
     * @param string $creditorFinInstBIC
     * @param string $creditorIBAN
     * @param string $creditorName
     * @param float $amount
     * @param string $currency
     * @param string $purpose
     * @param string $endToEndId
     * @return CustomerInstantCreditTransferBuilder
     */
    public function addTransaction(
        string $creditorFinInstBIC,
        string $creditorIBAN,
        string $creditorName,
        float $amount,
        string $currency,
        string $purpose,
        string $endToEndId
    ): CustomerInstantCreditTransferBuilder {
        $xpath = $this->prepareXPath($this->instance);
        $nbOfTxsList = $xpath->query('//CstmrCdtTrfInitn/PmtInf/NbOfTxs');
        $nbOfTxs = (int)DOMHelper::safeItemValue($nbOfTxsList);
        $nbOfTxs++;

        $pmtInfList = $xpath->query('//CstmrCdtTrfInitn/PmtInf');
        $xmlPmtInf = DOMHelper::safeItem($pmtInfList);

        $xmlCdtTrfTxInf = $this->instance->createElement('CdtTrfTxInf');
        $xmlPmtInf->appendChild($xmlCdtTrfTxInf);

        $xmlPmtId = $this->instance->createElement('PmtId');
        $xmlCdtTrfTxInf->appendChild($xmlPmtId);

        $xmlEndToEndId = $this->instance->createElement('EndToEndId');
        if ($endToEndId) {
            $xmlEndToEndId->nodeValue = $endToEndId;
        } else {
            $xmlEndToEndId->nodeValue = $this->randomService->uniqueIdWithDate(
                'pete' . str_pad((string)$nbOfTxs, 2, '0')
            );
        }
        $xmlPmtId->appendChild($xmlEndToEndId);

        $xmlAmt = $this->instance->createElement('Amt');
        $xmlCdtTrfTxInf->appendChild($xmlAmt);

        $xmlInstdAmt = $this->instance->createElement('InstdAmt');
        $xmlInstdAmt->setAttribute('Ccy', $currency);
        $xmlInstdAmt->nodeValue = number_format($amount, 2, '.', '');
        $xmlAmt->appendChild($xmlInstdAmt);

        $xmlCdtrAgt = $this->instance->createElement('CdtrAgt');
        $xmlCdtTrfTxInf->appendChild($xmlCdtrAgt);

        $xmlFinInstnId = $this->instance->createElement('FinInstnId');
        $xmlCdtrAgt->appendChild($xmlFinInstnId);

        $xmlBIC = $this->instance->createElement('BIC');
        $xmlBIC->nodeValue = $creditorFinInstBIC;
        $xmlFinInstnId->appendChild($xmlBIC);

        $xmlCdtr = $this->instance->createElement('Cdtr');
        $xmlCdtTrfTxInf->appendChild($xmlCdtr);

        $xmlNm = $this->instance->createElement('Nm');
        $xmlNm->nodeValue = $creditorName;
        $xmlCdtr->appendChild($xmlNm);

        $xmlCdtrAcct = $this->instance->createElement('CdtrAcct');
        $xmlCdtTrfTxInf->appendChild($xmlCdtrAcct);

        $xmlId = $this->instance->createElement('Id');
        $xmlCdtrAcct->appendChild($xmlId);

        $xmlIBAN = $this->instance->createElement('IBAN');
        $xmlIBAN->nodeValue = $creditorIBAN;
        $xmlId->appendChild($xmlIBAN);

        $xmlRmtInf = $this->instance->createElement('RmtInf');
        $xmlCdtTrfTxInf->appendChild($xmlRmtInf);

        $xmlUstrd = $this->instance->createElement('Ustrd');
        $xmlUstrd->nodeValue = $purpose;
        $xmlRmtInf->appendChild($xmlUstrd);

        $xmlNbOfTxs = DOMHelper::safeItem($nbOfTxsList);
        $xmlNbOfTxs->nodeValue = (string)$nbOfTxs;

        $nbOfTxsList = $xpath->query('//CstmrCdtTrfInitn/GrpHdr/NbOfTxs');
        $xmlNbOfTxs = DOMHelper::safeItem($nbOfTxsList);
        $xmlNbOfTxs->nodeValue = (string)$nbOfTxs;

        $ctrlSumList = $xpath->query('//CstmrCdtTrfInitn/GrpHdr/CtrlSum');
        $ctrlSum = (float)DOMHelper::safeItemValue($ctrlSumList);
        $xmlCtrlSum = DOMHelper::safeItem($ctrlSumList);
        $xmlCtrlSum->nodeValue = number_format($ctrlSum + $amount, 2, '.', '');

        $ctrlSumList = $xpath->query('//CstmrCdtTrfInitn/PmtInf/CtrlSum');
        $ctrlSum = (float)DOMHelper::safeItemValue($ctrlSumList);
        $xmlCtrlSum = DOMHelper::safeItem($ctrlSumList);
        $xmlCtrlSum->nodeValue = number_format($ctrlSum + $amount, 2, '.', '');

        return $this;
    }

    public function popInstance(): CustomerCreditTransfer
    {
        $instance = $this->instance;
        $this->instance = null;

        return $instance;
    }
}
