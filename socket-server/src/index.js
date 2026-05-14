require('dotenv').config({ path: '../.env' });
const express = require('express');
const http = require('http');
const { Server } = require('socket.io');
const axios = require('axios');
const crypto = require('crypto');
const logger = require('./logger');
const { validateInternalSignature } = require('./middleware/auth');
const { verifySocketToken } = require('./auth');

const app = express();
app.use(express.json());

const server = http.createServer(app);
const io = new Server(server, {
    cors: {
        origin: "*",
        methods: ["GET", "POST"]
    }
});

// Internal communication should always prefer localhost to avoid tunnel loopback issues
const APP_URL = process.env.APP_URL || 'http://127.0.0.1:8000';
const INTERNAL_APP_URL = 'http://127.0.0.1:8000'; 
const PORT = process.env.SOCKET_IO_PORT || 3001;
const INTERNAL_SECRET = process.env.SOCKET_INTERNAL_SECRET;

const activeUsers = new Map(); // userId -> Set of socketIds

const getActualSecret = () => {
    if (!INTERNAL_SECRET) return null;
    return INTERNAL_SECRET.startsWith('base64:') 
        ? Buffer.from(INTERNAL_SECRET.substring(7), 'base64') 
        : INTERNAL_SECRET;
};

const updateLaravelStatus = async (userId, status) => {
    const secret = getActualSecret();
    if (!secret) return null;

    const payload = { user_id: userId, status };
    const jsonPayload = JSON.stringify(payload);
    const signature = 'sha256=' + crypto.createHmac('sha256', secret).update(jsonPayload).digest('hex');

    try {
        const response = await axios.post(`${INTERNAL_APP_URL}/api/internal/user/status`, payload, {
            headers: {
                'X-Hub-Signature-256': signature,
                'Content-Type': 'application/json'
            }
        });
        return response.data;
    } catch (err) {
        logger.error({ userId, status, err: err.message }, 'Failed to update user status in Laravel');
        return null;
    }
};

// Auth middleware
io.use((socket, next) => {
    const token = socket.handshake.auth.token;
    const user = verifySocketToken(token);
    if (!user) return next(new Error('unauthorized'));
    socket.user = user;
    next();
});

// Room tracking registry (RoomName -> Map(userId -> socketCount))
const roomMemberRegistry = new Map();

const broadcastRoomUsers = (io, conversationId) => {
    const roomName = `conversation:${conversationId}`;
    const registry = roomMemberRegistry.get(roomName);
    const userIds = registry ? Array.from(registry.keys()) : [];
    
    io.to(roomName).emit('conversation:users', {
        conversationId,
        userIds
    });
    logger.info({ conversationId, userCount: userIds.length }, 'Broadcasted room users');
};

