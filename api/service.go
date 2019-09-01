package main

import (
	"log"
	"net/http"

	"github.com/gorilla/mux"
	"github.com/streadway/amqp"
)

var connect amqp.Connection

func main() {
	router := mux.NewRouter()
	router.HandleFunc("/login", signin).Methods("POST", "OPTIONS")
	//Everything with /s/.. requires you to login
	sec := router.PathPrefix("/s").Subrouter()
	sec.Use(auth)
	sec.HandleFunc("/refresh", refresh).Methods("GET", "OPTIONS")
	sec.HandleFunc("/motion", allMotion).Methods("GET", "OPTIONS")
	sec.HandleFunc("/motion/{code}", getMotion).Methods("DELETE", "GET", "OPTIONS")
	sec.HandleFunc("/stream/{code}", getVideo).Methods("GET", "OPTIONS")
	sec.HandleFunc("/service/motion", getVideo).Methods("GET", "OPTIONS")
	sec.HandleFunc("/service/door", getVideo).Methods("GET", "OPTIONS")
	sec.HandleFunc("/config/{service}", setConfig).Methods("POST", "OPTIONS")
	sec.HandleFunc("/config/{service}", getConfig).Methods("GET", "OPTIONS")

	log.Fatal(http.ListenAndServe(":8000", router))
}