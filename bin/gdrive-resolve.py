#!/usr/bin/env python3
"""
Resolve any Google Drive share URL to a direct download link for aria2c.

Based on gdown's URL resolution logic — handles all known GDrive page formats:
  1. Direct /uc?export=download links
  2. Virus scan confirmation form (#download-form)
  3. Embedded downloadUrl JSON
  4. Error pages (quota, permissions, etc.)

Usage: python3 gdrive-resolve.py <url_or_file_id>
Output: {"url": "...", "filename": "..."}
"""

import sys
import json
import re
import urllib.parse
import requests
import bs4


def resolve(url_or_id: str) -> dict:
    """
    Resolve a Google Drive URL/file ID to a direct download link.
    Uses a requests Session to handle cookies across redirects.
    """
    # ── Build the initial URL ──────────────────────────────
    # Always normalize to the /uc?id= format which gdown uses internally
    if re.match(r'^[a-zA-Z0-9_-]{20,}$', url_or_id):
        url = f'https://drive.google.com/uc?id={url_or_id}&export=download'
    else:
        file_id = None
        for pattern in [
            r'/file/d/([a-zA-Z0-9_-]+)',
            r'/folders/([a-zA-Z0-9_-]+)',
            r'[?&]id=([a-zA-Z0-9_-]+)',
        ]:
            m = re.search(pattern, url_or_id)
            if m:
                file_id = m.group(1)
                break

        if file_id:
            url = f'https://drive.google.com/uc?id={file_id}&export=download'
        else:
            url = url_or_id

    # ── Session setup (matching gdown's _get_session) ──────
    sess = requests.session()
    sess.headers.update({
        'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    })

    # ── First request — follow redirects to the actual page ─
    resp = sess.get(url, allow_redirects=True, timeout=30, stream=True)
    # We need the full body to parse, but stream=True avoids downloading huge files
    # Read just enough to parse the HTML (max 64KB for headers/page content)
    body_chunks = []
    body_size = 0
    content_type = resp.headers.get('content-type', '')

    # If we got a direct file download (not HTML), return this URL
    if 'html' not in content_type.lower() and content_type:
        filename = _extract_filename(resp)
        return {'url': resp.url, 'filename': filename}

    # Read HTML body for parsing (cap at 64KB — page metadata is in first few KB)
    for chunk in resp.iter_content(chunk_size=8192):
        body_chunks.append(chunk)
        body_size += len(chunk)
        if body_size > 65536:
            break

    body = b''.join(body_chunks).decode('utf-8', errors='replace')

    # ── Try to extract the download URL (gdown's get_url_from_gdrive_confirmation) ─
    resolved_url = _extract_download_url(body)

    if not resolved_url:
        return {
            'error': 'Cannot retrieve download link. The file may be private, require login, or have exceeded its download quota.'
        }

    # ── Fetch with the resolved URL to get filename from Content-Disposition ─
    resp2 = sess.get(resolved_url, allow_redirects=True, timeout=30, stream=True)
    # Read a small chunk to check it's a real file
    first_chunk = next(resp2.iter_content(chunk_size=1024), b'')

    if b'<!DOCTYPE html' in first_chunk or b'<html' in first_chunk[:100]:
        error_text = first_chunk.decode('utf-8', errors='replace')[:500]
        if 'quota' in error_text.lower():
            return {'error': 'Google Drive quota exceeded for this file. Try again later.'}
        if 'permission' in error_text.lower() or 'sign in' in error_text.lower():
            return {'error': 'File requires login or different permissions.'}
        return {'error': 'Failed to resolve download URL — Google returned a page instead of a file.'}

    filename = _extract_filename(resp2)

    return {
        'url': resp2.url,
        'filename': filename,
    }


def _extract_download_url(body: str) -> str | None:
    """
    Extract the actual download URL from a Google Drive HTML page.
    Handles all known formats (based on gdown's get_url_from_gdrive_confirmation).
    """
    for line in body.splitlines():
        # Method 1: Direct /uc?export=download link in href
        m = re.search(r'href="(\/uc\?export=download[^"]+)', line)
        if m:
            url = 'https://docs.google.com' + m.group(1)
            return url.replace('&amp;', '&')

        # Method 2: Confirmation form (#download-form)
        soup = bs4.BeautifulSoup(line, features='html.parser')
        form = soup.select_one('#download-form')
        if form is not None:
            action = form.get('action', '')
            url = action.replace('&amp;', '&')
            parsed = urllib.parse.urlsplit(url)
            params = urllib.parse.parse_qs(parsed.query)
            for inp in form.find_all('input', attrs={'type': 'hidden'}):
                name = inp.get('name')
                value = inp.get('value', '')
                if name:
                    params[name] = [value]
            query = urllib.parse.urlencode(params, doseq=True)
            resolved = urllib.parse.urlunsplit(parsed._replace(query=query))
            return resolved

        # Method 3: Embedded downloadUrl JSON
        m = re.search(r'"downloadUrl":"([^"]+)"', line)
        if m:
            url = m.group(1)
            url = url.replace('\\u003d', '=')
            url = url.replace('\\u0026', '&')
            return url

        # Method 4: Error detection
        m = re.search(r'<p class="uc-error-subcaption">(.*)</p>', line)
        if m:
            return None  # Will be handled as error by caller

    return None


def _extract_filename(resp: requests.Response) -> str | None:
    """Extract filename from response Content-Disposition header."""
    cd = resp.headers.get('content-disposition', '')
    if cd:
        # Try filename*=UTF-8''name first, then filename="name"
        m = re.search(r"filename\*=(?:UTF-8''|utf-8'')([^;\s]+)", cd, re.I)
        if m:
            return urllib.parse.unquote(m.group(1))
        m = re.search(r'filename="?([^";\s]+)"?', cd)
        if m:
            return m.group(1).strip('"')

    # Try to get from final URL path
    path = urllib.parse.urlparse(resp.url).path
    if path and '/' in path:
        name = path.rsplit('/', 1)[-1]
        if name and len(name) > 3 and '.' in name:
            return urllib.parse.unquote(name)

    return None


if __name__ == '__main__':
    if len(sys.argv) < 2:
        print(json.dumps({'error': 'Usage: gdrive-resolve.py <url_or_file_id>'}))
        sys.exit(1)

    result = resolve(sys.argv[1])
    print(json.dumps(result))
