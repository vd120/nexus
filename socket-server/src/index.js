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

const activeUsers    = new Map(); // userId -> Set of socketIds
const hiddenUsers    = new Set(); // userIds with show_online_status=false
const userInCall     = new Map(); // userId -> { callId, otherUserId, startedAt }
const callEndTimers  = new Map(); // userId -> setTimeout handle for delayed call:ended

// Purge stale userInCall entries every 5 minutes (covers ICE failures
// where neither party disconnects cleanly and call:end is never emitted)
setInterval(() => {
    const staleMs = 5 * 60 * 1000;
    const now = Date.now();
    for (const [uid, entry] of userInCall.entries()) {
        if (now - entry.startedAt > staleMs) {
            logger.warn({ uid, callId: entry.callId }, 'Purging stale userInCall entry');
            userInCall.delete(uid);
        }
    }
}, 60 * 1000);

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

    // If this user was reconnecting after a brief drop, cancel the pending call:ended timer
    if (callEndTimers.has(userId)) {
        clearTimeout(callEndTimers.get(userId));
        callEndTimers.delete(userId);
    }

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
            // notify_user_ids === [] means show_online_status is off — track and skip broadcast
            if (data && Array.isArray(data.notify_user_ids) && data.notify_user_ids.length === 0) {
                hiddenUsers.add(userId);
                return;
            }
            hiddenUsers.delete(userId);
            io.emit('user:online', { userId, last_active: data?.last_active });
        });
    }

    // Filter hidden users out of the bulk sync so reloads don't resurrect hidden users
    const onlineUserIds = Array.from(activeUsers.keys()).filter(id => !hiddenUsers.has(id));
    socket.emit('users:online', { userIds: onlineUserIds });

    socket.on('admin:join', () => {
        if (socket.user && socket.user.is_admin) {
            socket.join('admin');
            logger.info({ userId }, 'User joined admin room');
        } else {
            logger.warn({ userId }, 'Unauthorized attempt to join admin room');
        }
    });

    socket.on('pulse:join', () => {
        socket.join('pulse');
    });

    socket.on('memory:join', () => {
        socket.join('memory');
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

    // VoIP: relay WebRTC SDP offer to callee
    socket.on('call:offer', ({ targetUserId, callId, sdp }) => {
        if (!targetUserId || !sdp) return;
        io.to(`user:${targetUserId}`).emit('call:offer', {
            fromUserId: userId,
            callId,
            sdp
        });
        // Track that this user is in a call
        userInCall.set(userId, { callId, otherUserId: String(targetUserId), startedAt: Date.now() });
    });

    // VoIP: relay WebRTC SDP answer to caller
    socket.on('call:answer', ({ targetUserId, callId, sdp }) => {
        if (!targetUserId || !sdp) return;
        io.to(`user:${targetUserId}`).emit('call:answer', {
            fromUserId: userId,
            callId,
            sdp
        });
        // Track callee in call too
        userInCall.set(userId, { callId, otherUserId: String(targetUserId), startedAt: Date.now() });
    });

    // VoIP: relay ICE candidates
    socket.on('call:ice-candidate', ({ targetUserId, callId, candidate }) => {
        if (!targetUserId || !candidate) return;
        io.to(`user:${targetUserId}`).emit('call:ice-candidate', {
            fromUserId: userId,
            callId,
            candidate
        });
    });

    // VoIP: either party ended the call via socket (e.g. browser crash fallback)
    socket.on('call:end', ({ targetUserId, callId }) => {
        if (!targetUserId) return;
        io.to(`user:${targetUserId}`).emit('call:ended', {
            fromUserId: userId,
            callId,
            reason: 'hung_up'
        });
        userInCall.delete(userId);
        // Clean up the other party's entry to prevent duplicate call:ended on their disconnect
        const tId = String(targetUserId);
        if (userInCall.get(tId)?.otherUserId === userId) userInCall.delete(tId);
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
        // VoIP cleanup on unexpected disconnect.
        // Use a 2-second delay so Socket.IO auto-reconnects don't falsely end the call —
        // the client typically reconnects in < 1 second on a transient network hiccup.
        if (userInCall.has(userId)) {
            const { callId, otherUserId } = userInCall.get(userId);
            const timer = setTimeout(() => {
                callEndTimers.delete(userId);
                // Only emit if the user truly never reconnected
                const sockets = activeUsers.get(userId);
                if (!sockets || sockets.size === 0) {
                    io.to(`user:${otherUserId}`).emit('call:ended', {
                        fromUserId: userId,
                        callId,
                        reason: 'disconnected'
                    });
                    userInCall.delete(userId);
                    // Clean up the other party's entry to prevent a duplicate call:ended
                    const tId = String(otherUserId);
                    if (userInCall.get(tId)?.otherUserId === userId) userInCall.delete(tId);
                }
            }, 2000);
            callEndTimers.set(userId, timer);
        }

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
                        hiddenUsers.delete(userId);

                        // Cancel any ringing calls where this user is the callee
                        // so the caller can re-call immediately after a refresh
                        const secret = getActualSecret();
                        if (secret) {
                            const cleanupPayload = { user_id: userId };
                            const cleanupJson = JSON.stringify(cleanupPayload);
                            const cleanupSig = 'sha256=' + require('crypto').createHmac('sha256', secret).update(cleanupJson).digest('hex');
                            axios.post(`${INTERNAL_APP_URL}/api/internal/call/cleanup`, cleanupPayload, {
                                headers: { 'X-Hub-Signature-256': cleanupSig, 'Content-Type': 'application/json' }
                            }).catch(err => logger.warn({ err: err.message }, 'call/cleanup failed'));
                        }

                        const data = await updateLaravelStatus(userId, 'offline');
                        // Only broadcast if user had show_online_status on (notify_user_ids non-empty)
                        if (data && Array.isArray(data.notify_user_ids) && data.notify_user_ids.length === 0) return;
                        io.emit('user:offline', { userId, last_active: data?.last_active });
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

// Internal endpoint to update a user's online-status visibility in real-time
app.post('/internal/set-visibility', validateInternalSignature, (req, res) => {
    const { userId, visible } = req.body;
    if (!userId) return res.status(400).json({ error: 'userId required' });
    const id = String(userId);
    if (visible) {
        hiddenUsers.delete(id);
        // If user is connected, broadcast online to everyone now
        if (activeUsers.has(id)) {
            io.emit('user:online', { userId: id });
        }
    } else {
        hiddenUsers.add(id);
        // Broadcast offline so existing viewers update immediately
        io.emit('user:offline', { userId: id });
    }
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
