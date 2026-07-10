<?php

namespace Claroline\CoreBundle\Library\Mailing\Client;

class PostalRequestClient
{
    private const TIMEOUT = 5;
    private const CONNECT_TIMEOUT = 5;

    private $host;
    private $serverKey;

    public function __construct(string $host, string $serverKey)
    {
        $this->host = $host;
        $this->serverKey = $serverKey;
    }

    public function makeRequest($controller, $action, $parameters)
    {
        $url = sprintf('%s/api/v1/%s/%s', $this->host, $controller, $action);

        $headers = [
            'x-server-api-key' => $this->serverKey,
            'content-type' => 'application/json',
        ];

        $json = json_encode($parameters);

        $response = \Requests::post($url, $headers, $json, [
            'timeout' => self::TIMEOUT,
            'connect_timeout' => self::CONNECT_TIMEOUT,
        ]);

        if ($response->status_code === 200) {
            $json = json_decode($response->body);

            if ($json->status == 'success') {
                return $json->data;
            }

            if (isset($json->data->code)) {
                throw new \Postal\Error(sprintf('[%s] %s', $json->data->code, $json->data->message));
            }

            throw new \Postal\Error($json->data->message);
        }

        throw new \Postal\Error('Couldnâ€™t send message to API');
    }
}
