const WebSocket = require('ws');
const http = require('http');
const express = require('express');
const chalk = require('chalk');

const app = express();
const server = http.createServer(app);
const wss = new WebSocket.Server({ server });

const PORT = process.env.PORT || 8080;
const clients = new Map();
const activeCalls = new Map(); // Track active video calls

console.clear();
console.log(chalk.cyan('╔════════════════════════════════════════════════════════════╗'));
console.log(chalk.cyan('║       LearnTogether Real-time Server                       ║'));
console.log(chalk.cyan('║       WebSocket Communication Platform                     ║'));
console.log(chalk.cyan('║       with Video Call & Session Tracking                   ║'));
console.log(chalk.cyan('╚════════════════════════════════════════════════════════════╝\n'));

wss.on('connection', (ws) => {
    console.log(chalk.yellow('\nNew Connection Initiated...'));
    let clientData = {
        ws: ws,
        userId: null,
        userType: null,
        username: null,
        connectedAt: new Date()
    };

    ws.on('message', (message) => {
        try {
            const data = JSON.parse(message);
            
            switch (data.type) {
                case 'auth':
                    handleAuth(ws, clientData, data);
                    break;
                case 'notification':
                    handleNotification(clientData, data);
                    break;
                case 'message':
                    handleMessage(clientData, data);
                    break;
                case 'status':
                    handleStatusUpdate(clientData, data);
                    break;
                case 'request':
                    handleRequest(clientData, data);
                    break;
                case 'call_start':
                    handleCallStart(clientData, data);
                    break;
                case 'call_end':
                    handleCallEnd(clientData, data);
                    break;
                case 'call_update':
                    handleCallUpdate(clientData, data);
                    break;
                case 'ping':
                    ws.send(JSON.stringify({ type: 'pong' }));
                    break;
            }
        } catch (error) {
            console.error(chalk.red('Error:'), error.message);
        }
    });

    ws.on('close', () => {
        if (clientData.userId) {
            console.log(chalk.red(`\n${clientData.username} (${clientData.userType}) LOGGED OUT`));
            console.log(chalk.gray(`   ID: ${clientData.userId}`));
            console.log(chalk.gray(`   Time: ${new Date().toLocaleTimeString()}`));
            
            clients.delete(clientData.userId);
            broadcastStatusUpdate({
                userId: clientData.userId,
                username: clientData.username,
                status: 'offline'
            });
        }
        console.log(chalk.gray(`   Total connected: ${clients.size} users\n`));
    });

    ws.on('error', (error) => {
        console.error(chalk.red('WebSocket Error:'), error);
    });
});

function handleAuth(ws, clientData, data) {
    const { userId, userType, username } = data;
    
    if (!userId || !userType || !username) {
        ws.send(JSON.stringify({
            type: 'error',
            message: 'Missing authentication data'
        }));
        return;
    }

    clientData.userId = userId;
    clientData.userType = userType;
    clientData.username = username;
    
    clients.set(userId, clientData);
    
    ws.send(JSON.stringify({
        type: 'auth_success',
        message: 'Connected successfully',
        userId: userId
    }));

    console.log(chalk.green(`\n${username.toUpperCase()} (${userType.toUpperCase()}) LOGGED IN`));
    console.log(chalk.gray(`   ID: ${userId}`));
    console.log(chalk.gray(`   Time: ${new Date().toLocaleTimeString()}`));
    console.log(chalk.gray(`   Total connected: ${clients.size} users\n`));

    broadcastStatusUpdate({
        userId: userId,
        username: username,
        userType: userType,
        status: 'online'
    });
}

function handleNotification(senderData, data) {
    const { recipientId, title, message, notificationType } = data;
    const recipient = clients.get(recipientId);
    
    if (recipient && recipient.ws.readyState === WebSocket.OPEN) {
        recipient.ws.send(JSON.stringify({
            type: 'notification',
            from: senderData.username,
            fromId: senderData.userId,
            title: title,
            message: message,
            notificationType: notificationType,
            timestamp: new Date().toISOString()
        }));
        
        console.log(chalk.blue(`Notification sent from ${senderData.username} to ${recipient.username}`));
    }
}

function handleMessage(senderData, data) {
    const { recipientId, message } = data;
    const recipient = clients.get(recipientId);
    
    if (recipient && recipient.ws.readyState === WebSocket.OPEN) {
        recipient.ws.send(JSON.stringify({
            type: 'message',
            from: senderData.username,
            fromId: senderData.userId,
            message: message,
            timestamp: new Date().toISOString()
        }));
        
        console.log(chalk.cyan(`Message: ${senderData.username} -> ${recipient.username}`));
        console.log(chalk.gray(`   "${message.substring(0, 50)}..."\n`));
    }
}

function handleStatusUpdate(clientData, data) {
    const { status } = data;
    broadcastStatusUpdate({
        userId: clientData.userId,
        username: clientData.username,
        userType: clientData.userType,
        status: status
    });
    
    console.log(chalk.yellow(`${clientData.username} is now ${status.toUpperCase()}\n`));
}

/**
 * Handle video call start
 */
