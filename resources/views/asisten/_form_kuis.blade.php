{{-- form kuis: pertemuan, durasi, 5 soal pilihan A/B/C + kunci; $q = kuis yang diubah atau null, $terpakai = pertemuan yang sudah punya kuis --}}
@php($n = $kelas->jumlah_pertemuan)
<div class="grid cols-2" style="gap:0 12px">
    <div class="field"><label>Pertemuan</label>
        <select name="pertemuan" required>
            @for ($p = 1; $p <= $n; $p++)
                @continue(in_array($p, $terpakai, true) && $p !== $q?->pertemuan)
                <option value="{{ $p }}" @selected(old('pertemuan', $q?->pertemuan) == $p)>Pertemuan {{ $p }}</option>
            @endfor
        </select></div>
    <div class="field"><label>Durasi</label>
        <div class="row" style="gap:8px;flex-wrap:nowrap"><input type="number" name="durasi_menit" min="1" max="120" value="{{ old('durasi_menit', $q?->durasi_menit ?? 10) }}" required style="max-width:110px"><span class="muted">menit</span></div></div>
</div>
@php($soal = $q?->soal->values())
@for ($i = 0; $i < \App\Models\Kuis::JUMLAH_SOAL; $i++)
    @php($s = $soal[$i] ?? null)
    <fieldset class="kuis-soal">
        <legend>Soal {{ $i + 1 }}</legend>
        <div class="field" style="margin-bottom:8px">
            <textarea aria-label="Pertanyaan soal {{ $i + 1 }}" name="soal[{{ $i }}][pertanyaan]" rows="2" maxlength="1000" placeholder="Tulis pertanyaan" required>{{ old("soal.$i.pertanyaan", $s?->pertanyaan) }}</textarea></div>
        @foreach (\App\Models\Kuis::PILIHAN as $h => $label)
            <label class="kuis-opsi">
                <input type="radio" name="soal[{{ $i }}][kunci]" value="{{ $h }}" @checked(old("soal.$i.kunci", $s?->kunci) === $h) required title="Tandai sebagai jawaban benar">
                <span class="kuis-huruf">{{ $label }}</span>
                <input name="soal[{{ $i }}][{{ $h }}]" value="{{ old("soal.$i.$h", $s?->pilihan($h)) }}" maxlength="500" placeholder="Pilihan {{ $label }}" required>
            </label>
        @endforeach
    </fieldset>
@endfor
<p class="small muted" style="margin-top:0">Pilih bulatan di kiri untuk menandai jawaban yang benar. Urutan soal dan pilihan diacak untuk setiap mahasiswa.</p>
