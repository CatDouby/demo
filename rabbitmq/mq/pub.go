package mq

import (
	"encoding/json"
	"fmt"
	"net/http"

	"github.com/streadway/amqp"
)

// 发布消息
func Publish() {
	url := "amqp://guest:guest@localhost:5672/"
	// once := sync.Once{}
	// once.Do(func() {
	// 	conn = connect(url)
	// })
	ch := connect(url)
	if ch == nil {
		return
	}
	ch.Confirm(false)

	defer release()

	exName := "logs-ex"
	qName := "order_evt_q"
	key := "order_evt_k"

	err := ch.ExchangeDeclare(exName, "fanout", true, false, false, false, nil)
	if err != nil {
		fmt.Println("create exchange error:\t", err)
		return
	}

	// 创建队列。消息过期自动发到死信交换机
	q, err := ch.QueueDeclare(qName, false, false, false, false, amqp.Table{
		"x-message-ttl":             5000,
		"x-dead-letter-exchange":    "delay-ex",
		"x-dead-letter-routing-key": "",
	})
	if err != nil {
		fmt.Println("declare queue error:\t", err)
		return
	}
	// 绑定队列到交换机
	err = ch.QueueBind(q.Name, key, exName, false, nil)
	if err != nil {
		fmt.Println(err)
		return
	}

	// 创建死信交换机和队列
	dlxName := "delay-ex"
	err = ch.ExchangeDeclare(dlxName, "fanout", true, false, false, false, amqp.Table{
		"x-delayed-type": "fanout",
	})
	dlxQueue, err := ch.QueueDeclare(dlxName, false, false, false, false, nil)
	ch.QueueBind(dlxQueue.Name, "", dlxName, false, nil)

	apiPublish(ch)
}

func apiPublish(ch *amqp.Channel) {
	http.HandleFunc("/api/publish", func(w http.ResponseWriter, r *http.Request) {
		r.ParseForm()
		var data map[string]interface{}
		for k, v := range r.PostForm {
			data[k] = v[0]
		}
		action := r.PostForm.Get("action")
		body, err := json.Marshal(data)
		fmt.Println(string(body), action)

		exName := "logs-ex"
		// 创建消息
		msg := amqp.Publishing{
			Body:        []byte(body),
			ContentType: "application/json",
		}
		// 发布消息
		err = ch.Publish(exName, "", false, false, msg)
		if err != nil {
			fmt.Println(err)
			return
		}
	})
	err = http.ListenAndServe(":8080", nil)
	if err != nil {
		fmt.Println("listen api error:\t", err)
	}
}

// curl -X POST -d "name=sam&age=61&action=xdelay" \
// http://localhost:8080/api/publish
