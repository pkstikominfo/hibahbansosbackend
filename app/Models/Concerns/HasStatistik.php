<?php

namespace App\Models\Concerns;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Builder;

trait HasStatistik
{
    /**
     * Override di model jika perlu.
     * Contoh (Usulan):
     *  ['anggaran_disetujui' => 'total_anggaran_disetujui', 'anggaran_usulan' => 'total_anggaran_usulan']
     * Contoh (Spj):
     *  ['nilai_disetujui' => 'total_nilai_disetujui', 'nilai_usulan' => 'total_nilai_usulan']
     */
    protected array $statSumColumns = [];

    /**
     * Override di model jika perlu.
     * Contoh Usulan: 'created_at'
     * Contoh Spj   : 'tanggal_spj'
     */
    protected ?string $statDefaultBetweenColumn = 'created_at';

    /** Core query builder dengan where/whereIn/whereBetween dinamis */
    public static function getStatistikQB(
        array $where = [],
        ?string $betweenColumn = null,
        $betweenStart = null,
        $betweenEnd = null
    ): Builder {
        $instance = new static;

        // default range: 1 Jan tahun berjalan - sekarang
        $awalTahun  = Carbon::now()->startOfYear()->format('Y-m-d H:i:s');
        $sekarang   = Carbon::now()->format('Y-m-d H:i:s');

        $kolomBetween = $betweenColumn ?? $instance->statDefaultBetweenColumn;
        $awalBetween  = $betweenStart ?? $awalTahun;
        $akhirBetween = $betweenEnd   ?? $sekarang;

        $query = static::query();

        // Tambahkan between hanya jika kolom & batas tersedia
        if (!empty($kolomBetween) && $awalBetween !== null && $akhirBetween !== null) {
            [$a, $b] = self::normalizeBetweenBounds($awalBetween, $akhirBetween);
            $query->whereBetween($kolomBetween, [$a, $b]);
        }

        // Apply where dinamis
        foreach ($where as $key => $value) {
            if (!is_array($value)) {
                $query->where($key, $value);
                continue;
            }
            [$op, $val] = [$value[0] ?? '=', $value[1] ?? null];
            $op = strtolower($op);

            switch ($op) {
                case 'in':
                    $query->whereIn($key, (array)$val);
                    break;
                case 'not in':
                    $query->whereNotIn($key, (array)$val);
                    break;
                case 'between':
                    if (is_array($val) && count($val) === 2) {
                        [$a, $b] = self::normalizeBetweenBounds($val[0], $val[1]);
                        $query->whereBetween($key, [$a, $b]);
                    }
                    break;
                case 'not between':
                    if (is_array($val) && count($val) === 2) {
                        [$a, $b] = self::normalizeBetweenBounds($val[0], $val[1]);
                        $query->whereNotBetween($key, [$a, $b]);
                    }
                    break;
                case 'null':
                    $query->whereNull($key);
                    break;
                case 'not null':
                    $query->whereNotNull($key);
                    break;
                case 'like':
                    $query->where($key, 'like', $val);
                    break;
                default:
                    // (=, >, <, >=, <=, !=, <>)
                    $query->where($key, $value[0], $value[1]);
            }
        }

        return $query;
    }

    /** COUNT penerima, optional group by */
    public static function getStatistikJumlahPenerima(
        array $where = [],
        $groupBy = null,
        ?string $betweenColumn = null,
        $betweenStart = null,
        $betweenEnd = null
    ) {
        $q = static::getStatistikQB($where, $betweenColumn, $betweenStart, $betweenEnd);
        if (!empty($groupBy)) $q->groupBy((array)$groupBy);
        return $q->count();
    }

    /** SUM kolom-kolom yang didefinisikan di $statSumColumns, optional group by */
    public static function getStatistikJumlahAnggaran(
        array $where = [],
        $groupBy = null,
        ?string $betweenColumn = null,
        $betweenStart = null,
        $betweenEnd = null
    ) {
        $instance = new static;
        $q = static::getStatistikQB($where, $betweenColumn, $betweenStart, $betweenEnd);

        // Build selectRaw SUM(...) as alias, berdasarkan mapping di model
        $selects = [];
        foreach ($instance->statSumColumns as $col => $alias) {
            $selects[] = "SUM($col) as $alias";
        }
        if (empty($selects)) {
            // fallback jika belum diset di model — tidak pakai select khusus
            if (!empty($groupBy)) $q->groupBy((array)$groupBy);
            return $q->get();
        }

        $q->selectRaw(implode(', ', $selects));
        if (!empty($groupBy)) $q->groupBy((array)$groupBy);

        return $q->get();
    }

    /** Normalisasi batas between untuk tanggal (Y-m-d => start/end of day). Angka/string non-tanggal dibiarkan. */
    protected static function normalizeBetweenBounds($a, $b): array
    {
        $isDateA = is_string($a) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $a);
        $isDateB = is_string($b) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $b);

        if ($isDateA) $a = Carbon::parse($a)->startOfDay()->format('Y-m-d H:i:s');
        if ($isDateB) $b = Carbon::parse($b)->endOfDay()->format('Y-m-d H:i:s');

        return [$a, $b];
    }
}
