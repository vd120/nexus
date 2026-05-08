const crypto = require('crypto');
const logger = require('../logger');

const validateInternalSignature = (req, res, next) => {
    const signature = req.headers['x-hub-signature-256'];
    const secret = process.env.SOCKET_INTERNAL_SECRET;

    if (!signature) {
        logger.warn('Missing X-Hub-Signature-256 header');
        return res.status(401).json({ error: 'Unauthorized' });
    }

    if (!secret) {
        logger.error('SOCKET_INTERNAL_SECRET not configured');
        return res.status(500).json({ error: 'Internal Server Error' });
    }

    // Secret might be prefixed with "base64:" in Laravel
    const actualSecret = secret.startsWith('base64:') 
        ? Buffer.from(secret.substring(7), 'base64') 
        : secret;

    const hmac = crypto.createHmac('sha256', actualSecret);
    const body = JSON.stringify(req.body);
    const digest = 'sha256=' + hmac.update(body).digest('hex');

    if (signature !== digest) {
        logger.warn({ expected: digest, received: signature }, 'Invalid signature');
        return res.status(401).json({ error: 'Unauthorized' });
    }

    next();
};

module.exports = { validateInternalSignature };
