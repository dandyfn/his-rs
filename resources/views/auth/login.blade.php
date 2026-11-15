<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/medistock.css') }}">
</head>
<body class="halaman-login">

{{-- Latar animasi jaringan saraf: node bergerak, garis antarnode, dan sinyal yang merambat --}}
<canvas id="jaringan" aria-hidden="true"></canvas>

<div class="login-wrap">
    <section class="obat-card login-card">
        <div class="login-logo">
            <svg width="36" height="36" viewBox="0 0 36 36" aria-hidden="true">
                <rect width="36" height="36" rx="9" fill="#155eef"/>
                <path d="M15 8h6v7h7v6h-7v7h-6v-7H8v-6h7z" fill="#fff"/>
            </svg>
            <h1>{{ config('app.name') }}</h1>
        </div>
        <p class="teks-bantu">Hospital Information System. Masuk dengan akun sesuai peran Anda.</p>

        @if ($errors->any())
            <div class="obat-alert error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="obat-grid">
                <div>
                    <label for="username">Username</label>
                    <input id="username" name="username" value="{{ old('username') }}" required autofocus autocomplete="username">
                </div>
                <div>
                    <label for="password">Password</label>
                    <input id="password" type="password" name="password" required autocomplete="current-password">
                </div>
            </div>
            <p class="teks-bantu"><label style="font-weight:400"><input type="checkbox" name="ingat" value="1" style="width:auto"> Ingat saya</label></p>
            <button type="submit">Masuk</button>
        </form>
    </section>
</div>
<p class="login-catatan">Pendaftaran · Antrean Rawat Jalan · MediStock RS</p>

<script>
(() => {
    const canvas = document.getElementById('jaringan');
    const ctx = canvas.getContext('2d');
    const diam = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let W, H, dpr, node = [], sinyal = [], jarakMaks;
    const mouse = { x: -9999, y: -9999 };

    function ukur() {
        dpr = Math.min(window.devicePixelRatio || 1, 2);
        W = window.innerWidth; H = window.innerHeight;
        canvas.width = W * dpr; canvas.height = H * dpr;
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        jarakMaks = Math.max(110, Math.min(170, Math.sqrt(W * H) / 7));
        const jumlah = Math.round(Math.min(110, Math.max(40, (W * H) / 14000)));
        node = Array.from({ length: jumlah }, () => ({
            x: Math.random() * W, y: Math.random() * H,
            vx: (Math.random() - .5) * .35, vy: (Math.random() - .5) * .35,
            r: 1.4 + Math.random() * 2.2, denyut: Math.random() * Math.PI * 2,
        }));
        sinyal = [];
    }

    function tetangga(i) {
        const a = node[i], hasil = [];
        node.forEach((b, j) => { if (j !== i && Math.hypot(a.x - b.x, a.y - b.y) < jarakMaks) hasil.push(j); });
        return hasil;
    }

    // Sinyal: titik cahaya yang merambat dari satu node ke node tetangga, lalu bercabang
    function kirimSinyal(dari) {
        const t = tetangga(dari);
        if (!t.length || sinyal.length > 40) return;
        const ke = t[Math.floor(Math.random() * t.length)];
        sinyal.push({ dari, ke, p: 0, kecepatan: .012 + Math.random() * .018, sisaLompat: 2 + Math.floor(Math.random() * 3) });
    }

    function gambar() {
        ctx.clearRect(0, 0, W, H);

        // garis antarnode (sinapsis), makin dekat makin terang
        for (let i = 0; i < node.length; i++) {
            for (let j = i + 1; j < node.length; j++) {
                const a = node[i], b = node[j];
                const d = Math.hypot(a.x - b.x, a.y - b.y);
                if (d < jarakMaks) {
                    const dekatMouse = Math.hypot((a.x + b.x) / 2 - mouse.x, (a.y + b.y) / 2 - mouse.y) < 160;
                    const alpha = (1 - d / jarakMaks) * (dekatMouse ? .55 : .22);
                    ctx.strokeStyle = `rgba(147, 197, 253, ${alpha})`;
                    ctx.lineWidth = dekatMouse ? 1.1 : .8;
                    ctx.beginPath(); ctx.moveTo(a.x, a.y); ctx.lineTo(b.x, b.y); ctx.stroke();
                }
            }
        }

        // sinyal yang merambat
        sinyal = sinyal.filter(s => {
            const a = node[s.dari], b = node[s.ke];
            if (!a || !b) return false;
            s.p += s.kecepatan;
            const x = a.x + (b.x - a.x) * s.p, y = a.y + (b.y - a.y) * s.p;
            const g = ctx.createRadialGradient(x, y, 0, x, y, 9);
            g.addColorStop(0, 'rgba(165, 243, 252, .95)');
            g.addColorStop(1, 'rgba(165, 243, 252, 0)');
            ctx.fillStyle = g;
            ctx.beginPath(); ctx.arc(x, y, 9, 0, Math.PI * 2); ctx.fill();
            if (s.p >= 1) {
                b.denyut = 0; // node "menyala" ketika menerima sinyal
                if (s.sisaLompat > 0) {
                    const t = tetangga(s.ke).filter(j => j !== s.dari);
                    if (t.length) sinyal.push({ dari: s.ke, ke: t[Math.floor(Math.random() * t.length)], p: 0, kecepatan: s.kecepatan, sisaLompat: s.sisaLompat - 1 });
                }
                return false;
            }
            return true;
        });

        // node (neuron)
        node.forEach(n => {
            n.denyut += .03;
            const nyala = Math.max(0, 1 - n.denyut / 1.2);
            ctx.fillStyle = `rgba(191, 219, 254, ${.55 + nyala * .45})`;
            ctx.beginPath(); ctx.arc(n.x, n.y, n.r + nyala * 2.5, 0, Math.PI * 2); ctx.fill();
            if (nyala > 0) {
                ctx.strokeStyle = `rgba(103, 232, 249, ${nyala * .6})`;
                ctx.beginPath(); ctx.arc(n.x, n.y, n.r + 4 + (1 - nyala) * 10, 0, Math.PI * 2); ctx.stroke();
            }
        });
    }

    function gerak() {
        node.forEach(n => {
            n.x += n.vx; n.y += n.vy;
            if (n.x < -20) n.x = W + 20; else if (n.x > W + 20) n.x = -20;
            if (n.y < -20) n.y = H + 20; else if (n.y > H + 20) n.y = -20;
            // node sedikit tertarik ke arah kursor
            const dx = mouse.x - n.x, dy = mouse.y - n.y, d = Math.hypot(dx, dy);
            if (d < 180 && d > 1) { n.x += dx / d * .25; n.y += dy / d * .25; }
        });
        if (Math.random() < .06) kirimSinyal(Math.floor(Math.random() * node.length));
    }

    function putar() {
        gerak(); gambar();
        requestAnimationFrame(putar);
    }

    window.addEventListener('resize', ukur);
    window.addEventListener('mousemove', e => { mouse.x = e.clientX; mouse.y = e.clientY; });
    window.addEventListener('mouseleave', () => { mouse.x = mouse.y = -9999; });
    window.addEventListener('click', e => {
        // klik di latar: kirim sinyal dari node terdekat
        let terdekat = 0, min = Infinity;
        node.forEach((n, i) => { const d = Math.hypot(n.x - e.clientX, n.y - e.clientY); if (d < min) { min = d; terdekat = i; } });
        for (let k = 0; k < 3; k++) kirimSinyal(terdekat);
    });

    ukur();
    if (diam) { for (let k = 0; k < 8; k++) kirimSinyal(k); gambar(); } else { putar(); }
})();
</script>
</body>
</html>
