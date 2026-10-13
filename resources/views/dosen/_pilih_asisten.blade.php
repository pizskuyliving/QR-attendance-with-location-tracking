{{-- Asisten 1 & Asisten 2 (opsional) satu kelas --}}
<div class="pilih-dua">
    @foreach (['asisten_id' => ['Asisten 1', $k?->asisten_id], 'asisten2_id' => ['Asisten 2 (opsional)', $k?->asisten2_id]] as $nama => [$label, $terpilih])
        <label>
            <span class="small muted">{{ $label }}</span>
            <select name="{{ $nama }}">
                <option value="">— {{ $nama === 'asisten_id' ? 'Belum ada asisten' : 'Tidak ada' }} —</option>
                @foreach ($asisten as $a)
                    <option value="{{ $a->id }}" @selected($terpilih === $a->id)>{{ $a->name }}</option>
                @endforeach
            </select>
        </label>
    @endforeach
</div>
