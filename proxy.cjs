const http = require('http');
const httpProxy = require('http-proxy');

// Create the proxy server
const proxy = httpProxy.createProxyServer({});

// Middleware to add CORS headers and log response status
proxy.on('proxyRes', function (proxyRes, req, res) {
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Origin, X-Requested-With, Content-Type, Accept, Authorization');

  // Cache static assets
  if (req.url.match(/\.(js|css|png|jpg|jpeg|gif|svg|ico|woff2|woff|ttf)$/)) {
      res.setHeader('Cache-Control', 'public, max-age=31536000, immutable');
  } else {
      // Force no-store for dynamic pages to prevent infinite loading
      res.setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, proxy-revalidate');
  }

  // Simple status code color coding
  const code = proxyRes.statusCode;
  let color = '\x1b[37m'; 
  if (code >= 200 && code < 300) color = '\x1b[32m';
  else if (code >= 300 && code < 400) color = '\x1b[33m';
  else if (code >= 400) color = '\x1b[31m';

  const timestamp = new Date().toLocaleTimeString();
  const visitorIp = req.headers['x-forwarded-for'] || req.connection.remoteAddress;
  console.log(`${timestamp} | ${color}${code}${'\x1b[0m'} | ${req.method} ${req.url} | ${visitorIp}`);
});

// Listen for the proxy error event
proxy.on('error', function (err, req, res) {
  res.writeHead(500, {
    'Content-Type': 'text/plain'
  });
  res.end('Proxy Error: ' + err.message);
});

const server = http.createServer(function(req, res) {
  // Apply CORS headers to ALL requests
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Origin, X-Requested-With, Content-Type, Accept, Authorization');

  // Handle CORS OPTIONS pre-flight
  if (req.method === 'OPTIONS') {
    res.writeHead(200);
    res.end();
    return;
  }

  // If the request is for socket.io, send it to the socket server (3001)
  if (req.url.startsWith('/socket.io')) {
    proxy.web(req, res, { 
        target: 'http://127.0.0.1:3001', 
        ws: true,
        changeOrigin: false,
        xfwd: false,
        headers: {
            ...req.headers,
            'X-Forwarded-Host': req.headers['host'],
            'X-Forwarded-Proto': 'https',
            'X-Forwarded-Port': '443',
            'X-Forwarded-For': req.headers['x-forwarded-for'] || req.connection.remoteAddress
        }
    });
  } 
  // Otherwise, send it to the Laravel/Octane server (8000)
  else {
    proxy.web(req, res, { 
        target: 'http://127.0.0.1:8000',
        changeOrigin: false,
        xfwd: false,
        headers: {
            ...req.headers,
            'X-Forwarded-Host': req.headers['host'],
            'X-Forwarded-Proto': 'https',
            'X-Forwarded-Port': '443',
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
        changeOrigin: false,
        xfwd: false,
        headers: {
            ...req.headers,
            'X-Forwarded-Host': req.headers['host'],
            'X-Forwarded-Proto': 'https',
            'X-Forwarded-Port': '443',
            'X-Forwarded-For': req.headers['x-forwarded-for'] || req.connection.remoteAddress
        }
    });
  }
});


console.log('Nexus Proxy: Routing traffic to 8000 (Web) and 3001 (Sockets)...');
server.listen(8080, '0.0.0.0');
