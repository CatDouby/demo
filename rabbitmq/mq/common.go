package mq

import (
	"fmt"

	"github.com/streadway/amqp"
)

var err error
var _conn *amqp.Connection
var _ch *amqp.Channel

const EXCHANGETYPE_DELAYED = "x-delayed-message"

func connect(url string) *amqp.Channel {
	_conn, err = amqp.Dial(url)
	if err != nil {
		fmt.Println("connect amqp server error")
	}

	_ch, err = _conn.Channel()
	if err != nil {
		fmt.Println("create channel error", err)
		return nil
	}

	return _ch
}

func release() {
	_ch.Close()
	_conn.Close()
}
