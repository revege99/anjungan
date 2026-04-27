<?php

namespace App\Http\Controllers;

use App\Models\ClinicProfile;
use App\Models\MasterKategoriPengeluaran;
use App\Models\MasterLayanan;
use App\Models\Pengeluaran;
use App\Models\RekapPasien;
use App\Models\RekapPasienUpdate;
use App\Models\TransaksiPasien;
use App\Models\User;
use App\Services\ClinicDatabaseManager;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReportUiController extends Controller
{
    public function dashboard(Request $request): View
    {
        $selectedMonth = now()->startOfMonth();
        $today = now()->startOfDay();
        $loggedUser = $request->user();
        $clinicContext = $this->clinicContext($request, true);
        $selectedClinicId = $clinicContext['selectedClinicId'];
        $dashboardGreeting = $this->dashboardGreeting(
            $loggedUser,
            $clinicContext['selectedClinic'],
            $clinicContext['viewingAllClinics']
        );

        $masterLayanan = MasterLayanan::query()
            ->active()
            ->orderBy('urutan_laporan')
            ->orderBy('nama_layanan')
            ->get();

        $monthTransactionsQuery = TransaksiPasien::query()
            ->with(['masterLayanan', 'clinicProfile'])
            ->whereYear('tanggal', $selectedMonth->year)
            ->whereMonth('tanggal', $selectedMonth->month);
        $this->scopeQueryToClinic($monthTransactionsQuery, $selectedClinicId);
        $monthTransactions = $monthTransactionsQuery->get();

        $monthExpensesQuery = Pengeluaran::query()
            ->with(['masterKategoriPengeluaran', 'clinicProfile'])
            ->forBulan($selectedMonth->month, $selectedMonth->year);
        $this->scopeQueryToClinic($monthExpensesQuery, $selectedClinicId);
        $monthExpenses = $monthExpensesQuery->get();

        $todayTransactions = $monthTransactions->filter(
            fn (TransaksiPasien $transaction) => $transaction->tanggal?->toDateString() === $today->toDateString()
        );

        $todayExpenses = $monthExpenses->filter(
            fn (Pengeluaran $expense) => $expense->tanggal?->toDateString() === $today->toDateString()
        );

        $monthlyRevenue = (float) $monthTransactions->sum('jumlah_rp');
        $monthlyExpenseTotal = (float) $monthExpenses->sum('jumlah_rp');
        $monthlyNet = $monthlyRevenue - $monthlyExpenseTotal;
        $todayRevenue = (float) $todayTransactions->sum('jumlah_rp');
        $todayExpenseTotal = (float) $todayExpenses->sum('jumlah_rp');
        $averageTransaction = $monthTransactions->count() > 0
            ? $monthlyRevenue / $monthTransactions->count()
            : 0.0;

        $unmappedTransactions = $monthTransactions->filter(
            fn (TransaksiPasien $transaction) => ! $transaction->masterLayanan
        )->count();

        $mappedPercentage = $monthTransactions->count() > 0
            ? (($monthTransactions->count() - $unmappedTransactions) / $monthTransactions->count()) * 100
            : 100.0;

        $penjaminMix = $monthTransactions
            ->groupBy(fn (TransaksiPasien $transaction) => filled($transaction->penjamin)
                ? trim((string) $transaction->penjamin)
                : 'Tanpa Penjamin')
            ->map(function (Collection $items, string $label) use ($monthlyRevenue) {
                $amount = (float) $items->sum('jumlah_rp');

                return [
                    'label' => $label,
                    'count' => $items->count(),
                    'amount' => $amount,
                    'share' => $monthlyRevenue > 0 ? ($amount / $monthlyRevenue) * 100 : 0.0,
                ];
            })
            ->sortByDesc('amount')
            ->values()
            ->take(5);

        $topLayanan = $this->monthlyLayananRows($monthTransactions, $masterLayanan)
            ->filter(fn (array $row) => (float) $row['debet'] > 0)
            ->sortByDesc('debet')
            ->values()
            ->take(6)
            ->map(function (array $row, int $index) use ($monthlyRevenue) {
                $palette = ['blue', 'emerald', 'amber', 'sky', 'violet', 'slate'];
                $amount = (float) $row['debet'];

                return [
                    'kode' => $row['kode'],
                    'nama' => $row['keterangan'],
                    'amount' => $amount,
                    'share' => $monthlyRevenue > 0 ? ($amount / $monthlyRevenue) * 100 : 0.0,
                    'tone' => $palette[$index % count($palette)],
                ];
            });

        $monthlyTrend = collect(range(5, 0))
            ->map(function (int $offset) use ($selectedMonth, $selectedClinicId) {
                $month = $selectedMonth->copy()->subMonths($offset);
                $revenueQuery = TransaksiPasien::query()
                    ->whereYear('tanggal', $month->year)
                    ->whereMonth('tanggal', $month->month);
                $this->scopeQueryToClinic($revenueQuery, $selectedClinicId);
                $revenue = (float) $revenueQuery->sum('jumlah_rp');

                $expenseQuery = Pengeluaran::query()
                    ->forBulan($month->month, $month->year);
                $this->scopeQueryToClinic($expenseQuery, $selectedClinicId);
                $expense = (float) $expenseQuery->sum('jumlah_rp');

                return [
                    'label' => strtoupper($month->locale('id')->translatedFormat('M')),
                    'period' => $month->locale('id')->translatedFormat('F Y'),
                    'revenue' => $revenue,
                    'expense' => $expense,
                    'net' => $revenue - $expense,
                ];
            })
            ->values();

        $trendPeak = max(1, (float) $monthlyTrend
            ->flatMap(fn (array $row) => [$row['revenue'], $row['expense']])
            ->max());

        $monthlyTrend = $monthlyTrend->map(function (array $row) use ($trendPeak) {
            $row['revenue_ratio'] = min(100, ($row['revenue'] / $trendPeak) * 100);
            $row['expense_ratio'] = min(100, ($row['expense'] / $trendPeak) * 100);

            return $row;
        });

        $recentTransactionsQuery = TransaksiPasien::query()
            ->with(['masterLayanan', 'clinicProfile'])
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->limit(5);
        $this->scopeQueryToClinic($recentTransactionsQuery, $selectedClinicId);
        $recentTransactions = $recentTransactionsQuery->get();

        $recentExpensesQuery = Pengeluaran::query()
            ->with(['masterKategoriPengeluaran', 'clinicProfile'])
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->limit(5);
        $this->scopeQueryToClinic($recentExpensesQuery, $selectedClinicId);
        $recentExpenses = $recentExpensesQuery->get();

        $operationalRouteParams = $loggedUser?->isMaster() && $selectedClinicId
            ? ['clinic_id' => $selectedClinicId]
            : [];
        $reportRouteParams = $loggedUser?->isMaster()
            ? ($clinicContext['viewingAllClinics']
                ? ['clinic_id' => 'all']
                : $operationalRouteParams)
            : [];

        $quickActions = collect([
            [
                'label' => 'Transaksi Pasien',
                'description' => 'Tarik data SIMRS per tanggal dan simpan transaksi pasien lokal.',
                'route' => route('transaksi-pasien', $operationalRouteParams),
                'tone' => 'blue',
            ],
            [
                'label' => 'Input Pengeluaran',
                'description' => 'Catat kredit operasional dan atur kategori pengeluaran.',
                'route' => route('input-pengeluaran', $operationalRouteParams),
                'tone' => 'emerald',
            ],
            [
                'label' => 'Rekap Bulanan',
                'description' => 'Pastikan total layanan dan komponen transaksi tetap balance.',
                'route' => route('rekap-bulanan', $reportRouteParams),
                'tone' => 'amber',
            ],
            [
                'label' => 'Rekap Tahunan',
                'description' => 'Lihat performa debet dan kredit dalam tampilan per bulan.',
                'route' => route('rekap-tahunan', $reportRouteParams),
                'tone' => 'violet',
            ],
        ]);

        return view('dashboard', [
            'todayLabel' => $today->locale('id')->translatedFormat('l, d F Y'),
            'monthLabel' => $this->periodLabel($selectedMonth),
            'currentMonthName' => $selectedMonth->locale('id')->translatedFormat('F Y'),
            'monthlyRevenue' => $monthlyRevenue,
            'monthlyExpenseTotal' => $monthlyExpenseTotal,
            'monthlyNet' => $monthlyNet,
            'monthlyTransactionCount' => $monthTransactions->count(),
            'todayRevenue' => $todayRevenue,
            'todayTransactionCount' => $todayTransactions->count(),
            'todayExpenseTotal' => $todayExpenseTotal,
            'todayExpenseCount' => $todayExpenses->count(),
            'averageTransaction' => $averageTransaction,
            'activeServiceCount' => $masterLayanan->count(),
            'activeExpenseCategoryCount' => MasterKategoriPengeluaran::query()->active()->count(),
            'unmappedTransactionCount' => $unmappedTransactions,
            'mappedPercentage' => $mappedPercentage,
            'penjaminCount' => $penjaminMix->count(),
            'penjaminMix' => $penjaminMix,
            'topLayanan' => $topLayanan,
            'monthlyTrend' => $monthlyTrend,
            'recentTransactions' => $recentTransactions,
            'recentExpenses' => $recentExpenses,
            'quickActions' => $quickActions,
            'dashboardGreeting' => $dashboardGreeting,
            'clinicOptions' => $clinicContext['clinicOptions'],
            'showClinicFilter' => $clinicContext['showClinicFilter'],
            'selectedClinicFilter' => $clinicContext['viewingAllClinics']
                ? 'all'
                : (string) ($selectedClinicId ?: ''),
            'selectedClinicLabel' => $clinicContext['selectedClinicLabel'],
            'viewingAllClinics' => $clinicContext['viewingAllClinics'],
        ]);
    }

    public function transaksiPasien(Request $request): View
    {
        $selectedDate = $this->normalizeSelectedDate($request->string('tanggal')->toString());
        $dataMonth = $this->normalizeSelectedMonth($request->string('data_bulan')->toString() ?: $selectedDate);
        $selectedPenjamin = trim($request->string('data_penjamin')->toString());
        $selectedLocalStatus = $this->normalizeLocalStatusFilter($request->string('local_status')->toString());
        $preferredTab = $request->string('active_tab')->toString();
        $user = $request->user();
        $isMasterView = $user?->isMaster() ?? false;
        $clinicContext = $this->clinicContext($request, $isMasterView);
        $selectedClinicId = $clinicContext['selectedClinicId'];
        $showTransaksiTab = ! $isMasterView;
        $showDataTransaksiTab = true;
        $showRekapPasienTab = $isMasterView;

        $savedTransactions = collect();
        $visitRows = collect();

        if ($showTransaksiTab && $selectedClinicId) {
            $savedTransactions = TransaksiPasien::query()
                ->with('masterLayanan')
                ->where('clinic_profile_id', $selectedClinicId)
                ->whereDate('tanggal', $selectedDate)
                ->get()
                ->keyBy('simrs_no_rawat');

            $visitRows = $this->simrsVisitRows($selectedDate, $selectedClinicId)
                ->filter(function (array $row) use ($savedTransactions, $selectedLocalStatus) {
                    if ($selectedLocalStatus === 'saved') {
                        return $savedTransactions->has($row['simrs_no_rawat']);
                    }

                    if ($selectedLocalStatus === 'unsaved') {
                        return ! $savedTransactions->has($row['simrs_no_rawat']);
                    }

                    return true;
                })
                ->map(fn (array $row) => array_merge($row, [
                    'clinic_profile_id' => $selectedClinicId,
                    'map_key' => $selectedClinicId . '::' . $row['simrs_no_rawat'],
                ]))
                ->values();
        }

        $savedTransactionQuery = TransaksiPasien::query()
            ->with(['masterLayanan', 'clinicProfile'])
            ->whereYear('tanggal', $dataMonth->year)
            ->whereMonth('tanggal', $dataMonth->month);
        $this->scopeQueryToClinic($savedTransactionQuery, $selectedClinicId);

        if (filled($selectedPenjamin)) {
            $savedTransactionQuery->where('penjamin', $selectedPenjamin);
        }

        $savedTransactionList = $savedTransactionQuery
            ->orderByDesc('tanggal')
            ->orderBy('nama_pasien')
            ->get();

        $savedTransactionDataset = $savedTransactions
            ->values()
            ->concat($savedTransactionList)
            ->unique('id')
            ->keyBy(fn (TransaksiPasien $item) => $item->clinic_profile_id . '::' . $item->simrs_no_rawat);

        $penjaminOptionsQuery = TransaksiPasien::query()
            ->whereYear('tanggal', $dataMonth->year)
            ->whereMonth('tanggal', $dataMonth->month)
            ->whereNotNull('penjamin')
            ->where('penjamin', '!=', '')
            ->orderBy('penjamin')
            ->distinct();
        $this->scopeQueryToClinic($penjaminOptionsQuery, $selectedClinicId);
        $penjaminOptions = $penjaminOptionsQuery->pluck('penjamin');

        $rekapPasienQuery = RekapPasien::query()
            ->with(['masterLayanan', 'clinicProfile'])
            ->forBulan($dataMonth->month, $dataMonth->year)
            ->orderByDesc('tanggal')
            ->orderBy('nama_pasien');
        $this->scopeQueryToClinic($rekapPasienQuery, $selectedClinicId);
        $rekapPasienList = $showRekapPasienTab
            ? $rekapPasienQuery->get()
            : collect();

        $existingRekapKeys = $rekapPasienList->isNotEmpty()
            ? TransaksiPasien::query()
                ->select('clinic_profile_id', 'simrs_no_rawat')
                ->when($selectedClinicId, fn ($query) => $query->where('clinic_profile_id', $selectedClinicId))
                ->whereIn('simrs_no_rawat', $rekapPasienList->pluck('no_rawat')->filter()->unique()->values())
                ->get()
                ->mapWithKeys(fn (TransaksiPasien $item) => [
                    $item->clinic_profile_id . '::' . $item->simrs_no_rawat => true,
                ])
            : collect();

        $lastRekapUpdate = $selectedClinicId
            ? RekapPasienUpdate::query()
                ->with('user')
                ->where('clinic_profile_id', $selectedClinicId)
                ->first()
            : null;

        $allowedTabs = collect()
            ->when($showTransaksiTab, fn ($tabs) => $tabs->push('panel-transaksi-pasien'))
            ->when($showDataTransaksiTab, fn ($tabs) => $tabs->push('panel-data-transaksi'))
            ->when($showRekapPasienTab, fn ($tabs) => $tabs->push('panel-rekap-pasien'))
            ->values()
            ->all();
        $resolvedPreferredTab = in_array($preferredTab, $allowedTabs, true)
            ? $preferredTab
            : ($allowedTabs[0] ?? null);

        return view('pages.input-transaksi-pasien', [
            'selectedDate' => $selectedDate,
            'selectedDataMonth' => $dataMonth->format('Y-m'),
            'selectedPenjamin' => $selectedPenjamin,
            'selectedLocalStatus' => $selectedLocalStatus,
            'penjaminOptions' => $penjaminOptions,
            'preferredTab' => $resolvedPreferredTab,
            'visitRows' => $visitRows,
            'savedTransactions' => $savedTransactions,
            'savedTransactionList' => $savedTransactionList,
            'rekapPasienList' => $rekapPasienList,
            'existingRekapKeys' => $existingRekapKeys,
            'lastRekapUpdate' => $lastRekapUpdate,
            'showTransaksiTab' => $showTransaksiTab,
            'showDataTransaksiTab' => $showDataTransaksiTab,
            'showRekapPasienTab' => $showRekapPasienTab,
            'showUpdateRekapButton' => $showTransaksiTab,
            'viewingAllClinics' => $clinicContext['viewingAllClinics'],
            'savedTransactionData' => $savedTransactionDataset->map(function (TransaksiPasien $item) {
                return [
                    'id' => $item->id,
                    'map_key' => $item->clinic_profile_id . '::' . $item->simrs_no_rawat,
                    'clinic_profile_id' => $item->clinic_profile_id,
                    'user_id' => $item->user_id,
                    'simrs_no_rawat' => $item->simrs_no_rawat,
                    'simrs_no_reg' => $item->simrs_no_reg,
                    'tanggal' => optional($item->tanggal)->format('Y-m-d'),
                    'bulan' => $item->bulan,
                    'harian' => $item->harian,
                    'layanan_medis' => $item->layanan_medis,
                    'layanan_label' => $item->masterLayanan?->nama_layanan ?: $item->layanan_medis,
                    'dokter' => $item->dokter,
                    'penjamin' => $item->penjamin,
                    'no_rm' => $item->no_rm,
                    'nama_pasien' => $item->nama_pasien,
                    'jk' => $item->jk,
                    'statis' => $item->statis,
                    'genap' => $item->genap,
                    'status_pasien' => $item->status_pasien,
                    'alamat' => $item->alamat,
                    'lab' => $item->lab,
                    'icd' => $item->icd,
                    'diagnosa' => $item->diagnosa,
                    'farmasi' => $item->farmasi,
                    'uang_daftar' => (float) $item->uang_daftar,
                    'uang_periksa' => (float) $item->uang_periksa,
                    'uang_obat' => (float) $item->uang_obat,
                    'uang_bersalin' => (float) $item->uang_bersalin,
                    'jasa_dokter' => (float) $item->jasa_dokter,
                    'jml_hari' => (int) $item->jml_hari,
                    'rawat_inap' => (float) $item->rawat_inap,
                    'jml_visit' => (int) $item->jml_visit,
                    'honor_dr_visit' => (float) $item->honor_dr_visit,
                    'oksigen' => (float) $item->oksigen,
                    'perlengk_bayi' => (float) $item->perlengk_bayi,
                    'jaspel_nakes' => (float) $item->jaspel_nakes,
                    'bmhp' => (float) $item->bmhp,
                    'pkl' => (float) $item->pkl,
                    'lain_lain' => (float) $item->lain_lain,
                    'jumlah_rp' => (float) $item->jumlah_rp,
                    'utang_pasien' => (float) $item->utang_pasien,
                    'utang' => (float) $item->utang,
                    'bayar_utang_pasien' => (float) $item->bayar_utang_pasien,
                    'derma_solidaritas' => (float) $item->derma_solidaritas,
                    'saldo_kredit' => (float) $item->saldo_kredit,
                    'saldo' => (float) $item->saldo,
                    'petugas_admin' => $item->petugas_admin,
                    'keterangan' => $item->keterangan,
                    'meta' => [
                        'penjamin' => $item->penjamin,
                    ],
                ];
            })->all(),
            'loggedInAdminName' => $this->resolveLoggedInAdminName($request->user()),
            'loggedInAdminRole' => $request->user()?->pegawaiProfile?->jabatan ?: 'Petugas Admin',
            'clinicOptions' => $clinicContext['clinicOptions'],
            'showClinicFilter' => $clinicContext['showClinicFilter'],
            'selectedClinicId' => $selectedClinicId,
            'selectedClinicLabel' => $clinicContext['selectedClinicLabel'],
        ]);
    }

    public function profileKlinik(Request $request): View
    {
        $clinicContext = $this->clinicContext($request, false);
        $user = $request->user();
        $isCreateMode = $user?->isMaster() && $request->string('mode')->toString() === 'create';
        $clinicProfile = $isCreateMode ? null : $clinicContext['selectedClinic'];

        return view('pages.profile-klinik', [
            'clinicProfile' => $clinicProfile,
            'clinicOptions' => $clinicContext['clinicOptions'],
            'showClinicFilter' => $clinicContext['showClinicFilter'],
            'selectedClinicId' => $clinicContext['selectedClinicId'],
            'selectedClinicLabel' => $clinicContext['selectedClinicLabel'],
            'isCreateMode' => $isCreateMode,
        ]);
    }

    public function saveProfileKlinik(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user?->isMaster()) {
            $profileId = $request->integer('profile_id');
            $isCreateMode = $request->string('profile_mode')->toString() === 'create';

            if ($isCreateMode) {
                $clinicProfile = ClinicProfile::query()->create(
                    $this->validatedClinicProfile($request, null, true, null)
                );
            } else {
                $clinicProfile = $profileId > 0
                    ? ClinicProfile::query()->findOrFail($profileId)
                    : $this->clinicContext($request, false)['selectedClinic'];

                abort_if(! $clinicProfile, 404);

                $clinicProfile->update(
                    $this->validatedClinicProfile($request, $clinicProfile->id, true, $clinicProfile)
                );
            }
        } else {
            abort_if(! $user?->clinic_profile_id, 403, 'Akun ini belum terhubung ke klinik.');

            $clinicProfile = ClinicProfile::query()->findOrFail($user->clinic_profile_id);
            $clinicProfile->update(
                $this->validatedClinicProfile($request, $clinicProfile->id, false, $clinicProfile)
            );
        }

        return redirect()
            ->route('profile-klinik', ['clinic_id' => $clinicProfile->id])
            ->with('success', 'Profil klinik berhasil diperbarui.');
    }

    public function inputPengeluaran(Request $request): View
    {
        $selectedMonth = $this->normalizeSelectedMonth($request->string('bulan')->toString());
        $selectedCategory = trim($request->string('kategori')->toString());
        $preferredTab = $request->string('active_tab')->toString();
        $clinicContext = $this->clinicContext($request, false);
        $selectedClinicId = $clinicContext['selectedClinicId'];

        $categoryOptions = MasterKategoriPengeluaran::query()
            ->active()
            ->orderBy('urutan_laporan')
            ->orderBy('nama_kategori')
            ->get();

        $expensesQuery = Pengeluaran::query()
            ->with('masterKategoriPengeluaran')
            ->where('clinic_profile_id', $selectedClinicId)
            ->forBulan($selectedMonth->month, $selectedMonth->year);

        if (filled($selectedCategory)) {
            $expensesQuery->where('kategori_pengeluaran', $selectedCategory);
        }

        $expenses = $expensesQuery
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->get();

        return view('pages.input-pengeluaran', [
            'selectedMonth' => $selectedMonth->format('Y-m'),
            'selectedCategory' => $selectedCategory,
            'categoryOptions' => $categoryOptions,
            'expenses' => $expenses,
            'expenseData' => $expenses->keyBy('id')->map(function (Pengeluaran $expense) {
                return [
                    'id' => $expense->id,
                    'clinic_profile_id' => $expense->clinic_profile_id,
                    'user_id' => $expense->user_id,
                    'master_kategori_pengeluaran_id' => $expense->master_kategori_pengeluaran_id,
                    'tanggal' => optional($expense->tanggal)->format('Y-m-d'),
                    'kategori_pengeluaran' => $expense->kategori_pengeluaran,
                    'deskripsi' => $expense->deskripsi,
                    'jumlah_rp' => (float) $expense->jumlah_rp,
                    'petugas_admin' => $expense->petugas_admin,
                    'keterangan' => $expense->keterangan,
                ];
            })->all(),
            'preferredTab' => in_array($preferredTab, ['panel-input-pengeluaran', 'panel-data-pengeluaran'], true)
                ? $preferredTab
                : null,
            'totalPengeluaran' => (float) $expenses->sum('jumlah_rp'),
            'loggedInAdminName' => $this->resolveLoggedInAdminName($request->user()),
            'loggedInAdminRole' => $request->user()?->pegawaiProfile?->jabatan ?: 'Petugas Admin',
            'clinicOptions' => $clinicContext['clinicOptions'],
            'showClinicFilter' => $clinicContext['showClinicFilter'],
            'selectedClinicId' => $selectedClinicId,
            'selectedClinicLabel' => $clinicContext['selectedClinicLabel'],
        ]);
    }

    public function kodeLayanan(Request $request): View
    {
        $search = trim($request->string('q')->toString());
        $editId = $request->integer('edit');

        $usageCounts = TransaksiPasien::query()
            ->select('layanan_medis', DB::raw('COUNT(*) as total'))
            ->groupBy('layanan_medis')
            ->pluck('total', 'layanan_medis');

        $recordsQuery = MasterLayanan::query()
            ->orderBy('urutan_laporan')
            ->orderBy('kode_layanan');

        if (filled($search)) {
            $recordsQuery->where(function ($query) use ($search) {
                $query->where('kode_layanan', 'like', '%' . $search . '%')
                    ->orWhere('nama_layanan', 'like', '%' . $search . '%')
                    ->orWhere('simrs_kd_poli', 'like', '%' . $search . '%')
                    ->orWhere('simrs_nm_poli', 'like', '%' . $search . '%');
            });
        }

        $records = $recordsQuery->get()->map(function (MasterLayanan $item) use ($usageCounts) {
            return [
                'id' => $item->id,
                'code' => $item->kode_layanan,
                'name' => $item->nama_layanan,
                'mapping_key' => $item->simrs_kd_poli,
                'mapping_label' => $item->simrs_nm_poli,
                'sort_order' => (int) $item->urutan_laporan,
                'is_active' => (bool) $item->is_active,
                'usage_count' => (int) ($usageCounts[$item->kode_layanan] ?? 0),
            ];
        });

        $editingItem = $editId > 0
            ? MasterLayanan::query()->find($editId)
            : null;

        return view('pages.master-reference', [
            'variant' => 'layanan',
            'pageTitle' => 'Kode Layanan',
            'pageEyebrow' => 'Master Transaksi',
            'pageDescription' => 'Atur kode layanan, nama laporan, dan mapping poli SIMRS dalam satu tempat.',
            'routeIndex' => 'kode-layanan',
            'routeStore' => 'kode-layanan.store',
            'routeUpdate' => 'kode-layanan.update',
            'search' => $search,
            'records' => $records,
            'editingItem' => $editingItem,
            'stats' => [
                'total' => MasterLayanan::query()->count(),
                'active' => MasterLayanan::query()->active()->count(),
                'mapped' => MasterLayanan::query()->whereNotNull('simrs_kd_poli')->where('simrs_kd_poli', '!=', '')->count(),
                'used' => $usageCounts->sum(),
            ],
        ]);
    }

    public function storeKodeLayanan(Request $request): RedirectResponse
    {
        MasterLayanan::query()->create($this->validatedMasterLayanan($request));

        return redirect()
            ->route('kode-layanan')
            ->with('success', 'Kode layanan berhasil disimpan.');
    }

    public function updateKodeLayanan(Request $request, MasterLayanan $masterLayanan): RedirectResponse
    {
        $masterLayanan->update($this->validatedMasterLayanan($request, $masterLayanan->id));

        return redirect()
            ->route('kode-layanan')
            ->with('success', 'Kode layanan berhasil diperbarui.');
    }

    public function kodePengeluaran(Request $request): View
    {
        $search = trim($request->string('q')->toString());
        $editId = $request->integer('edit');

        $usageCounts = Pengeluaran::query()
            ->select('master_kategori_pengeluaran_id', DB::raw('COUNT(*) as total'))
            ->whereNotNull('master_kategori_pengeluaran_id')
            ->groupBy('master_kategori_pengeluaran_id')
            ->pluck('total', 'master_kategori_pengeluaran_id');

        $recordsQuery = MasterKategoriPengeluaran::query()
            ->orderBy('urutan_laporan')
            ->orderBy('kode_kategori');

        if (filled($search)) {
            $recordsQuery->where(function ($query) use ($search) {
                $query->where('kode_kategori', 'like', '%' . $search . '%')
                    ->orWhere('nama_kategori', 'like', '%' . $search . '%');
            });
        }

        $records = $recordsQuery->get()->map(function (MasterKategoriPengeluaran $item) use ($usageCounts) {
            return [
                'id' => $item->id,
                'code' => $item->kode_kategori,
                'name' => $item->nama_kategori,
                'mapping_key' => null,
                'mapping_label' => null,
                'sort_order' => (int) $item->urutan_laporan,
                'is_active' => (bool) $item->is_active,
                'usage_count' => (int) ($usageCounts[$item->id] ?? 0),
            ];
        });

        $editingItem = $editId > 0
            ? MasterKategoriPengeluaran::query()->find($editId)
            : null;

        return view('pages.master-reference', [
            'variant' => 'pengeluaran',
            'pageTitle' => 'Kode Pengeluaran',
            'pageEyebrow' => 'Master Transaksi',
            'pageDescription' => 'Kelola kode kategori pengeluaran untuk dipakai konsisten pada input kredit dan laporan.',
            'routeIndex' => 'kode-pengeluaran',
            'routeStore' => 'kode-pengeluaran.store',
            'routeUpdate' => 'kode-pengeluaran.update',
            'search' => $search,
            'records' => $records,
            'editingItem' => $editingItem,
            'stats' => [
                'total' => MasterKategoriPengeluaran::query()->count(),
                'active' => MasterKategoriPengeluaran::query()->active()->count(),
                'mapped' => MasterKategoriPengeluaran::query()->whereNotNull('kode_kategori')->where('kode_kategori', '!=', '')->count(),
                'used' => $usageCounts->sum(),
            ],
        ]);
    }

    public function storeKodePengeluaran(Request $request): RedirectResponse
    {
        MasterKategoriPengeluaran::query()->create($this->validatedMasterKategoriPengeluaran($request));

        return redirect()
            ->route('kode-pengeluaran')
            ->with('success', 'Kode pengeluaran berhasil disimpan.');
    }

    public function updateKodePengeluaran(Request $request, MasterKategoriPengeluaran $masterKategoriPengeluaran): RedirectResponse
    {
        $masterKategoriPengeluaran->update(
            $this->validatedMasterKategoriPengeluaran($request, $masterKategoriPengeluaran->id)
        );

        return redirect()
            ->route('kode-pengeluaran')
            ->with('success', 'Kode pengeluaran berhasil diperbarui.');
    }

    public function rekapBulanan(Request $request): View
    {
        $selectedMonth = $this->normalizeSelectedMonth($request->string('bulan')->toString());
        $clinicContext = $this->clinicContext($request, true);

        $transactionsQuery = TransaksiPasien::query()
            ->whereYear('tanggal', $selectedMonth->year)
            ->whereMonth('tanggal', $selectedMonth->month);
        $this->scopeQueryToClinic($transactionsQuery, $clinicContext['selectedClinicId']);
        $transactions = $transactionsQuery->get();

        $expensesQuery = Pengeluaran::query()
            ->forBulan($selectedMonth->month, $selectedMonth->year);
        $this->scopeQueryToClinic($expensesQuery, $clinicContext['selectedClinicId']);
        $expenses = $expensesQuery->get();

        $layananRows = $this->monthlyLayananRows(
            $transactions,
            MasterLayanan::query()->active()->orderBy('urutan_laporan')->get()
        );

        $komponenRows = $this->monthlyKomponenRows($transactions);
        $pengeluaranRows = $this->monthlyPengeluaranRows(
            $expenses,
            MasterKategoriPengeluaran::query()->active()->orderBy('urutan_laporan')->get()
        );

        $totalDebitLayanan = (float) $layananRows->sum('debet');
        $totalDebitKomponen = (float) $komponenRows->sum('debet');
        $totalKredit = (float) $pengeluaranRows->sum('kredit');

        return view('pages.rekap-bulanan', [
            'selectedMonth' => $selectedMonth->format('Y-m'),
            'periodLabel' => $this->periodLabel($selectedMonth),
            'layananRows' => $layananRows,
            'komponenRows' => $komponenRows,
            'pengeluaranRows' => $pengeluaranRows,
            'totalDebitLayanan' => $totalDebitLayanan,
            'totalDebitKomponen' => $totalDebitKomponen,
            'totalKredit' => $totalKredit,
            'saldoAkhir' => $totalDebitKomponen - $totalKredit,
            'isBalanced' => abs($totalDebitLayanan - $totalDebitKomponen) < 0.01,
            'clinicOptions' => $clinicContext['clinicOptions'],
            'showClinicFilter' => $clinicContext['showClinicFilter'],
            'selectedClinicFilter' => $clinicContext['viewingAllClinics']
                ? 'all'
                : (string) ($clinicContext['selectedClinicId'] ?: ''),
            'selectedClinicLabel' => $clinicContext['selectedClinicLabel'],
            'viewingAllClinics' => $clinicContext['viewingAllClinics'],
        ]);
    }

    public function rekapTahunan(Request $request): View
    {
        $selectedYear = $this->normalizeSelectedYear($request->string('tahun')->toString());
        $clinicContext = $this->clinicContext($request, true);

        $transactionsQuery = TransaksiPasien::query()
            ->whereYear('tanggal', $selectedYear);
        $this->scopeQueryToClinic($transactionsQuery, $clinicContext['selectedClinicId']);
        $transactions = $transactionsQuery->get();

        $expensesQuery = Pengeluaran::query()
            ->forTahun($selectedYear);
        $this->scopeQueryToClinic($expensesQuery, $clinicContext['selectedClinicId']);
        $expenses = $expensesQuery->get();

        $masterLayanan = MasterLayanan::query()
            ->active()
            ->orderBy('urutan_laporan')
            ->orderBy('nama_layanan')
            ->get();

        $masterKategori = MasterKategoriPengeluaran::query()
            ->active()
            ->orderBy('urutan_laporan')
            ->orderBy('nama_kategori')
            ->get();

        $penerimaanRows = $this->yearlyLayananRows($transactions, $masterLayanan);
        $pengeluaranRows = $this->yearlyPengeluaranRows($expenses, $masterKategori);
        $monthHeaders = $this->yearMonthHeaders();

        $penerimaanMonthlyTotals = $this->yearlySideTotals($penerimaanRows, 'debet');
        $pengeluaranMonthlyTotals = $this->yearlySideTotals($pengeluaranRows, 'kredit');
        $saldoMonthlyTotals = collect(range(1, 12))
            ->mapWithKeys(fn (int $month) => [
                $month => (float) ($penerimaanMonthlyTotals[$month] ?? 0) - (float) ($pengeluaranMonthlyTotals[$month] ?? 0),
            ])
            ->all();

        $annualDebitTotal = (float) array_sum($penerimaanMonthlyTotals);
        $annualKreditTotal = (float) array_sum($pengeluaranMonthlyTotals);

        return view('pages.rekap-tahunan', [
            'selectedYear' => $selectedYear,
            'monthHeaders' => $monthHeaders,
            'penerimaanRows' => $penerimaanRows,
            'pengeluaranRows' => $pengeluaranRows,
            'penerimaanMonthlyTotals' => $penerimaanMonthlyTotals,
            'pengeluaranMonthlyTotals' => $pengeluaranMonthlyTotals,
            'saldoMonthlyTotals' => $saldoMonthlyTotals,
            'annualDebitTotal' => $annualDebitTotal,
            'annualKreditTotal' => $annualKreditTotal,
            'annualSaldo' => $annualDebitTotal - $annualKreditTotal,
            'clinicOptions' => $clinicContext['clinicOptions'],
            'showClinicFilter' => $clinicContext['showClinicFilter'],
            'selectedClinicFilter' => $clinicContext['viewingAllClinics']
                ? 'all'
                : (string) ($clinicContext['selectedClinicId'] ?: ''),
            'selectedClinicLabel' => $clinicContext['selectedClinicLabel'],
            'viewingAllClinics' => $clinicContext['viewingAllClinics'],
        ]);
    }

    public function syncRekapPasien(Request $request): RedirectResponse
    {
        $user = $request->user();

        abort_if($user?->isMaster(), 403, 'Role master tidak menggunakan tombol update rekap pasien.');

        $selectedDate = $this->normalizeSelectedDate($request->string('tanggal')->toString());
        $selectedLocalStatus = $this->normalizeLocalStatusFilter($request->string('local_status')->toString());
        $selectedPenjamin = trim($request->string('data_penjamin')->toString());
        $clinicProfileId = $this->resolveOperationalClinicId($request);
        $masterLayananByCode = MasterLayanan::query()
            ->active()
            ->get()
            ->keyBy('kode_layanan');

        $savedTransactions = TransaksiPasien::query()
            ->where('clinic_profile_id', $clinicProfileId)
            ->whereDate('tanggal', $selectedDate)
            ->get()
            ->keyBy('simrs_no_rawat');

        $visitRows = $this->simrsVisitRows($selectedDate, $clinicProfileId)
            ->filter(function (array $row) use ($savedTransactions, $selectedLocalStatus) {
                if ($selectedLocalStatus === 'saved') {
                    return $savedTransactions->has($row['simrs_no_rawat']);
                }

                if ($selectedLocalStatus === 'unsaved') {
                    return ! $savedTransactions->has($row['simrs_no_rawat']);
                }

                return true;
            })
            ->values();

        $now = now();
        $payload = $visitRows->map(function (array $row) use ($clinicProfileId, $masterLayananByCode, $now) {
            $layananCode = filled($row['layanan_medis'] ?? null)
                ? strtoupper(trim((string) $row['layanan_medis']))
                : null;
            $masterLayanan = $layananCode ? $masterLayananByCode->get($layananCode) : null;
            $tanggal = Carbon::parse($row['tanggal']);

            return [
                'clinic_profile_id' => $clinicProfileId,
                'master_layanan_id' => $masterLayanan?->id,
                'tanggal' => $tanggal->toDateString(),
                'bulan' => $tanggal->month,
                'tahun' => $tanggal->year,
                'no_rawat' => $row['simrs_no_rawat'],
                'no_rm' => $row['no_rm'] ?: null,
                'nama_pasien' => $row['nama_pasien'] ?: null,
                'layanan_medis' => $masterLayanan?->kode_layanan,
                'synced_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->all();

        RekapPasien::query()
            ->where('clinic_profile_id', $clinicProfileId)
            ->whereDate('tanggal', $selectedDate)
            ->delete();

        if (! empty($payload)) {
            RekapPasien::query()->upsert(
                $payload,
                ['clinic_profile_id', 'no_rawat'],
                ['master_layanan_id', 'tanggal', 'bulan', 'tahun', 'no_rm', 'nama_pasien', 'layanan_medis', 'synced_at', 'updated_at']
            );
        }

        RekapPasienUpdate::query()->updateOrCreate(
            ['clinic_profile_id' => $clinicProfileId],
            [
                'user_id' => $user?->id,
                'tanggal_data' => $selectedDate,
                'total_data' => count($payload),
                'synced_at' => $now,
            ]
        );

        return redirect()
            ->route('transaksi-pasien', [
                'tanggal' => $selectedDate,
                'data_bulan' => Carbon::parse($selectedDate)->format('Y-m'),
                'data_penjamin' => $selectedPenjamin ?: null,
                'local_status' => $selectedLocalStatus ?: null,
                'clinic_id' => $clinicProfileId,
                'active_tab' => 'panel-transaksi-pasien',
            ])
            ->with('success', 'Rekap pasien berhasil diperbarui dari data transaksi pasien yang tampil.');
    }

    public function storeTransaksiPasien(Request $request): RedirectResponse
    {
        $data = $this->validatedTransaksi($request);
        $redirectClinicId = $this->resolveTransaksiRedirectClinicFilter($request, $data['clinic_profile_id']);

        TransaksiPasien::query()->create($data);

        return redirect()
            ->route('transaksi-pasien', [
                'tanggal' => $data['tanggal'],
                'data_bulan' => Carbon::parse($data['tanggal'])->format('Y-m'),
                'data_penjamin' => $data['penjamin'] ?: null,
                'clinic_id' => $redirectClinicId,
                'active_tab' => 'panel-data-transaksi',
            ])
            ->with('success', 'Transaksi pasien berhasil disimpan.');
    }

    public function updateTransaksiPasien(Request $request, TransaksiPasien $transaksiPasien): RedirectResponse
    {
        $this->ensureOperationalRecordAccess($request->user(), $transaksiPasien->clinic_profile_id);
        $data = $this->validatedTransaksi($request, $transaksiPasien->id);
        $redirectClinicId = $this->resolveTransaksiRedirectClinicFilter($request, $data['clinic_profile_id']);

        $transaksiPasien->update($data);

        return redirect()
            ->route('transaksi-pasien', [
                'tanggal' => $data['tanggal'],
                'data_bulan' => Carbon::parse($data['tanggal'])->format('Y-m'),
                'data_penjamin' => $data['penjamin'] ?: null,
                'clinic_id' => $redirectClinicId,
                'active_tab' => 'panel-data-transaksi',
            ])
            ->with('success', 'Transaksi pasien berhasil diperbarui.');
    }

    public function destroyTransaksiPasien(Request $request, TransaksiPasien $transaksiPasien): RedirectResponse
    {
        $this->ensureOperationalRecordAccess($request->user(), $transaksiPasien->clinic_profile_id);
        $tanggal = optional($transaksiPasien->tanggal)->toDateString() ?: now()->toDateString();
        $bulan = optional($transaksiPasien->tanggal)->format('Y-m') ?: now()->format('Y-m');
        $penjamin = $transaksiPasien->penjamin;
        $clinicId = $this->resolveTransaksiRedirectClinicFilter($request, $transaksiPasien->clinic_profile_id);
        $transaksiPasien->delete();

        return redirect()
            ->route('transaksi-pasien', [
                'tanggal' => $tanggal,
                'data_bulan' => $bulan,
                'data_penjamin' => $penjamin ?: null,
                'clinic_id' => $clinicId,
                'active_tab' => 'panel-data-transaksi',
            ])
            ->with('success', 'Transaksi pasien berhasil dihapus.');
    }

    public function storePengeluaran(Request $request): RedirectResponse
    {
        $data = $this->validatedPengeluaran($request);

        Pengeluaran::query()->create($data);

        return redirect()
            ->route('input-pengeluaran', [
                'bulan' => Carbon::parse($data['tanggal'])->format('Y-m'),
                'kategori' => $data['kategori_pengeluaran'] ?: null,
                'clinic_id' => $data['clinic_profile_id'],
                'active_tab' => 'panel-data-pengeluaran',
            ])
            ->with('success', 'Pengeluaran berhasil disimpan.');
    }

    public function updatePengeluaran(Request $request, Pengeluaran $pengeluaran): RedirectResponse
    {
        $this->ensureOperationalRecordAccess($request->user(), $pengeluaran->clinic_profile_id);
        $data = $this->validatedPengeluaran($request);

        $pengeluaran->update($data);

        return redirect()
            ->route('input-pengeluaran', [
                'bulan' => Carbon::parse($data['tanggal'])->format('Y-m'),
                'kategori' => $data['kategori_pengeluaran'] ?: null,
                'clinic_id' => $data['clinic_profile_id'],
                'active_tab' => 'panel-data-pengeluaran',
            ])
            ->with('success', 'Pengeluaran berhasil diperbarui.');
    }

    public function destroyPengeluaran(Request $request, Pengeluaran $pengeluaran): RedirectResponse
    {
        $this->ensureOperationalRecordAccess($request->user(), $pengeluaran->clinic_profile_id);
        $bulan = optional($pengeluaran->tanggal)->format('Y-m') ?: now()->format('Y-m');
        $kategori = $pengeluaran->kategori_pengeluaran;
        $clinicId = $pengeluaran->clinic_profile_id;
        $pengeluaran->delete();

        return redirect()
            ->route('input-pengeluaran', [
                'bulan' => $bulan,
                'kategori' => $kategori ?: null,
                'clinic_id' => $clinicId,
                'active_tab' => 'panel-data-pengeluaran',
            ])
            ->with('success', 'Pengeluaran berhasil dihapus.');
    }

    private function simrsVisitRows(string $selectedDate, ?int $clinicProfileId = null): Collection
    {
        $simrs = $this->resolveSimrsConnection($clinicProfileId);
        $masterLayanan = MasterLayanan::query()
            ->active()
            ->orderBy('urutan_laporan')
            ->orderBy('nama_layanan')
            ->get();

        $diagnosaSub = $simrs->table('diagnosa_pasien as dp')
            ->leftJoin('penyakit as py', 'py.kd_penyakit', '=', 'dp.kd_penyakit')
            ->selectRaw("
                dp.no_rawat,
                GROUP_CONCAT(DISTINCT dp.kd_penyakit ORDER BY dp.prioritas SEPARATOR ', ') as icd,
                GROUP_CONCAT(DISTINCT py.nm_penyakit ORDER BY dp.prioritas SEPARATOR '; ') as diagnosa
            ")
            ->groupBy('dp.no_rawat');

        $labSub = $simrs->table('periksa_lab as plab')
            ->leftJoin('jns_perawatan_lab as jpl', 'jpl.kd_jenis_prw', '=', 'plab.kd_jenis_prw')
            ->selectRaw("
                plab.no_rawat,
                GROUP_CONCAT(DISTINCT jpl.nm_perawatan SEPARATOR '; ') as lab
            ")
            ->groupBy('plab.no_rawat');

        $farmasiSub = $simrs->table('pemeriksaan_ralan as pr')
            ->selectRaw("
                pr.no_rawat,
                GROUP_CONCAT(NULLIF(TRIM(pr.rtl), '') ORDER BY pr.tgl_perawatan, pr.jam_rawat SEPARATOR '\n\n') as farmasi
            ")
            ->groupBy('pr.no_rawat');

        $obatSub = $simrs->table('detail_pemberian_obat as dpo')
            ->selectRaw("
                dpo.no_rawat,
                COALESCE(SUM(dpo.total), 0) as uang_obat
            ")
            ->groupBy('dpo.no_rawat');

        $rawatDr = $simrs->table('rawat_jl_dr')
            ->selectRaw('no_rawat, COALESCE(biaya_rawat, 0) as biaya_rawat, COALESCE(tarif_tindakandr, 0) as jasa_dokter');

        $rawatPr = $simrs->table('rawat_jl_pr')
            ->selectRaw('no_rawat, COALESCE(biaya_rawat, 0) as biaya_rawat, 0 as jasa_dokter');

        $rawatDrpr = $simrs->table('rawat_jl_drpr')
            ->selectRaw('no_rawat, COALESCE(biaya_rawat, 0) as biaya_rawat, COALESCE(tarif_tindakandr, 0) as jasa_dokter');

        $rawatSub = $simrs->query()
            ->fromSub($rawatDr->unionAll($rawatPr)->unionAll($rawatDrpr), 'rawat_rows')
            ->selectRaw('
                no_rawat,
                COALESCE(SUM(biaya_rawat), 0) as uang_periksa,
                COALESCE(SUM(jasa_dokter), 0) as jasa_dokter,
                COUNT(*) as jml_visit
            ')
            ->groupBy('no_rawat');

        $kamarSub = $simrs->table('kamar_inap')
            ->selectRaw('
                no_rawat,
                COALESCE(SUM(lama), 0) as jml_hari,
                COALESCE(SUM(ttl_biaya), 0) as rawat_inap
            ')
            ->groupBy('no_rawat');

        return $simrs->table('reg_periksa as rp')
            ->leftJoin('pasien as ps', 'ps.no_rkm_medis', '=', 'rp.no_rkm_medis')
            ->leftJoin('dokter as dk', 'dk.kd_dokter', '=', 'rp.kd_dokter')
            ->leftJoin('poliklinik as pl', 'pl.kd_poli', '=', 'rp.kd_poli')
            ->leftJoin('penjab as pj', 'pj.kd_pj', '=', 'rp.kd_pj')
            ->leftJoinSub($diagnosaSub, 'diag', fn ($join) => $join->on('diag.no_rawat', '=', 'rp.no_rawat'))
            ->leftJoinSub($labSub, 'lab_data', fn ($join) => $join->on('lab_data.no_rawat', '=', 'rp.no_rawat'))
            ->leftJoinSub($farmasiSub, 'farmasi_data', fn ($join) => $join->on('farmasi_data.no_rawat', '=', 'rp.no_rawat'))
            ->leftJoinSub($obatSub, 'obat_data', fn ($join) => $join->on('obat_data.no_rawat', '=', 'rp.no_rawat'))
            ->leftJoinSub($rawatSub, 'rawat_data', fn ($join) => $join->on('rawat_data.no_rawat', '=', 'rp.no_rawat'))
            ->leftJoinSub($kamarSub, 'ranap_data', fn ($join) => $join->on('ranap_data.no_rawat', '=', 'rp.no_rawat'))
            ->whereDate('rp.tgl_registrasi', $selectedDate)
            ->orderBy('rp.jam_reg')
            ->selectRaw('
                rp.no_rawat,
                rp.no_reg,
                rp.kd_poli,
                rp.tgl_registrasi,
                rp.jam_reg,
                rp.no_rkm_medis as no_rm,
                rp.biaya_reg,
                rp.stts_daftar,
                rp.status_lanjut,
                rp.status_bayar,
                dk.nm_dokter as dokter,
                pl.nm_poli as simrs_nama_poli,
                pj.png_jawab as penjamin,
                ps.nm_pasien,
                ps.jk,
                ps.alamat,
                COALESCE(diag.icd, "") as icd,
                COALESCE(diag.diagnosa, "") as diagnosa,
                COALESCE(lab_data.lab, "") as lab,
                COALESCE(farmasi_data.farmasi, "") as farmasi,
                COALESCE(obat_data.uang_obat, 0) as uang_obat,
                COALESCE(rawat_data.uang_periksa, 0) as uang_periksa,
                COALESCE(rawat_data.jasa_dokter, 0) as jasa_dokter,
                COALESCE(rawat_data.jml_visit, 0) as jml_visit,
                COALESCE(ranap_data.jml_hari, 0) as jml_hari,
                COALESCE(ranap_data.rawat_inap, 0) as rawat_inap
            ')
            ->get()
            ->map(function ($row) use ($masterLayanan) {
                $tanggal = Carbon::parse($row->tgl_registrasi);
                $mappedLayanan = $this->resolveMappedLayananFromSimrs(
                    $row->kd_poli,
                    $masterLayanan
                );
                $fallbackKodePoli = $row->kd_poli ? strtoupper(trim((string) $row->kd_poli)) : null;
                $mappedNamaLayanan = $mappedLayanan['nama_layanan'] ?? null;

                return [
                    'simrs_no_rawat' => $row->no_rawat,
                    'simrs_no_reg' => $row->no_reg,
                    'tanggal' => $tanggal->toDateString(),
                    'bulan' => $tanggal->month,
                    'harian' => $tanggal->locale('id')->translatedFormat('l'),
                    'layanan_medis' => $mappedLayanan['kode_layanan']
                        ?? $fallbackKodePoli,
                    'layanan_label' => $mappedNamaLayanan ?: 'Belum Dimapping',
                    'dokter' => $row->dokter,
                    'penjamin' => $row->penjamin,
                    'no_rm' => $row->no_rm,
                    'nama_pasien' => $row->nm_pasien,
                    'jk' => $row->jk,
                    'statis' => $row->stts_daftar,
                    'genap' => '',
                    'status_pasien' => $row->status_lanjut,
                    'alamat' => $row->alamat,
                    'lab' => $row->lab,
                    'icd' => $row->icd,
                    'diagnosa' => $row->diagnosa,
                    'farmasi' => $row->farmasi,
                    'uang_daftar' => (float) $row->biaya_reg,
                    'uang_periksa' => (float) $row->uang_periksa,
                    'uang_obat' => (float) $row->uang_obat,
                    'uang_bersalin' => 0,
                    'jasa_dokter' => (float) $row->jasa_dokter,
                    'jml_hari' => (int) $row->jml_hari,
                    'rawat_inap' => (float) $row->rawat_inap,
                    'jml_visit' => (int) $row->jml_visit,
                    'honor_dr_visit' => 0,
                    'oksigen' => 0,
                    'perlengk_bayi' => 0,
                    'jaspel_nakes' => 0,
                    'bmhp' => 0,
                    'pkl' => 0,
                    'lain_lain' => 0,
                    'jumlah_rp' => collect([
                        (float) $row->biaya_reg,
                        (float) $row->uang_periksa,
                        (float) $row->uang_obat,
                        (float) $row->jasa_dokter,
                        (float) $row->rawat_inap,
                    ])->sum(),
                    'utang_pasien' => 0,
                    'utang' => 0,
                    'bayar_utang_pasien' => 0,
                    'derma_solidaritas' => 0,
                    'saldo_kredit' => 0,
                    'saldo' => 0,
                    'petugas_admin' => '',
                    'keterangan' => '',
                    'meta' => [
                        'jam_reg' => $row->jam_reg,
                        'status_bayar' => $row->status_bayar,
                        'penjamin' => $row->penjamin,
                        'kode_layanan' => $mappedLayanan['kode_layanan'] ?? null,
                        'nama_layanan' => $mappedNamaLayanan,
                        'simrs_kd_poli' => $row->kd_poli,
                        'simrs_nama_poli' => $row->simrs_nama_poli,
                    ],
                ];
            })
            ->values();
    }

    private function resolveSimrsConnection(?int $clinicProfileId = null)
    {
        return app(ClinicDatabaseManager::class)->connectionFor($clinicProfileId, 'simrs');
    }

    private function clinicOptionsForUser(?User $user = null): Collection
    {
        $user ??= auth()->user();

        if (! $user) {
            return collect();
        }

        if ($user->isMaster()) {
            return ClinicProfile::query()
                ->active()
                ->orderBy('nama_klinik')
                ->get();
        }

        if (! $user->clinic_profile_id) {
            return collect();
        }

        return ClinicProfile::query()
            ->active()
            ->whereKey($user->clinic_profile_id)
            ->get();
    }

    private function clinicContext(Request $request, bool $allowAll = false, string $parameter = 'clinic_id'): array
    {
        $user = $request->user();
        $clinicOptions = $this->clinicOptionsForUser($user);
        $selectedClinic = null;
        $selectedClinicId = null;
        $viewingAllClinics = false;

        if ($user?->isMaster()) {
            $rawClinic = strtolower(trim((string) $request->query($parameter, '')));

            if ($allowAll && $rawClinic === 'all') {
                $viewingAllClinics = true;
            } else {
                $requestedClinicId = (int) $rawClinic;
                $selectedClinic = $requestedClinicId > 0
                    ? $clinicOptions->firstWhere('id', $requestedClinicId)
                    : null;
                $selectedClinic ??= $clinicOptions->first();
                $selectedClinicId = $selectedClinic?->id;
            }
        } else {
            $selectedClinic = $clinicOptions->first();
            $selectedClinicId = $selectedClinic?->id;
        }

        return [
            'clinicOptions' => $clinicOptions,
            'selectedClinic' => $selectedClinic,
            'selectedClinicId' => $selectedClinicId,
            'selectedClinicLabel' => $viewingAllClinics
                ? 'Semua Klinik'
                : ($selectedClinic?->nama_pendek ?: $selectedClinic?->nama_klinik ?: 'Klinik'),
            'viewingAllClinics' => $viewingAllClinics,
            'showClinicFilter' => $user?->isMaster() && $clinicOptions->isNotEmpty(),
        ];
    }

    private function resolveOperationalClinicId(Request $request): int
    {
        $user = $request->user();
        $clinicOptions = $this->clinicOptionsForUser($user);

        if ($user?->isMaster()) {
            $requestedClinicId = (int) $request->input('clinic_profile_id', $request->query('clinic_id'));
            $selectedClinic = null;

            if ($requestedClinicId > 0) {
                $selectedClinic = $clinicOptions->firstWhere('id', $requestedClinicId);
                abort_if(! $selectedClinic, 422, 'Klinik tujuan tidak valid.');
            }

            $selectedClinic ??= $clinicOptions->first();

            abort_if(! $selectedClinic, 422, 'Pilih klinik tujuan terlebih dahulu.');

            return (int) $selectedClinic->id;
        }

        abort_if(! $user?->clinic_profile_id, 403, 'Akun ini belum terhubung ke klinik.');

        return (int) $user->clinic_profile_id;
    }

    private function scopeQueryToClinic($query, ?int $clinicProfileId)
    {
        if ($clinicProfileId) {
            $query->where('clinic_profile_id', $clinicProfileId);
        }

        return $query;
    }

    private function ensureOperationalRecordAccess(?User $user, ?int $clinicProfileId): void
    {
        abort_if(! $user, 403);

        if ($user->isMaster()) {
            return;
        }

        abort_if(
            ! $user->clinic_profile_id || (int) $user->clinic_profile_id !== (int) $clinicProfileId,
            403,
            'Anda tidak punya akses ke data klinik ini.'
        );
    }

    private function resolveTransaksiRedirectClinicFilter(Request $request, ?int $fallbackClinicId): string|int|null
    {
        $user = $request->user();

        if ($user?->isMaster()) {
            $contextClinic = strtolower(trim((string) $request->input('context_clinic_id', $request->query('clinic_id', ''))));

            if ($contextClinic === 'all') {
                return 'all';
            }
        }

        return $fallbackClinicId;
    }

    private function validatedTransaksi(Request $request, ?int $ignoreId = null): array
    {
        $clinicProfileId = $this->resolveOperationalClinicId($request);

        $data = $request->validate([
            'simrs_no_rawat' => [
                'required',
                'string',
                'max:30',
                Rule::unique('transaksi_pasien', 'simrs_no_rawat')
                    ->where(fn ($query) => $query->where('clinic_profile_id', $clinicProfileId))
                    ->ignore($ignoreId),
            ],
            'clinic_profile_id' => ['nullable', 'integer'],
            'simrs_no_reg' => ['nullable', 'string', 'max:20'],
            'tanggal' => ['required', 'date'],
            'bulan' => ['required', 'integer', 'between:1,12'],
            'harian' => ['nullable', 'string', 'max:30'],
            'layanan_medis' => ['nullable', 'string', 'max:255'],
            'dokter' => ['nullable', 'string', 'max:255'],
            'penjamin' => ['nullable', 'string', 'max:255'],
            'no_rm' => ['nullable', 'string', 'max:50'],
            'nama_pasien' => ['nullable', 'string', 'max:255'],
            'jk' => ['nullable', 'string', 'max:20'],
            'statis' => ['nullable', 'string', 'max:100'],
            'genap' => ['nullable', 'string', 'max:100'],
            'status_pasien' => ['nullable', 'string', 'max:100'],
            'alamat' => ['nullable', 'string'],
            'lab' => ['nullable', 'string'],
            'icd' => ['nullable', 'string'],
            'diagnosa' => ['nullable', 'string'],
            'farmasi' => ['nullable', 'string'],
            'uang_daftar' => ['nullable', 'numeric', 'min:0'],
            'uang_periksa' => ['nullable', 'numeric', 'min:0'],
            'uang_obat' => ['nullable', 'numeric', 'min:0'],
            'uang_bersalin' => ['nullable', 'numeric', 'min:0'],
            'jasa_dokter' => ['nullable', 'numeric', 'min:0'],
            'jml_hari' => ['nullable', 'integer', 'min:0'],
            'rawat_inap' => ['nullable', 'numeric', 'min:0'],
            'jml_visit' => ['nullable', 'integer', 'min:0'],
            'honor_dr_visit' => ['nullable', 'numeric', 'min:0'],
            'oksigen' => ['nullable', 'numeric', 'min:0'],
            'perlengk_bayi' => ['nullable', 'numeric', 'min:0'],
            'jaspel_nakes' => ['nullable', 'numeric', 'min:0'],
            'bmhp' => ['nullable', 'numeric', 'min:0'],
            'pkl' => ['nullable', 'numeric', 'min:0'],
            'lain_lain' => ['nullable', 'numeric', 'min:0'],
            'utang_pasien' => ['nullable', 'numeric', 'min:0'],
            'utang' => ['nullable', 'numeric', 'min:0'],
            'bayar_utang_pasien' => ['nullable', 'numeric', 'min:0'],
            'derma_solidaritas' => ['nullable', 'numeric', 'min:0'],
            'saldo_kredit' => ['nullable', 'numeric', 'min:0'],
            'saldo' => ['nullable', 'numeric', 'min:0'],
            'keterangan' => ['nullable', 'string'],
        ]);

        $tanggal = Carbon::parse($data['tanggal']);
        $data['tanggal'] = $tanggal->toDateString();
        $data['bulan'] = (int) ($data['bulan'] ?? $tanggal->month);
        $data['harian'] = $data['harian'] ?: $tanggal->locale('id')->translatedFormat('l');

        $mappedLayanan = $this->resolveMappedLayananFromStoredValue($data['layanan_medis'] ?? null);
        if ($mappedLayanan) {
            $data['layanan_medis'] = $mappedLayanan['kode_layanan'];
        } elseif (filled($data['layanan_medis'])) {
            $data['layanan_medis'] = strtoupper(trim((string) $data['layanan_medis']));
        }

        foreach ([
            'uang_daftar',
            'uang_periksa',
            'uang_obat',
            'uang_bersalin',
            'jasa_dokter',
            'rawat_inap',
            'honor_dr_visit',
            'oksigen',
            'perlengk_bayi',
            'jaspel_nakes',
            'bmhp',
            'pkl',
            'lain_lain',
            'utang_pasien',
            'utang',
            'bayar_utang_pasien',
            'derma_solidaritas',
            'saldo_kredit',
            'saldo',
        ] as $field) {
            $data[$field] = (float) ($data[$field] ?? 0);
        }

        $data['jml_hari'] = (int) ($data['jml_hari'] ?? 0);
        $data['jml_visit'] = (int) ($data['jml_visit'] ?? 0);
        $data['jumlah_rp'] = collect([
            $data['uang_daftar'],
            $data['uang_periksa'],
            $data['uang_obat'],
            $data['uang_bersalin'],
            $data['jasa_dokter'],
            $data['rawat_inap'],
            $data['honor_dr_visit'],
            $data['oksigen'],
            $data['perlengk_bayi'],
            $data['jaspel_nakes'],
            $data['bmhp'],
            $data['pkl'],
            $data['lain_lain'],
        ])->sum();
        $data['clinic_profile_id'] = $clinicProfileId;
        $data['user_id'] = $request->user()?->id;
        $data['petugas_admin'] = $this->resolveLoggedInAdminName($request->user());

        return $data;
    }

    private function validatedPengeluaran(Request $request): array
    {
        $clinicProfileId = $this->resolveOperationalClinicId($request);
        $data = $request->validate([
            'clinic_profile_id' => ['nullable', 'integer'],
            'master_kategori_pengeluaran_id' => ['nullable', 'integer', 'exists:master_kategori_pengeluaran,id'],
            'tanggal' => ['required', 'date'],
            'deskripsi' => ['required', 'string', 'max:255'],
            'jumlah_rp' => ['required', 'numeric', 'min:0'],
            'keterangan' => ['nullable', 'string'],
        ]);

        $tanggal = Carbon::parse($data['tanggal']);
        $kategori = null;

        if (filled($data['master_kategori_pengeluaran_id'])) {
            $kategori = MasterKategoriPengeluaran::query()
                ->whereKey($data['master_kategori_pengeluaran_id'])
                ->value('nama_kategori');
        }

        return [
            'clinic_profile_id' => $clinicProfileId,
            'master_kategori_pengeluaran_id' => $data['master_kategori_pengeluaran_id'] ?? null,
            'user_id' => $request->user()?->id,
            'tanggal' => $tanggal->toDateString(),
            'bulan' => $tanggal->month,
            'tahun' => $tanggal->year,
            'kategori_pengeluaran' => $kategori,
            'deskripsi' => $data['deskripsi'],
            'jumlah_rp' => (float) $data['jumlah_rp'],
            'petugas_admin' => $this->resolveLoggedInAdminName($request->user()),
            'keterangan' => $data['keterangan'] ?? null,
        ];
    }

    private function validatedMasterLayanan(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'kode_layanan' => [
                'required',
                'string',
                'max:20',
                Rule::unique('master_layanan', 'kode_layanan')->ignore($ignoreId),
            ],
            'nama_layanan' => ['required', 'string', 'max:255'],
            'simrs_kd_poli' => ['nullable', 'string', 'max:100'],
            'simrs_nm_poli' => ['nullable', 'string', 'max:255'],
            'urutan_laporan' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        return [
            'kode_layanan' => strtoupper(trim((string) $data['kode_layanan'])),
            'nama_layanan' => trim((string) $data['nama_layanan']),
            'simrs_kd_poli' => filled($data['simrs_kd_poli'] ?? null)
                ? strtolower(trim((string) $data['simrs_kd_poli']))
                : null,
            'simrs_nm_poli' => filled($data['simrs_nm_poli'] ?? null)
                ? trim((string) $data['simrs_nm_poli'])
                : null,
            'urutan_laporan' => (int) ($data['urutan_laporan'] ?? 0),
            'is_active' => $request->boolean('is_active'),
        ];
    }

    private function validatedMasterKategoriPengeluaran(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'kode_kategori' => [
                'required',
                'string',
                'max:20',
                Rule::unique('master_kategori_pengeluaran', 'kode_kategori')->ignore($ignoreId),
            ],
            'nama_kategori' => ['required', 'string', 'max:255'],
            'urutan_laporan' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        return [
            'kode_kategori' => strtoupper(trim((string) $data['kode_kategori'])),
            'nama_kategori' => trim((string) $data['nama_kategori']),
            'urutan_laporan' => (int) ($data['urutan_laporan'] ?? 0),
            'is_active' => $request->boolean('is_active'),
        ];
    }

    private function validatedClinicProfile(
        Request $request,
        ?int $ignoreId = null,
        bool $allowMasterFields = false,
        ?ClinicProfile $existingProfile = null
    ): array
    {
        $data = $request->validate([
            'kode_klinik' => $allowMasterFields
                ? [
                    'required',
                    'string',
                    'max:40',
                    Rule::unique('clinic_profiles', 'kode_klinik')->ignore($ignoreId),
                ]
                : ['nullable', 'string', 'max:40'],
            'nama_klinik' => ['required', 'string', 'max:255'],
            'nama_pendek' => ['nullable', 'string', 'max:120'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'alamat' => ['nullable', 'string'],
            'kota' => ['nullable', 'string', 'max:120'],
            'provinsi' => ['nullable', 'string', 'max:120'],
            'kode_pos' => ['nullable', 'string', 'max:20'],
            'telepon' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'penanggung_jawab' => ['nullable', 'string', 'max:255'],
            'jam_pelayanan' => ['nullable', 'string', 'max:255'],
            'deskripsi_singkat' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        return [
            'kode_klinik' => $allowMasterFields
                ? strtoupper(trim((string) $data['kode_klinik']))
                : ($existingProfile?->kode_klinik ?: 'KLN' . str_pad((string) ($ignoreId ?: 1), 3, '0', STR_PAD_LEFT)),
            'nama_klinik' => trim((string) $data['nama_klinik']),
            'nama_pendek' => filled($data['nama_pendek'] ?? null)
                ? trim((string) $data['nama_pendek'])
                : null,
            'tagline' => filled($data['tagline'] ?? null)
                ? trim((string) $data['tagline'])
                : null,
            'alamat' => filled($data['alamat'] ?? null)
                ? trim((string) $data['alamat'])
                : null,
            'kota' => filled($data['kota'] ?? null)
                ? trim((string) $data['kota'])
                : null,
            'provinsi' => filled($data['provinsi'] ?? null)
                ? trim((string) $data['provinsi'])
                : null,
            'kode_pos' => filled($data['kode_pos'] ?? null)
                ? trim((string) $data['kode_pos'])
                : null,
            'telepon' => filled($data['telepon'] ?? null)
                ? trim((string) $data['telepon'])
                : null,
            'email' => filled($data['email'] ?? null)
                ? strtolower(trim((string) $data['email']))
                : null,
            'website' => filled($data['website'] ?? null)
                ? trim((string) $data['website'])
                : null,
            'penanggung_jawab' => filled($data['penanggung_jawab'] ?? null)
                ? trim((string) $data['penanggung_jawab'])
                : null,
            'jam_pelayanan' => filled($data['jam_pelayanan'] ?? null)
                ? trim((string) $data['jam_pelayanan'])
                : null,
            'deskripsi_singkat' => filled($data['deskripsi_singkat'] ?? null)
                ? trim((string) $data['deskripsi_singkat'])
                : null,
            'is_active' => $allowMasterFields
                ? $request->boolean('is_active', true)
                : ($existingProfile?->is_active ?? true),
        ];
    }

    private function normalizeLocalStatusFilter(?string $value): string
    {
        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, ['saved', 'unsaved'], true)
            ? $normalized
            : '';
    }

    private function monthlyLayananRows(Collection $transactions, Collection $masterLayanan): Collection
    {
        $groupedTransactions = $transactions->groupBy(
            fn (TransaksiPasien $item) => $this->normalizeLayananKey($item->layanan_medis)
        );

        $consumedKeys = [];
        $rows = collect();

        foreach ($masterLayanan as $layanan) {
            $sum = 0;

            foreach ($this->layananLookupKeys($layanan) as $lookupKey) {
                if ($groupedTransactions->has($lookupKey)) {
                    $sum += (float) $groupedTransactions->get($lookupKey)->sum('jumlah_rp');
                    $consumedKeys[] = $lookupKey;
                }
            }

            $rows->push([
                'kode' => $layanan->kode_layanan,
                'keterangan' => $layanan->nama_layanan,
                'debet' => $sum,
                'kredit' => 0,
            ]);
        }

        $leftoverIndex = 1;
        foreach ($groupedTransactions as $normalizedLayanan => $items) {
            if (in_array($normalizedLayanan, $consumedKeys, true) || blank($normalizedLayanan)) {
                continue;
            }

            $rows->push([
                'kode' => 'LX' . $leftoverIndex,
                'keterangan' => $this->resolveMappedLayananFromStoredValue(
                    $items->first()->layanan_medis,
                    $masterLayanan
                )['nama_layanan'] ?? ($items->first()->layanan_medis ?: 'Layanan Lain'),
                'debet' => (float) $items->sum('jumlah_rp'),
                'kredit' => 0,
            ]);

            $leftoverIndex++;
        }

        return $rows;
    }

    private function monthlyKomponenRows(Collection $transactions): Collection
    {
        return collect([
            ['kode' => 'D1', 'keterangan' => 'Uang Daftar', 'field' => 'uang_daftar'],
            ['kode' => 'D2', 'keterangan' => 'Uang Periksa', 'field' => 'uang_periksa'],
            ['kode' => 'D3', 'keterangan' => 'Uang Obat', 'field' => 'uang_obat'],
            ['kode' => 'D4', 'keterangan' => 'Uang Bersalin', 'field' => 'uang_bersalin'],
            ['kode' => 'D5', 'keterangan' => 'Jasa Dokter', 'field' => 'jasa_dokter'],
            ['kode' => 'D6', 'keterangan' => 'Rawat Inap', 'field' => 'rawat_inap'],
            ['kode' => 'D7', 'keterangan' => 'Honor dr Visit', 'field' => 'honor_dr_visit'],
            ['kode' => 'D8', 'keterangan' => 'Oksigen', 'field' => 'oksigen'],
            ['kode' => 'D9', 'keterangan' => 'Perlengkapan Bayi', 'field' => 'perlengk_bayi'],
            ['kode' => 'D10', 'keterangan' => 'Jaspel Nakes', 'field' => 'jaspel_nakes'],
            ['kode' => 'D11', 'keterangan' => 'BMHP', 'field' => 'bmhp'],
            ['kode' => 'D12', 'keterangan' => 'PKL', 'field' => 'pkl'],
            ['kode' => 'D13', 'keterangan' => 'Lain-lain', 'field' => 'lain_lain'],
        ])->map(function (array $component) use ($transactions) {
            return [
                'kode' => $component['kode'],
                'keterangan' => $component['keterangan'],
                'debet' => (float) $transactions->sum($component['field']),
                'kredit' => 0,
            ];
        });
    }

    private function monthlyPengeluaranRows(Collection $expenses, Collection $categories): Collection
    {
        $groupedExpenses = $expenses->groupBy(
            fn (Pengeluaran $expense) => strtolower(trim((string) $expense->kategori_pengeluaran))
        );

        $consumedKeys = [];
        $rows = collect();

        foreach ($categories as $category) {
            $normalized = strtolower(trim((string) $category->nama_kategori));
            $sum = 0;

            if ($groupedExpenses->has($normalized)) {
                $sum = (float) $groupedExpenses->get($normalized)->sum('jumlah_rp');
                $consumedKeys[] = $normalized;
            }

            $rows->push([
                'kode' => $category->kode_kategori,
                'keterangan' => $category->nama_kategori,
                'debet' => 0,
                'kredit' => $sum,
            ]);
        }

        $leftoverIndex = 1;
        foreach ($groupedExpenses as $normalizedCategory => $items) {
            if (in_array($normalizedCategory, $consumedKeys, true) || blank($normalizedCategory)) {
                continue;
            }

            $rows->push([
                'kode' => 'PX' . $leftoverIndex,
                'keterangan' => $items->first()->kategori_pengeluaran ?: 'Kategori Lain',
                'debet' => 0,
                'kredit' => (float) $items->sum('jumlah_rp'),
            ]);

            $leftoverIndex++;
        }

        return $rows;
    }

    private function yearlyLayananRows(Collection $transactions, Collection $masterLayanan): Collection
    {
        $grouped = [];

        foreach ($transactions as $transaction) {
            $resolved = $this->resolveMappedLayananFromStoredValue($transaction->layanan_medis, $masterLayanan);
            $code = $resolved['kode_layanan'] ?? strtoupper(trim((string) $transaction->layanan_medis));
            $month = (int) (optional($transaction->tanggal)->format('n') ?: $transaction->bulan);

            if ($code === '' || $month < 1 || $month > 12) {
                continue;
            }

            $grouped[$code]['keterangan'] = $resolved['nama_layanan'] ?? 'Belum Dimapping';
            $grouped[$code]['months'][$month] = ($grouped[$code]['months'][$month] ?? 0) + (float) $transaction->jumlah_rp;
        }

        $rows = collect();
        $consumedCodes = [];

        foreach ($masterLayanan as $layanan) {
            $consumedCodes[] = $layanan->kode_layanan;
            $monthCells = $this->blankYearMonthCells();
            $rowTotal = 0;

            foreach (range(1, 12) as $month) {
                $value = (float) data_get($grouped, $layanan->kode_layanan . '.months.' . $month, 0);
                $monthCells[$month]['debet'] = $value;
                $rowTotal += $value;
            }

            $rows->push([
                'kode' => $layanan->kode_layanan,
                'keterangan' => $layanan->nama_layanan,
                'months' => $monthCells,
                'total_debet' => $rowTotal,
                'total_kredit' => 0,
            ]);
        }

        foreach ($grouped as $code => $payload) {
            if (in_array($code, $consumedCodes, true)) {
                continue;
            }

            $monthCells = $this->blankYearMonthCells();
            $rowTotal = 0;

            foreach (range(1, 12) as $month) {
                $value = (float) ($payload['months'][$month] ?? 0);
                $monthCells[$month]['debet'] = $value;
                $rowTotal += $value;
            }

            $rows->push([
                'kode' => $code,
                'keterangan' => $payload['keterangan'] ?? 'Belum Dimapping',
                'months' => $monthCells,
                'total_debet' => $rowTotal,
                'total_kredit' => 0,
            ]);
        }

        return $rows;
    }

    private function yearlyPengeluaranRows(Collection $expenses, Collection $categories): Collection
    {
        $categoriesById = $categories->keyBy('id');
        $categoriesByName = $categories->keyBy(
            fn (MasterKategoriPengeluaran $category) => strtolower(trim((string) $category->nama_kategori))
        );

        $grouped = [];

        foreach ($expenses as $expense) {
            $month = (int) (optional($expense->tanggal)->format('n') ?: $expense->bulan);

            if ($month < 1 || $month > 12) {
                continue;
            }

            $matchedCategory = null;

            if ($expense->master_kategori_pengeluaran_id) {
                $matchedCategory = $categoriesById->get($expense->master_kategori_pengeluaran_id);
            }

            if (! $matchedCategory && filled($expense->kategori_pengeluaran)) {
                $matchedCategory = $categoriesByName->get(strtolower(trim((string) $expense->kategori_pengeluaran)));
            }

            $code = $matchedCategory?->kode_kategori ?: strtoupper(trim((string) ($expense->kategori_pengeluaran ?: 'LAIN')));
            $label = $matchedCategory?->nama_kategori ?: ($expense->kategori_pengeluaran ?: 'Belum Dimapping');

            if ($code === '') {
                continue;
            }

            $grouped[$code]['keterangan'] = $label;
            $grouped[$code]['months'][$month] = ($grouped[$code]['months'][$month] ?? 0) + (float) $expense->jumlah_rp;
        }

        $rows = collect();
        $consumedCodes = [];

        foreach ($categories as $category) {
            $consumedCodes[] = $category->kode_kategori;
            $monthCells = $this->blankYearMonthCells();
            $rowTotal = 0;

            foreach (range(1, 12) as $month) {
                $value = (float) data_get($grouped, $category->kode_kategori . '.months.' . $month, 0);
                $monthCells[$month]['kredit'] = $value;
                $rowTotal += $value;
            }

            $rows->push([
                'kode' => $category->kode_kategori,
                'keterangan' => $category->nama_kategori,
                'months' => $monthCells,
                'total_debet' => 0,
                'total_kredit' => $rowTotal,
            ]);
        }

        foreach ($grouped as $code => $payload) {
            if (in_array($code, $consumedCodes, true)) {
                continue;
            }

            $monthCells = $this->blankYearMonthCells();
            $rowTotal = 0;

            foreach (range(1, 12) as $month) {
                $value = (float) ($payload['months'][$month] ?? 0);
                $monthCells[$month]['kredit'] = $value;
                $rowTotal += $value;
            }

            $rows->push([
                'kode' => $code,
                'keterangan' => $payload['keterangan'] ?? 'Belum Dimapping',
                'months' => $monthCells,
                'total_debet' => 0,
                'total_kredit' => $rowTotal,
            ]);
        }

        return $rows;
    }

    private function yearMonthHeaders(): Collection
    {
        return collect(range(1, 12))->map(function (int $month) {
            return [
                'number' => $month,
                'label' => Carbon::create()->month($month)->locale('id')->translatedFormat('F'),
            ];
        });
    }

    private function yearlySideTotals(Collection $rows, string $side): array
    {
        return collect(range(1, 12))
            ->mapWithKeys(fn (int $month) => [
                $month => (float) $rows->sum(fn (array $row) => (float) data_get($row, 'months.' . $month . '.' . $side, 0)),
            ])
            ->all();
    }

    private function blankYearMonthCells(): array
    {
        return collect(range(1, 12))
            ->mapWithKeys(fn (int $month) => [
                $month => [
                    'debet' => 0.0,
                    'kredit' => 0.0,
                ],
            ])
            ->all();
    }

    private function resolveMappedLayananFromSimrs(
        ?string $simrsKdPoli,
        ?Collection $masterLayanan = null
    ): ?array
    {
        $masterLayanan ??= MasterLayanan::query()
            ->active()
            ->orderBy('urutan_laporan')
            ->orderBy('nama_layanan')
            ->get();

        $normalizedKdPoli = $this->normalizeLayananKey($simrsKdPoli);

        if ($normalizedKdPoli === '') {
            return null;
        }

        $matched = $masterLayanan->first(
            fn (MasterLayanan $layanan) => $this->normalizeLayananKey($layanan->simrs_kd_poli) === $normalizedKdPoli
        );

        if (! $matched) {
            return null;
        }

        return [
            'kode_layanan' => $matched->kode_layanan,
            'nama_layanan' => $matched->nama_layanan,
        ];
    }

    private function resolveMappedLayananFromStoredValue(
        ?string $storedValue,
        ?Collection $masterLayanan = null
    ): ?array
    {
        $masterLayanan ??= MasterLayanan::query()
            ->active()
            ->orderBy('urutan_laporan')
            ->orderBy('nama_layanan')
            ->get();

        $normalizedValue = $this->normalizeLayananKey($storedValue);

        if ($normalizedValue === '') {
            return null;
        }

        $matched = $masterLayanan->first(function (MasterLayanan $layanan) use ($normalizedValue) {
            return in_array($normalizedValue, $this->layananLookupKeys($layanan), true);
        });

        if (! $matched) {
            return null;
        }

        return [
            'kode_layanan' => $matched->kode_layanan,
            'nama_layanan' => $matched->nama_layanan,
        ];
    }

    private function layananLookupKeys(MasterLayanan $layanan): array
    {
        return collect([
            $layanan->kode_layanan,
            $layanan->nama_layanan,
            $layanan->simrs_kd_poli,
            $layanan->simrs_nm_poli,
        ])
            ->merge($this->layananAliases($layanan->nama_layanan))
            ->map(fn (?string $value) => $this->normalizeLayananKey($value))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function layananAliases(?string $layananName): array
    {
        $normalized = $this->normalizeLayananKey($layananName);

        return match ($normalized) {
            'klinik umum' => ['klinik umum', 'poliklinik umum', 'umum'],
            'partus' => ['partus', 'kia'],
            'curetage' => ['curetage', 'curetase', 'curettage', 'curretage'],
            default => [$normalized],
        };
    }

    private function normalizeLayananKey(?string $layananName): string
    {
        $value = strtolower(trim((string) $layananName));
        $value = str_replace(['/', '-', '_'], ' ', $value);
        $value = preg_replace('/\s+/', ' ', $value) ?: '';

        return $value;
    }

    private function periodLabel(Carbon $selectedMonth): string
    {
        $lastDay = $selectedMonth->copy()->endOfMonth()->day;

        return '1-' . $lastDay . ' ' . $selectedMonth->locale('id')->translatedFormat('F Y');
    }

    private function normalizeSelectedDate(?string $selectedDate): string
    {
        if (blank($selectedDate)) {
            return now()->toDateString();
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $selectedDate)->toDateString();
        } catch (\Throwable) {
            return now()->toDateString();
        }
    }

    private function normalizeSelectedMonth(?string $selectedMonth): Carbon
    {
        if (blank($selectedMonth)) {
            return now()->startOfMonth();
        }

        try {
            if (preg_match('/^\d{4}-\d{2}$/', $selectedMonth) === 1) {
                return Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth();
            }

            return Carbon::parse($selectedMonth)->startOfMonth();
        } catch (\Throwable) {
            return now()->startOfMonth();
        }
    }

    private function normalizeSelectedYear(?string $selectedYear): int
    {
        $year = (int) $selectedYear;

        if ($year < 2000 || $year > 2100) {
            return now()->year;
        }

        return $year;
    }

    private function resolveLoggedInAdminName(?User $user = null): string
    {
        $user ??= auth()->user();

        if (! $user) {
            return 'Sistem';
        }

        return trim((string) ($user->pegawaiProfile?->jabatan
            ? $user->name . ' · ' . $user->pegawaiProfile->jabatan
            : $user->name));
    }

    private function dashboardGreeting(
        ?User $user = null,
        ?ClinicProfile $clinicProfile = null,
        bool $viewingAllClinics = false
    ): array
    {
        $user ??= auth()->user();
        $clinicProfile ??= $user?->clinicProfile;
        $clinicName = $viewingAllClinics
            ? 'seluruh klinik yang terhubung'
            : ($clinicProfile?->nama_pendek ?: $clinicProfile?->nama_klinik ?: 'Klinik');
        $hour = now()->hour;
        $period = match (true) {
            $hour < 11 => 'Selamat pagi',
            $hour < 15 => 'Selamat siang',
            $hour < 18 => 'Selamat sore',
            default => 'Selamat malam',
        };

        $name = trim((string) ($user?->name ?: 'Tim Klinik'));
        $role = $user?->pegawaiProfile?->jabatan ?: ($user?->role ? ucfirst((string) $user->role) : 'Administrator');

        return [
            'title' => $period . ', ' . $name . '. Semangat untuk hari ini.',
            'body' => 'Semoga kabarnya baik, fokus kerja tetap terjaga, dan seluruh aktivitas di ' . $clinicName . ' hari ini berjalan rapi, tenang, dan lancar.',
            'role' => $role,
        ];
    }
}
