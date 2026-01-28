/**
 * WebSocket Client Configuration
 * Include in your PHP pages
 */

const WEBSOCKET_CONFIG = {
    url: 'ws://localhost:8080',
    maxReconnectAttempts: 5,
    reconnectDelay: 3000
};

// Initialize WebSocket
function initializeWebSocket(userId, userType, username) {
    if (typeof WebSocketClient === 'undefined') {
        console.error('WebSocketClient not loaded');
        return null;
    }

    const wsClient = new WebSocketClient({
        url: WEBSOCKET_CONFIG.url,
        
        onConnect: () => {
            console.log('Real-time connection established');
        },
        
        onDisconnect: () => {
            console.log('Real-time connection lost');
        },
        
        onNotification: (notification) => {
            console.log('Notification:', notification);
        },
        
        onMessage: (message) => {
            console.log('Message:', message);
        },
        
        onStatusUpdate: (status) => {
            console.log('Status:', status);
        },
        
        onRequest: (request) => {
            console.log('Request:', request);
        },
        
        onError: (error) => {
            console.error('Error:', error);
        }
    });

    wsClient.connect(userId, userType, username);
    return wsClient;
}

// CSS Styles
const notificationStyles = `
<style>
.ws-status-indicator {
    display: inline-block;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    margin-left: 8px;
    background: #9ca3af;
    transition: all 0.3s ease;
}

.ws-status-indicator.online {
    background: #10b981;
    box-shadow: 0 0 8px rgba(16, 185, 129, 0.7);
}

.ws-status-indicator.offline {
    background: #6b7280;
}

.notifications-container {
    position: fixed;
    top: 90px;
    right: 24px;
    z-index: 1000;
    max-width: 350px;
}

.notification {
    background: white;
    border-radius: 10px;
    padding: 16px;
    margin-bottom: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    border-left: 4px solid #0f766e;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
    animation: slideIn 0.3s ease-out;
}

.notification.notification-request {
    border-left-color: #3b82f6;
}

.notification.notification-message {
    border-left-color: #10b981;
}

.notification.notification-general {
    border-left-color: #f59e0b;
}

.notification-content {
    flex: 1;
}

.notification-content h4 {
    margin: 0 0 4px 0;
    font-size: 14px;
    font-weight: 600;
    color: #065f46;
}

.notification-content p {
    margin: 0;
    font-size: 13px;
    color: #6b7280;
}

.notification-close {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 20px;
    color: #9ca3af;
    padding: 0;
    line-height: 1;
}

.notification-close:hover {
    color: #6b7280;
}

@keyframes slideIn {
    from {
        transform: translateX(400px);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}
</style>
`;
