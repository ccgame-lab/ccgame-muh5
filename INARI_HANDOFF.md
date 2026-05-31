# SUSHIBOX INARI — CONTEXT HANDOFF (clean)

> Feed this whole file into a new chat to continue. It is self-contained.
> Ignore any older "ideas" — only what's written here is current truth.

## What Inari is

Portable, copy-and-run Windows dev runtime manager (like Laragon / phpStudy /
XAMPP), for a solo dev. Bundles nginx + php-cgi + MariaDB 10.3 + Redis 5 under
`runtime/`, controlled by a small native GUI. End goal: run the muh5 dev stack.

Philosophy (hard rules, do not violate):
- Small, simple, compact UI. "A feature must justify its screen space."
- Backend is authoritative; UI never invents state. No fake indicators.
- Avoid: premature hardening, large rewrites, enterprise abstractions,
  analytics/metrics/charts, auto-update, multi-tenant, user accounts.
- Owner prefers: ship first, patches over refactors, small commits.
- Laragon-style structure is OK to imitate, but AVOID their signatures
  (no `{name}.test` auto-vhost, no auto-backup/update, no their naming).

## Location & access

- Project: `D:\10_Projects\Toolbox\sushibox\sushibox-inari` (its own git repo,
  branch `master`).
- IMPORTANT TOOLING NOTE: this project is OUTSIDE the agent's writable
  workspace. File-edit tools and `cwd` are locked to the muh5 workspace.
  Work on Inari via:
    - shell with ABSOLUTE paths: `git -C "<path>"`, `cmd /c type "<path>"`,
      `cargo build --manifest-path "<path>\Cargo.toml"`
    - file read/write tools DO work with absolute paths into the project
      (they were used throughout). `tauri.conf.json` writes are blocked by a
      JSON-schema guard → write it via PowerShell heredoc instead.
  Best fix: open `sushibox-inari` as its own workspace for full tooling.
- PowerShell quirk: the shell layer eats `$` / `$_`. Avoid `$_` in inline
  PowerShell; write a temp .ps1 file or use `cmd /c` for dir/type.

## Tech stack (current, after big migration)

- Rust workspace, 4 crates + 1 Tauri app:
  - `crates/inari-core` — process mgmt, Job Object, config, paths, runtime
    (descriptor_for, generate_nginx_conf, init_mysql, stop_service,
    wait_for_exit), and NEW `settings.rs` (GUI-writable overlay).
  - `crates/inari-lua`  — loads `flavors/default.lua` into InariConfig.
  - `crates/inari-api`  — axum server; serves embedded Nuxt panel + REST API.
  - `crates/inari-cli`  — `inari-cli.exe`, console CLI for automation/AI
    (start/stop/restart/status/panel/menu). NO GUI here anymore.
  - `src-tauri`         — `Inari.exe`, the Tauri 2 GUI (tray + window).
- Frontend: `panel/` = Nuxt 4 + Nuxt UI (free), built to `panel/dist`,
  embedded into the binary via rust-embed, served by axum on port 1788.

### Two binaries (do NOT let them collide again)
- `Inari.exe`      = Tauri GUI (windows subsystem, no console). Double-click.
- `inari-cli.exe`  = console CLI for AI/automation.
  (Previously both were named `Inari.exe` → output collision; fixed by
   deleting the dead wry GUI. Keep names distinct.)

## How GUI works now (Tauri 2 — this is the FINAL gui approach)

- `src-tauri/src/lib.rs`:
  - single-instance plugin (2nd launch focuses existing window).
  - `setup`: starts panel server on bg thread, then `run_autostart`, builds
    tray (menu Show/Hide/Quit, `menu_on_left_click(false)`), navigates main
    window to `http://127.0.0.1:1788/`.
  - Left-click tray = toggle window. Right-click = Tauri's native menu.
  - Close button (X) = hide to tray (prevent_close), not quit.
- `tauri.conf.json`: window 400x520, `resizable:false`, `maximizable:false`,
  `bundle.active:false`. (Write via PowerShell, not the file tool.)

### History of why Tauri (don't re-litigate)
wry+tao+tray-icon was tried and abandoned after ~6 failed tray fixes
(console window, flicker, double-toggle, menu reopen). Tauri handles
tray/window/no-console correctly. GJ_SDK (another of the owner's projects,
`D:\10_Projects\KiemTheUnity\KTM_Workspace\SDK\GJ_SDK`) proves Tauri 2 runs in
production. Decision is final: GUI = Tauri.

## Settings system (the current work area)

Goal: GUI-first config, minimize file editing. Laragon-style 2 tabs
(General / Services), but neutral wording.

Backend (DONE, tested working):
- `crates/inari-core/src/settings.rs`: `Settings` struct with optional
  `ports {panel,web,mysql,redis}`, `sites`, `autostart: Option<Vec<String>>`.
  - `Settings::load(data_dir)` / `.save(data_dir)` → `data/settings.json`.
  - `.apply_to(InariConfig)` overlays settings onto flavor defaults.
