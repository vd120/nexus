require('dotenv').config({ path: '../.env' });
const express = require('express');
const http = require('http');
const { Server } = require('socket.io');
const { createAdapter } = require('@socket.io/redis-adapter');
const Redis = require('ioredis');
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
const INTERNAL_APP_URL = 'http://127.0.0.1:8000';
const PORT = process.env.SOCKET_IO_PORT || 3001;
const INTERNAL_SECRET = process.env.SOCKET_INTERNAL_SECRET;
const REDIS_URL = process.env.REDIS_URL || 'redis://127.0.0.1:6379';

// Redis clients — pub for commands, sub for adapter subscriptions
const pubClient = new Redis(REDIS_URL);
const subClient = pubClient.duplicate();

pubClient.on('error', (err) => logger.error({ err: err.message }, 'Redis pub client error'));
subClient.on('error', (err) => logger.error({ err: err.message }, 'Redis sub client error'));

// In-memory only: setTimeout handles cannot be serialized or shared across instances
const callEndTimers = new Map();

// Redis key helpers
const rk = {
    userSockets:  (userId)   => `nexus:socket:user:${userId}`,
    onlineUsers:  ()         => 'nexus:onlineUsers',
    hiddenUsers:  ()         => 'nexus:hiddenUsers',
    userCall:     (userId)   => `nexus:call:${userId}`,
    roomMembers:  (roomName) => `nexus:room:${roomName}`,
};

