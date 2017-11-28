<?php

namespace Maximaster\Coupanda\Http;

use Bitrix\Main\Web\Json;

class JsonResponse
{
    private $payload = [];
    private $status = 0;
    private $message = '';

    public function setStatus($status)
    {
        $this->status = $status;
    }

    public function setMessage($message)
    {
        $this->message = $message;
    }

    public function setPayload(array $payload)
    {
        $this->payload = $payload;
    }

    public function render()
    {
        $data = [
            'status' => $this->status,
            'message' => $this->message,
            'payload' => empty($this->payload) ? new \stdClass() : $this->payload
        ];

        return Json::encode($data);
    }
}
