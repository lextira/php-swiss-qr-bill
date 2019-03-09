<?php

namespace Sprain\SwissQrBill\PaymentPart\Output\HtmlOutput;

use Sprain\SwissQrBill\PaymentPart\Output\AbstractOutput;
use Sprain\SwissQrBill\PaymentPart\Output\Element\AbstractElement;
use Sprain\SwissQrBill\PaymentPart\Output\Element\OutputElementInterface;
use Sprain\SwissQrBill\PaymentPart\Output\Element\Placeholder;
use Sprain\SwissQrBill\PaymentPart\Output\Element\Text;
use Sprain\SwissQrBill\PaymentPart\Output\Element\Title;
use Sprain\SwissQrBill\PaymentPart\Output\HtmlOutput\Template\PlaceholderElementTemplate;
use Sprain\SwissQrBill\PaymentPart\Output\HtmlOutput\Template\TextElementTemplate;
use Sprain\SwissQrBill\PaymentPart\Output\HtmlOutput\Template\PaymentPartTemplate;
use Sprain\SwissQrBill\PaymentPart\Output\HtmlOutput\Template\TitleElementTemplate;
use Sprain\SwissQrBill\PaymentPart\Output\OutputInterface;
use Sprain\SwissQrBill\PaymentPart\Translation\Translation;

final class HtmlOutput extends AbstractOutput implements OutputInterface
{
    public function getPaymentPart() : string
    {
        $paymentPart = PaymentPartTemplate::TEMPLATE;

        $paymentPart = $this->addSwissQrCodeImage($paymentPart);
        $paymentPart = $this->addInformationContent($paymentPart);
        $paymentPart = $this->addInformationContentReceipt($paymentPart);
        $paymentPart = $this->addCurrencyContent($paymentPart);
        $paymentPart = $this->addAmountContent($paymentPart);
        $paymentPart = $this->addAmountContentReceipt($paymentPart);
        $paymentPart = $this->addFurtherInformationContent($paymentPart);

        $paymentPart = $this->translateContents($paymentPart, $this->getLanguage());

        return $paymentPart;
    }

    private function addSwissQrCodeImage(string $paymentPart) : string
    {
        $qrCode = $this->qrBill->getQrCode();
        $qrCode->setWriterByExtension('svg');

        $paymentPart = str_replace('{{ swiss-qr-image }}', $qrCode->writeDataUri(), $paymentPart);

        return $paymentPart;
    }

    private function addInformationContent(string $paymentPart) : string
    {
        $informationContent = '';

        foreach($this->getInformationElements() as $informationElement) {
            $informationContentPart = $this->getContentElement($informationElement);
            $informationContent .= $informationContentPart;
        }

        $paymentPart = str_replace('{{ information-content }}', $informationContent, $paymentPart);

        return $paymentPart;
    }

    private function addInformationContentReceipt(string $paymentPart) : string
    {
        $informationContent = '';

        foreach($this->getInformationElementsOfReceipt() as $informationElement) {
            $informationContent .= $this->getContentElement($informationElement);
        }

        $paymentPart = str_replace('{{ information-content-receipt }}', $informationContent, $paymentPart);

        return $paymentPart;
    }

    private function addCurrencyContent(string $paymentPart) : string
    {
        $currencyContent = '';

        foreach($this->getCurrencyElements() as $currencyElement) {
            $currencyContent .= $this->getContentElement($currencyElement);
        }

        $paymentPart = str_replace('{{ currency-content }}', $currencyContent, $paymentPart);

        return $paymentPart;
    }

    private function addAmountContent(string $paymentPart) : string
    {
        $amountContent = '';

        foreach($this->getAmountElements() as $amountElement) {
            $amountContent .= $this->getContentElement($amountElement);
        }

        $paymentPart = str_replace('{{ amount-content }}', $amountContent, $paymentPart);

        return $paymentPart;
    }

    private function addAmountContentReceipt(string $paymentPart) : string
    {
        $amountContent = '';

        foreach($this->getAmountElementsReceipt() as $amountElement) {
            $amountContent .= $this->getContentElement($amountElement);
        }

        $paymentPart = str_replace('{{ amount-content-receipt }}', $amountContent, $paymentPart);

        return $paymentPart;
    }

    private function addFurtherInformationContent(string $paymentPart) : string
    {
        $furtherInformationContent = '';

        foreach($this->getFurtherInformationElements() as $furtherInformationElement) {
            $furtherInformationContent .= $this->getContentElement($furtherInformationElement);
        }

        $paymentPart = str_replace('{{ further-information-content }}', $furtherInformationContent, $paymentPart);

        return $paymentPart;
    }

    private function getContentElement(OutputElementInterface $element) : string
    {
        if ($element instanceof Title) {
            $elementTemplate = TitleElementTemplate::TEMPLATE;
            $elementString = str_replace('{{ title }}', $element->getTitle(), $elementTemplate);

            return $elementString;
        }

        if ($element instanceof Text) {
            $elementTemplate = TextElementTemplate::TEMPLATE;
            $elementString = str_replace('{{ text }}', nl2br($element->getText()), $elementTemplate);

            return $elementString;
        }

        if ($element instanceof Placeholder) {
            $elementTemplate = PlaceholderElementTemplate::TEMPLATE;
            $elementString = $elementTemplate;

            $svgDoc = new \DOMDocument();
            $svgDoc->loadXML(file_get_contents($element->getFile()));
            $svg = $svgDoc->getElementsByTagName('svg');
            $dataUri = 'data:image/svg+xml;base64,' . base64_encode($svg->item(0)->C14N());

            $elementString = str_replace('{{ file }}', $dataUri, $elementString);
            $elementString = str_replace('{{ width }}', $element->getWidth(), $elementString);
            $elementString = str_replace('{{ height }}', $element->getHeight(), $elementString);
            $elementString = str_replace('{{ id }}', $element->getType(), $elementString);

            return $elementString;
        }
    }

    private function translateContents($paymentPart, $language)
    {
        $translations = Translation::getAllByLanguage($language);
        foreach($translations as $key => $text) {
            $paymentPart = str_replace('{{ text.' . $key . ' }}', $text, $paymentPart);
        }

        return $paymentPart;
    }
}