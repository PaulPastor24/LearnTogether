const WebSocket = require('ws');
const express = require('express');
const http = require('http');

const app = express();
const server = http.createServer(app);
const wss = new WebSocket.Server({ server });

const PORT = process.env.PORT || 8080;

// Store connected clients with their user info
const clients = new Map();

// Connection handler
wss.on('connection', (ws) => {
    console.log('New client connected');
    
    let clientData = {
        ws: ws,
        userId: null,
        userType: null, // 'tutor' or 'learner'
        username: null
    };

    // Handle incoming messages
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
                
                default:
                    console.log('Unknown message type:', data.type);
            }
        } catch (error) {
            console.error('Error parsing message:', error);
            ws.send(JSON.stringify({
                type: 'error',
                message: 'Invalid message format'
            }));
        }
    });

    // Handle client disconnect
    ws.on('close', () => {
        if (clientData.userId) {
            console.log(`Client ${clientData.username} disconnected`);
            clients.delete(clientData.userId);
            
            // Notify others that user went offline
            broadcastStatusUpdate({
                userId: clientData.userId,
                username: clientData.username,
                status: 'offline'
            });
        }
    });

    // Handle errors
    ws.on('error', (error) => {
        console.error('WebSocket error:', error);
    });
});

/**
 * Handle user authentication
 */
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
    
    // Send confirmation
    ws.send(JSON.stringify({
        type: 'auth_success',
        message: 'Connected successfully',
        userId: userId
    }));

    console.log(`${userType.toUpperCase()} ${username} (ID: ${userId}) connected`);

    // Notify others that user came online
    broadcastStatusUpdate({
        userId: userId,
        username: username,
        userType: userType,
        status: 'online'
    });
}

/**
 * Handle notifications
 */
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
            notificationType: notificationType, // 'request', 'message', 'status', etc.
            timestamp: new Date().toISOString()
        }));
        
        console.log(`Notification sent to ${recipient.username}`);
    } else {
        console.log(`Recipient ${recipientId} not online`);
    }
}

/**
 * Handle direct messages
 */
function handleMessage(senderData, data) {
    const { recipientId, message, conversationId } = data;
    
    const recipient = clients.get(recipientId);
    
    if (recipient && recipient.ws.readyState === WebSocket.OPEN) {
        recipient.ws.send(JSON.stringify({
            type: 'message',
            from: senderData.username,
            fromId: senderData.userId,
            message: message,
            conversationId: conversationId,
            timestamp: new Date().toISOString()
        }));
        
        // Send receipt confirmation
        senderData.ws.send(JSON.stringify({
            type: 'message_sent',
            conversationId: conversationId,
            timestamp: new Date().toISOString()
        }));
    }
}

/**
 * Handle status updates (online/offline)
 */
function handleStatusUpdate(clientData, data) {
    const { status } = data;
    
    broadcastStatusUpdate({
        userId: clientData.userId,
        username: clientData.username,
        userType: clientData.userType,
        status: status
    });
}

/**
 * Handle session/tutor requests
 */
function handleRequest(senderData, data) {
    const { recipientId, subject, requestType, details } = data;
    
    const recipient = clients.get(recipientId);
    
    if (recipient && recipient.ws.readyState === WebSocket.OPEN) {
        recipient.ws.send(JSON.stringify({
            type: 'request',
            from: senderData.username,
            fromId: senderData.userId,
            fromType: senderData.userType,
            subject: subject,
            requestType: requestType, // 'session', 'booking', etc.
            details: details,
            timestamp: new Date().toISOString()
        }));
    }
}

/**
 * Broadcast status update to all connected clients
 */
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

/**
 * Get online users list
 */
function getOnlineUsers() {
    const onlineUsers = [];
    clients.forEach((client) => {
        onlineUsers.push({
            userId: client.userId,
            username: client.username,
            userType: client.userType,
            status: 'online'
        });
    });
    return onlineUsers;
}

// Express routes for REST API
app.get('/api/online-users', (req, res) => {
    res.json(getOnlineUsers());
});

app.get('/api/user-status/:userId', (req, res) => {
    const userId = req.params.userId;
    const client = clients.get(userId);
    
    if (client) {
        res.json({
            userId: userId,
            status: 'online'
        });
    } else {
        res.json({
            userId: userId,
            status: 'offline'
        });
    }
});

// Start server
server.listen(PORT, () => {
    console.log(`WebSocket server running on ws://localhost:${PORT}`);
    console.log(`REST API available at http://localhost:${PORT}/api`);
});

// Graceful shutdown
process.on('SIGTERM', () => {
    console.log('SIGTERM received, shutting down gracefully');
    server.close(() => {
        console.log('Server closed');
        process.exit(0);
    });
});