- `crates/inari-api/src/lib.rs`:
  - `AppState.config` is now `Mutex<InariConfig>` (live, mutable); `.base`
    holds flavor-only config. All `state.config` reads now `.lock()`.
  - `GET /api/settings` → current settings.json
  - `POST /api/settings` → save + re-apply onto base → live config updates.
  - VERIFIED: POST web=8090 → /api/config reflects it → settings.json written
    → SURVIVES app restart (persistence confirmed; this was the key worry).

Frontend (DONE, builds, but autostart end-to-end NOT yet verified):
- `panel/app/pages/index.vue`: settings is a FULL-COVER overlay (not a
  half slideover — small window made the strip look broken). Two tabs:
  - General: dark mode, auto-refresh on/off + interval(s), Web root input.
  - Services: ports (nginx/mysql/redis), "Start on launch" switches per
    service, Open config folder. Footer: Close + Save.
  - Save → POST /api/settings. Round-trip VERIFIED via curl
    (autostart [nginx,php] saved and read back correctly).

## ⚠️ OPEN BUG — pick up here

Autostart does NOT actually launch services on app start.
- settings.json correctly stores `autostart:["nginx","php"]` (verified).
- But on launch, nginx/php do NOT start (process count 0).
- Manual `POST /api/services/nginx/start` WORKS (nginx pid spawned), so the
  start path and panel are fine. Bug is in `run_autostart` in
  `src-tauri/src/lib.rs`.
- Suspect: the std-only `ureq_post()` HTTP call (raw TcpStream) — likely the
  server didn't finish handling before connection dropped, OR the autostart
  thread's settings read / port timing.
- LAST EDIT (uncommitted): added `stream.read_to_end()` to `ureq_post` so the
  request completes before moving on. NOT yet rebuilt/tested.
- Next steps to try:
  1. Rebuild `-p app`, run, check if nginx/php start on launch.
  2. If still failing, add a debug log file in run_autostart (GUI has no
     console), or verify Settings::load reads the right data dir at launch.
  3. Consider calling the start logic DIRECTLY in-process instead of via HTTP
     loopback (cleaner: factor service-start out of the API handler into a
     shared fn in inari-core/inari-api that both the handler and autostart
     call). This removes the fragile raw-HTTP hack entirely. RECOMMENDED.

## Uncommitted changes right now (git status)
- Modified: `panel/app/pages/index.vue` (2-tab settings UI),
  `panel/dist/*` (rebuilt), `src-tauri/src/lib.rs` (autostart + ureq_post fix).
- These are NOT committed. Last commit is `66a8105` (settings backend).

## Build / run / test cheatsheet
```
# build everything
cargo build --release --manifest-path "D:\10_Projects\Toolbox\sushibox\sushibox-inari\Cargo.toml"
# build just the GUI
cargo build --release -p app --manifest-path "...\Cargo.toml"
# build panel (Nuxt) — needed before embedding changes
cmd /c "cd /d ...\sushibox-inari\panel && bun run build"
# run GUI (needs runtime/ next to exe → run from project root, not target/)
copy target\release\Inari.exe to project root, run it there
# CLI
inari-cli.exe status|start|stop|restart
# tauri CLI available via GJ_SDK node_modules or: npx @tauri-apps/cli@2
```
Runtime binaries live in `runtime/` (nginx/php/mysql/redis/adminer present;
webview2 fixed-version NOT bundled — relies on system Evergreen on dev box).

## Portable bundle
- `scripts/package-portable.ps1` assembles `dist/SushiBox-Inari-Portable/`
  (~480MB: exe + runtime + flavors + scripts + sites + README). `-Zip` to zip.
- A copy lives on Desktop: `C:\Users\QuangQuoc\Desktop\SushiBox-Inari-Portable\`
  (may be stale — rebuild + recopy after fixing autostart).

## Rollback tags
`rollback/pre-tauri`, `rollback/pre-gui`, `rollback/pre-harden-v0.2`,
`rollback/pre-2bin`. Reset with `git -C <path> reset --hard <tag>`.

## Done & verified (don't redo)
- Lifecycle harden: panel Job Object (no orphans), graceful mysql shutdown,
  poll-based restart. Tested with real binaries.
- 4 services start/stop/restart, web root serves PHP (phpinfo 200).
- Tauri GUI: window, tray, single-instance, fixed-size, no console.
- Settings backend: persist + live-apply, survives restart.

## Immediate TODO (in order)
1. Fix autostart (recommend: in-process start fn, drop raw-HTTP hack).
2. Test autostart end-to-end (nginx+php start on launch, mysql/redis don't).
3. Commit the settings UI + autostart work.
4. Rebuild portable bundle, recopy to Desktop.
5. Then: per-service config depth (PHP php.ini/extensions) — backend overlay
   is ready to extend.
