<?php

namespace Iidev\ChatBotWebhooks\Core;

use XLite\InjectLoggerTrait;

class Main extends \XLite\Base\Singleton
{
    use InjectLoggerTrait;

    protected static $instance;

    public static function getInstance()
    {
        if (null === static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    protected function generateTextElement($message)
    {
        return [
            "type" => "text",
            "message" => $message
        ];
    }

    protected function generateCards($elements)
    {
        return [
            "type" => "cards",
            "elements" => $elements
        ];
    }

    protected function generateElement($title, $buttons)
    {
        return [
            "title" => $title,
            "buttons" => $buttons
        ];
    }

    protected function generateButton($type, $title, $value)
    {
        return [
            "type" => $type,
            "title" => $title,
            "value" => $value
        ];
    }

    protected function generateResponse($items)
    {
        return [
            'responses' => [
                $items
            ]
        ];
    }


    public function __construct()
    {
        parent::__construct();
    }
}
