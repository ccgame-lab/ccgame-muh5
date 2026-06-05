#!/usr/bin/env python3
"""
r2.py — Release tool for MU H5 client assets on Cloudflare R2.

Transfer model: object upload/download runs through the VPS, which already has
a working rclone remote (`ccgame-r2:`). Editing happens locally; this tool
streams the local file UP to the VPS and `rclone rcat`s it into R2 (no fresh
local R2 S3 credentials required). CDN cache purge runs locally via the
Cloudflare API.

Workflow (e.g. Việt hoá config1.json):
    python r2.py pull resource/cfg/config1.json          # download for editing
    # ...edit ./work/resource/cfg/config1.json...
    python r2.py push resource/cfg/config1.json --backup # upload (rclone) + purge CDN

Config is read from <repo-root>/.env.local (never hard-coded):
    R2_PUBLIC_BASE        e.g. https://cdn.ccgame.org   (CDN maps to bucket root)
    R2_RESOURCE_BASE      e.g. h5/muh5/resource          (path prefix inside bucket)
    R2_SSH_HOST           e.g. ccgame-prod               (SSH alias with rclone)
    R2_RCLONE_PREFIX      e.g. ccgame-r2:game-assets     (rclone remote + bucket)
    CLOUDFLARE_API_TOKEN  Cloudflare token with Zone:Cache Purge on the zone
    CLOUDFLARE_ZONE_ID    e.g. 6b71362247c4b888559b60083d6a2209

Paths are RELATIVE to R2_RESOURCE_BASE; prefix with "/" for an absolute key.

NOTE: config1.json is served with a 31-day edge cache. An overwrite is NOT
visible to players until the CDN is purged. If purge fails (e.g. invalid token)
this tool prints a loud warning and the manual dashboard steps.
"""
from __future__ import annotations

import argparse
import json
import mimetypes
import os
import shlex
import subprocess
import sys
import time
import urllib.error
import urllib.request
from pathlib import Path

REPO_ROOT = Path(__file__).resolve().parents[2]
ENV_FILE = REPO_ROOT / ".env.local"
DEFAULT_WORKDIR = Path(__file__).resolve().parent / "work"

DEFAULTS = {
    "R2_PUBLIC_BASE": "https://cdn.ccgame.org",
    "R2_RESOURCE_BASE": "h5/muh5/resource",
    "R2_SSH_HOST": "ccgame-prod",
    "R2_RCLONE_PREFIX": "ccgame-r2:game-assets",
    "CLOUDFLARE_ZONE_ID": "6b71362247c4b888559b60083d6a2209",
}

CONTENT_TYPES = {
    ".json": "application/json; charset=utf-8",
    ".js": "application/javascript; charset=utf-8",
    ".html": "text/html; charset=utf-8",
    ".txt": "text/plain; charset=utf-8",
    ".png": "image/png",
    ".jpg": "image/jpeg",
    ".jpeg": "image/jpeg",
    ".webp": "image/webp",
    ".mp3": "audio/mpeg",
    ".fnt": "text/plain; charset=utf-8",
}


def load_env() -> dict[str, str]:
    env = dict(DEFAULTS)
    if ENV_FILE.exists():
        for raw in ENV_FILE.read_text(encoding="utf-8").splitlines():
            line = raw.strip()
            if not line or line.startswith("#") or "=" not in line:
                continue
            key, val = line.split("=", 1)
            env[key.strip()] = val.strip().strip('"').strip("'")
    for key in list(DEFAULTS) + ["CLOUDFLARE_API_TOKEN"]:
        if os.environ.get(key):
            env[key] = os.environ[key]
    return env


def require(env: dict[str, str], *keys: str) -> None:
    missing = [k for k in keys if not env.get(k)]
    if missing:
        sys.exit(f"error: missing config in {ENV_FILE.name}: {', '.join(missing)}")


def resolve_key(path: str, base: str) -> str:
    """Map a user path to a bucket-relative key (under the resource base)."""
    path = path.strip()
    if path.startswith("/"):
        return path.lstrip("/")
    return f"{base.strip('/')}/{path.lstrip('/')}"


