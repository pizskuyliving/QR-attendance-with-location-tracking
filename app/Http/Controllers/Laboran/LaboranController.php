<?php

namespace App\Http\Controllers\Laboran;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\LogAktivitas;
use App\Models\Mahasiswa;
use App\Models\Matkul;
use App\Models\Pengaturan;
use App\Models\User;
use App\Services\Pantauan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Menu khusus laboran (peran admin): daftar dosen beserta matkulnya, dan pengaturan sistem. */
class LaboranController extends Controller
{
    public function dosen(Request $request)
    {
        $cari = trim((string) $request->query('cari'));
        $dosen = User::where('role', 'dosen')
            ->when($cari !== '', fn ($q) => $q->where(fn ($w) => $w->whereLike('name', "%{$cari}%")->orWhereLike('email', "%{$cari}%")))
            ->with(['matkul' => fn ($q) => $q->orderBy('nama'), 'matkul.kelas' => fn ($q) => $q->withCount('mahasiswa'), 'matkul.kelas.asisten', 'matkul.kelas.asisten2'])
            ->orderBy('name')->get()
            ->map(function (User $d) {
                $matkul = $d->matkul->map(fn (Matkul $m) => ['matkul' => $m, 'kelas' => $m->kelas->map(fn (Kelas $k) => Pantauan::untuk($k))]);
                $kelas = $matkul->flatMap(fn ($m) => $m['kelas']);

                return [
                    'dosen' => $d,
                    'matkul' => $matkul,
                    'jumlahKelas' => $kelas->count(),
                    'jumlahMahasiswa' => $kelas->sum('mahasiswa'),
                    'asisten' => $kelas->flatMap(fn ($p) => $p['kelas']->daftarAsisten())->unique('id')->count(),
                    'perhatian' => $kelas->filter(fn ($p) => $p['perhatian'])->count(),
                    'tanpaAsisten' => $kelas->filter(fn ($p) => $p['kelas']->daftarAsisten()->isEmpty())->count(),
                ];
            });

        return view('laboran.dosen', compact('dosen', 'cari'));
    }

    /** Satu halaman untuk persetujuan asisten, daftar semua akun, dan tambah akun. */
    public function akun(Request $request)
    {
        $peran = in_array($request->query('peran'), ['admin', 'dosen', 'asisten'], true) ? $request->query('peran') : null;
        $cari = trim((string) $request->query('cari'));
        $akun = User::withCount(['kelas', 'kelasKedua', 'matkul'])->where('status', 'aktif')
            ->when($peran, fn ($q) => $q->where('role', $peran))
            ->when($cari !== '', fn ($q) => $q->where(fn ($w) => $w->whereLike('name', "%{$cari}%")->orWhereLike('email', "%{$cari}%")))
            ->orderByRaw("case role when 'admin' then 1 when 'dosen' then 2 else 3 end")->orderBy('name')->get();
        $jumlahPeran = User::where('status', 'aktif')->select('role', DB::raw('count(*) as n'))->groupBy('role')->pluck('n', 'role');
        $menunggu = User::where('status', 'menunggu')->oldest()->get();
        $ditolak = User::where('status', 'ditolak')->latest('updated_at')->get();
        $tambahPeran = in_array($request->query('tambah'), ['admin', 'dosen', 'asisten'], true) ? $request->query('tambah') : 'asisten';

        return view('laboran.akun', compact('akun', 'peran', 'cari', 'jumlahPeran', 'menunggu', 'ditolak', 'tambahPeran'));
    }

    public function sistem()
    {
        $akun = User::select('role', 'status', DB::raw('count(*) as n'))->groupBy('role', 'status')->get();
        $jumlah = [
            'Laboran' => $akun->where('role', 'admin')->sum('n'),
            'Dosen' => $akun->where('role', 'dosen')->sum('n'),
            'Asisten aktif' => $akun->where('role', 'asisten')->where('status', 'aktif')->sum('n'),
            'Asisten menunggu' => $akun->where('role', 'asisten')->where('status', 'menunggu')->sum('n'),
            'Mahasiswa' => Mahasiswa::count(),
            'Mata kuliah' => Matkul::count(),
            'Kelas' => Kelas::count(),
            'Kelas tanpa asisten' => Kelas::whereNull('asisten_id')->count(),
        ];

        // batas unggah PHP (php.ini) sering lebih kecil dari batas aplikasi di Laragon/XAMPP
        $phpUnggahKb = min(self::keKb(ini_get('upload_max_filesize')), self::keKb(ini_get('post_max_size')));

        return view('laboran.sistem', compact('jumlah', 'phpUnggahKb'));
    }

    public function simpan(Request $request)
    {
        $v = $request->validate([
            'institusi' => 'required|string|max:150',
            'domain_email' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9.\-,@ ]*$/i'],
            'maks_unggah_mb' => 'required|integer|min:1|max:500',
            'huruf' => 'required|array',
            'huruf.A' => 'required|integer|between:1,100',
            'huruf.B' => 'required|integer|between:1,100|lt:huruf.A',
            'huruf.C' => 'required|integer|between:1,100|lt:huruf.B',
            'huruf.D' => 'required|integer|between:1,100|lt:huruf.C',
        ], [
            'domain_email.regex' => 'Domain hanya boleh berisi huruf, angka, titik, dan tanda hubung; pisahkan dengan koma.',
            'huruf.*.lt' => 'Batas nilai huruf harus menurun dari A ke D.',
        ]);

        $domain = array_values(array_unique(array_filter(array_map(
            fn ($d) => strtolower(trim($d, " .@")), explode(',', (string) ($v['domain_email'] ?? ''))
        ))));
        Pengaturan::simpan([
            'institusi' => trim($v['institusi']),
            'domain_email' => $domain,
            'daftar_mahasiswa' => $request->boolean('daftar_mahasiswa'),
            'daftar_asisten' => $request->boolean('daftar_asisten'),
            'maks_unggah_kb' => (int) $v['maks_unggah_mb'] * 1024,
            'huruf' => ['A' => (int) $v['huruf']['A'], 'B' => (int) $v['huruf']['B'], 'C' => (int) $v['huruf']['C'], 'D' => (int) $v['huruf']['D'], 'E' => 0],
        ]);
        LogAktivitas::catat(null, 'ubah_pengaturan', "{$request->user()->name} mengubah pengaturan sistem");

        return back()->with('ok', 'Pengaturan sistem disimpan.');
    }

    private static function keKb(string|false $nilai): int
    {
        $nilai = trim((string) $nilai);
        $angka = (int) $nilai;

        return match (strtolower(substr($nilai, -1))) {
            'g' => $angka * 1024 * 1024,
            'm' => $angka * 1024,
            'k' => $angka,
            default => intdiv($angka, 1024),
        };
    }
}