io.on('connection', async (socket) => {
    const userId = String(socket.user.user_id);
    
    if (!activeUsers.has(userId)) {
        activeUsers.set(userId, new Set());
    }
    const userSockets = activeUsers.get(userId);
    userSockets.add(socket.id);

    socket.join(`user:${userId}`);
    socket.join('public');
    socket.join('global');

    // If first connection for this user
    if (userSockets.size === 1) {
        updateLaravelStatus(userId, 'online').then(data => {
            io.emit('user:online', { 
                userId, 
                last_active: data?.last_active 
            });
        });
    }

    const onlineUserIds = Array.from(activeUsers.keys());
    socket.emit('users:online', { userIds: onlineUserIds });

    socket.on('admin:join', () => {
        if (socket.user && socket.user.is_admin) {
            socket.join('admin');
            logger.info({ userId }, 'User joined admin room');
        } else {
            logger.warn({ userId }, 'Unauthorized attempt to join admin room');
        }
    });

    socket.on('conversation:join', ({ conversationId }) => {
        if (!conversationId) return;
        const roomName = `conversation:${conversationId}`;
        socket.join(roomName);

        if (!roomMemberRegistry.has(roomName)) {
            roomMemberRegistry.set(roomName, new Map());
        }
        const registry = roomMemberRegistry.get(roomName);
        registry.set(userId, (registry.get(userId) || 0) + 1);
        
        console.log(`\x1b[32m[USER JOINED ROOM]\x1b[0m User: ${userId}, Room: ${roomName}, Total Unique Users: ${registry.size}`);
        broadcastRoomUsers(io, conversationId);
    });

    socket.on('conversation:leave', ({ conversationId }) => {
        if (!conversationId) return;
        const roomName = `conversation:${conversationId}`;
        socket.leave(roomName);

        const registry = roomMemberRegistry.get(roomName);
        if (registry && registry.has(userId)) {
            const count = registry.get(userId) - 1;
            if (count <= 0) registry.delete(userId);
            else registry.set(userId, count);
        }
        
        console.log(`\x1b[31m[USER LEFT ROOM]\x1b[0m User: ${userId}, Room: ${roomName}, Total Unique Users: ${registry ? registry.size : 0}`);
        broadcastRoomUsers(io, conversationId);
    });

    socket.on('chat:typing', (data) => {
        socket.to(`conversation:${data.conversationId}`).emit('chat:typing', {
            ...data,
            userId
        });
    });

    socket.on('chat:delivered', async (data) => {
        const { messageId } = data;
        if (!messageId) return;

        const secret = getActualSecret();
        if (!secret) return;

        const payload = { message_id: messageId, user_id: userId };
        const jsonPayload = JSON.stringify(payload);
        const signature = 'sha256=' + crypto.createHmac('sha256', secret).update(jsonPayload).digest('hex');

        try {
            await axios.post(`${INTERNAL_APP_URL}/api/internal/chat/delivered`, payload, {
                headers: {
                    'X-Hub-Signature-256': signature,
                    'Content-Type': 'application/json'
                }
            });
            logger.info({ userId, messageId }, 'Marked message as delivered via socket');
        } catch (err) {
            logger.error({ userId, messageId, err: err.message }, 'Failed to mark message as delivered via internal API');
        }
    });

    socket.on('disconnecting', () => {
        const rooms = Array.from(socket.rooms);
        rooms.forEach(roomName => {
            if (roomName.startsWith('conversation:')) {
                const conversationId = roomName.split(':')[1];
                const registry = roomMemberRegistry.get(roomName);
                if (registry && registry.has(userId)) {
                    const count = registry.get(userId) - 1;
                    if (count <= 0) registry.delete(userId);
                    else registry.set(userId, count);
                    broadcastRoomUsers(io, conversationId);
                }
            }
        });
    });

    socket.on('disconnect', () => {
        const userSockets = activeUsers.get(userId);
        if (userSockets) {
            userSockets.delete(socket.id);
            if (userSockets.size === 0) {
                // Use a small timeout to allow for page refreshes
                logger.info({ userId }, 'User disconnected, starting 5s offline timeout');
                setTimeout(async () => {
                    const currentSockets = activeUsers.get(userId);
                    const socketCount = currentSockets ? currentSockets.size : 0;
                    
                    if (socketCount === 0) {
                        logger.info({ userId }, 'Timeout finished: User still has no sockets, broadcasting OFFLINE');
                        activeUsers.delete(userId);
                        const data = await updateLaravelStatus(userId, 'offline');
                        io.emit('user:offline', { 
                            userId, 
                            last_active: data?.last_active 
                        });
                    } else {
                        logger.info({ userId, socketCount }, 'Timeout finished: User reconnected, cancelling OFFLINE broadcast');
                    }
                }, 5000);
            }
        }
    });

    // Diagnostic Handshake
    socket.on('ping', () => {
        socket.emit('pong', { time: new Date().toISOString() });
    });
});

// Internal endpoint for Laravel events (messages, notifications)
app.post('/internal/emit', validateInternalSignature, (req, res) => {
    const { room, event, data } = req.body;
    io.to(room).emit(event, data);
    res.json({ success: true });
});

const clearAllOnlineStatuses = async () => {
    const secret = getActualSecret();
    if (!secret) return;

    const payload = { action: 'clear_all_online' };
    const jsonPayload = JSON.stringify(payload);
    const signature = 'sha256=' + crypto.createHmac('sha256', secret).update(jsonPayload).digest('hex');

    try {
        await axios.post(`${INTERNAL_APP_URL}/api/internal/user/status`, payload, {
            headers: {
                'X-Hub-Signature-256': signature,
                'Content-Type': 'application/json'
            }
        });
        logger.info('Cleared all online statuses in Laravel on startup');
    } catch (err) {
        logger.error({ err: err.message }, 'Failed to clear all online statuses on startup');
    }
};

server.listen(PORT, '0.0.0.0', () => {
    logger.info(`Socket server running on port ${PORT}`);
    clearAllOnlineStatuses();
});