def rclone_path(env: dict[str, str], key: str) -> str:
    """Full rclone source/dest, e.g. ccgame-r2:game-assets/h5/muh5/resource/cfg/x."""
    return f"{env['R2_RCLONE_PREFIX'].rstrip('/')}/{key}"


def public_url(env: dict[str, str], key: str) -> str:
    return f"{env['R2_PUBLIC_BASE'].rstrip('/')}/{key}"


def content_type_for(name: str) -> str:
    ext = Path(name).suffix.lower()
    if ext in CONTENT_TYPES:
        return CONTENT_TYPES[ext]
    guessed, _ = mimetypes.guess_type(name)
    return guessed or "application/octet-stream"


def local_for(env: dict[str, str], key: str, override: str | None) -> Path:
    """Resolve the local file path mirroring a key under ./work, or an override."""
    if override:
        return Path(override)
    rel = key
    base = env["R2_RESOURCE_BASE"].strip("/") + "/"
    if rel.startswith(base):
        rel = rel[len(base):]
    return DEFAULT_WORKDIR / rel


def ssh_run(env, remote_cmd: str, *, stdin=None, stdout=None) -> subprocess.CompletedProcess:
    cp = subprocess.run(
        ["ssh", env["R2_SSH_HOST"], remote_cmd],
        stdin=stdin, stdout=stdout, stderr=subprocess.PIPE, text=(stdout is None),
    )
    if cp.returncode != 0:
        err = cp.stderr if isinstance(cp.stderr, str) else (cp.stderr or b"").decode("utf-8", "replace")
        sys.exit(f"error: remote command failed ({cp.returncode}): {remote_cmd}\n{err.strip()}")
    return cp


def cmd_ls(env, args) -> None:
    prefix = resolve_key(args.prefix, env["R2_RESOURCE_BASE"]) if args.prefix else env["R2_RESOURCE_BASE"].strip("/")
    src = rclone_path(env, prefix)
    cp = subprocess.run(
        ["ssh", env["R2_SSH_HOST"], f"rclone lsl {shlex.quote(src)}"],
        stderr=subprocess.PIPE, stdout=subprocess.PIPE, text=True,
    )
    sys.stdout.write(cp.stdout)
    if cp.returncode != 0:
        sys.exit(cp.stderr.strip())


def cmd_pull(env, args) -> None:
    key = resolve_key(args.remote, env["R2_RESOURCE_BASE"])
    dest = Path(args.dest) if args.dest else local_for(env, key, None)
    if args.dest and dest.is_dir():
        dest = dest / Path(key).name
    dest.parent.mkdir(parents=True, exist_ok=True)
    src = rclone_path(env, key)
    with open(dest, "wb") as fh:
        ssh_run(env, f"rclone cat {shlex.quote(src)}", stdout=fh)
    size = dest.stat().st_size
    if size == 0:
        sys.exit(f"error: pulled 0 bytes — does {src} exist? (try: r2.py ls {args.remote})")
    print(f"pulled  {src}\n     -> {dest}  ({size:,} bytes)")


def cmd_push(env, args) -> None:
    require(env, "R2_PUBLIC_BASE")
    key = resolve_key(args.remote, env["R2_RESOURCE_BASE"])
    local = local_for(env, key, args.local)
    if not local.is_file():
        sys.exit(f"error: local file not found: {local}")
    dest = rclone_path(env, key)
    ctype = content_type_for(local.name)

    if args.backup:
        stamp = time.strftime("%Y%m%d")
        bak = f"{dest}.bak-{stamp}"
        # Only copy if the source object currently exists.
        check = subprocess.run(
            ["ssh", env["R2_SSH_HOST"], f"rclone lsf {shlex.quote(dest)}"],
            stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True,
        )
        if check.returncode == 0 and check.stdout.strip():
            ssh_run(env, f"rclone copyto {shlex.quote(dest)} {shlex.quote(bak)}")
            print(f"backup  {bak}")
        else:
            print(f"backup  skipped (no existing object at {key})")

    header = shlex.quote(f"Content-Type: {ctype}")
    # --s3-no-check-bucket: R2 token can't CreateBucket; skip the existence check
    # that rclone's multipart rcat otherwise triggers (else 403 AccessDenied).
    remote_cmd = f"rclone rcat --s3-no-check-bucket --header-upload {header} {shlex.quote(dest)}"
    with open(local, "rb") as fh:
        ssh_run(env, remote_cmd, stdin=fh)
    print(f"pushed  {local}  ({local.stat().st_size:,} bytes, {ctype})\n     -> {dest}")

    url = public_url(env, key)
    if args.no_purge:
        print(f"purge   skipped (--no-purge)\n        not live until purged: {url}")
        return
    purge_urls(env, [url])


