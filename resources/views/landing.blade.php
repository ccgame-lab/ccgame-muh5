@php
    // ===== Landing dopamine MU Archangel H5 (khách organic / no_session) =====
    // Thiết kế qua claude.ai/design -> port. Theme dark #07070a + gold #c9a94e.
    $portalUrl   = config('portal.url') ?: 'https://ccgame.org';
    $playUrl     = $playUrl     ?? $portalUrl;                  // CTA "Chơi Ngay" -> cổng CCGame (auth + launch)
    $fbUrl       = $fbUrl       ?? 'https://www.facebook.com/groups/gcenter.vn';                 // Nhóm FB acquisition (admin: owner, công khai 622+)
    $fanpageUrl  = $fanpageUrl  ?? 'https://www.facebook.com/profile.php?id=61591520487111';     // Fanpage CCGame (đổi sang vanity khi set username)
    $ogImage     = url('/assets/landing/og-image.jpg');
    $canonical   = url('/');
    // muh5 chỉ có 3 class: MG (Đấu Sĩ), DK (Chiến Binh), ELF (Tiên Nữ)
    $classes = [
        ['code' => 'MG',  'name' => 'Đấu Sĩ',     'en' => 'Magic Gladiator',  'role' => 'Lai kiếm-phép · Linh hoạt',  'desc' => 'Vừa chém vừa nổ phép, không tốn ô năng lượng riêng. Cơ động, mạnh sớm, càn quét tốt cả khi solo lẫn đi nhóm.'],
        ['code' => 'DK',  'name' => 'Chiến Binh', 'en' => 'Dark Knight',      'role' => 'Cận chiến · Tank-DPS',       'desc' => 'Cầm đại đao xông thẳng vào trận. Máu dày, sát thương cao, đứng tuyến đầu gánh team trong cả PvP lẫn săn boss.'],
        ['code' => 'ELF', 'name' => 'Tiên Nữ',    'en' => 'Fairy Elf',        'role' => 'Tầm xa · Hỗ trợ',            'desc' => 'Cung thủ bắn tỉa kiêm buff đồng đội. Tăng sức mạnh cả nhóm và rỉa boss từ khoảng cách an toàn.'],
    ];
