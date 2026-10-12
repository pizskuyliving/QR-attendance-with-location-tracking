<!DOCTYPE html>
<html lang="id"><body style="font-family:Arial,sans-serif;background:#1E1B4B;padding:24px;color:#F5F3FF">
<div style="max-width:440px;margin:0 auto;background:#2E2A6E;border-radius:16px;padding:28px;border:1px solid {{ $penting ? '#F87171' : '#3D3890' }}">
    <h2 style="margin:0 0 12px;font-family:Arial,sans-serif;color:{{ $penting ? '#FCA5A5' : '#F5F3FF' }}">{{ $subjek }}</h2>
    <p>Halo {{ $nama }},</p>
    @foreach ($paragraf as $p)<p style="line-height:1.5">{{ $p }}</p>@endforeach
    <p style="margin:22px 0 4px"><a href="{{ $tautan }}" style="display:inline-block;background:#FDE047;color:#1E1B4B;text-decoration:none;font-weight:bold;padding:12px 22px;border-radius:999px">{{ $labelTautan }}</a></p>
    <p style="color:#8B82C3;font-size:.75rem;margin:18px 0 0;border-top:1px solid #3D3890;padding-top:12px">Email ini dikirim otomatis oleh sistem FLEX. Mohon tidak membalas email ini.</p>
</div>
</body></html>
