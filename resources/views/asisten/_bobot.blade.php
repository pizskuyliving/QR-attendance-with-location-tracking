{{-- input bobot nilai akhir: kehadiran, tugas, UTS, UAS; jumlahnya harus 100 --}}
<div class="bobot-isi" data-bobot>
    @foreach (\App\Support\Bobot::KOLOM as $kolom => $label)
        <label><span>{{ $label }}</span><span class="bobot-input"><input type="number" name="{{ $kolom }}" min="0" max="100" value="{{ old($kolom, $k?->$kolom ?? ['bobot_hadir' => 20, 'bobot_tugas' => 80][$kolom] ?? 0) }}" required>%</span></label>
    @endforeach
    <div class="bobot-total small">Jumlah <b data-bobot-total></b>%</div>
</div>
<script>
    document.querySelectorAll('[data-bobot]:not([data-siap])').forEach(el => {
        el.dataset.siap = 1;
        const total = el.querySelector('[data-bobot-total]');
        const hitung = () => {
            const n = [...el.querySelectorAll('input')].reduce((s, i) => s + (+i.value || 0), 0);
            total.textContent = n;
            total.parentElement.classList.toggle('salah', n !== 100);
        };
        el.addEventListener('input', hitung);
        hitung();
    });
</script>
