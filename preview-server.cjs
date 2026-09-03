const http = require('node:http');
const path = require('node:path');
const { PHP, PHPRequestHandler } = require('@php-wasm/universal');
const { loadNodeRuntime, useHostFilesystem } = require('@php-wasm/node');

const root = __dirname;
const port = Number(process.env.PORT || 8000);

function readBody(request) {
    return new Promise((resolve, reject) => {
        const chunks = [];
        request.on('data', (chunk) => chunks.push(chunk));
        request.on('end', () => resolve(Buffer.concat(chunks)));
        request.on('error', reject);
    });
}

function responseHeaders(headers) {
    const result = {};
    Object.entries(headers || {}).forEach(([key, values]) => {
        // Node accepts a string or an array for response headers.
        result[key] = Array.isArray(values) && values.length === 1 ? values[0] : values;
    });
    return result;
}

async function start() {
    // PHP-Wasm is only used for the optional local preview. Production hosting
    // should run index.php through a normal PHP 7.4+ server.
    const runtimeId = await loadNodeRuntime('8.3', {
        emscriptenOptions: { processId: process.pid },
    });
    const php = new PHP(runtimeId);
    useHostFilesystem(php);
    php.chdir(root);

    const phpServer = new PHPRequestHandler({
        php,
        documentRoot: root,
        absoluteUrl: `http://0.0.0.0:${port}`,
    });

    const server = http.createServer(async (request, response) => {
        if (!['GET', 'HEAD', 'POST'].includes(request.method)) {
            response.writeHead(405, { 'content-type': 'text/plain; charset=utf-8' });
            response.end('Method Not Allowed');
            return;
        }

        try {
            const body = request.method === 'GET' || request.method === 'HEAD'
                ? undefined
                : await readBody(request);
            const phpResponse = await phpServer.request({
                method: request.method,
                url: request.url || '/',
                headers: request.headers,
                body,
            });
            const headers = responseHeaders(phpResponse.headers);
            if (request.method === 'HEAD') {
                response.writeHead(phpResponse.httpStatusCode, headers);
                response.end();
                return;
            }
            response.writeHead(phpResponse.httpStatusCode, headers);
            response.end(Buffer.from(phpResponse.bytes));
        } catch (error) {
            console.error(error);
            response.writeHead(500, { 'content-type': 'text/plain; charset=utf-8' });
            response.end('Preview server error');
        }
    });

    server.listen(port, '0.0.0.0', () => {
        console.log(`Mojodi-kala preview is running at http://0.0.0.0:${port}`);
    });
}

start().catch((error) => {
    console.error(error);
    process.exitCode = 1;
});
