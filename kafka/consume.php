<?php

/**
 * 需要 rdkafka 扩展
 * 1. 消费者端应避免频繁的启动停止以减少 Rebalance 发生产生的内耗
 * 2. 合理配置 session.timout.ms, heartbeat.interval.ms, max.poll.interval.ms 等参数，保证消费者端有足够的处理业务时间，避免掉线被提出 ISR 列表
 */

$conf = new \RdKafka\Conf();
$conf->set('metadata.broker.list', '127.0.0.1:9092');
$conf->set('enable.auto.commit', 'false');
$conf->set('session.timeout.ms', 10000);
$conf->set('auto.offset.reset', 'largest');     // latest|largest
$conf->set('group.id', 'register_sendgift');    // group.id
// $conf->set('group.id', 'register.sendsms');

$topicConf = new \RdKafka\TopicConf();
$topicConf->set('auto.commit.interval.ms', 100);
$conf->setDefaultTopicConf($topicConf);

$conf->setRebalanceCb('rebalanceCallback');
$consumer = new \RdKafka\KafkaConsumer($conf);
$consumer->subscribe(['register']);
// 手动设置分区
// $consumer->assign([
//     new \RdKafka\TopicPartition($message->topic_name, 0),
//     new \RdKafka\TopicPartition($message->topic_name, 1),
// ]);

while (1) {
    $message = $consumer->consume(0);
    echo "group register_sendgift msg \n";
    if (RD_KAFKA_RESP_ERR_NO_ERROR == $message->err) {
        print_r(json_decode($message->payload, true));
    } else {
        echo "consume err: {$message->err}\n";
        sleep(2);
        continue;
    }

    try {
        $consumer->commit($message);
    } catch (Exception $e) {
        // error log
        file_put_contents('./kafka-conumser.log', date('y-m-d H:i:s'). ' '. $e->getMessage(), FILE_APPEND);
    }
}

// 取消订阅
// $consumer->unsubscribe();

function rebalanceCallback(\RdKafka\KafkaConsumer $consumer, $err, array $partitions = null) {
    switch ($err) {
        case RD_KAFKA_RESP_ERR__ASSIGN_PARTITIONS:
            echo "分区重平衡，偏移量移动到最后";
            foreach ($partitions as $v){
                $v->setOffset(RD_KAFKA_OFFSET_END);
            }
            $consumer->assign($partitions);
            break;
        case RD_KAFKA_RESP_ERR__REVOKE_PARTITIONS:
            $consumer->assign(null);
            break;
        case RD_KAFKA_RESP_ERR__TIMED_OUT:
            echo "获取超时\n";
            break;
        default:
            throw new \Exception($err);
            break;
}
}