def cmd_purge(env, args) -> None:
    require(env, "R2_PUBLIC_BASE", "R2_RESOURCE_BASE")
    urls = []
    for item in args.paths:
        if item.startswith(("http://", "https://")):
            urls.append(item)
        else:
            urls.append(public_url(env, resolve_key(item, env["R2_RESOURCE_BASE"])))
    purge_urls(env, urls)


def purge_urls(env: dict[str, str], urls: list[str]) -> None:
    require(env, "CLOUDFLARE_API_TOKEN", "CLOUDFLARE_ZONE_ID")
    endpoint = f"https://api.cloudflare.com/client/v4/zones/{env['CLOUDFLARE_ZONE_ID']}/purge_cache"
    body = json.dumps({"files": urls}).encode("utf-8")
    req = urllib.request.Request(
        endpoint, data=body, method="POST",
        headers={"Authorization": f"Bearer {env['CLOUDFLARE_API_TOKEN']}", "Content-Type": "application/json"},
    )
    try:
        with urllib.request.urlopen(req, timeout=30) as resp:
            payload = json.loads(resp.read().decode("utf-8"))
        ok = payload.get("success")
        detail = json.dumps(payload.get("errors", payload))
    except urllib.error.HTTPError as exc:
        ok, detail = False, exc.read().decode("utf-8", "replace")
    except urllib.error.URLError as exc:
        ok, detail = False, str(exc)

    if ok:
        for u in urls:
            print(f"purged  {u}")
        return

    warn = "\n".join([
        "",
        "!" * 68,
        "  PURGE FAILED — the upload succeeded but is NOT live at the edge.",
        f"  Reason: {detail}",
        "  config1.json is cached up to 31 days. Until purged, players see the",
        "  old version. Fix the Cloudflare token in .env.local, then run:",
        "      python r2.py purge " + " ".join(shlex.quote(u) for u in urls),
        "  Or purge manually: Cloudflare dashboard > ccgame.org > Caching >",
        "  Configuration > Purge Cache > Custom > paste the URL(s) above.",
        "!" * 68,
    ])
    print(warn, file=sys.stderr)
    sys.exit(1)


def main() -> None:
    parser = argparse.ArgumentParser(
        description="Release MU H5 client assets to R2 (via VPS rclone) + purge CDN.",
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog="Paths are relative to R2_RESOURCE_BASE unless prefixed with '/' (absolute key).",
    )
    sub = parser.add_subparsers(dest="cmd", required=True)

    p = sub.add_parser("ls", help="list objects under a prefix")
    p.add_argument("prefix", nargs="?", default="")
    p.set_defaults(func=cmd_ls)

    p = sub.add_parser("pull", help="download an object for editing")
    p.add_argument("remote")
    p.add_argument("dest", nargs="?", help="local destination (default: ./work/<path>)")
    p.set_defaults(func=cmd_pull)

    p = sub.add_parser("push", help="upload an object (rclone) and purge its CDN URL")
    p.add_argument("remote")
    p.add_argument("local", nargs="?", help="local source (default: ./work/<path>)")
    p.add_argument("--backup", action="store_true", help="copy existing object to <key>.bak-YYYYMMDD first")
    p.add_argument("--no-purge", action="store_true", help="upload only, skip cache purge")
    p.set_defaults(func=cmd_push)

    p = sub.add_parser("purge", help="purge CDN cache for paths or full URLs")
    p.add_argument("paths", nargs="+")
    p.set_defaults(func=cmd_purge)

    args = parser.parse_args()
    args.func(load_env(), args)


if __name__ == "__main__":
    main()
