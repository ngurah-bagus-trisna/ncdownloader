#!/usr/bin/env python3
"""Resolve a Google Drive URL to a direct download link for aria2c.

Usage: python3 gdrive-resolve.py <url_or_file_id>
Output: {"url": "...", "filename": "..."}
"""

import sys
import json
import re
import requests
from bs4 import BeautifulSoup


def extract_file_id(url: str) -> str | None:
    """Extract GDrive file ID from various URL formats."""
    patterns = [
        r'/file/d/([a-zA-Z0-9_-]+)',
        r'/folders/([a-zA-Z0-9_-]+)',
        r'[?&]id=([a-zA-Z0-9_-]+)',
    ]
    for pattern in patterns:
        m = re.search(pattern, url)
        if m:
            return m.group(1)
    # Check if the input itself is just a file ID
    if re.match(r'^[a-zA-Z0-9_-]{20,}$', url):
        return url
    return None


def resolve(url_or_id: str) -> dict:
    """Resolve a GDrive URL to a direct download link."""
    session = requests.Session()
    session.headers.update({
        'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    })

    file_id = extract_file_id(url_or_id)
    if file_id:
        url = f'https://drive.google.com/uc?id={file_id}&export=download'
    else:
        url = url_or_id

    resp = session.get(url, allow_redirects=True, timeout=30)
    filename = None

    # Parse the page — check if it's a confirmation page
    soup = BeautifulSoup(resp.text, 'html.parser')
    form = soup.find('form', id='download-form')

    if form:
        # Extract filename from confirmation page
        name_span = soup.find('span', class_='uc-name-size')
        if name_span and name_span.find('a'):
            filename = name_span.find('a').text.strip()

        # Extract and submit the confirmation form
        inputs = {}
        for inp in form.find_all('input'):
            if inp.get('name'):
                inputs[inp.get('name')] = inp.get('value', '')

        action = form.get('action', '')
        if action.startswith('http'):
            full_url = action
        elif action:
            full_url = f'https://drive.usercontent.google.com{action}'
        else:
            # Build from the form's parent URL
            full_url = 'https://drive.usercontent.google.com/download'

        resp = session.get(full_url, params=inputs, allow_redirects=True, timeout=60)

    # Check if we got a file or another HTML page
    ct = resp.headers.get('content-type', '')
    if 'html' in ct.lower() or len(resp.content) < 5000:
        # May have hit quota or another error page
        error_text = ''
        if 'quota' in resp.text.lower():
            error_text = 'Google Drive quota exceeded for this file'
        elif 'virus' in resp.text.lower():
            error_text = 'Failed to bypass virus scan confirmation'
        if error_text:
            return {'error': error_text}

    # Extract filename from Content-Disposition if not already found
    cd = resp.headers.get('content-disposition', '')
    if cd and not filename:
        # Parse: attachment; filename="file.rar"; filename*=UTF-8''file.rar
        for pattern in [r'filename\*?=(?:UTF-8\'\')?"?([^";\s]+)"?', r'filename="?([^";\s]+)"?']:
            m = re.search(pattern, cd)
            if m:
                filename = m.group(1).strip('"')
                break

    return {
        'url': resp.url,
        'filename': filename,
    }


if __name__ == '__main__':
    if len(sys.argv) < 2:
        print(json.dumps({'error': 'Usage: gdrive-resolve.py <url_or_file_id>'}))
        sys.exit(1)

    result = resolve(sys.argv[1])
    print(json.dumps(result))
