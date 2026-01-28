/**
 * LearnTogether WebSocket Client
 * Client-side real-time communication
 */

class WebSocketClient {
    constructor(config = {}) {
        this.url = config.url || 'ws://localhost:8080';
        this.ws = null;
        this.userId = null;
        this.userType = null;
        this.username = null;
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
        this.reconnectDelay = 3000;
        
        this.onConnect = config.onConnect || (() => {});
        this.onDisconnect = config.onDisconnect || (() => {});
        this.onNotification = config.onNotification || (() => {});
        this.onMessage = config.onMessage || (() => {});
        this.onStatusUpdate = config.onStatusUpdate || (() => {});
        this.onRequest = config.onRequest || (() => {});
        this.onCallIncoming = config.onCallIncoming || (() => {});
        this.onCallEnded = config.onCallEnded || (() => {});
        this.onError = config.onError || (() => {});
    }

    connect(userId, userType, username) {
        this.userId = userId;
        this.userType = userType;
        this.username = username;

        try {
            this.ws = new WebSocket(this.url);

            this.ws.onopen = () => {
                console.log('Connected to server');
                this.reconnectAttempts = 0;
                
                this.send({
                    type: 'auth',
                    userId: this.userId,
                    userType: this.userType,
                    username: this.username
                });

                this.onConnect();
                this.updateStatusIndicator('online');
            };

            this.ws.onmessage = (event) => {
                this.handleMessage(event.data);
            };

            this.ws.onerror = (error) => {
                console.error('❌ WebSocket error:', error);
                this.onError(error);
            };

            this.ws.onclose = () => {
                console.log('❌ Disconnected');
                this.onDisconnect();
                this.updateStatusIndicator('offline');
                this.attemptReconnect();
            };
        } catch (error) {
            console.error('Connection failed:', error);
            this.onError(error);
        }
    }

    handleMessage(data) {
        try {
            const message = JSON.parse(data);

            switch (message.type) {
                case 'auth_success':
                    console.log('Authentication successful');
                    break;

                case 'notification':
                    this.onNotification(message);
                    this.displayNotification(message);
                    break;

                case 'message':
                    this.onMessage(message);
                    break;

                case 'status_update':
                    this.onStatusUpdate(message.data);
                    break;

                case 'request':
                    this.onRequest(message);
                    this.displayNotification({
                        title: `New ${message.requestType}`,
                        message: `${message.from}: ${message.subject}`,
                        notificationType: 'request'
                    });
                    break;

                case 'call_incoming':
                    this.onCallIncoming(message);
                    this.displayNotification({
                        title: 'Incoming Call',
                        message: `${message.from} is calling about ${message.subject}`,
                        notificationType: 'call'
                    });
                    break;

                case 'call_update':
                    console.log('Call status:', message.status);
                    break;

                case 'call_ended':
                    this.onCallEnded(message);
                    this.displayNotification({
                        title: 'Call Ended',
                        message: `Call with ${message.from} has ended`,
                        notificationType: 'info'
                    });
                    break;

                case 'pong':
                    // Server is alive
                    break;

                case 'error':
                    console.error('Server error:', message.message);
                    this.onError(message);
                    break;
            }
        } catch (error) {
            console.error('Error handling message:', error);
        }
    }

    send(data) {
        if (this.ws && this.ws.readyState === WebSocket.OPEN) {
            this.ws.send(JSON.stringify(data));
        } else {
            console.warn('WebSocket not connected');
        }
    }

    sendNotification(recipientId, title, message, notificationType = 'general') {
        this.send({
            type: 'notification',
            recipientId: recipientId,
            title: title,
            message: message,
            notificationType: notificationType
        });
    }

    sendMessage(recipientId, message) {
        this.send({
            type: 'message',
            recipientId: recipientId,
            message: message
        });
    }

    sendRequest(recipientId, subject, requestType = 'session') {
        this.send({
            type: 'request',
            recipientId: recipientId,
            subject: subject,
            requestType: requestType
        });
    }

    sendCallStart(recipientId, channelName, subject) {
        this.send({
            type: 'call_start',
            recipientId: recipientId,
            channelName: channelName,
            subject: subject
        });
    }

    sendCallUpdate(callId, recipientId, status) {
        this.send({
            type: 'call_update',
            callId: callId,
            recipientId: recipientId,
            status: status
        });
    }

    sendCallEnd(callId, recipientId) {
        this.send({
            type: 'call_end',
            callId: callId,
            recipientId: recipientId
        });
    }

    updateStatus(status) {
        this.send({
            type: 'status',
            status: status
        });
    }

    attemptReconnect() {
        if (this.reconnectAttempts < this.maxReconnectAttempts) {
            this.reconnectAttempts++;
            console.log(`🔄 Reconnecting... (${this.reconnectAttempts}/${this.maxReconnectAttempts})`);
            
            setTimeout(() => {
                this.connect(this.userId, this.userType, this.username);
            }, this.reconnectDelay);
        }
    }

    disconnect() {
        if (this.ws) {
            this.ws.close();
        }
    }

    isConnected() {
        return this.ws && this.ws.readyState === WebSocket.OPEN;
    }

    updateStatusIndicator(status) {
        const indicator = document.querySelector('.ws-status-indicator');
        if (indicator) {
            indicator.classList.remove('online', 'offline');
            indicator.classList.add(status);
            indicator.title = `Real-time: ${status}`;
        }
    }

    displayNotification(notifData) {
        if (document.hidden && 'Notification' in window) {
            if (Notification.permission === 'granted') {
                new Notification(notifData.title, {
                    body: notifData.message,
                    icon: '/LearnTogether/images/LT.png'
                });
            }
        }

        // In-app notification
        const container = document.querySelector('.notifications-container') || 
                         this.createNotificationContainer();
        
        const notifEl = document.createElement('div');
        notifEl.className = `notification notification-${notifData.notificationType || 'general'}`;
        notifEl.innerHTML = `
            <div class="notification-content">
                <h4>${notifData.title}</h4>
                <p>${notifData.message}</p>
            </div>
            <button class="notification-close">×</button>
        `;
        
        notifEl.querySelector('.notification-close').onclick = () => notifEl.remove();
        container.appendChild(notifEl);
        
        setTimeout(() => notifEl.remove(), 5000);
    }

    createNotificationContainer() {
        const container = document.createElement('div');
        container.className = 'notifications-container';
        document.body.appendChild(container);
        return container;
    }
}

if ('Notification' in window && Notification.permission === 'default') {
    Notification.requestPermission();
}
