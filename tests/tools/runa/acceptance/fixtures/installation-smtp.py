#!/usr/bin/env python3
"""Isolated TLS SMTP acceptance fixture; synthetic messages only, never production."""

from __future__ import annotations

import socketserver
import ssl
from pathlib import Path


class Handler(socketserver.StreamRequestHandler):
    def handle(self) -> None:
        def reply(message: str) -> None:
            self.wfile.write(message.encode() + b"\r\n")
            self.wfile.flush()

        reply("220 smtp fixture")
        while line := self.rfile.readline(8192):
            command = line.decode(errors="replace").strip().split(" ", 1)[0].upper()
            if command in ("EHLO", "HELO"):
                reply("250 smtp fixture")
            elif command == "DATA":
                reply("354 End with dot")
                content = bytearray()
                while line := self.rfile.readline(8192):
                    if line == b".\r\n":
                        break
                    content.extend(line)
                    if len(content) > 65536:
                        return
                if not Path("/reports/allow-mail").exists():
                    Path("/reports/rejected.eml").write_bytes(content)
                    reply("550 synthetic rejection for installer recovery acceptance")
                else:
                    with Path("/reports/accepted.eml").open("ab") as stream:
                        stream.write(content)
                    reply("250 Accepted")
            elif command == "QUIT":
                reply("221 Bye")
                return
            else:
                reply("250 OK")


class Server(socketserver.ThreadingTCPServer):
    allow_reuse_address = True
    context: ssl.SSLContext

    def get_request(self) -> tuple[ssl.SSLSocket, tuple[str, int]]:
        connection, address = super().get_request()
        return self.context.wrap_socket(connection, server_side=True), address


def main() -> None:
    context = ssl.SSLContext(ssl.PROTOCOL_TLS_SERVER)
    context.load_cert_chain("/fixture/certificate.pem", "/fixture/private-key.pem")
    with Server(("0.0.0.0", 2465), Handler) as server:
        server.context = context
        server.serve_forever()


if __name__ == "__main__":
    main()
