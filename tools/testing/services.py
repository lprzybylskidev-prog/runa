#!/usr/bin/env python3
"""Development-only queue -> SMTP -> Mailpit integration smoke; leaves one identifiable test message."""
import argparse
import json
from pathlib import Path
import secrets
import subprocess
import time
import urllib.request

argparse.ArgumentParser(description=__doc__).parse_args()

ROOT = Path(__file__).resolve().parents[2]
subject = 'Runa runtime smoke ' + secrets.token_hex(8)
code = r'''
require 'src/vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (!$app->environment('local') || config('mail.default') !== 'smtp'
    || config('mail.mailers.smtp.host') !== 'mailpit' || config('queue.default') !== 'database') {
    throw new RuntimeException('This smoke requires local environment, Mailpit SMTP and the database queue.');
}
$subject = trim(stream_get_contents(STDIN));
Illuminate\Support\Facades\Mail::to('runtime-smoke@example.test')->queue(
    (new Illuminate\Mail\Mailable)->subject($subject)->html('<p>Runa queue and SMTP transport smoke.</p>')
);
'''
subprocess.run(['php', '-r', code], input=subject, text=True, cwd=ROOT, check=True)
# Bounded integration-test polling, not a runtime healthcheck or repair loop.
for attempt in range(30):
    with urllib.request.urlopen('http://mailpit:8025/api/v1/messages', timeout=5) as response:
        messages = json.load(response)['messages']
    if any(message['Subject'] == subject for message in messages):
        print('PASS: a real queued Laravel email was processed by the worker and captured by Mailpit.')
        break
    time.sleep(1)
else:
    raise SystemExit('FAIL: the queued smoke message did not arrive in Mailpit.')