@endphp
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MU Archangel H5 - Đào MU private miễn phí, cày là có đồ | CCGame</title>
    <meta name="description" content="MU Online private bản H5 chơi ngay trên trình duyệt. Free wing, ngọc, set tân thủ, auto reset, nạp đầu x10, săn boss đua top. Đăng nhập qua cổng CCGame, không cần tải.">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ $canonical }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}">

    {{-- Open Graph (quyết định preview khi dán link vào nhóm FB / forum) --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="MU Archangel H5">
    <meta property="og:locale" content="vi_VN">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:title" content="MU Archangel H5 - Vào là có đồ, cày là ra ngọc">
    <meta property="og:description" content="MU Online private H5: free wing/ngọc/set tân thủ, auto reset, nạp đầu x10. Chơi thẳng trên trình duyệt qua cổng CCGame.">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="MU Archangel H5 - Vào là có đồ, cày là ra ngọc">
    <meta name="twitter:description" content="MU Online private H5: free đồ, auto reset, nạp đầu x10. Chơi thẳng trên trình duyệt qua cổng CCGame.">
    <meta name="twitter:image" content="{{ $ogImage }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&family=Playfair+Display:wght@700;800;900&display=swap" rel="stylesheet">

    @verbatim
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        html{scroll-behavior:smooth}
        body{background:#07070a;color:#ece8df;font-family:'Be Vietnam Pro',sans-serif;-webkit-font-smoothing:antialiased;overflow-x:hidden}
        ::selection{background:#c9a94e;color:#07070a}
        a{color:inherit;text-decoration:none}
        @keyframes fadeUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:none}}
        @keyframes ctaPulse{0%,100%{transform:scale(1)}50%{transform:scale(1.03)}}
        @keyframes floaty{0%,100%{transform:translateY(0)}50%{transform:translateY(-10px)}}
        /* hover thật (thay style-hover của Claude Design) */
        .lx-cta{transition:background .18s ease,border-color .18s ease,transform .18s ease}
        .lx-cta-solid:hover{background:#e6cf8d}
        .lx-cta-out:hover{background:rgba(201,169,78,.09);border-color:#c9a94e}
        .lx-nav{transition:color .15s ease}
        .lx-nav:hover{color:#c9a94e}
        .lx-foot{transition:color .15s ease}
        .lx-foot:hover{color:#c9a94e}
        /* class selector */
        .lx-clsbtn{transition:background .15s ease}
        .lx-clsbtn:hover{background:rgba(201,169,78,.05)}
        .lx-clsbtn .lx-clsbar{display:none}
        .lx-clsbtn.is-active .lx-clsbar{display:block}
        .lx-clsbtn .lx-clsarrow{display:none}
        .lx-clsbtn.is-active .lx-clsarrow{display:inline}
        .lx-clscode{display:flex;align-items:center;justify-content:center;width:46px;height:34px;border:1px solid rgba(201,169,78,.35);color:#c9a94e;font-family:'Playfair Display',serif;font-weight:800;font-size:16px;border-radius:2px;flex:0 0 auto}
        .lx-clsbtn.is-active .lx-clscode{background:#c9a94e;color:#07070a;border-color:transparent}
        @media (prefers-reduced-motion: reduce){*{animation:none !important;scroll-behavior:auto !important}}
    </style>
    @endverbatim
</head>
<body>
<div style="position:relative">

    <header style="position:sticky;top:0;z-index:50;background:rgba(7,7,10,.82);backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px);border-bottom:1px solid rgba(201,169,78,.16)">
        <div style="max-width:1240px;margin:0 auto;padding:0 clamp(20px,5vw,40px);min-height:70px;display:flex;align-items:center;justify-content:space-between;gap:18px;flex-wrap:wrap">
            <a href="{{ $playUrl }}" style="display:flex;align-items:baseline;gap:9px;font-family:'Playfair Display',serif;font-weight:900;font-size:22px;letter-spacing:.03em">
                <span style="color:#c9a94e">MU</span><span style="color:#ece8df">ARCHANGEL</span><span style="font-family:'Be Vietnam Pro',sans-serif;font-weight:700;font-size:12px;color:#c9a94e;border:1px solid rgba(201,169,78,.45);padding:2px 6px;border-radius:2px;letter-spacing:.06em">H5</span>
            </a>
            <nav style="display:flex;align-items:center;gap:26px;flex-wrap:wrap">
                <a href="#dacsac" class="lx-nav" style="font-size:14px;font-weight:600;color:#b0aa9c;letter-spacing:.01em">Đặc sắc</a>
                <a href="#nhanvat" class="lx-nav" style="font-size:14px;font-weight:600;color:#b0aa9c;letter-spacing:.01em">Nhân vật</a>
                <a href="#sukien" class="lx-nav" style="font-size:14px;font-weight:600;color:#b0aa9c;letter-spacing:.01em">Sự kiện</a>
                <a href="#hinhanh" class="lx-nav" style="font-size:14px;font-weight:600;color:#b0aa9c;letter-spacing:.01em">Hình ảnh</a>
                <a href="#congdong" class="lx-nav" style="font-size:14px;font-weight:600;color:#b0aa9c;letter-spacing:.01em">Cộng đồng</a>
            </nav>
            <a href="{{ $playUrl }}" class="lx-cta lx-cta-solid" style="background:#c9a94e;color:#07070a;font-weight:800;font-size:14px;letter-spacing:.04em;padding:11px 22px;border-radius:3px;white-space:nowrap">CHƠI NGAY</a>
        </div>
    </header>

    <section style="position:relative;min-height:100dvh;display:flex;align-items:center;overflow:hidden;border-bottom:1px solid rgba(201,169,78,.12)">
        <div style="position:absolute;inset:0;background:radial-gradient(58% 68% at 80% 44%, rgba(201,169,78,.18), rgba(201,169,78,.04) 42%, transparent 70%);pointer-events:none"></div>
        <div style="position:absolute;inset:0;background:linear-gradient(180deg,rgba(7,7,10,0) 60%,rgba(7,7,10,.55) 88%,#07070a);pointer-events:none"></div>
        <div style="position:relative;max-width:1240px;margin:0 auto;padding:108px clamp(20px,5vw,40px) 72px;width:100%;display:flex;flex-wrap:wrap;align-items:center;gap:clamp(32px,5vw,72px)">

            <div style="flex:1 1 460px;min-width:300px">
                <div style="display:flex;align-items:center;gap:11px;margin-bottom:22px">
                    <span style="width:9px;height:9px;background:#c9a94e;display:inline-block"></span>
                    <span style="font-size:12px;font-weight:700;letter-spacing:.24em;text-transform:uppercase;color:#c9a94e">MU Archangel H5 · Server S1 đang mở</span>
                </div>
                <h1 style="font-family:'Playfair Display',serif;font-weight:900;font-size:clamp(38px,5.4vw,72px);line-height:1.02;letter-spacing:-.01em;margin-bottom:24px">
                    <span style="background:linear-gradient(180deg,#f4e9c8,#c9a94e 55%,#9c7f3a);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;color:#c9a94e">Vào là có đồ.</span><br>
                    <span style="color:#ece8df">Cày là ra ngọc.</span>
                </h1>
                <p style="font-size:clamp(16px,1.5vw,19px);line-height:1.62;color:#b6b0a2;max-width:540px;margin-bottom:30px">
                    Free wing, ngọc và full set tân thủ ngay khi vào game. Đồ mạnh từ boss, cày là có. Đăng nhập qua cổng CCGame, chơi thẳng trên trình duyệt, không cần tải.
                </p>
                <div style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:34px">
                    <span style="display:flex;align-items:center;gap:8px;border:1px solid rgba(201,169,78,.3);color:#d8cfb6;font-size:13px;font-weight:600;padding:8px 14px;border-radius:999px"><span style="color:#c9a94e;font-size:10px">◆</span>Free đồ + ngọc + wing</span>
                    <span style="display:flex;align-items:center;gap:8px;border:1px solid rgba(201,169,78,.3);color:#d8cfb6;font-size:13px;font-weight:600;padding:8px 14px;border-radius:999px"><span style="color:#c9a94e;font-size:10px">◆</span>Auto reset, cày là có</span>
                    <span style="display:flex;align-items:center;gap:8px;border:1px solid rgba(201,169,78,.3);color:#d8cfb6;font-size:13px;font-weight:600;padding:8px 14px;border-radius:999px"><span style="color:#c9a94e;font-size:10px">◆</span>Nạp đầu x10 + GiftCode</span>
                </div>
                <div style="display:flex;flex-wrap:wrap;gap:14px;margin-bottom:18px">
                    <a href="{{ $playUrl }}" class="lx-cta lx-cta-solid" style="background:#c9a94e;color:#07070a;font-weight:800;font-size:16px;letter-spacing:.04em;padding:16px 36px;border-radius:3px;white-space:nowrap;box-shadow:0 10px 34px rgba(201,169,78,.26);animation:ctaPulse 2.6s ease-in-out infinite">CHƠI NGAY</a>
                    <a href="#sukien" class="lx-cta lx-cta-out" style="background:transparent;border:1px solid rgba(201,169,78,.55);color:#e6d3a3;font-weight:700;font-size:16px;padding:15px 30px;border-radius:3px;white-space:nowrap">Nhận GiftCode tân thủ</a>
                </div>
                <div style="display:flex;align-items:center;flex-wrap:wrap;gap:10px;font-size:13px;color:#8a8475">
                    <span>Miễn phí vào chơi</span><span style="color:#c9a94e">▪</span><span>Đăng nhập qua cổng CCGame</span>
                </div>
            </div>

            <div style="flex:1 1 420px;min-width:300px;align-self:stretch;display:flex">
                <div style="position:relative;flex:1;min-height:clamp(420px,62vh,640px);animation:floaty 7s ease-in-out infinite">
                    <div style="position:absolute;inset:0;border:1px solid rgba(201,169,78,.22);border-radius:4px;overflow:hidden;background:#0b0b10">
                        <img src="{{ asset('assets/landing/hero-knight.jpg') }}" alt="MU Archangel H5 - Chiến binh giáp vàng" loading="eager" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;object-position:center 18%">
                        <div style="position:absolute;inset:0;background:radial-gradient(60% 70% at 50% 30%, rgba(201,169,78,.16), transparent 72%);mix-blend-mode:screen;pointer-events:none"></div>
                        <div style="position:absolute;inset:0;background:linear-gradient(180deg,rgba(7,7,10,0) 55%,rgba(7,7,10,.6) 100%);pointer-events:none"></div>
                        <span style="position:absolute;top:10px;left:10px;width:26px;height:26px;border-top:2px solid #c9a94e;border-left:2px solid #c9a94e"></span>
                        <span style="position:absolute;bottom:10px;right:10px;width:26px;height:26px;border-bottom:2px solid #c9a94e;border-right:2px solid #c9a94e"></span>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <div style="border-bottom:1px solid rgba(201,169,78,.12);background:#090910">
        <div style="max-width:1240px;margin:0 auto;padding:0 clamp(20px,5vw,40px)">
            <div style="display:flex;flex-wrap:wrap;font-family:ui-monospace,SFMono-Regular,monospace">
                <div style="flex:1 1 170px;padding:22px 18px;border-left:1px solid rgba(201,169,78,.1)">
                    <div style="font-size:10px;letter-spacing:.2em;color:#c9a94e;margin-bottom:6px">PHIÊN BẢN</div>
                    <div style="font-size:15px;color:#ece8df">MU H5 Season</div>
                </div>
                <div style="flex:1 1 170px;padding:22px 18px;border-left:1px solid rgba(201,169,78,.1)">
                    <div style="font-size:10px;letter-spacing:.2em;color:#c9a94e;margin-bottom:6px">LỐI CHƠI</div>
                    <div style="font-size:15px;color:#ece8df">Cày &amp; săn boss</div>
                </div>
                <div style="flex:1 1 170px;padding:22px 18px;border-left:1px solid rgba(201,169,78,.1)">
                    <div style="font-size:10px;letter-spacing:.2em;color:#c9a94e;margin-bottom:6px">NỀN TẢNG</div>
                    <div style="font-size:15px;color:#ece8df">H5 trình duyệt</div>
                </div>
                <div style="flex:1 1 170px;padding:22px 18px;border-left:1px solid rgba(201,169,78,.1)">
                    <div style="font-size:10px;letter-spacing:.2em;color:#c9a94e;margin-bottom:6px">RESET</div>
                    <div style="font-size:15px;color:#ece8df">Tự động</div>
                </div>
                <div style="flex:1 1 170px;padding:22px 18px;border-left:1px solid rgba(201,169,78,.1)">
                    <div style="font-size:10px;letter-spacing:.2em;color:#c9a94e;margin-bottom:6px">ĐĂNG NHẬP</div>
                    <div style="font-size:15px;color:#ece8df">Cổng CCGame</div>
                </div>
            </div>
        </div>
    </div>

    <section id="dacsac" style="padding:clamp(72px,9vw,118px) 0">
        <div style="max-width:1240px;margin:0 auto;padding:0 clamp(20px,5vw,40px)">
            <div style="display:flex;flex-wrap:wrap;align-items:flex-end;justify-content:space-between;gap:24px">
                <div>
                    <div style="display:flex;align-items:center;gap:12px;margin-bottom:18px">
                        <span style="width:26px;height:1px;background:#c9a94e;display:inline-block"></span>
                        <span style="font-size:12px;font-weight:700;letter-spacing:.28em;text-transform:uppercase;color:#c9a94e">Đặc sắc server</span>
                    </div>
                    <h2 style="font-family:'Playfair Display',serif;font-weight:800;font-size:clamp(30px,3.6vw,48px);line-height:1.08;color:#ece8df;max-width:620px">Vì sao dân cày ở lại</h2>
                </div>
                <p style="font-size:15px;line-height:1.65;color:#9a9488;max-width:380px">Đồ mạnh nhất đi ra từ boss và sự kiện, không khoá sau ví tiền. Tân thủ cày là có đồ, bắt nhịp nhanh ngay từ phút đầu.</p>
            </div>

            <div style="border-top:1px solid rgba(201,169,78,.3);padding-top:30px;margin-top:46px;display:flex;flex-wrap:wrap;gap:32px;align-items:flex-start">
                <div style="flex:0 0 auto;font-family:'Playfair Display',serif;font-weight:900;font-size:clamp(44px,5.5vw,72px);color:#c9a94e;line-height:.85;min-width:88px">00</div>
                <div style="flex:1 1 340px">
                    <h3 style="font-family:'Playfair Display',serif;font-weight:700;font-size:clamp(22px,2.4vw,30px);color:#ece8df;margin-bottom:12px">Đồ xịn từ boss, không từ shop</h3>
                    <p style="font-size:16px;line-height:1.62;color:#a8a296;max-width:680px">Cày là có, train là ra đồ. Mọi món mạnh nhất đều rơi từ boss và sự kiện, không bị khoá sau webshop. Càng chăm săn càng giàu, công cày được trả công xứng đáng.</p>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(228px,1fr));gap:1px;background:rgba(201,169,78,.13);border:1px solid rgba(201,169,78,.13);margin-top:46px">
                <div style="background:#09090f;padding:30px 26px">
                    <div style="font-family:'Playfair Display',serif;font-size:20px;color:#c9a94e;margin-bottom:14px">01</div>
                    <h4 style="font-size:17px;font-weight:700;color:#ece8df;margin-bottom:9px">Auto reset</h4>
                    <p style="font-size:14px;line-height:1.6;color:#928c80">Reset không giới hạn, mỗi lần reset cộng thưởng và mạnh thêm. Càng cày càng bứt tốp.</p>
                </div>
                <div style="background:#09090f;padding:30px 26px">
                    <div style="font-family:'Playfair Display',serif;font-size:20px;color:#c9a94e;margin-bottom:14px">02</div>
                    <h4 style="font-size:17px;font-weight:700;color:#ece8df;margin-bottom:9px">Free tân thủ</h4>
                    <p style="font-size:14px;line-height:1.6;color:#928c80">Vào game nhận ngay wing, ngọc và set khởi đầu để bắt nhịp cày kéo từ phút đầu.</p>
                </div>
                <div style="background:#09090f;padding:30px 26px">
                    <div style="font-family:'Playfair Display',serif;font-size:20px;color:#c9a94e;margin-bottom:14px">03</div>
                    <h4 style="font-size:17px;font-weight:700;color:#ece8df;margin-bottom:9px">Săn boss &amp; sự kiện</h4>
                    <p style="font-size:14px;line-height:1.6;color:#928c80">Boss xuất hiện theo giờ, sự kiện liên tục, đua top nhận quà giá trị.</p>
                </div>
                <div style="background:#09090f;padding:30px 26px">
                    <div style="font-family:'Playfair Display',serif;font-size:20px;color:#c9a94e;margin-bottom:14px">04</div>
                    <h4 style="font-size:17px;font-weight:700;color:#ece8df;margin-bottom:9px">Hệ thống Cánh</h4>
                    <p style="font-size:14px;line-height:1.6;color:#928c80">Cánh nhiều cấp, nâng bằng nguyên liệu cày được. Lên cánh là lên lực chiến.</p>
                </div>
            </div>
        </div>
    </section>

    <section id="nhanvat" style="padding:clamp(72px,9vw,118px) 0;background:#090910;border-top:1px solid rgba(201,169,78,.12);border-bottom:1px solid rgba(201,169,78,.12)">
        <div style="max-width:1240px;margin:0 auto;padding:0 clamp(20px,5vw,40px)">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:18px">
                <span style="width:26px;height:1px;background:#c9a94e;display:inline-block"></span>
                <span style="font-size:12px;font-weight:700;letter-spacing:.28em;text-transform:uppercase;color:#c9a94e">Chọn lối chơi</span>
            </div>
            <h2 style="font-family:'Playfair Display',serif;font-weight:800;font-size:clamp(30px,3.6vw,48px);line-height:1.08;color:#ece8df;max-width:620px">Ba class, ba kiểu cày</h2>

            <div style="display:flex;flex-wrap:wrap;gap:clamp(22px,3vw,44px);margin-top:46px">
                <div style="flex:1 1 290px;min-width:270px;border:1px solid rgba(201,169,78,.18);align-self:flex-start">
                    @foreach($classes as $i => $cls)
                        <button type="button" onclick="lxSel({{ $i }})" data-idx="{{ $i }}" class="lx-clsbtn {{ $i === 0 ? 'is-active' : '' }}" style="position:relative;display:flex;align-items:center;gap:14px;width:100%;text-align:left;background:transparent;border:0;border-bottom:1px solid rgba(201,169,78,.1);padding:16px 18px;cursor:pointer;color:#ece8df;font-family:'Be Vietnam Pro',sans-serif">
                            <span class="lx-clsbar" style="position:absolute;left:0;top:0;bottom:0;width:3px;background:#c9a94e"></span>
                            <span class="lx-clscode">{{ $cls['code'] }}</span>
                            <span style="font-size:15px;font-weight:600;letter-spacing:.01em">{{ $cls['name'] }}</span>
                            <span class="lx-clsarrow" style="margin-left:auto;color:#c9a94e;font-size:18px">›</span>
                        </button>
                    @endforeach
                </div>

                <div style="flex:1.4 1 380px;min-width:300px;display:flex;flex-direction:column">
                    <div style="position:relative;flex:1;min-height:360px;background:repeating-linear-gradient(45deg,#0b0b10,#0b0b10 11px,#0e0e15 11px,#0e0e15 22px);border:1px solid rgba(201,169,78,.2);border-radius:4px;overflow:hidden;display:flex;align-items:center;justify-content:center;text-align:center">
                        <div style="position:absolute;inset:0;background:radial-gradient(54% 60% at 50% 38%, rgba(201,169,78,.12), transparent 70%)"></div>
                        <div style="position:relative;font-family:ui-monospace,SFMono-Regular,monospace;font-size:12px;letter-spacing:.16em;color:#8a8475;line-height:2;text-transform:uppercase">
                            <div style="color:#c9a94e;font-weight:700;letter-spacing:.22em;margin-bottom:8px">[ ẢNH CLASS ]</div>
                            <span id="lx-img-label">{{ $classes[0]['code'] }} · {{ $classes[0]['name'] }}</span><br><span style="opacity:.6">sprite + icon skill</span>
                        </div>
                        <div style="position:absolute;left:0;right:0;bottom:0;padding:22px;background:linear-gradient(180deg,transparent,rgba(7,7,10,.92));text-align:left">
                            <div id="lx-cls-name" style="font-family:'Playfair Display',serif;font-weight:800;font-size:clamp(26px,3vw,38px);color:#ece8df;line-height:1">{{ $classes[0]['name'] }}</div>
                            <div id="lx-cls-en" style="font-size:13px;letter-spacing:.14em;text-transform:uppercase;color:#c9a94e;margin-top:6px;font-family:ui-monospace,monospace">{{ $classes[0]['en'] }}</div>
                        </div>
                    </div>
                    <div style="padding-top:22px">
                        <div id="lx-cls-role" style="display:inline-block;font-size:12px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#c9a94e;border:1px solid rgba(201,169,78,.3);padding:6px 12px;border-radius:999px;margin-bottom:16px">{{ $classes[0]['role'] }}</div>
                        <p id="lx-cls-desc" style="font-size:16px;line-height:1.62;color:#a8a296;max-width:560px;margin-bottom:22px">{{ $classes[0]['desc'] }}</p>
                        <a href="{{ $playUrl }}" class="lx-cta lx-cta-solid" style="display:inline-block;background:#c9a94e;color:#07070a;font-weight:800;font-size:15px;letter-spacing:.03em;padding:13px 28px;border-radius:3px;white-space:nowrap">Vào game thử class này</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="sukien" style="padding:clamp(72px,9vw,118px) 0">
        <div style="max-width:1240px;margin:0 auto;padding:0 clamp(20px,5vw,40px)">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:18px">
                <span style="width:26px;height:1px;background:#c9a94e;display:inline-block"></span>
                <span style="font-size:12px;font-weight:700;letter-spacing:.28em;text-transform:uppercase;color:#c9a94e">Sự kiện &amp; quà</span>
            </div>
            <h2 style="font-family:'Playfair Display',serif;font-weight:800;font-size:clamp(30px,3.6vw,48px);line-height:1.08;color:#ece8df;max-width:620px">Vào sớm, nhận sớm</h2>

            <div style="display:flex;flex-wrap:wrap;gap:20px;margin-top:46px">
                <div style="flex:1 1 320px;position:relative;border:1px solid rgba(201,169,78,.3);background:#0c0c13;border-radius:4px;padding:34px;overflow:hidden">
                    <div style="position:absolute;right:-10px;top:-30px;font-family:'Playfair Display',serif;font-weight:900;font-size:clamp(80px,12vw,150px);line-height:1;color:rgba(201,169,78,.07)">x10</div>
                    <div style="position:relative">
                        <div style="font-family:'Playfair Display',serif;font-weight:900;font-size:clamp(40px,6vw,68px);line-height:.9;background:linear-gradient(180deg,#f4e9c8,#c9a94e 60%,#9c7f3a);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;color:#c9a94e;margin-bottom:14px">x10</div>
                        <h3 style="font-size:20px;font-weight:700;color:#ece8df;margin-bottom:10px">Nạp đầu nhân 10</h3>
                        <p style="font-size:15px;line-height:1.6;color:#9a9488;margin-bottom:24px;max-width:420px">Lần nạp đầu tiên được nhân 10 giá trị nhận về. Áp dụng cho mọi tài khoản mới, một lần duy nhất.</p>
                        <a href="{{ $playUrl }}" class="lx-cta lx-cta-solid" style="display:inline-block;background:#c9a94e;color:#07070a;font-weight:800;font-size:15px;padding:13px 26px;border-radius:3px;white-space:nowrap">Nạp ngay</a>
                    </div>
                </div>
                <div style="flex:1 1 320px;border:1px solid rgba(201,169,78,.18);background:#0c0c13;border-radius:4px;padding:34px;display:flex;flex-direction:column">
                    <h3 style="font-size:20px;font-weight:700;color:#ece8df;margin-bottom:10px">GiftCode tân thủ</h3>
                    <p style="font-size:15px;line-height:1.6;color:#9a9488;margin-bottom:20px;max-width:420px">Nhập code khi tạo nhân vật để nhận gói quà khởi đầu: ngọc, vàng và vật phẩm hỗ trợ cày level.</p>
                    <div style="display:inline-flex;align-self:flex-start;align-items:center;gap:10px;border:1px dashed rgba(201,169,78,.5);border-radius:4px;padding:11px 16px;font-family:ui-monospace,SFMono-Regular,monospace;color:#c9a94e;letter-spacing:.16em;font-size:13px;margin-bottom:24px">[ NHẬP CODE TRONG NHÓM ]</div>
                    <a href="{{ $fbUrl }}" target="_blank" rel="noopener" class="lx-cta lx-cta-out" style="display:inline-block;align-self:flex-start;margin-top:auto;background:transparent;border:1px solid rgba(201,169,78,.55);color:#e6d3a3;font-weight:700;font-size:15px;padding:12px 24px;border-radius:3px;white-space:nowrap">Vào nhóm lấy code</a>
                </div>
            </div>

            <div style="margin-top:44px;border-top:1px solid rgba(201,169,78,.2);padding-top:30px">
                <div style="font-size:13px;font-weight:700;letter-spacing:.16em;text-transform:uppercase;color:#c9a94e;margin-bottom:24px">Mốc nạp tích luỹ</div>
                <div style="display:flex;flex-wrap:wrap;gap:1px;background:rgba(201,169,78,.13);border:1px solid rgba(201,169,78,.13)">
                    <div style="flex:1 1 210px;background:#09090f;padding:26px 22px">
                        <div style="font-family:'Playfair Display',serif;font-size:22px;color:#c9a94e;margin-bottom:10px">Mốc 1</div>
                        <p style="font-size:14px;line-height:1.55;color:#a8a296">Hộp Ngọc khởi đầu + Ngựa cưỡi 7 ngày</p>
                    </div>
                    <div style="flex:1 1 210px;background:#09090f;padding:26px 22px">
                        <div style="font-family:'Playfair Display',serif;font-size:22px;color:#c9a94e;margin-bottom:10px">Mốc 2</div>
                        <p style="font-size:14px;line-height:1.55;color:#a8a296">Cánh cấp 2 + Pet đồng hành</p>
                    </div>
                    <div style="flex:1 1 210px;background:#09090f;padding:26px 22px">
                        <div style="font-family:'Playfair Display',serif;font-size:22px;color:#c9a94e;margin-bottom:10px">Mốc 3</div>
                        <p style="font-size:14px;line-height:1.55;color:#a8a296">Set đồ cấp cao + Ngọc ép trang bị</p>
                    </div>
                    <div style="flex:1 1 210px;background:#09090f;padding:26px 22px">
                        <div style="font-family:'Playfair Display',serif;font-size:22px;color:#c9a94e;margin-bottom:10px">Mốc 4</div>
                        <p style="font-size:14px;line-height:1.55;color:#a8a296">Tước hiệu VIP + Skin vũ khí giới hạn</p>
                    </div>
                </div>
                <p style="font-size:13px;color:#6f6a5e;margin-top:16px;font-family:ui-monospace,SFMono-Regular,monospace">* Phần thưởng và mốc cụ thể cập nhật trong game.</p>
            </div>
        </div>
    </section>

    <section id="hinhanh" style="padding:clamp(72px,9vw,118px) 0;background:#090910;border-top:1px solid rgba(201,169,78,.12);border-bottom:1px solid rgba(201,169,78,.12)">
        <div style="max-width:1240px;margin:0 auto;padding:0 clamp(20px,5vw,40px)">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:18px">
                <span style="width:26px;height:1px;background:#c9a94e;display:inline-block"></span>
                <span style="font-size:12px;font-weight:700;letter-spacing:.28em;text-transform:uppercase;color:#c9a94e">Hình ảnh in-game</span>
            </div>
            <h2 style="font-family:'Playfair Display',serif;font-weight:800;font-size:clamp(30px,3.6vw,48px);line-height:1.08;color:#ece8df;max-width:620px">Nhìn là muốn cày</h2>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));grid-auto-rows:160px;gap:14px;margin-top:46px">
                <div style="grid-row:span 2;position:relative;background:repeating-linear-gradient(45deg,#0b0b10,#0b0b10 11px,#0e0e15 11px,#0e0e15 22px);border:1px solid rgba(201,169,78,.18);border-radius:4px;display:flex;align-items:flex-end;padding:18px"><span style="font-family:ui-monospace,SFMono-Regular,monospace;font-size:11px;letter-spacing:.16em;color:#c9a94e;text-transform:uppercase">[ Boss thế giới ]</span></div>
                <div style="position:relative;background:repeating-linear-gradient(45deg,#0b0b10,#0b0b10 11px,#0e0e15 11px,#0e0e15 22px);border:1px solid rgba(201,169,78,.18);border-radius:4px;display:flex;align-items:flex-end;padding:18px"><span style="font-family:ui-monospace,SFMono-Regular,monospace;font-size:11px;letter-spacing:.16em;color:#c9a94e;text-transform:uppercase">[ PvP công thành ]</span></div>
                <div style="position:relative;background:repeating-linear-gradient(45deg,#0b0b10,#0b0b10 11px,#0e0e15 11px,#0e0e15 22px);border:1px solid rgba(201,169,78,.18);border-radius:4px;display:flex;align-items:flex-end;padding:18px"><span style="font-family:ui-monospace,SFMono-Regular,monospace;font-size:11px;letter-spacing:.16em;color:#c9a94e;text-transform:uppercase">[ Bản đồ &amp; cảnh ]</span></div>
                <div style="grid-row:span 2;position:relative;background:repeating-linear-gradient(45deg,#0b0b10,#0b0b10 11px,#0e0e15 11px,#0e0e15 22px);border:1px solid rgba(201,169,78,.18);border-radius:4px;display:flex;align-items:flex-end;padding:18px"><span style="font-family:ui-monospace,SFMono-Regular,monospace;font-size:11px;letter-spacing:.16em;color:#c9a94e;text-transform:uppercase">[ Cánh tiến hoá ]</span></div>
                <div style="position:relative;background:repeating-linear-gradient(45deg,#0b0b10,#0b0b10 11px,#0e0e15 11px,#0e0e15 22px);border:1px solid rgba(201,169,78,.18);border-radius:4px;display:flex;align-items:flex-end;padding:18px"><span style="font-family:ui-monospace,SFMono-Regular,monospace;font-size:11px;letter-spacing:.16em;color:#c9a94e;text-transform:uppercase">[ Giao diện in-game ]</span></div>
                <div style="position:relative;background:repeating-linear-gradient(45deg,#0b0b10,#0b0b10 11px,#0e0e15 11px,#0e0e15 22px);border:1px solid rgba(201,169,78,.18);border-radius:4px;display:flex;align-items:flex-end;padding:18px"><span style="font-family:ui-monospace,SFMono-Regular,monospace;font-size:11px;letter-spacing:.16em;color:#c9a94e;text-transform:uppercase">[ BXH &amp; đua top ]</span></div>
            </div>
        </div>
    </section>

    <section id="congdong" style="padding:clamp(72px,9vw,118px) 0">
        <div style="max-width:1240px;margin:0 auto;padding:0 clamp(20px,5vw,40px)">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:18px">
                <span style="width:26px;height:1px;background:#c9a94e;display:inline-block"></span>
                <span style="font-size:12px;font-weight:700;letter-spacing:.28em;text-transform:uppercase;color:#c9a94e">Cộng đồng</span>
            </div>
            <h2 style="font-family:'Playfair Display',serif;font-weight:800;font-size:clamp(30px,3.6vw,48px);line-height:1.08;color:#ece8df;max-width:620px;margin-bottom:14px">Vào nhóm trước khi cày</h2>
            <p style="font-size:15px;line-height:1.65;color:#9a9488;max-width:520px">Nơi anh em tìm guild, săn code, hỏi build và cập nhật sự kiện sớm nhất.</p>

            <div style="display:flex;flex-wrap:wrap;gap:20px;margin-top:40px">
                <div style="flex:1 1 320px;border:1px solid rgba(201,169,78,.18);background:#0c0c13;border-radius:4px;padding:32px;display:flex;flex-direction:column">
                    <h3 style="font-size:19px;font-weight:700;color:#ece8df;margin-bottom:10px">Nhóm Facebook</h3>
                    <p style="font-size:15px;line-height:1.6;color:#9a9488;margin-bottom:24px">Thảo luận, săn GiftCode, tìm guild và hỏi đáp cùng anh em đang cày.</p>
                    <a href="{{ $fbUrl }}" target="_blank" rel="noopener" class="lx-cta lx-cta-solid" style="display:inline-block;align-self:flex-start;margin-top:auto;background:#c9a94e;color:#07070a;font-weight:800;font-size:15px;padding:12px 26px;border-radius:3px;white-space:nowrap">Vào nhóm Facebook</a>
                </div>
                <div style="flex:1 1 320px;border:1px solid rgba(201,169,78,.18);background:#0c0c13;border-radius:4px;padding:32px;display:flex;flex-direction:column">
                    <h3 style="font-size:19px;font-weight:700;color:#ece8df;margin-bottom:10px">Fanpage</h3>
                    <p style="font-size:15px;line-height:1.6;color:#9a9488;margin-bottom:24px">Thông báo sự kiện, lịch bảo trì và cập nhật phiên bản chính thức.</p>
                    <a href="{{ $fanpageUrl }}" target="_blank" rel="noopener" class="lx-cta lx-cta-out" style="display:inline-block;align-self:flex-start;margin-top:auto;background:transparent;border:1px solid rgba(201,169,78,.55);color:#e6d3a3;font-weight:700;font-size:15px;padding:11px 24px;border-radius:3px;white-space:nowrap">Theo dõi Fanpage</a>
                </div>
            </div>
        </div>
    </section>

    <section style="position:relative;padding:clamp(80px,10vw,140px) 0;text-align:center;border-top:1px solid rgba(201,169,78,.2);overflow:hidden">
        <div style="position:absolute;inset:0;background:radial-gradient(50% 80% at 50% 0%, rgba(201,169,78,.16), transparent 65%);pointer-events:none"></div>
        <div style="position:relative;max-width:760px;margin:0 auto;padding:0 clamp(20px,5vw,40px)">
            <h2 style="font-family:'Playfair Display',serif;font-weight:900;font-size:clamp(36px,5.5vw,68px);line-height:1.02;margin-bottom:18px"><span style="background:linear-gradient(180deg,#f4e9c8,#c9a94e 55%,#9c7f3a);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;color:#c9a94e">Sẵn sàng cày chưa?</span></h2>
            <p style="font-size:clamp(16px,1.5vw,18px);line-height:1.6;color:#b6b0a2;margin-bottom:34px">Server S1 đang mở. Đăng nhập qua cổng CCGame và vào trận ngay trên trình duyệt.</p>
            <div style="display:flex;flex-wrap:wrap;gap:14px;justify-content:center">
                <a href="{{ $playUrl }}" class="lx-cta lx-cta-solid" style="background:#c9a94e;color:#07070a;font-weight:800;font-size:17px;letter-spacing:.04em;padding:17px 44px;border-radius:3px;white-space:nowrap;box-shadow:0 10px 34px rgba(201,169,78,.26);animation:ctaPulse 2.6s ease-in-out infinite">CHƠI NGAY</a>
                <a href="{{ $fbUrl }}" target="_blank" rel="noopener" class="lx-cta lx-cta-out" style="background:transparent;border:1px solid rgba(201,169,78,.55);color:#e6d3a3;font-weight:700;font-size:17px;padding:16px 34px;border-radius:3px;white-space:nowrap">Vào nhóm Facebook</a>
            </div>
        </div>
    </section>

    <footer style="border-top:1px solid rgba(201,169,78,.12);padding:38px 0;background:#090910">
        <div style="max-width:1240px;margin:0 auto;padding:0 clamp(20px,5vw,40px);display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:20px">
            <div>
                <div style="display:flex;align-items:baseline;gap:8px;font-family:'Playfair Display',serif;font-weight:900;font-size:18px;margin-bottom:6px"><span style="color:#c9a94e">MU</span><span style="color:#ece8df">ARCHANGEL</span><span style="font-family:'Be Vietnam Pro',sans-serif;font-weight:700;font-size:11px;color:#c9a94e;border:1px solid rgba(201,169,78,.4);padding:1px 5px;border-radius:2px">H5</span></div>
                <div style="font-size:13px;color:#6f6a5e;font-family:ui-monospace,SFMono-Regular,monospace">Vận hành qua cổng CCGame · muh5.ccgame.org</div>
            </div>
            <div style="display:flex;flex-wrap:wrap;gap:22px">
                <a href="#dacsac" class="lx-foot" style="font-size:13px;color:#8a8475">Đặc sắc</a>
                <a href="#nhanvat" class="lx-foot" style="font-size:13px;color:#8a8475">Nhân vật</a>
                <a href="#sukien" class="lx-foot" style="font-size:13px;color:#8a8475">Sự kiện</a>
                <a href="#congdong" class="lx-foot" style="font-size:13px;color:#8a8475">Cộng đồng</a>
            </div>
        </div>
        <div style="max-width:1240px;margin:24px auto 0;padding:0 clamp(20px,5vw,40px);font-size:12px;color:#5a554c">© {{ date('Y') }} MU Archangel H5. Phiên bản, tỉ lệ EXP/Drop và phần thưởng sự kiện được cập nhật chính thức trong game.</div>
    </footer>

</div>

<script>
    var LX_CLASSES = {!! json_encode($classes, JSON_UNESCAPED_UNICODE) !!};
    function lxSel(i){
        var c = LX_CLASSES[i];
        if(!c) return;
        document.querySelectorAll('.lx-clsbtn').forEach(function(b){
            b.classList.toggle('is-active', Number(b.getAttribute('data-idx')) === i);
        });
        var set = function(id, v){ var el = document.getElementById(id); if(el) el.textContent = v; };
        set('lx-img-label', c.code + ' · ' + c.name);
        set('lx-cls-name', c.name);
        set('lx-cls-en', c.en);
        set('lx-cls-role', c.role);
        set('lx-cls-desc', c.desc);
    }
</script>
</body>
</html>
