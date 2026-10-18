<?php

namespace App\Http\Controllers\Asisten;

use App\Http\Controllers\Controller;
use App\Models\CatatanDosen;
use App\Models\Kelas;
use App\Models\LogAktivitas;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Kotak pesan asisten: semua catatan dosen dari kelas yang diajar, di satu tempat,
 * supaya tidak menumpuk di dashboard dan halaman kelas.
 */
class PesanController extends Controller
{
    /** Catatan dari kelas yang diajar asisten ini (slot 1 atau 2). */
    public static function milik(int $userId): Builder
    {
        // whereIn + subquery memakai indeks (kelas_id, dibaca_at); lebih cepat daripada whereHas saat pesan sudah ratusan ribu
        return CatatanDosen::whereIn('kelas_id', Kelas::diajar($userId)->select('id'));
    }

    public static function jumlahBaru(int $userId): int
    {
        return self::milik($userId)->whereNull('dibaca_at')->count();
    }

    public function index(Request $request)
    {
        $u = $request->user();
        $filter = $request->query('lihat') === 'baru' ? 'baru' : 'semua';
        $kelasId = $request->integer('kelas') ?: null;

        $pesan = self::milik($u->id)->with('kelas', 'dosen')
            ->when($filter === 'baru', fn ($q) => $q->whereNull('dibaca_at'))
            ->when($kelasId, fn ($q) => $q->where('kelas_id', $kelasId))
            ->latest('id')->paginate(20)->withQueryString();
        $daftarKelas = Kelas::diajar($u->id)->orderBy('mata_kuliah')->orderBy('nama_kelas')->get();
        $baru = self::jumlahBaru($u->id);
        $dibaca = self::milik($u->id)->whereNotNull('dibaca_at')->when($kelasId, fn ($q) => $q->where('kelas_id', $kelasId))->count();

        return view('asisten.pesan', compact('pesan', 'daftarKelas', 'filter', 'kelasId', 'baru', 'dibaca'));
    }

    public function baca(Request $request, CatatanDosen $catatan)
    {
        abort_unless($catatan->kelas?->diajarOleh($request->user()), 403);
        $catatan->dibaca_at ??= now();
        $catatan->save();

        return back();
    }

    public function bacaSemua(Request $request)
    {
        $n = self::milik($request->user()->id)->whereNull('dibaca_at')
            ->when($request->integer('kelas'), fn ($q, $k) => $q->where('kelas_id', $k))
            ->update(['dibaca_at' => now()]);

        return back()->with('ok', $n ? "$n pesan ditandai sudah dibaca." : 'Tidak ada pesan baru.');
    }

    /** Hapus satu pesan. Pesan milik kelas, jadi ikut hilang untuk asisten kedua dan dari ringkasan dosen. */
    public function hapus(Request $request, CatatanDosen $catatan)
    {
        abort_unless($catatan->kelas?->diajarOleh($request->user()), 403);
        LogAktivitas::catat($catatan->kelas, 'pesan_hapus', "{$request->user()->name} menghapus pesan dosen tanggal {$catatan->created_at->format('d/m/Y H:i')}");
        $catatan->delete();

        return back()->with('ok', 'Pesan dihapus.');
    }

    /** Bersihkan semua pesan yang sudah dibaca (bisa per kelas). Pesan yang belum dibaca tidak tersentuh. */
    public function hapusDibaca(Request $request)
    {
        $kelasId = $request->integer('kelas') ?: null;
        $n = self::milik($request->user()->id)->whereNotNull('dibaca_at')
            ->when($kelasId, fn ($q) => $q->where('kelas_id', $kelasId))
            ->delete();
        if ($n) {
            LogAktivitas::catat($kelasId ? Kelas::find($kelasId) : null, 'pesan_hapus', "{$request->user()->name} menghapus {$n} pesan dosen yang sudah dibaca");
        }

        return back()->with('ok', $n ? "{$n} pesan yang sudah dibaca dihapus." : 'Tidak ada pesan yang sudah dibaca.');
    }
}