function handleCallStart(senderData, data) {
    const { recipientId, channelName, subject } = data;
    const recipient = clients.get(recipientId);
    
    if (recipient && recipient.ws.readyState === WebSocket.OPEN) {
        recipient.ws.send(JSON.stringify({
            type: 'call_incoming',
            from: senderData.username,
            fromId: senderData.userId,
            fromType: senderData.userType,
            subject: subject,
            channelName: channelName,
            timestamp: new Date().toISOString()
        }));
    }
    
    const callId = `${senderData.userId}_${recipientId}_${Date.now()}`;
    activeCalls.set(callId, {
        callId: callId,
        caller: senderData.username,
        callerId: senderData.userId,
        callerType: senderData.userType,
        recipient: recipient?.username || 'Unknown',
        recipientId: recipientId,
        subject: subject,
        channelName: channelName,
        startTime: new Date(),
        status: 'ringing'
    });
    
    console.log(chalk.magenta.bold(`\nVIDEO CALL INITIATED`));
    console.log(chalk.cyan(`   From: ${senderData.username} (${senderData.userType})`));
    console.log(chalk.cyan(`   To: ${recipient?.username || 'Unknown'}`));
    console.log(chalk.cyan(`   Subject: ${subject}`));
    console.log(chalk.gray(`   Channel: ${channelName}`));
    console.log(chalk.gray(`   Time: ${new Date().toLocaleTimeString()}`));
    console.log(chalk.gray(`   Status: RINGING\n`));
}

/**
 * Handle video call update (accepted, rejected, etc.)
 */
function handleCallUpdate(senderData, data) {
    const { callId, status, recipientId } = data;
    const recipient = clients.get(recipientId);
    
    if (recipient && recipient.ws.readyState === WebSocket.OPEN) {
        recipient.ws.send(JSON.stringify({
            type: 'call_update',
            callId: callId,
            status: status,
            from: senderData.username,
            timestamp: new Date().toISOString()
        }));
    }
    
    const call = activeCalls.get(callId);
    if (call) {
        call.status = status;
        
        if (status === 'accepted') {
            console.log(chalk.green.bold(`\nCALL ACCEPTED`));
            console.log(chalk.green(`   ${senderData.username} accepted call from ${call.caller}`));
            console.log(chalk.gray(`   Connected: ${new Date().toLocaleTimeString()}\n`));
        } else if (status === 'rejected') {
            console.log(chalk.red.bold(`\nCALL REJECTED`));
            console.log(chalk.red(`   ${senderData.username} rejected call from ${call.caller}\n`));
            activeCalls.delete(callId);
        }
    }
}

/**
 * Handle video call end
 */
function handleCallEnd(senderData, data) {
    const { callId, recipientId } = data;
    const recipient = clients.get(recipientId);
    
    if (recipient && recipient.ws.readyState === WebSocket.OPEN) {
        recipient.ws.send(JSON.stringify({
            type: 'call_ended',
            callId: callId,
            from: senderData.username,
            timestamp: new Date().toISOString()
        }));
    }
    
    const call = activeCalls.get(callId);
    if (call) {
        const duration = Math.round((new Date() - call.startTime) / 1000);
        const minutes = Math.floor(duration / 60);
        const seconds = duration % 60;
        
        console.log(chalk.yellow.bold(`\nCALL ENDED`));
        console.log(chalk.yellow(`   Between: ${call.caller} & ${call.recipient}`));
        console.log(chalk.yellow(`   Subject: ${call.subject}`));
        console.log(chalk.green(`   Duration: ${minutes}m ${seconds}s`));
        console.log(chalk.gray(`   Ended: ${new Date().toLocaleTimeString()}\n`));
        
        activeCalls.delete(callId);
    }
}

function handleRequest(senderData, data) {
    const { recipientId, subject, requestType } = data;
    const recipient = clients.get(recipientId);
    
    if (recipient && recipient.ws.readyState === WebSocket.OPEN) {
        recipient.ws.send(JSON.stringify({
            type: 'request',
            from: senderData.username,
            fromId: senderData.userId,
            fromType: senderData.userType,
            subject: subject,
            requestType: requestType,
            timestamp: new Date().toISOString()
        }));
        
        console.log(chalk.magenta(`New Request: ${senderData.username} -> ${recipient.username}`));
        console.log(chalk.gray(`   Subject: ${subject} (${requestType})\n`));
    }
}

function broadcastStatusUpdate(statusData) {
    const message = JSON.stringify({
        type: 'status_update',
        data: statusData
    });

    clients.forEach((client) => {
        if (client.ws.readyState === WebSocket.OPEN) {
            client.ws.send(message);
        }
    });
}

app.get('/api/stats', (req, res) => {
    const onlineUsers = Array.from(clients.values()).map(c => ({
        userId: c.userId,
        username: c.username,
        userType: c.userType,
        connectedAt: c.connectedAt
    }));
    
    const activeCalls_list = Array.from(activeCalls.values()).map(call => ({
        callId: call.callId,
        caller: call.caller,
        recipient: call.recipient,
        subject: call.subject,
        duration: Math.round((new Date() - call.startTime) / 1000),
        status: call.status,
        startTime: call.startTime
    }));
    
    res.json({
        timestamp: new Date().toISOString(),
        totalConnected: clients.size,
        activeCallSessions: activeCalls.size,
        users: onlineUsers,
        calls: activeCalls_list
    });
});

server.listen(PORT, () => {
    console.log(chalk.green(`\nServer running on ws://localhost:${PORT}`));
    console.log(chalk.green(`Stats available at http://localhost:${PORT}/api/stats\n`));
    console.log(chalk.gray('═'.repeat(60)));
    console.log(chalk.cyan('Waiting for connections...\n'));
});

process.on('SIGINT', () => {
    console.log(chalk.yellow('\n\nShutting down server...'));
    wss.clients.forEach((client) => {
        client.close();
    });
    server.close(() => {
        console.log(chalk.red('Server closed\n'));
        process.exit(0);
    });
});
