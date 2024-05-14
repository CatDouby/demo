package main

import (
	"flag"
	"rabbitmq-demo/mq"
)

func main() {
	var amqpSide string
	flag.StringVar(&amqpSide, "amqp-side", "", "pub|sub")
	flag.Parse()
	if "pub" == amqpSide {
		mq.Publish()
	} else if "sub" == amqpSide {
		mq.Subscribe()
	} else {
		println("unsupported amqp side:\t", amqpSide)
	}
}
