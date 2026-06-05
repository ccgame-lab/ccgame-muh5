# r2-release — phát hành asset client MU H5 lên Cloudflare R2

Tool để **cập nhật asset client** (config1.json, ảnh, js…) đang nằm trên R2 bucket
`game-assets` (path `h5/muh5/resource/`), serve qua `cdn.ccgame.org`.

## Mô hình hoạt động
- **Sửa ở local** (vd Việt hoá config1.json).
- **Upload chạy qua VPS**: máy local Windows không có rclone; VPS `ccgame-prod`
  đã có remote rclone `ccgame-r2:` hoạt động. Tool stream file lên VPS rồi
  `rclone rcat` vào R2 → **không cần R2 S3 key ở local**.
- **Purge CDN chạy ở local** qua Cloudflare API.

> ⚠️ **config1.json cache edge 31 ngày** (`max-age=2678400`). Ghi đè xong **chưa
> live** cho tới khi purge. Tool tự purge sau khi push (token đã test OK); nếu
> purge fail nó báo to + hướng dẫn purge tay.

## Yêu cầu
- Python 3 (đã có). Không cần boto3/rclone ở local.
- SSH tới `ccgame-prod` chạy được (`ssh ccgame-prod` không hỏi pass).
- `.env.local` ở repo root có các key (xem bên dưới).

## Cấu hình (`.env.local`, đã gitignore)
```
R2_PUBLIC_BASE=https://cdn.ccgame.org
R2_RESOURCE_BASE=h5/muh5/resource
R2_SSH_HOST=ccgame-prod
R2_RCLONE_PREFIX=ccgame-r2:game-assets
CLOUDFLARE_ZONE_ID=6b71362247c4b888559b60083d6a2209
CLOUDFLARE_API_TOKEN=<token có quyền Zone:Cache Purge cho ccgame.org>
```
> ℹ️ Token hiện tại (`cfat_…`) **hợp lệ cho purge** (đã test `purge_cache` OK).
> Lưu ý: token chỉ-Cache-Purge sẽ trả 401 ở endpoint `/user/tokens/verify` —
> đó là bình thường, không phải token hỏng; cứ test bằng `r2.py purge <url>`.

## Lệnh
Path tính **tương đối với `R2_RESOURCE_BASE`** (vd `resource/cfg/config1.json`).
Thêm tiền tố `/` để dùng key tuyệt đối.

```powershell
# Liệt kê
python r2.py ls resource/cfg

# Tải về để sửa (mặc định lưu ./work/<path>)
python r2.py pull resource/cfg/config1.json

# ...sửa file ./work/resource/cfg/config1.json...

# Upload (rclone qua VPS) + tự purge CDN; --backup lưu bản cũ .bak-YYYYMMDD trên R2
python r2.py push resource/cfg/config1.json --backup

# Chỉ upload, bỏ qua purge
python r2.py push resource/cfg/config1.json --no-purge

# Purge thủ công (path hoặc URL đầy đủ)
python r2.py purge resource/cfg/config1.json
```

## Quy trình Việt hoá config1.json (kết hợp tool cfg-i18n)
```powershell
python r2.py pull resource/cfg/config1.json                 # 1. kéo bản live về ./work
python ..\cfg-i18n\apply.py                                  # 2. apply glossary -> ghi đè ./work
python r2.py push resource/cfg/config1.json --backup         # 3. đẩy lên + purge
```
`apply.py` tự backup bản trước khi sửa (`config1.json.preVH-*`) và chỉ thay đúng
chuỗi trong glossary (exact match), **không đụng** thuật ngữ MU tiếng Anh.

## Purge tay (khi token hỏng)
Cloudflare dashboard → `ccgame.org` → Caching → Configuration → Purge Cache →
Custom Purge → dán URL, vd:
`https://cdn.ccgame.org/h5/muh5/resource/resource/cfg/config1.json`
