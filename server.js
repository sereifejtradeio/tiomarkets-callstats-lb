const express = require('express');
const SocketServer = require('ws').Server;
const path = require('path');
const url = require('url');
const execPHP = require('./execphp.js')();
execPHP.phpFolder = './';

const PORT = process.env.PORT || 3000;
const INDEX = path.join(__dirname, 'index.html');

const server = express()
    .use((req, res) => {
        res.header("Access-Control-Allow-Origin", "http://tio-salescallstats.com");
        res.header(
            "Access-Control-Allow-Headers", "Origin, X-Requested-With, Content-Type, Accept, Authorization"
        );

        if( req.method === "OPTIONS" ) {
            res.header("Access-Control-Allow-Methods", "PUT, POST, PATCH, DELETE, GET");
            return res.status(200).json({});
        }

        let request_ip = req.headers['x-forwarded-for'];

        console.log("Request IP: " + request_ip);

        if (request_ip === '107.180.46.203') {
            res.sendFile(INDEX);
        } else {
            res.send(403, 'IP not allowed!');
        }
    })
    .listen(PORT, () => console.log(`Listening on ${ PORT }`));

const wss = new SocketServer({ server });

wss.on('connection', (ws) => {
    console.log('Client connected');
    ws.on('close', () => console.log('Client disconnected'));
    
    setInterval(() => {
        wss.clients.forEach((client) => {
            client.send(JSON.stringify(new Date().toTimeString()));
        });
    }, 1000);

    execPHP.parseFile('process.php',function(phpResult) {
        console.log(phpResult);
        if(phpResult) {
            wss.clients.forEach((client) => {
                // if (client !== ws && client.readyState === WebSocket.OPEN) {
                //     client.send(phpResult);
                // }
                client.send(phpResult);
            });
        }
    });

    setInterval(() => {
        execPHP.parseFile('process.php',function(phpResult) {
            console.log(phpResult);
            if(phpResult) {
                wss.clients.forEach((client) => {
                    // if (client !== ws && client.readyState === WebSocket.OPEN) {
                    //     client.send(phpResult);
                    // }
                    client.send(phpResult);
                });
            }
        });

    }, 900000); //15 Minutes
});