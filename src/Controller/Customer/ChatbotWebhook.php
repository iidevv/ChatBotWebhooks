<?php

namespace Iidev\ChatBotWebhooks\Controller\Customer;

use XLite\Core\Request;
use \XLite\Core\Config;
use XLite\InjectLoggerTrait;

class ChatbotWebhook extends \XLite\Controller\Customer\ACustomer
{

    use InjectLoggerTrait;

    public function handleRequest()
    {
        if (Request::getInstance()->isPost()) {
            $this->handlePostRequest();
        } elseif (Request::getInstance()->isGet()) {
            $this->handleGetRequest();
        } else {
            $this->sendResponse(['error' => 'Invalid request method']);
        }

        exit;
    }

    private function handlePostRequest()
    {
        $headers = getallheaders();
        $token = isset($headers['Token']) ? $headers['Token'] : '';
        $expectedToken = Config::getInstance()->Iidev->ChatBotWebhooks->token;

        if ($token !== $expectedToken) {
            http_response_code(401);
            exit;
        }


        $inputJSON = file_get_contents('php://input');
        $data = json_decode($inputJSON, true);

        if ($data) {
            $this->processChatbotRequest($data);
        } else {
            $this->sendResponse(['error' => 'Invalid JSON payload']);
        }
    }

    private function handleGetRequest()
    {
        $token = Config::getInstance()->Iidev->ChatBotWebhooks->token;
        $request = Request::getInstance();

        if ($request->token !== $token) {
            http_response_code(401);
            exit;
        }

        echo $request->challenge;
        exit;
    }

    public function processChatbotRequest($data)
    {
        if(empty($data['chatId'])) {
            $this->sendResponse(['error' => 'chatId is required']);
        }

        $webhookName = $data['node']['webhookName'];
        
        if($webhookName === "xcart_order_tracking") {
            $orderTracking = new \Iidev\ChatBotWebhooks\Core\OrderTracking();

            $response = $orderTracking->processOrderTrackingData($data);

            if($response['error']) {
                http_response_code($response['error_code']);
            }
            $this->sendResponse($response);
        }

        http_response_code(404);
        $this->sendResponse(['error' => 'webhook not found']);
    }

    private function sendResponse($response)
    {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    protected function doNoAction()
    {
        $this->handleRequest();
    }

}
