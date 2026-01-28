/**
 * WebSocket Configuration
 * Configure the WebSocket connection settings
 */

const WEBSOCKET_CONFIG = {
    // Server URL - change to your server address in production
    url: 'ws://localhost:8080',
    
    // Maximum reconnection attempts
    maxReconnectAttempts: 5,
    
    // Delay between reconnection attempts (ms)
    reconnectDelay: 3000,
    
    // Enable console logging
    debug: true
};

/**
 * Initialize WebSocket Client
 * Call this function in your PHP page with the user data
 */
function initializeWebSocket(userId, userType, username) {
    if (typeof WebSocketClient === 'undefined') {
        console.error('WebSocketClient not loaded');
        return null;
    }

    const wsClient = new WebSocketClient({
        url: WEBSOCKET_CONFIG.url,
        
        onConnect: () => {
            console.log('Connected to real-time server');
            updateUserStatusIndicator('online');
        },
        
        onDisconnect: () => {
            console.log('Disconnected from server');
            updateUserStatusIndicator('offline');
        },
        
        onNotification: (notification) => {
            console.log('Notification received:', notification);
            displayNotificationUI(notification);
        },
        
        onMessage: (message) => {
            console.log('Message received:', message);
            handleNewMessage(message);
        },
        
        onStatusUpdate: (status) => {
            console.log('Status update:', status);
            updateOnlineUserList(status);
        },
        
        onRequest: (request) => {
            console.log('Request received:', request);
            handleNewRequest(request);
        },
        
        onError: (error) => {
            console.error('WebSocket error:', error);
        }
    });

    // Connect to server
    wsClient.connect(userId, userType, username);
    
    return wsClient;
}

/**
 * Update user status indicator
 */
function updateUserStatusIndicator(status) {
    const indicator = document.querySelector('.user-status-indicator');
    if (indicator) {
        if (status === 'online') {
            indicator.classList.add('online');
            indicator.classList.remove('offline');
            indicator.title = 'Online';
        } else {
            indicator.classList.add('offline');
            indicator.classList.remove('online');
            indicator.title = 'Offline';
        }
    }
}

/**
 * Display notification in UI
 */
function displayNotificationUI(notification) {
    // Create notification element
    const notificationEl = document.createElement('div');
    notificationEl.className = `notification notification-${notification.notificationType}`;
    notificationEl.innerHTML = `
        <div class="notification-content">
            <h4>${notification.title}</h4>
            <p>${notification.message}</p>
            <small>${new Date(notification.timestamp).toLocaleTimeString()}</small>
        </div>
        <button class="notification-close" onclick="this.parentElement.remove()">×</button>
    `;
    
    // Add to notification container
    let container = document.querySelector('.notifications-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'notifications-container';
        document.body.appendChild(container);
    }
    
    container.appendChild(notificationEl);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        notificationEl.remove();
    }, 5000);
}

/**
 * Handle new message
 */
function handleNewMessage(message) {
    // Implement based on your chat UI
    console.log(`New message from ${message.from}: ${message.message}`);
}

/**
 * Handle new request
 */
function handleNewRequest(request) {
    // Implement based on your request UI
    console.log(`New request from ${request.from}: ${request.subject}`);
}

/**
 * Update online user list
 */
function updateOnlineUserList(statusData) {
    const userEl = document.querySelector(`[data-user-id="${statusData.userId}"]`);
    if (userEl) {
        const statusIndicator = userEl.querySelector('.status-indicator');
        if (statusIndicator) {
            if (statusData.status === 'online') {
                statusIndicator.classList.add('online');
                statusIndicator.classList.remove('offline');
            } else {
                statusIndicator.classList.add('offline');
                statusIndicator.classList.remove('online');
            }
        }
    }
}

// CSS for notifications (add to your stylesheet)
const notificationStyles = `
<style>
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

.notification.notification-status {
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
    margin: 0 0 4px 0;
    font-size: 13px;
    color: #6b7280;
}

.notification-content small {
    font-size: 12px;
    color: #9ca3af;
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

.status-indicator {
    display: inline-block;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    margin-right: 6px;
    background: #9ca3af;
}

.status-indicator.online {
    background: #10b981;
    box-shadow: 0 0 6px rgba(16, 185, 129, 0.6);
}

.status-indicator.offline {
    background: #6b7280;
}

.user-status-indicator {
    display: inline-block;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    margin-left: 8px;
    background: #9ca3af;
}

.user-status-indicator.online {
    background: #10b981;
    box-shadow: 0 0 8px rgba(16, 185, 129, 0.7);
}

.user-status-indicator.offline {
    background: #6b7280;
}
</style>
`;
