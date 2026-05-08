const http = require('http');
const httpProxy = require('http-proxy');

// Create the proxy server
const proxy = httpProxy.createProxyServer({});

// Listen for the proxy error event
proxy.on('error', function (err, req, res) {
  res.writeHead(500, {
    'Content-Type': 'text/plain'
  });
  res.end('Proxy Error: ' + err.message);
});

const server = http.createServer(function(req, res) {
  // If the request is for socket.io, send it to the socket server (3001)
  if (req.url.startsWith('/socket.io')) {
    proxy.web(req, res, { 
        target: 'http://127.0.0.1:3001', 
        ws: true,
        changeOrigin: true,
        xfwd: false,
        headers: {
            ...req.headers, // Preserve all original headers (Cookies, User-Agent, etc.)
            'X-Forwarded-Host': req.headers['host'],
            'X-Forwarded-Proto': req.headers['x-forwarded-proto'] || 'http',
            'X-Forwarded-Port': req.headers['x-forwarded-port'] || '80',
            'X-Forwarded-For': req.headers['x-forwarded-for'] || req.connection.remoteAddress
        }
    });
  } 
  // Otherwise, send it to the Laravel/Octane server (8000)
  else {
    proxy.web(req, res, { 
        target: 'http://127.0.0.1:8000',
        changeOrigin: true,
        xfwd: false, // Disable automatic x-forwarded headers to prevent overwriting ngrok's https
        headers: {
            ...req.headers, // Preserve all original headers (Cookies, User-Agent, etc.)
            'X-Forwarded-Host': req.headers['host'],
            'X-Forwarded-Proto': req.headers['x-forwarded-proto'] || 'http',
            'X-Forwarded-Port': req.headers['x-forwarded-port'] || '80',
            'X-Forwarded-For': req.headers['x-forwarded-for'] || req.connection.remoteAddress
        }
    });
  }
});

// Support WebSocket proxying
server.on('upgrade', function (req, socket, head) {
  if (req.url.startsWith('/socket.io')) {
    proxy.ws(req, socket, head, { 
        target: 'http://127.0.0.1:3001',
        changeOrigin: true,
        xfwd: false,
        headers: {
            ...req.headers,
            'X-Forwarded-Host': req.headers['host'],
            'X-Forwarded-Proto': req.headers['x-forwarded-proto'] || 'http',
            'X-Forwarded-Port': req.headers['x-forwarded-port'] || '80',
            'X-Forwarded-For': req.headers['x-forwarded-for'] || req.connection.remoteAddress
        }
    });
  }
});

console.log('Nexus Proxy: Routing traffic to 8000 (Web) and 3001 (Sockets)...');
server.listen(8080, '0.0.0.0');