// Purge stale userInCall entries every 5 minutes (covers ICE failures
// where neither party disconnects cleanly and call:end is never emitted)
setInterval(async () => {
    try {
        const staleMs = 5 * 60 * 1000;
        const now = Date.now();
        const callKeys = await pubClient.keys('nexus:call:*');
        for (const key of callKeys) {
            const entry = await pubClient.hgetall(key);
            if (entry && entry.startedAt && (now - parseInt(entry.startedAt)) > staleMs) {
                const uid = key.replace('nexus:call:', '');
                logger.warn({ uid, callId: entry.callId }, 'Purging stale userInCall entry');
                await pubClient.del(key);
            }
        }
    } catch (err) {
        logger.error({ err: err.message }, 'Error during stale call purge');
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

const broadcastRoomUsers = async (io, conversationId) => {
    const roomName = `conversation:${conversationId}`;
    const userIds = await pubClient.hkeys(rk.roomMembers(roomName));
    io.to(roomName).emit('conversation:users', { conversationId, userIds });
    logger.info({ conversationId, userCount: userIds.length }, 'Broadcasted room users');
};

io.on('connection', async (socket) => {
    const userId = String(socket.user.user_id);

    // If this user was reconnecting after a brief drop, cancel the pending call:ended timer
    if (callEndTimers.has(userId)) {
        clearTimeout(callEndTimers.get(userId));
        callEndTimers.delete(userId);
    }

    // Track this socket in Redis
    await pubClient.hset(rk.userSockets(userId), socket.id, '1');
    const socketCount = await pubClient.hlen(rk.userSockets(userId));

    socket.join(`user:${userId}`);
    socket.join('public');
    socket.join('global');

    // If first connection for this user
    if (socketCount === 1) {
        await pubClient.sadd(rk.onlineUsers(), userId);
        updateLaravelStatus(userId, 'online').then(async (data) => {
            // notify_user_ids === [] means show_online_status is off — track and skip broadcast
            if (data && Array.isArray(data.notify_user_ids) && data.notify_user_ids.length === 0) {
                await pubClient.sadd(rk.hiddenUsers(), userId);
                return;
            }
            await pubClient.srem(rk.hiddenUsers(), userId);
            io.emit('user:online', { userId, last_active: data?.last_active });
        });
    }

    // Send bulk online users list to the newly connected socket (filter hidden users)
    const [allOnlineIds, hiddenIds] = await Promise.all([
        pubClient.smembers(rk.onlineUsers()),
        pubClient.smembers(rk.hiddenUsers()),
    ]);
    const hiddenSet = new Set(hiddenIds);
    socket.emit('users:online', { userIds: allOnlineIds.filter(id => !hiddenSet.has(id)) });

    socket.on('admin:join', () => {
        if (socket.user && socket.user.is_admin) {
            socket.join('admin');
            logger.info({ userId }, 'User joined admin room');
        } else {
            logger.warn({ userId }, 'Unauthorized attempt to join admin room');
        }
    });

    socket.on('pulse:join',  () => socket.join('pulse'));
    socket.on('memory:join', () => socket.join('memory'));

    socket.on('conversation:join', async ({ conversationId }) => {
        if (!conversationId) return;
        const roomName = `conversation:${conversationId}`;
        socket.join(roomName);
        await pubClient.hincrby(rk.roomMembers(roomName), userId, 1);
        const memberCount = await pubClient.hlen(rk.roomMembers(roomName));
        console.log(`\x1b[32m[USER JOINED ROOM]\x1b[0m User: ${userId}, Room: ${roomName}, Total Unique Users: ${memberCount}`);
        await broadcastRoomUsers(io, conversationId);
    });

    socket.on('conversation:leave', async ({ conversationId }) => {
        if (!conversationId) return;
        const roomName = `conversation:${conversationId}`;
        socket.leave(roomName);
        const current = parseInt(await pubClient.hget(rk.roomMembers(roomName), userId)) || 0;
        if (current - 1 <= 0) await pubClient.hdel(rk.roomMembers(roomName), userId);
        else await pubClient.hset(rk.roomMembers(roomName), userId, current - 1);
        const memberCount = await pubClient.hlen(rk.roomMembers(roomName));
        console.log(`\x1b[31m[USER LEFT ROOM]\x1b[0m User: ${userId}, Room: ${roomName}, Total Unique Users: ${memberCount}`);
        await broadcastRoomUsers(io, conversationId);
    });

    socket.on('chat:typing', (data) => {
        socket.to(`conversation:${data.conversationId}`).emit('chat:typing', { ...data, userId });
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
    socket.on('call:offer', async ({ targetUserId, callId, sdp }) => {
        if (!targetUserId || !sdp) return;
        io.to(`user:${targetUserId}`).emit('call:offer', { fromUserId: userId, callId, sdp });
        await pubClient.hset(rk.userCall(userId), { callId, otherUserId: String(targetUserId), startedAt: String(Date.now()) });
    });

    // VoIP: relay WebRTC SDP answer to caller
    socket.on('call:answer', async ({ targetUserId, callId, sdp }) => {
        if (!targetUserId || !sdp) return;
        io.to(`user:${targetUserId}`).emit('call:answer', { fromUserId: userId, callId, sdp });
        await pubClient.hset(rk.userCall(userId), { callId, otherUserId: String(targetUserId), startedAt: String(Date.now()) });
    });

    // VoIP: relay ICE candidates
    socket.on('call:ice-candidate', ({ targetUserId, callId, candidate }) => {
        if (!targetUserId || !candidate) return;
        io.to(`user:${targetUserId}`).emit('call:ice-candidate', { fromUserId: userId, callId, candidate });
    });

    // VoIP: either party ended the call via socket (e.g. browser crash fallback)
    socket.on('call:end', async ({ targetUserId, callId }) => {
        if (!targetUserId) return;
        io.to(`user:${targetUserId}`).emit('call:ended', { fromUserId: userId, callId, reason: 'hung_up' });
        await pubClient.del(rk.userCall(userId));
        const tId = String(targetUserId);
        const otherCallUserId = await pubClient.hget(rk.userCall(tId), 'otherUserId');
        if (otherCallUserId === userId) await pubClient.del(rk.userCall(tId));
    });

    socket.on('disconnecting', async () => {
        const rooms = Array.from(socket.rooms);
        for (const roomName of rooms) {
            if (!roomName.startsWith('conversation:')) continue;
            const conversationId = roomName.split(':')[1];
            const current = parseInt(await pubClient.hget(rk.roomMembers(roomName), userId)) || 0;
            if (current - 1 <= 0) await pubClient.hdel(rk.roomMembers(roomName), userId);
            else await pubClient.hset(rk.roomMembers(roomName), userId, current - 1);
            await broadcastRoomUsers(io, conversationId);
        }
    });

    socket.on('disconnect', async () => {
        // VoIP cleanup on unexpected disconnect.
        // Use a 2-second delay so Socket.IO auto-reconnects don't falsely end the call.
        const callEntry = await pubClient.hgetall(rk.userCall(userId));
        if (callEntry && callEntry.callId) {
            const { callId, otherUserId } = callEntry;
            const timer = setTimeout(async () => {
                callEndTimers.delete(userId);
                const remaining = await pubClient.hlen(rk.userSockets(userId));
                if (remaining === 0) {
                    io.to(`user:${otherUserId}`).emit('call:ended', { fromUserId: userId, callId, reason: 'disconnected' });
                    await pubClient.del(rk.userCall(userId));
                    const otherCallUserId = await pubClient.hget(rk.userCall(otherUserId), 'otherUserId');
                    if (otherCallUserId === userId) await pubClient.del(rk.userCall(otherUserId));
                }
            }, 2000);
            callEndTimers.set(userId, timer);
        }

        await pubClient.hdel(rk.userSockets(userId), socket.id);
        const remainingSockets = await pubClient.hlen(rk.userSockets(userId));

        if (remainingSockets === 0) {
            logger.info({ userId }, 'User disconnected, starting 5s offline timeout');
            setTimeout(async () => {
                const currentCount = await pubClient.hlen(rk.userSockets(userId));
                if (currentCount === 0) {
                    logger.info({ userId }, 'Timeout finished: User still has no sockets, broadcasting OFFLINE');
                    await pubClient.del(rk.userSockets(userId));
                    await pubClient.srem(rk.onlineUsers(), userId);
                    await pubClient.srem(rk.hiddenUsers(), userId);

                    // Cancel any ringing calls where this user is the callee
                    const secret = getActualSecret();
                    if (secret) {
                        const cleanupPayload = { user_id: userId };
                        const cleanupJson = JSON.stringify(cleanupPayload);
                        const cleanupSig = 'sha256=' + crypto.createHmac('sha256', secret).update(cleanupJson).digest('hex');
                        axios.post(`${INTERNAL_APP_URL}/api/internal/call/cleanup`, cleanupPayload, {
                            headers: { 'X-Hub-Signature-256': cleanupSig, 'Content-Type': 'application/json' }
                        }).catch(err => logger.warn({ err: err.message }, 'call/cleanup failed'));
                    }

                    const data = await updateLaravelStatus(userId, 'offline');
                    if (data && Array.isArray(data.notify_user_ids) && data.notify_user_ids.length === 0) return;
                    io.emit('user:offline', { userId, last_active: data?.last_active });
                } else {
                    logger.info({ userId, currentCount }, 'Timeout finished: User reconnected, cancelling OFFLINE broadcast');
                }
            }, 5000);
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

// Batch emit: broadcast the same event to many rooms in one request
app.post('/internal/emit-batch', validateInternalSignature, (req, res) => {
    const { rooms, event, data } = req.body;
    if (!Array.isArray(rooms) || !event) {
        return res.status(400).json({ error: 'rooms (array) and event are required' });
    }
    for (const room of rooms) {
        io.to(room).emit(event, data);
    }
    res.json({ success: true, count: rooms.length });
});

// Internal endpoint to update a user's online-status visibility in real-time
app.post('/internal/set-visibility', validateInternalSignature, async (req, res) => {
    const { userId, visible } = req.body;
    if (!userId) return res.status(400).json({ error: 'userId required' });
    const id = String(userId);
    if (visible) {
        await pubClient.srem(rk.hiddenUsers(), id);
        const isOnline = await pubClient.sismember(rk.onlineUsers(), id);
        if (isOnline) io.emit('user:online', { userId: id });
    } else {
        await pubClient.sadd(rk.hiddenUsers(), id);
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

// Bootstrap: wait for Redis to be ready, then attach adapter and start server
pubClient.once('ready', async () => {
    logger.info({ url: REDIS_URL }, 'Connected to Redis');

    // Clear stale state from any previous server run
    const staleKeys = await pubClient.keys('nexus:socket:user:*');
    const staleRoomKeys = await pubClient.keys('nexus:room:*');
    const allStale = [rk.onlineUsers(), rk.hiddenUsers(), ...staleKeys, ...staleRoomKeys];
    if (allStale.length) await pubClient.del(...allStale);

    io.adapter(createAdapter(pubClient, subClient));

    server.listen(PORT, '0.0.0.0', () => {
        logger.info(`Socket server running on port ${PORT}`);
        clearAllOnlineStatuses();
    });
});
