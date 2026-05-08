const crypto = require('crypto');
const logger = require('./logger');

const verifySocketToken = (token) => {
    if (!token) return null;

    try {
        const secret = process.env.SOCKET_INTERNAL_SECRET;
        if (!secret) {
            logger.error('SOCKET_INTERNAL_SECRET not configured');
            return null;
        }

        const actualSecret = secret.startsWith('base64:') 
            ? Buffer.from(secret.substring(7), 'base64') 
            : secret;

        const [encodedPayload, signature] = token.split('.');
        if (!encodedPayload || !signature) return null;

        const jsonPayload = Buffer.from(encodedPayload, 'base64').toString();
        const expectedSignature = crypto.createHmac('sha256', actualSecret)
            .update(jsonPayload)
            .digest('hex');

        if (signature !== expectedSignature) {
            logger.warn('Invalid socket token signature');
            return null;
        }

        const payload = JSON.parse(jsonPayload);
        
        // Check expiration
        if (payload.exp && Date.now() / 1000 > payload.exp) {
            logger.warn('Socket token expired');
            return null;
        }

        return payload;
    } catch (err) {
        logger.error({ err }, 'Error verifying socket token');
        return null;
    }
};

module.exports = { verifySocketToken };
