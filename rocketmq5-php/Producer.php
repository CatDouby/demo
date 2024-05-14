<?php
/**
 * Licensed to the Apache Software Foundation (ASF) under one or more
 * contributor license agreements.  See the NOTICE file distributed with
 * this work for additional information regarding copyright ownership.
 * The ASF licenses this file to You under the Apache License, Version 2.0
 * (the "License"); you may not use this file except in compliance with
 * the License.  You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Apache\Rocketmq;

require 'vendor/autoload.php';


use Apache\Rocketmq\V2\MessageQueue;
use Apache\Rocketmq\V2\MessagingServiceClient;
use Apache\Rocketmq\V2\QueryRouteRequest;
use Apache\Rocketmq\V2\ReceiveMessageRequest;
use Apache\Rocketmq\V2\Resource;
use Apache\Rocketmq\V2\SendMessageRequest;
use Grpc\ChannelCredentials;
use const Grpc\STATUS_OK;

class Producer
{
    /** @var MessagingServiceClient */
    protected $client;

    public function init($config)
    {
        /**
         * Client ID is currently concatenated using a fixed host name to
         * facilitate code debugging.
         */
        $clientId = $this->getClientID();
        $client = new MessagingServiceClient($config['endpoint'], [
            'credentials' => ChannelCredentials::createInsecure(),
            'update_metadata' => function ($metaData) use ($clientId) {
                // clientID, x-mq-client-id ?
                $metaData['headers'] = ['x-mq-client-id' => $clientId]; // Pass the ClientID to the server through the header
                return $metaData;
            }
        ]);
        $this->client = $client;
    }

    public function send($topic, $data) {
        $qr = new QueryRouteRequest();
        $rs = new Resource();
        $rs->setResourceNamespace('');
        $rs->setName($topic);
        $qr->setTopic($rs);
        $status = $this->client->QueryRoute($qr)->wait();
        if (STATUS_OK != $status[1]->code) {
            print_r($status); // This prints out the response data returned by the server
            return ;
        }

        $message = new \Apache\Rocketmq\V2\Message();
        $message->setTopic($rs);
        $message->setUserProperties([
            'name' => uniqid('msg-', true),
            'client_id' => $this->getClientID(),
        ]);
        $message->setBody(json_encode($data));
        $msgRequest = new SendMessageRequest();
        $msgRequest->setMessages([$message]);
        $unaryCall = $this->client->SendMessage($msgRequest);
        list($resp, $status) = $unaryCall->wait();
        /** @var \Apache\Rocketmq\V2\SendMessageRespons $resp */
        echo $resp->serializeToJsonString();  // serializeToString() serializeToJsonString()
        print_r($status);
    }

    public function getRandStr($length) {
        $str = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $len = strlen($str)-1;
        $randstr = '';
        for ($i=0;$i<$length;$i++) {
            $num=mt_rand(0,$len);
            $randstr .= $str[$num];
        }
        return $randstr;
    }

    public function getClientID() : string {
        static $s;
        if ($s) return $s;
        if (file_exists('./client-id.txt') && $s = file_get_contents('./client-id.txt')) {
            return $s;
        }
        $s = 'missyourlove' . '@' . posix_getpid() . '@' . rand(0, 10) . '@' . $this->getRandStr(10);
        echo "\ngenerate client id:\n$s\n";
        file_put_contents('./client-id.txt', $s);
        return $s;
    }
}

$config = [
    'endpoint' => 'localhost:8081', // The grpc service port
    'accessKey' => '',
    'secretKey' => '',
];
$p = new Producer();
$p->init($config);
$msg = [
    'name' => 'foo',
    'number' => 'XX0000-20240513-0001'
];
$p->send('topicB', $msg);

// rocketmq5-php/grpc/Apache/Rocketmq/V2/Code.php :CLIENT_ID_REQUIRED
