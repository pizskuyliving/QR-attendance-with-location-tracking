// Dialog dan notifikasi bertema, pengganti confirm/prompt/alert bawaan browser.
// Form cukup diberi data-konfirmasi="pesan"; label tombol OK diambil dari tombol kirimnya.
// Tambah data-tanya="nama_field" agar dialog meminta isian (mis. kata sandi baru) sebelum dikirim.
(() => {
    const IKON = {
        tanya: '<path d="M9.1 9a3 3 0 0 1 5.8 1c0 2-3 3-3 3"/><path d="M12 17h.01"/><circle cx="12" cy="12" r="10"/>',
        bahaya: '<path d="m21.7 18-8-14a2 2 0 0 0-3.4 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.7-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/>',
        kunci: '<rect width="18" height="11" x="3" y="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
    };
    const svg = n => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + IKON[n] + '</svg>';
    const gerakMinim = () => matchMedia('(prefers-reduced-motion: reduce)').matches;

    function dialog({ pesan, ok = 'Ya', batal = 'Batal', bahaya = false, isian = null }) {
        return new Promise(selesai => {
            const d = document.createElement('dialog');
            d.className = 'dlg-flex' + (bahaya ? ' bahaya' : '');
            d.innerHTML = '<form method="dialog"><div class="dlg-ikon">' + svg(isian ? 'kunci' : bahaya ? 'bahaya' : 'tanya') + '</div>'
                + '<p class="dlg-pesan"></p>' + (isian ? '<input class="dlg-isian" autocomplete="off">' : '')
                + '<div class="dlg-aksi"><button type="button" class="btn ghost sm" value="batal"></button><button class="btn sm" value="ok"></button></div></form>';
            d.querySelector('.dlg-pesan').textContent = pesan;
            const [tBatal, tOk] = d.querySelectorAll('button');
            tBatal.textContent = batal;
            tOk.textContent = ok;
            if (bahaya) tOk.classList.add('danger');
            const inp = d.querySelector('.dlg-isian');
            if (inp) Object.assign(inp, { required: true, minLength: isian.min || 0, placeholder: isian.placeholder || '' });

            let hasil = null;
            const tutup = v => {
                hasil = v;
                if (gerakMinim()) return d.close();
                d.classList.add('keluar');
                setTimeout(() => d.close(), 160);
            };
            tBatal.onclick = () => tutup(null);
            d.querySelector('form').onsubmit = e => { e.preventDefault(); tutup(inp ? inp.value : true); };
            d.addEventListener('cancel', e => { e.preventDefault(); tutup(null); });
            d.addEventListener('click', e => { if (e.target === d) tutup(null); }); // klik di luar kotak
            d.addEventListener('close', () => { d.remove(); selesai(hasil); });
            document.body.append(d);
            d.showModal();
            (inp || tOk).focus();
        });
    }

    function kabar(pesan, jenis = 'bad', judul = '') {
        const t = document.createElement('div');
        t.className = 'toast ' + jenis;
        t.setAttribute('role', jenis === 'bad' ? 'alert' : 'status');
        if (judul) t.append(Object.assign(document.createElement('b'), { textContent: judul }));
        t.append(Object.assign(document.createElement('span'), { textContent: pesan }));
        const hilang = () => { t.classList.add('out'); setTimeout(() => t.remove(), 300); };
        t.onclick = hilang;
        document.querySelectorAll('.toast').forEach(x => x.remove());
        document.body.append(t);
        setTimeout(hilang, 4500);
    }

    window.Flex = {
        konfirmasi: (pesan, opsi = {}) => dialog({ pesan, ...opsi }).then(Boolean),
        tanya: (pesan, opsi = {}) => dialog({ pesan, isian: opsi, ...opsi }),
        kabar,
    };

    document.addEventListener('submit', async e => {
        const f = e.target;
        if (!f.dataset.konfirmasi || f.dataset.lolos) return;
        e.preventDefault();
        const tombol = e.submitter || f.querySelector('button');
        const opsi = {
            pesan: f.dataset.konfirmasi,
            ok: f.dataset.ok || (tombol && tombol.textContent.trim()) || 'Ya',
            bahaya: 'bahaya' in f.dataset || !!(tombol && tombol.matches('.danger, .bad')),
        };
        if (f.dataset.tanya) {
            const v = await dialog({ ...opsi, isian: { min: +(f.dataset.min || 0), placeholder: f.dataset.placeholder } });
            if (v === null) return;
            f.elements[f.dataset.tanya].value = v;
        } else if (!(await dialog(opsi))) return;
        f.dataset.lolos = '1';
        f.requestSubmit(tombol && tombol.form === f ? tombol : undefined);
        delete f.dataset.lolos;
    });
})();
