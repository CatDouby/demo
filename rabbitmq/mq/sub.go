package mq

import (
	"fmt"
	"time"

	"github.com/streadway/amqp"
)

// 订阅消费消息
func Subscribe() {
	url := "amqp://guest:guest@localhost:5672/"
	ch := connect(url)
	if ch == nil {
		return
	}

	// 队列1
	q1, err := ch.QueueDeclare("order_evt_q", false, false, false, false, nil)
	if err != nil {
		fmt.Println(err)
		return
	}
	// 接收消息
	msgs, err := ch.Consume(q1.Name, "", true, false, false, false, nil)
	if err != nil {
		fmt.Println(err)
		return
	}

	// 队列2
	q2, err := ch.QueueDeclare("delay-ex", false, false, false, false, nil)
	exMsgs, err := ch.Consume(q2.Name, "", true, false, false, false, nil)

	// 循环接收消息。当只处理延迟的死信消息时就不需要订阅业务队列 q1
	//   这样q1就会因为没有处理而过期，然后成为死信
	var msg amqp.Delivery
	for {
		select {
		case msg = <-msgs:
			fmt.Println(time.Now().Format("06-01-02 15:04:05"), string(msg.Body))
		case msg = <-exMsgs:
			fmt.Println(time.Now().Format("06-01-02 15:04:05"), string(msg.Body))
		}
	}

	// 主动循环拉取
	// for {
	// 	msg, _, err := ch.Get(q.Name, false)
	// 	if err != nil {
	// 		fmt.Println("fetch msg error:\t", err)
	// 	}
	// 	msg.Ack(true)
	// }
}
