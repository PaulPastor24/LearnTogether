/**
 * LearnTogether WebSocket Client
 * Handles real-time communication between tutors and learners
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
        
        // Event callbacks
        this.onConnect = config.onConnect || (() => {});
        this.onDisconnect = config.onDisconnect || (() => {});
        this.onNotification = config.onNotification || (() => {});
        this.onMessage = config.onMessage || (() => {});
        this.onStatusUpdate = config.onStatusUpdate || (() => {});
        this.onRequest = config.onRequest || (() => {});
        this.onError = config.onError || (() => {});
    }

    /**
     * Connect to WebSocket server
     */
    connect(userId, userType, username) {
        this.userId = userId;
        this.userType = userType;
        this.username = username;

        try {
            this.ws = new WebSocket(this.url);

            this.ws.onopen = () => {
                console.log('WebSocket connected');
                this.reconnectAttempts = 0;
                
                // Authenticate
                this.send({
                    type: 'auth',
                    userId: this.userId,
                    userType: this.userType,
                    username: this.username
                });

                this.onConnect();
            };

            this.ws.onmessage = (event) => {
                this.handleMessage(event.data);
            };

            this.ws.onerror = (error) => {
                console.error('WebSocket error:', error);
                this.onError(error);
            };

            this.ws.onclose = () => {
                console.log('WebSocket disconnected');
                this.onDisconnect();
                this.attemptReconnect();
            };
        } catch (error) {
            console.error('Connection failed:', error);
            this.onError(error);
        }
    }

    /**
     * Handle incoming messages
     */
    handleMessage(data) {
        try {
            const message = JSON.parse(data);

            switch (message.type) {
                case 'auth_success':
                    console.log('Authentication successful');
                    break;

                case 'notification':
                    this.onNotification(message);
                    this.showNotification(message);
                    break;

                case 'message':
                    this.onMessage(message);
                    break;

                case 'status_update':
                    this.onStatusUpdate(message.data);
                    break;

                case 'request':
                    this.onRequest(message);
                    this.showNotification({
                        title: `New ${message.requestType} request`,
                        message: `${message.from} requested: ${message.subject}`,
                        notificationType: 'request'
                    });
                    break;

                case 'message_sent':
                    console.log('Message delivered');
                    break;

                case 'error':
                    console.error('Server error:', message.message);
                    this.onError(message);
                    break;

                default:
                    console.log('Unknown message type:', message.type);
            }
        } catch (error) {
            console.error('Error handling message:', error);
        }
    }

    /**
     * Send message to server
     */
    send(data) {
        if (this.ws && this.ws.readyState === WebSocket.OPEN) {
            this.ws.send(JSON.stringify(data));
        } else {
            console.warn('WebSocket not connected');
        }
    }

    /**
     * Send notification to specific user
     */
    sendNotification(recipientId, title, message, notificationType = 'general') {
        this.send({
            type: 'notification',
            recipientId: recipientId,
            title: title,
            message: message,
            notificationType: notificationType
        });
    }

    /**
     * Send direct message
     */
    sendMessage(recipientId, message, conversationId = null) {
        this.send({
            type: 'message',
            recipientId: recipientId,
            message: message,
            conversationId: conversationId
        });
    }

    /**
     * Send tutor/session request
     */
    sendRequest(recipientId, subject, requestType = 'session', details = {}) {
        this.send({
            type: 'request',
            recipientId: recipientId,
            subject: subject,
            requestType: requestType,
            details: details
        });
    }

    /**
     * Update user status
     */
    updateStatus(status) {
        this.send({
            type: 'status',
            status: status
        });
    }

    /**
     * Attempt to reconnect
     */
    attemptReconnect() {
        if (this.reconnectAttempts < this.maxReconnectAttempts) {
            this.reconnectAttempts++;
            console.log(`🔄 Reconnecting... (${this.reconnectAttempts}/${this.maxReconnectAttempts})`);
            
            setTimeout(() => {
                this.connect(this.userId, this.userType, this.username);
            }, this.reconnectDelay);
        } else {
            console.error('Max reconnection attempts reached');
        }
    }

    /**
     * Disconnect from server
     */
    disconnect() {
        if (this.ws) {
            this.ws.close();
        }
    }

    /**
     * Check if connected
     */
    isConnected() {
        return this.ws && this.ws.readyState === WebSocket.OPEN;
    }

    /**
     * Show browser notification
     */
    showNotification(notificationData) {
        // Only show if not already focused on the page
        if (document.hidden && 'Notification' in window) {
            if (Notification.permission === 'granted') {
                new Notification(notificationData.title, {
                    body: notificationData.message,
                    icon: '/LearnTogether/images/LT.png'
                });
            }
        }
    }
}

// Request notification permission
if ('Notification' in window && Notification.permission === 'default') {
    Notification.requestPermission();
}

// Export for use in PHP pages
if (typeof module !== 'undefined' && module.exports) {
    module.exports = WebSocketClient;
}
