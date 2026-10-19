#!/usr/bin/env python3
"""Disposable FastCGI peer for the real Nginx fallback integration test; no application data."""
import socket
import struct


def read(stream, length):
    output = b''
    while len(output) < length:
        part = stream.recv(length - len(output))
        if not part:
            raise EOFError
        output += part
    return output


def decode(data):
    values = {}
    position = 0
    while position < len(data):
        sizes = []
        for _ in range(2):
            size = data[position]
            if size & 128:
                size = struct.unpack('!I', data[position:position + 4])[0] & 0x7fffffff
                position += 4
            else:
                position += 1
            sizes.append(size)
        key_size, value_size = sizes
        key = data[position:position + key_size].decode()
        position += key_size
        values[key] = data[position:position + value_size].decode()
        position += value_size
    return values


def record(kind, request_id, data):
    return struct.pack('!BBHHBB', 1, kind, request_id, len(data), 0, 0) + data


server = socket.socket()
server.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
server.bind(('0.0.0.0', 9000))
server.listen()
while True:
    connection, _ = server.accept()
    with connection:
        parameters = b''
        try:
            while True:
                version, kind, request_id, length, padding, _ = struct.unpack('!BBHHBB', read(connection, 8))
                payload = read(connection, length)
                read(connection, padding)
                if kind == 4:
                    parameters += payload
                if kind == 5 and not payload:
                    break
            uri = decode(parameters).get('REQUEST_URI', '/').split('?')[0]
            statuses = {'/api/forbidden': 403, '/api/missing': 404, '/api/conflict': 409,
                        '/api/ready': 503, '/api/upstream-502': 502, '/unavailable': 503}
            status = statuses.get(uri, 200)
            mime = 'application/json' if uri.startswith('/api/') else 'text/html'
            body = b'{"message":"fixture response"}' if mime == 'application/json' else b'<h1>Fixture response</h1>'
            output = f'Status: {status}\r\nContent-Type: {mime}\r\nRetry-After: 17\r\n\r\n'.encode() + body
            connection.sendall(record(6, request_id, output) + record(6, request_id, b'') + record(3, request_id, b'\0' * 8))
        except (EOFError, ConnectionError):
            pass
