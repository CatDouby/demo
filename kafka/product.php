<?php

/**
 * 需要 rdkafka 扩展
 */

$conf = new \RdKafka\Conf();
$conf->set('metadata.broker.list', '127.0.0.1:9092');
// $topicConf = new \RdKafka\TopicConf();
// $conf->setDefaultTopicConf($topicConf);

$topicName = 'register';
$id = uniqid('', true). str_replace(' ', '_', microtime());
$payload = [
    'name' => 'foo1',
    'uid' => 10001,
    'order_no' => date('YmdHis'),
];

// 生产者投递
$producer = new \RdKafka\Producer($conf);
$topic = $producer->newTopic($topicName);
$topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode($payload), $id);
$producer->poll(0);
$x = $producer->flush(1000);
switch ($x) {
    case RD_KAFKA_RESP_ERR_NO_ERROR:
        echo "push sucess\n";
        break;
    case RD_KAFKA_RESP_ERR__TIMED_OUT:
    case RD_KAFKA_RESP_ERR__NOT_IMPLEMENTED:
    default:
        echo "push err, code: $x\n";
        break;
}
