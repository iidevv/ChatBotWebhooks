<?php

namespace Iidev\ChatBotWebhooks\View;

use XCart\Extender\Mapping\ListChild;

/**
 * @ListChild (list="center")
 */
class ChatbotWebhook extends \XLite\View\AView
{
    /**
     * @return array
     */
    public static function getAllowedTargets()
    {
        return array_merge(parent::getAllowedTargets(), ['chatbot_webhook']);
    }

    /**
     * @return string
     */
    protected function getDefaultTemplate()
    {
        return '';
    }
}