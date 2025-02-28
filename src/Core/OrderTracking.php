<?php

namespace Iidev\ChatBotWebhooks\Core;

use Iidev\ChatBotWebhooks\Core\Main;
use XCart\Extender\Mapping\Extender;
use XLite\Core\Database;
use Qualiteam\SkinActAftership\Utils\Slug;

/**
 * @Extender\Mixin
 */
class OrderTracking extends Main
{

    protected function getCustomCarriers()
    {
        return [
            'customco-api' => "https://api.customco.com/scripts/cgiip.exe/facts.htm?startpage=protrace&pronum=TRACKING_NUMBER",
            'roadrunner-freight' => "https://freight.rrts.com/Tools/Tracking/Pages/MultipleResults.aspx?PROS=TRACKING_NUMBER",
            'rl-carriers' => "https://www2.rlcarriers.com/freight/shipping/shipment-tracing?pro=TRACKING_NUMBER&docType=PRO&source=web"
        ];
    }

    public function processOrderTrackingData($data)
    {
        $orderNumber = $data['attributes']['xcart_order_number'];
        $zipcode = $data['attributes']['xcart_order_zipcode'];

        if (!$orderNumber || !$zipcode) {
            return [
                "error" => "Data not provided",
                "error_code" => 400,
            ];
        }

        /** @var \XLite\Model\Order $order */
        $order = Database::getRepo('XLite\Model\Order')->findOneBy([
            "orderNumber" => $orderNumber
        ]);

        if (!$order) {
            return [
                "error" => "Order not found.",
                "error_code" => 404,
            ];
        }

        if ($order->getProfile()?->getShippingAddress()?->getZipcode() != $zipcode) {
            return [
                "error" => "Zipcode doesn't match.",
                "error_code" => 400,
            ];
        }

        if($order->getPaymentStatus()?->getId() === 7) {
            $message = $this->generateTextElement("We've refunded your payment. Let us know if you need anything else!");

            return $this->generateResponse($message);
        }

        if(in_array($order->getShippingStatus()?->getId(), [5, 20])) {
            $message = $this->generateTextElement("Your order has been canceled. Let us know if you need any assistance!");

            return $this->generateResponse($message);
        }

        return $this->processTrackingNumbers($order);
    }

    protected function processTrackingNumbers($order)
    {
        $trackingNumbers = $order->getTrackingNumbers();
        if ($trackingNumbers->count() == 0) {
            $message = $this->generateTextElement("Your order is being prepared. Please give us a little more time!");

            return $this->generateResponse($message);
        }

        $buttons = [];

        foreach ($trackingNumbers as $trackingNumber) {
            if ($trackingNumber->getAftershipCourierName() && $trackingNumber->getValue()) {
                $buttons[] = $this->generateButton('url', $trackingNumber->getValue(), $this->getUrl($trackingNumber));
            }
        }

        $element = $this->generateElement("Tracking information:", $buttons);
        $cards = $this->generateCards([$element]);

        return $this->generateResponse($cards);
    }

    protected function getUrl($trackingNumber): string
    {
        $slug = Slug::getSlugByName($trackingNumber->getAftershipCourierName());

        return $this->isSlugCustomCarrier($slug)
            ? $this->getCustomCarrierUrl($slug, $trackingNumber)
            : $this->prepareDefaultUrl($trackingNumber);
    }

    protected function isSlugCustomCarrier($slug): bool
    {
        $customCarriers = $this->getCustomCarriers();

        return isset($customCarriers[$slug]) ? true : false;
    }

    protected function getCustomCarrierUrl($slug, $trackingNumber)
    {
        $customCarriers = $this->getCustomCarriers();
        $url = str_replace("TRACKING_NUMBER", $trackingNumber->getValue(), $customCarriers[$slug]);

        return $url;
    }

    protected function prepareUrlParams($trackingNumber): array
    {
        return [
            'trackNumber' => $trackingNumber->getValue(),
            'slug' => Slug::getSlugByName($trackingNumber->getAftershipCourierName()),
        ];
    }

    protected function prepareDefaultUrl($trackingNumber): string
    {
        return \Includes\Utils\URLManager::getShopURL(
            \XLite\Core\Converter::buildURL(
                'trackings',
                '',
                $this->prepareUrlParams($trackingNumber),
                \XLite::CART_SELF,
                true
            )
        );
    }
}