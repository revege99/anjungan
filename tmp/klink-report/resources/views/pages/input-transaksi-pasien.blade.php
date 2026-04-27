@extends('layouts.app')

@section('title', 'Transaksi Pasien | Klink Report')

@section('content')
<style>
    .transaksi-shell {
        display: grid;
        gap: 18px;
    }

    .transaksi-shell > * {
        min-width: 0;
    }

    .hero-card,
    .filter-card,
    .table-card,
    .message-card {
        min-width: 0;
        max-width: 100%;
        border: 1px solid rgba(148, 163, 184, 0.18);
        border-radius: 28px;
        background: rgba(255, 255, 255, 0.9);
        box-shadow: 0 20px 50px rgba(15, 23, 42, 0.08);
        backdrop-filter: blur(16px);
    }

    .hero-card {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        align-items: end;
        gap: 14px 16px;
        padding: 18px 20px;
    }

    .page-eyebrow {
        margin: 0;
        color: #2563eb;
        font-size: 0.78rem;
        font-weight: 800;
        letter-spacing: 0.2em;
        text-transform: uppercase;
    }

    .hero-copy {
        min-width: 0;
    }

    .hero-copy h1 {
        margin: 6px 0 0;
        color: #10233d;
        font-size: 1.45rem;
        line-height: 1.1;
    }

    .hero-stats {
        display: grid;
        min-width: 420px;
        gap: 8px;
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    .hero-stat {
        border-radius: 18px;
        padding: 10px 12px;
        background: linear-gradient(180deg, #f8fbff, #eef4ff);
        border: 1px solid rgba(59, 130, 246, 0.12);
    }

    .hero-stat span {
        display: block;
        color: #64748b;
        font-size: 0.61rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .hero-stat strong {
        display: block;
        margin-top: 5px;
        color: #1e293b;
        font-size: 0.95rem;
        font-weight: 700;
        line-height: 1;
    }

    .hero-stats.is-master {
        min-width: 420px;
        grid-template-columns: minmax(280px, 1fr);
    }

    .hero-filter-form {
        display: grid;
        gap: 10px;
    }

    .hero-filter-form .field-wrap {
        min-width: 0;
        flex: 1 1 auto;
    }

    .hero-action-form {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .hero-action-form .btn {
        width: 100%;
        min-height: 42px;
        border-radius: 14px;
        padding-inline: 14px;
        font-size: 0.76rem;
    }

    .sync-status-note {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        max-width: 100%;
        padding: 10px 14px;
        border-radius: 16px;
        background: linear-gradient(180deg, rgba(239, 246, 255, 0.96), rgba(224, 242, 254, 0.92));
        border: 1px solid rgba(56, 189, 248, 0.16);
        color: #1e3a5f;
        font-size: 0.76rem;
        line-height: 1.6;
    }

    .filter-card,
    .table-card,
    .message-card {
        padding: 24px;
    }

    .message-card.success {
        border-color: rgba(34, 197, 94, 0.2);
        background: linear-gradient(180deg, rgba(240, 253, 244, 0.96), rgba(236, 253, 245, 0.92));
        color: #166534;
    }

    .message-card.error {
        border-color: rgba(239, 68, 68, 0.18);
        background: linear-gradient(180deg, rgba(254, 242, 242, 0.96), rgba(254, 226, 226, 0.92));
        color: #991b1b;
    }

    .message-card p {
        margin: 0;
        font-weight: 700;
    }

    .message-card ul {
        margin: 12px 0 0;
        padding-left: 18px;
    }

    .field-wrap {
        display: flex;
        min-width: 220px;
        flex: 1 1 220px;
        flex-direction: column;
        gap: 8px;
    }

    .field-wrap label,
    .modal-group label {
        color: #334155;
        font-size: 0.84rem;
        font-weight: 700;
    }

    .field-wrap input,
    .field-wrap select,
    .modal-group input,
    .modal-group select,
    .modal-group textarea {
        width: 100%;
        border: 1px solid #d7e1ef;
        border-radius: 16px;
        padding: 12px 14px;
        background: #f8fafc;
        color: #10233d;
        font-size: 0.95rem;
        transition: border-color 160ms ease, box-shadow 160ms ease, background 160ms ease;
    }

    .field-wrap input:focus,
    .field-wrap select:focus,
    .modal-group input:focus,
    .modal-group select:focus,
    .modal-group textarea:focus {
        outline: none;
        border-color: #60a5fa;
        background: white;
        box-shadow: 0 0 0 4px rgba(96, 165, 250, 0.16);
    }

    .modal-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        border: none;
        border-radius: 16px;
        padding: 12px 18px;
        font-size: 0.92rem;
        font-weight: 700;
        cursor: pointer;
        text-decoration: none;
        transition: transform 160ms ease, box-shadow 160ms ease, background 160ms ease;
    }

    .btn:hover {
        transform: translateY(-1px);
    }

    .btn-primary {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: white;
        box-shadow: 0 14px 30px rgba(37, 99, 235, 0.2);
    }

    .btn-secondary {
        background: #e2e8f0;
        color: #10233d;
    }

    .btn-muted {
        background: #f1f5f9;
        color: #334155;
    }

    .btn-danger {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: white;
        box-shadow: 0 14px 30px rgba(239, 68, 68, 0.18);
    }

    .table-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 18px;
    }

    .table-head h2 {
        margin: 0;
        color: #1f2937;
        font-size: 1rem;
        font-weight: 700;
        letter-spacing: 0.03em;
    }

    .penjamin-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border-radius: 999px;
        padding: 6px 10px;
        background: rgba(226, 232, 240, 0.8);
        color: #475569;
        font-size: 0.72rem;
        font-weight: 700;
        line-height: 1;
    }

    .penjamin-badge.is-bpjs {
        background: linear-gradient(135deg, #d1fae5, #bbf7d0);
        color: #166534;
        box-shadow: inset 0 0 0 1px rgba(22, 163, 74, 0.12);
    }

    .tabs-card {
        padding-top: 18px;
        overflow: hidden;
    }

    .tabs-toolbar {
        display: flex;
        flex-wrap: wrap;
        min-width: 0;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        margin-bottom: 18px;
    }

    .tabs-nav {
        display: inline-flex;
        flex-wrap: wrap;
        min-width: 0;
        max-width: 100%;
        align-items: center;
        gap: 8px;
        padding: 6px;
        border: 1px solid rgba(148, 163, 184, 0.18);
        border-radius: 18px;
        background: linear-gradient(180deg, rgba(248, 250, 252, 0.96), rgba(241, 245, 249, 0.96));
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.6);
    }

    .tab-button {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        border: none;
        border-radius: 14px;
        padding: 11px 16px;
        background: transparent;
        color: #64748b;
        font-size: 0.8rem;
        font-weight: 700;
        letter-spacing: 0.02em;
        cursor: pointer;
        transition: background 160ms ease, color 160ms ease, box-shadow 160ms ease, transform 160ms ease;
    }

    .tab-button:hover {
        color: #1e293b;
        transform: translateY(-1px);
    }

    .tab-button:focus-visible {
        outline: none;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.18);
    }

    .tab-button.is-active {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: white;
        box-shadow: 0 12px 24px rgba(37, 99, 235, 0.18);
    }

    .tab-button-count {
        display: inline-flex;
        min-width: 24px;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        padding: 4px 8px;
        background: rgba(148, 163, 184, 0.16);
        color: inherit;
        font-size: 0.72rem;
        font-weight: 700;
        line-height: 1;
    }

    .tab-button.is-active .tab-button-count {
        background: rgba(255, 255, 255, 0.16);
        color: white;
    }

    .tabs-toolbar-forms {
        display: flex;
        min-width: 0;
        flex: 1 1 440px;
        justify-content: flex-end;
        max-width: 100%;
        padding-right: 4px;
    }

    .tab-filter-form[hidden] {
        display: none;
    }

    .tab-filter-form {
        display: flex;
        width: min(100%, 520px);
        min-width: 0;
        max-width: 100%;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 8px;
    }

    .tab-filter-field {
        display: flex;
        min-width: 140px;
        flex: 0 0 150px;
        flex-direction: column;
        gap: 5px;
    }

    .tab-filter-field.is-wide {
        flex-basis: 180px;
    }

    .tab-filter-field.is-date {
        flex-basis: 170px;
    }

    .tab-filter-field.is-status {
        flex-basis: 170px;
    }

    .tab-filter-field label {
        color: #64748b;
        font-size: 0.63rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .tab-filter-field input,
    .tab-filter-field select {
        height: 40px;
        border: 1px solid #d7e1ef;
        border-radius: 14px;
        padding: 9px 12px;
        background: #f8fafc;
        color: #10233d;
        font-size: 0.8rem;
    }

    .tab-filter-field input:focus,
    .tab-filter-field select:focus {
        outline: none;
        border-color: #60a5fa;
        background: white;
        box-shadow: 0 0 0 4px rgba(96, 165, 250, 0.16);
    }

    .tab-filter-actions {
        display: flex;
        align-items: flex-end;
        justify-content: flex-end;
        gap: 8px;
    }

    .tab-filter-button {
        min-height: 40px;
        border-radius: 14px;
        padding: 10px 13px;
        font-size: 0.74rem;
        font-weight: 700;
        letter-spacing: 0.03em;
    }

    .tab-panel[hidden] {
        display: none;
    }

    .table-wrap {
        width: 100%;
        max-width: 100%;
        overflow-x: auto;
    }

    .data-table {
        width: 100%;
        min-width: 980px;
        border-collapse: collapse;
    }

    .data-table th {
        padding: 12px 14px;
        border-bottom: 1px solid #dbe3ef;
        text-align: left;
        color: #64748b;
        font-size: 0.7rem;
        font-weight: 800;
        letter-spacing: 0.14em;
        text-transform: uppercase;
    }

    .data-table td {
        padding: 14px;
        border-bottom: 1px solid #edf2f7;
        color: #334155;
        font-size: 0.86rem;
        vertical-align: top;
    }

    .row-title {
        font-size: 0.88rem;
        font-weight: 700;
        color: #172033;
    }

    .row-subtitle {
        margin-top: 4px;
        color: #64748b;
        font-size: 0.77rem;
        line-height: 1.55;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border-radius: 999px;
        padding: 7px 11px;
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .status-badge.ready {
        background: rgba(34, 197, 94, 0.14);
        color: #166534;
    }

    .status-badge.pending {
        background: rgba(148, 163, 184, 0.16);
        color: #475569;
    }

    .action-stack {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .action-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        border: none;
        border-radius: 14px;
        padding: 0;
        cursor: pointer;
        box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.12);
        transition: transform 160ms ease, box-shadow 160ms ease, background 160ms ease, color 160ms ease;
    }

    .action-btn svg {
        width: 18px;
        height: 18px;
        fill: none;
        stroke: currentColor;
        stroke-linecap: round;
        stroke-linejoin: round;
        stroke-width: 1.9;
    }

    .action-btn:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow:
            inset 0 0 0 1px rgba(148, 163, 184, 0.16),
            0 12px 20px rgba(15, 23, 42, 0.08);
    }

    .action-btn:disabled {
        cursor: not-allowed;
        opacity: 0.45;
        box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.1);
    }

    .action-btn.input {
        background: linear-gradient(180deg, #eaf3ff, #dbeafe);
        color: #1d4ed8;
    }

    .action-btn.edit {
        background: linear-gradient(180deg, #ecfeff, #cffafe);
        color: #0f766e;
    }

    .action-btn.delete {
        background: linear-gradient(180deg, #fff1f2, #ffe4e6);
        color: #be123c;
    }

    .empty-state {
        padding: 34px 18px;
        border: 1px dashed #cbd5e1;
        border-radius: 22px;
        text-align: center;
        color: #64748b;
        line-height: 1.8;
    }

    .inline-form {
        margin: 0;
    }

    .transaksi-modal[hidden] {
        display: none;
    }

    .transaksi-modal {
        position: fixed;
        inset: 0;
        z-index: 80;
    }

    .modal-overlay {
        position: absolute;
        inset: 0;
        background: rgba(15, 23, 42, 0.56);
        backdrop-filter: blur(4px);
    }

    .modal-dialog {
        position: relative;
        z-index: 1;
        width: min(1240px, calc(100vw - 32px));
        max-height: calc(100vh - 32px);
        margin: 16px auto;
        overflow: hidden;
        border-radius: 28px;
        border: 1px solid rgba(148, 163, 184, 0.18);
        background: white;
        box-shadow: 0 40px 90px rgba(15, 23, 42, 0.26);
    }

    .modal-header,
    .modal-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 20px 24px;
        border-bottom: 1px solid #e5edf6;
    }

    .modal-footer {
        justify-content: flex-end;
        border-top: 1px solid #e5edf6;
        border-bottom: none;
    }

    .modal-header h3 {
        margin: 0;
        color: #0f172a;
        font-size: 1.05rem;
        font-weight: 700;
    }

    .modal-header p {
        margin: 6px 0 0;
        color: #64748b;
        font-size: 0.78rem;
    }

    .modal-title-row {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 10px;
    }

    .modal-penjamin {
        padding: 7px 12px;
        font-size: 0.72rem;
    }

    .modal-close {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 42px;
        height: 42px;
        border: none;
        border-radius: 14px;
        background: #f1f5f9;
        color: #0f172a;
        font-size: 1.3rem;
        cursor: pointer;
    }

    .modal-body {
        max-height: calc(100vh - 210px);
        overflow: auto;
        padding: 22px 24px 28px;
        background: linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
    }

    .modal-summary {
        display: grid;
        gap: 14px;
        margin-bottom: 18px;
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    .summary-chip {
        border: 1px solid rgba(37, 99, 235, 0.12);
        border-radius: 20px;
        padding: 12px 14px;
        background: white;
    }

    .summary-chip span {
        display: block;
        color: #64748b;
        font-size: 0.68rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .summary-chip strong {
        display: block;
        margin-top: 8px;
        color: #10233d;
        font-size: 0.94rem;
    }

    .modal-section {
        margin-top: 18px;
        padding: 18px;
        border: 1px solid #e2e8f0;
        border-radius: 24px;
        background: rgba(255, 255, 255, 0.88);
    }

    .modal-section h4 {
        margin: 0 0 16px;
        color: #0f172a;
        font-size: 0.9rem;
        font-weight: 700;
    }

    .modal-grid {
        display: grid;
        gap: 16px;
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    .modal-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .modal-group label {
        font-size: 0.76rem;
        font-weight: 700;
    }

    .modal-group input,
    .modal-group select,
    .modal-group textarea {
        border-radius: 14px;
        padding: 10px 12px;
        font-size: 0.84rem;
    }

    .modal-group.span-2 {
        grid-column: span 2;
    }

    .modal-group.span-4 {
        grid-column: span 4;
    }

    .readonly-display {
        background: #e2e8f0 !important;
        font-weight: 800;
        color: #0f172a !important;
    }

    @media (max-width: 1200px) {
        .hero-card {
            grid-template-columns: 1fr;
            align-items: start;
        }

        .hero-stats {
            min-width: 0;
        }

        .modal-summary,
        .modal-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .modal-group.span-4 {
            grid-column: span 2;
        }
    }

    @media (max-width: 768px) {
        .hero-card,
        .filter-card,
        .table-card,
        .message-card {
            border-radius: 22px;
            padding: 18px;
        }

        .hero-card {
            grid-template-columns: 1fr;
            align-items: stretch;
        }

        .tabs-nav {
            display: flex;
            width: 100%;
        }

        .tabs-toolbar-forms {
            width: 100%;
            flex-basis: 100%;
            justify-content: stretch;
        }

        .tab-filter-form {
            width: 100%;
            min-width: 0;
            justify-content: flex-start;
        }

        .tab-filter-field,
        .tab-filter-field.is-wide,
        .tab-filter-field.is-date {
            min-width: 0;
            flex: 1 1 180px;
        }

        .tab-button {
            justify-content: center;
            flex: 1 1 100%;
        }

        .hero-stats,
        .modal-summary,
        .modal-grid {
            grid-template-columns: 1fr;
        }

        .modal-group.span-2,
        .modal-group.span-4 {
            grid-column: span 1;
        }

        .modal-dialog {
            width: min(100vw - 20px, 1240px);
            margin: 10px auto;
            border-radius: 22px;
        }

        .modal-header,
        .modal-footer,
        .modal-body {
            padding-left: 16px;
            padding-right: 16px;
        }

        .table-head {
            flex-direction: column;
            align-items: flex-start;
        }
    }
</style>

@php
    $formattedSelectedDate = \Carbon\Carbon::parse($selectedDate)->format('d/m/Y');
    $formattedDataMonth = \Carbon\Carbon::createFromFormat('Y-m', $selectedDataMonth)->locale('id')->translatedFormat('F Y');
    $canEditOperationalData = auth()->user()?->canEditOperationalData();
    $isMasterTransaksiView = auth()->user()?->isMaster();
    $lastRekapUpdateLabel = $lastRekapUpdate?->synced_at?->locale('id')->translatedFormat('d M Y H:i');
@endphp

<div class="transaksi-shell">
    <section class="hero-card">
        <div class="hero-copy">
            <p class="page-eyebrow">Data Transaksi</p>
            <h1>Transaksi Pasien</h1>
        </div>

        @if ($isMasterTransaksiView)
            <div class="hero-stats is-master">
                <article class="hero-stat">
                    <form method="GET" action="{{ route('transaksi-pasien') }}" class="hero-filter-form">
                        <input type="hidden" name="tanggal" value="{{ $selectedDate }}">
                        <input type="hidden" name="data_bulan" value="{{ $selectedDataMonth }}">
                        <input type="hidden" name="data_penjamin" value="{{ $selectedPenjamin }}">
                        <input type="hidden" name="active_tab" value="{{ $preferredTab ?: 'panel-data-transaksi' }}">
                        <div class="field-wrap">
                            <label for="hero-clinic-id">Klinik Aktif</label>
                            <select id="hero-clinic-id" name="clinic_id">
                                <option value="all" @selected($viewingAllClinics)>Semua Klinik</option>
                                @foreach ($clinicOptions as $clinicOption)
                                    <option value="{{ $clinicOption->id }}" @selected((int) $selectedClinicId === (int) $clinicOption->id)>
                                        {{ $clinicOption->kode_klinik }} · {{ $clinicOption->nama_klinik }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary filter-submit">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M4 6h16"></path>
                                <path d="M7 12h10"></path>
                                <path d="M10 18h4"></path>
                            </svg>
                            Filter Data
                        </button>
                    </form>
                </article>
            </div>
        @else
            <div class="hero-stats">
                <article class="hero-stat">
                    <span>Tanggal Aktif</span>
                    <strong>{{ $formattedSelectedDate }}</strong>
                </article>
                <article class="hero-stat">
                    <span>Data SIK</span>
                    <strong>{{ $visitRows->count() }}</strong>
                </article>
                <article class="hero-stat">
                    <span>Data Lokal</span>
                    <strong>{{ $savedTransactions->count() }}</strong>
                </article>
                @if ($showUpdateRekapButton)
                    <article class="hero-stat">
                        <span>Update Rekap</span>
                        <form method="POST" action="{{ route('transaksi-pasien.sync-rekap') }}" class="hero-action-form">
                            @csrf
                            <input type="hidden" name="tanggal" value="{{ $selectedDate }}">
                            <input type="hidden" name="data_bulan" value="{{ $selectedDataMonth }}">
                            <input type="hidden" name="data_penjamin" value="{{ $selectedPenjamin }}">
                            <input type="hidden" name="local_status" value="{{ $selectedLocalStatus }}">
                            <button type="submit" class="btn btn-primary">Update</button>
                        </form>
                    </article>
                @endif
            </div>
        @endif
    </section>

    @if (session('success'))
        <section class="message-card success">
            <p>{{ session('success') }}</p>
        </section>
    @endif

    @if ($errors->any())
        <section class="message-card error">
            <p>Masih ada data yang perlu diperbaiki sebelum disimpan.</p>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </section>
    @endif

    <section class="table-card tabs-card">
        <div class="tabs-toolbar">
            <div class="tabs-nav" role="tablist" aria-label="Tab data transaksi pasien">
                @if ($showTransaksiTab)
                    <button
                        type="button"
                        class="tab-button is-active"
                        role="tab"
                        id="tab-transaksi-pasien"
                        aria-selected="true"
                        aria-controls="panel-transaksi-pasien"
                        data-tab-target="panel-transaksi-pasien"
                    >
                        Transaksi Pasien
                        <span class="tab-button-count">{{ $visitRows->count() }}</span>
                    </button>
                @endif

                <button
                    type="button"
                    class="tab-button {{ $showTransaksiTab ? '' : 'is-active' }}"
                    role="tab"
                    id="tab-data-transaksi"
                    aria-selected="{{ $showTransaksiTab ? 'false' : 'true' }}"
                    aria-controls="panel-data-transaksi"
                    data-tab-target="panel-data-transaksi"
                >
                    Data Transaksi
                    <span class="tab-button-count">{{ $savedTransactionList->count() }}</span>
                </button>

                @if ($showRekapPasienTab)
                    <button
                        type="button"
                        class="tab-button"
                        role="tab"
                        id="tab-rekap-pasien"
                        aria-selected="false"
                        aria-controls="panel-rekap-pasien"
                        data-tab-target="panel-rekap-pasien"
                    >
                        Rekap Pasien
                        <span class="tab-button-count">{{ $rekapPasienList->count() }}</span>
                    </button>
                @endif
            </div>

            <div class="tabs-toolbar-forms">
                @if ($showTransaksiTab)
                <form
                    method="GET"
                    action="{{ route('transaksi-pasien') }}"
                    class="tab-filter-form"
                    data-tab-filter="panel-transaksi-pasien"
                    @if ($preferredTab === 'panel-data-transaksi') hidden @endif
                >
                    <input type="hidden" name="data_bulan" value="{{ $selectedDataMonth }}">
                    <input type="hidden" name="data_penjamin" value="{{ $selectedPenjamin }}">
                    @unless ($showClinicFilter)
                        <input type="hidden" name="clinic_id" value="{{ $selectedClinicId }}">
                    @endunless
                    <input type="hidden" name="active_tab" value="panel-transaksi-pasien">

                    <div class="tab-filter-field is-date">
                        <label for="tab_tanggal">Tanggal Registrasi</label>
                        <input id="tab_tanggal" type="date" name="tanggal" value="{{ $selectedDate }}">
                    </div>

                    <div class="tab-filter-field is-status">
                        <label for="local_status">Status Lokal</label>
                        <select id="local_status" name="local_status">
                            <option value="" @selected($selectedLocalStatus === '')>Semua Status</option>
                            <option value="saved" @selected($selectedLocalStatus === 'saved')>Sudah Tersimpan</option>
                            <option value="unsaved" @selected($selectedLocalStatus === 'unsaved')>Belum Tersimpan</option>
                        </select>
                    </div>

                    @if ($showClinicFilter)
                        <div class="tab-filter-field is-wide">
                            <label for="tab_clinic_id">Klinik</label>
                            <select id="tab_clinic_id" name="clinic_id">
                                @foreach ($clinicOptions as $clinicOption)
                                    <option value="{{ $clinicOption->id }}" @selected((int) $selectedClinicId === (int) $clinicOption->id)>
                                        {{ $clinicOption->kode_klinik }} · {{ $clinicOption->nama_klinik }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="tab-filter-actions">
                        <button type="submit" class="btn btn-primary tab-filter-button filter-submit">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M4 6h16"></path>
                                <path d="M7 12h10"></path>
                                <path d="M10 18h4"></path>
                            </svg>
                            Filter Data
                        </button>
                    </div>
                </form>
                @endif

                <form
                    method="GET"
                    action="{{ route('transaksi-pasien') }}"
                    class="tab-filter-form"
                    data-tab-filter="panel-data-transaksi"
                    @if ($preferredTab !== 'panel-data-transaksi') hidden @endif
                >
                    <input type="hidden" name="tanggal" value="{{ $selectedDate }}">
                    <input type="hidden" name="local_status" value="{{ $selectedLocalStatus }}">
                    <input type="hidden" name="clinic_id" value="{{ $viewingAllClinics ? 'all' : $selectedClinicId }}">
                    <input type="hidden" name="active_tab" value="panel-data-transaksi">

                    @if ($isMasterTransaksiView && ! $viewingAllClinics && $lastRekapUpdate)
                        <div class="sync-status-note">
                            Terakhir update rekap: {{ $lastRekapUpdateLabel }} · data tanggal {{ optional($lastRekapUpdate->tanggal_data)->format('d/m/Y') }} · {{ number_format((int) $lastRekapUpdate->total_data, 0, ',', '.') }} baris
                        </div>
                    @endif

                    <div class="tab-filter-field">
                        <label for="data_bulan">Bulan</label>
                        <input id="data_bulan" type="month" name="data_bulan" value="{{ $selectedDataMonth }}">
                    </div>

                    <div class="tab-filter-field is-wide">
                        <label for="data_penjamin">Jenis Penjamin</label>
                        <select id="data_penjamin" name="data_penjamin">
                            <option value="">Semua Penjamin</option>
                            @foreach ($penjaminOptions as $penjaminOption)
                                <option value="{{ $penjaminOption }}" @selected($selectedPenjamin === $penjaminOption)>
                                    {{ $penjaminOption }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    @if ($showClinicFilter && ! $isMasterTransaksiView)
                        <div class="tab-filter-field is-wide">
                            <label for="data_clinic_id">Klinik</label>
                            <select id="data_clinic_id" name="clinic_id">
                                @foreach ($clinicOptions as $clinicOption)
                                    <option value="{{ $clinicOption->id }}" @selected((int) $selectedClinicId === (int) $clinicOption->id)>
                                        {{ $clinicOption->kode_klinik }} · {{ $clinicOption->nama_klinik }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="tab-filter-actions">
                        <button type="submit" class="btn btn-primary tab-filter-button filter-submit">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M4 6h16"></path>
                                <path d="M7 12h10"></path>
                                <path d="M10 18h4"></path>
                            </svg>
                            Filter Data
                        </button>
                    </div>
                </form>

                @if ($showRekapPasienTab)
                    <form
                        method="GET"
                        action="{{ route('transaksi-pasien') }}"
                        class="tab-filter-form"
                        data-tab-filter="panel-rekap-pasien"
                        @if ($preferredTab !== 'panel-rekap-pasien') hidden @endif
                    >
                        <input type="hidden" name="tanggal" value="{{ $selectedDate }}">
                        <input type="hidden" name="data_penjamin" value="{{ $selectedPenjamin }}">
                        <input type="hidden" name="local_status" value="{{ $selectedLocalStatus }}">
                        <input type="hidden" name="clinic_id" value="{{ $viewingAllClinics ? 'all' : $selectedClinicId }}">
                        <input type="hidden" name="active_tab" value="panel-rekap-pasien">

                        @if (! $viewingAllClinics && $lastRekapUpdate)
                            <div class="sync-status-note">
                                Terakhir update rekap: {{ $lastRekapUpdateLabel }} · data tanggal {{ optional($lastRekapUpdate->tanggal_data)->format('d/m/Y') }} · {{ number_format((int) $lastRekapUpdate->total_data, 0, ',', '.') }} baris
                            </div>
                        @endif

                        <div class="tab-filter-field">
                            <label for="rekap_bulan">Bulan</label>
                            <input id="rekap_bulan" type="month" name="data_bulan" value="{{ $selectedDataMonth }}">
                        </div>

                        <div class="tab-filter-actions">
                            <button type="submit" class="btn btn-primary tab-filter-button filter-submit">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M4 6h16"></path>
                                    <path d="M7 12h10"></path>
                                    <path d="M10 18h4"></path>
                                </svg>
                                Filter Data
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </div>

        @if ($showTransaksiTab)
        <div class="tab-panel" id="panel-transaksi-pasien" role="tabpanel" aria-labelledby="tab-transaksi-pasien">
            <div class="table-head">
                <h2>Data Baris Tanggal {{ $formattedSelectedDate }}</h2>
            </div>

            @if ($visitRows->isEmpty())
                <div class="empty-state">
                    Tidak ada data untuk tanggal {{ $formattedSelectedDate }}.
                </div>
            @else
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Pasien</th>
                                <th>Kunjungan</th>
                                <th>Layanan</th>
                                <th>Dokter</th>
                                <th>Status Lokal</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($visitRows as $row)
                                @php
                                    $localTransaction = $savedTransactions->get($row['simrs_no_rawat']);
                                @endphp
                                <tr>
                                    <td>
                                        <div class="row-title">{{ $row['nama_pasien'] ?: 'Tanpa Nama' }}</div>
                                        <div class="row-subtitle">
                                            NO RM: {{ $row['no_rm'] ?: '-' }}
                                            <br>
                                            JK: {{ $row['jk'] ?: '-' }} | Hari: {{ $row['harian'] ?: '-' }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="row-title">{{ $row['simrs_no_rawat'] }}</div>
                                        <div class="row-subtitle">
                                            No Reg: {{ $row['simrs_no_reg'] ?: '-' }}
                                            <br>
                                            Jam: {{ data_get($row, 'meta.jam_reg', '-') }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="row-title">{{ $row['layanan_label'] ?: '-' }}</div>
                                        <div class="row-subtitle">
                                            Kode: {{ $row['layanan_medis'] ?: '-' }}
                                            <br>
                                            SIMRS: {{ data_get($row, 'meta.simrs_kd_poli', '-') }}{{ data_get($row, 'meta.simrs_nama_poli') ? ' · ' . data_get($row, 'meta.simrs_nama_poli') : '' }}
                                            <br>
                                            Status Pasien: {{ $row['status_pasien'] ?: '-' }}
                                        </div>
                                    </td>
                                    <td>
                                        @php
                                            $penjamin = data_get($row, 'meta.penjamin', '-');
                                            $isBpjs = str_contains(strtolower($penjamin), 'bpjs');
                                        @endphp
                                        <div class="row-title">{{ $row['dokter'] ?: '-' }}</div>
                                        <div class="row-subtitle">
                                            <span class="penjamin-badge {{ $isBpjs ? 'is-bpjs' : '' }}">
                                                {{ $penjamin }}
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        @if ($localTransaction)
                                            <span class="status-badge ready">Tersimpan</span>
                                            <div class="row-subtitle">
                                                Total: Rp {{ number_format((float) $localTransaction->jumlah_rp, 0, ',', '.') }}
                                                <br>
                                                Admin: {{ $localTransaction->petugas_admin ?: '-' }}
                                            </div>
                                        @else
                                            <span class="status-badge pending">Belum</span>
                                            <div class="row-subtitle">
                                                Belum diisi.
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="action-stack">
                                            <button
                                                type="button"
                                                class="action-btn input js-open-modal"
                                                data-mode="create"
                                                data-map-key="{{ $row['map_key'] }}"
                                                title="Input transaksi"
                                                aria-label="Input transaksi"
                                                @if ($localTransaction) disabled @endif
                                            >
                                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                                    <rect x="3.5" y="3.5" width="17" height="17" rx="4"></rect>
                                                    <path d="M12 8v8"></path>
                                                    <path d="M8 12h8"></path>
                                                </svg>
                                            </button>

                                            @if ($canEditOperationalData)
                                                <button
                                                    type="button"
                                                    class="action-btn edit js-open-modal"
                                                    data-mode="edit"
                                                    data-map-key="{{ $row['map_key'] }}"
                                                    data-transaction-id="{{ $localTransaction?->id }}"
                                                    title="Edit transaksi"
                                                    aria-label="Edit transaksi"
                                                    @if (! $localTransaction) disabled @endif
                                                >
                                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                                        <path d="M4 20h4l10-10-4-4L4 16v4Z"></path>
                                                        <path d="M12.5 7.5l4 4"></path>
                                                    </svg>
                                                </button>
                                            @endif

                                            <form
                                                method="POST"
                                                action="{{ $localTransaction ? route('transaksi-pasien.destroy', $localTransaction) : '#' }}"
                                                class="inline-form"
                                                data-confirm-delete
                                                data-confirm-title="Hapus transaksi pasien?"
                                                data-confirm-message="Data transaksi pasien lokal ini akan dihapus permanen dari aplikasi."
                                                data-confirm-button="Ya, hapus transaksi"
                                            >
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="context_clinic_id" value="{{ $viewingAllClinics ? 'all' : $selectedClinicId }}">
                                                <button
                                                    type="submit"
                                                    class="action-btn delete"
                                                    title="Hapus transaksi"
                                                    aria-label="Hapus transaksi"
                                                    @if (! $localTransaction) disabled @endif
                                                >
                                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                                        <path d="M4 7h16"></path>
                                                        <path d="M9 7V4h6v3"></path>
                                                        <path d="M6 7l1 13h10l1-13"></path>
                                                        <path d="M10 11v5"></path>
                                                        <path d="M14 11v5"></path>
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
        @endif

        <div class="tab-panel" id="panel-data-transaksi" role="tabpanel" aria-labelledby="tab-data-transaksi" hidden>
            <div class="table-head">
                <h2>
                    Data Transaksi {{ $formattedDataMonth }}
                    @if (filled($selectedPenjamin))
                        · {{ $selectedPenjamin }}
                    @endif
                </h2>
            </div>

            @if ($savedTransactionList->isEmpty())
                <div class="empty-state">
                    Belum ada data lokal untuk periode {{ $formattedDataMonth }}.
                </div>
            @else
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Pasien</th>
                                <th>No Rawat</th>
                                <th>Layanan</th>
                                <th>Total</th>
                                <th>Petugas</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($savedTransactionList as $savedTransaction)
                                <tr>
                                    <td>
                                        <div class="row-title">{{ $savedTransaction->nama_pasien ?: 'Tanpa Nama' }}</div>
                                        <div class="row-subtitle">
                                            NO RM: {{ $savedTransaction->no_rm ?: '-' }}
                                            <br>
                                            {{ $savedTransaction->dokter ?: 'Dokter belum diisi' }}
                                            @if ($viewingAllClinics)
                                                <br>
                                                Klinik: {{ $savedTransaction->clinicProfile?->nama_pendek ?: $savedTransaction->clinicProfile?->nama_klinik ?: '-' }}
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div class="row-title">{{ $savedTransaction->simrs_no_rawat }}</div>
                                        <div class="row-subtitle">
                                            {{ optional($savedTransaction->tanggal)->format('d/m/Y') ?: '-' }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="row-title">{{ $savedTransaction->masterLayanan?->nama_layanan ?: $savedTransaction->layanan_medis ?: '-' }}</div>
                                        <div class="row-subtitle">
                                            Kode: {{ $savedTransaction->layanan_medis ?: '-' }}
                                            <br>
                                            <span class="penjamin-badge {{ str_contains(strtolower((string) $savedTransaction->penjamin), 'bpjs') ? 'is-bpjs' : '' }}">
                                                {{ $savedTransaction->penjamin ?: 'Tanpa penjamin' }}
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="row-title">Rp {{ number_format((float) $savedTransaction->jumlah_rp, 0, ',', '.') }}</div>
                                        <div class="row-subtitle">
                                            {{ $savedTransaction->jml_visit }} visit
                                        </div>
                                    </td>
                                    <td>
                                        <div class="row-title">{{ $savedTransaction->petugas_admin ?: '-' }}</div>
                                        <div class="row-subtitle">
                                            {{ $savedTransaction->keterangan ?: '-' }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="action-stack">
                                            @if ($canEditOperationalData)
                                                <button
                                                    type="button"
                                                    class="action-btn edit js-open-modal"
                                                    data-mode="edit"
                                                    data-map-key="{{ $savedTransaction->clinic_profile_id . '::' . $savedTransaction->simrs_no_rawat }}"
                                                    data-transaction-id="{{ $savedTransaction->id }}"
                                                    title="Edit transaksi"
                                                    aria-label="Edit transaksi"
                                                >
                                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                                        <path d="M4 20h4l10-10-4-4L4 16v4Z"></path>
                                                        <path d="M12.5 7.5l4 4"></path>
                                                    </svg>
                                                </button>
                                            @endif

                                            <form
                                                method="POST"
                                                action="{{ route('transaksi-pasien.destroy', $savedTransaction) }}"
                                                class="inline-form"
                                                data-confirm-delete
                                                data-confirm-title="Hapus transaksi pasien?"
                                                data-confirm-message="Data transaksi pasien lokal ini akan dihapus permanen dari aplikasi."
                                                data-confirm-button="Ya, hapus transaksi"
                                            >
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="context_clinic_id" value="{{ $viewingAllClinics ? 'all' : $selectedClinicId }}">
                                                <button
                                                    type="submit"
                                                    class="action-btn delete"
                                                    title="Hapus transaksi"
                                                    aria-label="Hapus transaksi"
                                                >
                                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                                        <path d="M4 7h16"></path>
                                                        <path d="M9 7V4h6v3"></path>
                                                        <path d="M6 7l1 13h10l1-13"></path>
                                                        <path d="M10 11v5"></path>
                                                        <path d="M14 11v5"></path>
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        @if ($showRekapPasienTab)
            <div class="tab-panel" id="panel-rekap-pasien" role="tabpanel" aria-labelledby="tab-rekap-pasien" hidden>
                <div class="table-head">
                    <h2>Rekap Pasien {{ $formattedDataMonth }}</h2>
                </div>

                @if ($rekapPasienList->isEmpty())
                    <div class="empty-state">
                        Belum ada rekap pasien untuk periode {{ $formattedDataMonth }}.
                    </div>
                @else
                    <div class="table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Pasien</th>
                                    <th>No Rawat</th>
                                    <th>Layanan</th>
                                    @if ($viewingAllClinics)
                                        <th>Klinik</th>
                                    @endif
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rekapPasienList as $rekapPasien)
                                    @php
                                        $rekapKey = $rekapPasien->clinic_profile_id . '::' . $rekapPasien->no_rawat;
                                        $statusAda = $existingRekapKeys->has($rekapKey);
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="row-title">{{ optional($rekapPasien->tanggal)->format('d/m/Y') ?: '-' }}</div>
                                            <div class="row-subtitle">
                                                {{ $rekapPasien->synced_at?->locale('id')->translatedFormat('d M Y H:i') ?: 'Belum ada waktu update' }}
                                            </div>
                                        </td>
                                        <td>
                                            <div class="row-title">{{ $rekapPasien->nama_pasien ?: 'Tanpa Nama' }}</div>
                                            <div class="row-subtitle">
                                                NO RM: {{ $rekapPasien->no_rm ?: '-' }}
                                            </div>
                                        </td>
                                        <td>
                                            <div class="row-title">{{ $rekapPasien->no_rawat }}</div>
                                        </td>
                                        <td>
                                            <div class="row-title">{{ $rekapPasien->masterLayanan?->nama_layanan ?: ($rekapPasien->layanan_medis ?: 'Belum Dimapping') }}</div>
                                            <div class="row-subtitle">
                                                Kode: {{ $rekapPasien->layanan_medis ?: '-' }}
                                            </div>
                                        </td>
                                        @if ($viewingAllClinics)
                                            <td>
                                                <div class="row-title">{{ $rekapPasien->clinicProfile?->nama_pendek ?: $rekapPasien->clinicProfile?->nama_klinik ?: '-' }}</div>
                                                <div class="row-subtitle">
                                                    {{ $rekapPasien->clinicProfile?->kode_klinik ?: '-' }}
                                                </div>
                                            </td>
                                        @endif
                                        <td>
                                            <span class="status-badge {{ $statusAda ? 'ready' : 'pending' }}">
                                                {{ $statusAda ? 'Sudah Ada' : 'Tidak Ada' }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @endif
    </section>
</div>

<div class="transaksi-modal" id="transaksiModal" hidden>
    <div class="modal-overlay js-close-modal"></div>

    <div class="modal-dialog">
        <div class="modal-header">
            <div>
                <div class="modal-title-row">
                    <h3 id="modalTitle">Input Transaksi Pasien</h3>
                    <span class="penjamin-badge modal-penjamin" id="summary-penjamin">Penjamin: -</span>
                </div>
                <p id="modalSubtitle">Form transaksi pasien.</p>
            </div>

            <button type="button" class="modal-close js-close-modal" aria-label="Tutup modal">&times;</button>
        </div>

        <form
            id="transaksiForm"
            method="POST"
            action="{{ route('transaksi-pasien.store') }}"
            data-store-url="{{ route('transaksi-pasien.store') }}"
            data-update-url="{{ url('/transaksi-pasien/__ID__') }}"
        >
            @csrf
            <input type="hidden" name="_method" id="field-method" value="POST">
            <input type="hidden" name="_modal_mode" id="field-meta-mode" value="create">
            <input type="hidden" name="_transaction_id" id="field-meta-transaction-id">
            <input type="hidden" name="active_tab_context" id="field-active-tab-context">
            <input type="hidden" name="context_clinic_id" value="{{ $viewingAllClinics ? 'all' : $selectedClinicId }}">
            <input type="hidden" name="simrs_no_rawat" id="field-simrs_no_rawat">
            <input type="hidden" name="simrs_no_reg" id="field-simrs_no_reg">
            <input type="hidden" name="dokter" id="field-dokter">
            <input type="hidden" name="penjamin" id="field-penjamin">
            <input type="hidden" name="clinic_profile_id" id="field-clinic_profile_id" value="{{ $selectedClinicId }}">

            <div class="modal-body">
                <div class="modal-summary">
                    <div class="summary-chip">
                        <span>No Rawat</span>
                        <strong id="summary-no-rawat">-</strong>
                    </div>
                    <div class="summary-chip">
                        <span>Pasien</span>
                        <strong id="summary-pasien">-</strong>
                    </div>
                    <div class="summary-chip">
                        <span>Layanan</span>
                        <strong id="summary-layanan">-</strong>
                    </div>
                    <div class="summary-chip">
                        <span>Jumlah Rp.</span>
                        <strong id="summary-total">Rp 0</strong>
                    </div>
                </div>

                <section class="modal-section">
                    <h4>Informasi Dasar</h4>
                    <div class="modal-grid">
                        <div class="modal-group">
                            <label for="field-layanan_medis">Kode Layanan</label>
                            <input id="field-layanan_medis" name="layanan_medis" type="text" class="readonly-display" readonly>
                        </div>
                        @if ($showClinicFilter)
                            <div class="modal-group">
                                <label>Klinik Tujuan</label>
                                <input type="text" value="{{ $selectedClinicLabel }}" class="readonly-display" readonly>
                            </div>
                        @endif
                        <div class="modal-group">
                            <label for="field-tanggal">Tgl.</label>
                            <input id="field-tanggal" name="tanggal" type="date">
                        </div>
                        <div class="modal-group">
                            <label for="field-bulan">Bulan</label>
                            <input id="field-bulan" name="bulan" type="number" min="1" max="12">
                        </div>
                        <div class="modal-group">
                            <label for="field-harian">Harian</label>
                            <input id="field-harian" name="harian" type="text">
                        </div>

                        <div class="modal-group">
                            <label for="field-no_rm">NO RM</label>
                            <input id="field-no_rm" name="no_rm" type="text">
                        </div>
                        <div class="modal-group span-2">
                            <label for="field-nama_pasien">Nama Pasien</label>
                            <input id="field-nama_pasien" name="nama_pasien" type="text">
                        </div>
                        <div class="modal-group">
                            <label for="field-jk">Lk/Pr</label>
                            <select id="field-jk" name="jk">
                                <option value="">Pilih</option>
                                <option value="L">L</option>
                                <option value="P">P</option>
                            </select>
                        </div>

                        <div class="modal-group">
                            <label for="field-statis">Statis</label>
                            <input id="field-statis" name="statis" type="text">
                        </div>
                        <div class="modal-group">
                            <label for="field-genap">Genap</label>
                            <input id="field-genap" name="genap" type="text">
                        </div>
                        <div class="modal-group span-2">
                            <label for="field-status_pasien">Status Pasien</label>
                            <input id="field-status_pasien" name="status_pasien" type="text">
                        </div>

                        <div class="modal-group span-4">
                            <label for="field-alamat">Alamat</label>
                            <textarea id="field-alamat" name="alamat" rows="2"></textarea>
                        </div>
                    </div>
                </section>

                <section class="modal-section">
                    <h4>Informasi Klinis</h4>
                    <div class="modal-grid">
                        <div class="modal-group span-2">
                            <label for="field-lab">Lab.</label>
                            <textarea id="field-lab" name="lab" rows="2"></textarea>
                        </div>
                        <div class="modal-group">
                            <label for="field-icd">ICD</label>
                            <textarea id="field-icd" name="icd" rows="2"></textarea>
                        </div>
                        <div class="modal-group span-4">
                            <label for="field-diagnosa">Diagnosa</label>
                            <textarea id="field-diagnosa" name="diagnosa" rows="2"></textarea>
                        </div>
                        <div class="modal-group span-4">
                            <label for="field-farmasi">Farmasi</label>
                            <textarea id="field-farmasi" name="farmasi" rows="2"></textarea>
                        </div>
                    </div>
                </section>

                <section class="modal-section">
                    <h4>Komponen Transaksi</h4>
                    <div class="modal-grid">
                        <div class="modal-group">
                            <label for="field-uang_daftar">Uang Daftar</label>
                            <input id="field-uang_daftar" name="uang_daftar" type="number" step="0.01" min="0" class="js-rupiah-field">
                        </div>
                        <div class="modal-group">
                            <label for="field-uang_periksa">Uang Periksa</label>
                            <input id="field-uang_periksa" name="uang_periksa" type="number" step="0.01" min="0" class="js-rupiah-field">
                        </div>
                        <div class="modal-group">
                            <label for="field-uang_obat">Uang Obat</label>
                            <input id="field-uang_obat" name="uang_obat" type="number" step="0.01" min="0" class="js-rupiah-field">
                        </div>
                        <div class="modal-group">
                            <label for="field-uang_bersalin">Uang Bersalin</label>
                            <input id="field-uang_bersalin" name="uang_bersalin" type="number" step="0.01" min="0" class="js-rupiah-field">
                        </div>

                        <div class="modal-group">
                            <label for="field-jasa_dokter">Jasa Dokter</label>
                            <input id="field-jasa_dokter" name="jasa_dokter" type="number" step="0.01" min="0" class="js-rupiah-field">
                        </div>
                        <div class="modal-group">
                            <label for="field-jml_hari">Jml Hari</label>
                            <input id="field-jml_hari" name="jml_hari" type="number" min="0">
                        </div>
                        <div class="modal-group">
                            <label for="field-rawat_inap">Rawat Inap</label>
                            <input id="field-rawat_inap" name="rawat_inap" type="number" step="0.01" min="0" class="js-rupiah-field">
                        </div>
                        <div class="modal-group">
                            <label for="field-jml_visit">Jml. Visit</label>
                            <input id="field-jml_visit" name="jml_visit" type="number" min="0">
                        </div>

                        <div class="modal-group">
                            <label for="field-honor_dr_visit">Honor dr Visit</label>
                            <input id="field-honor_dr_visit" name="honor_dr_visit" type="number" step="0.01" min="0" class="js-rupiah-field">
                        </div>
                        <div class="modal-group">
                            <label for="field-oksigen">Oksigen</label>
                            <input id="field-oksigen" name="oksigen" type="number" step="0.01" min="0" class="js-rupiah-field">
                        </div>
                        <div class="modal-group">
                            <label for="field-perlengk_bayi">Perlengk Bayi</label>
                            <input id="field-perlengk_bayi" name="perlengk_bayi" type="number" step="0.01" min="0" class="js-rupiah-field">
                        </div>
                        <div class="modal-group">
                            <label for="field-jaspel_nakes">Jaspel Nakes</label>
                            <input id="field-jaspel_nakes" name="jaspel_nakes" type="number" step="0.01" min="0" class="js-rupiah-field">
                        </div>

                        <div class="modal-group">
                            <label for="field-bmhp">BMHP</label>
                            <input id="field-bmhp" name="bmhp" type="number" step="0.01" min="0" class="js-rupiah-field">
                        </div>
                        <div class="modal-group">
                            <label for="field-pkl">PKL</label>
                            <input id="field-pkl" name="pkl" type="number" step="0.01" min="0" class="js-rupiah-field">
                        </div>
                        <div class="modal-group">
                            <label for="field-lain_lain">Lain-lain</label>
                            <input id="field-lain_lain" name="lain_lain" type="number" step="0.01" min="0" class="js-rupiah-field">
                        </div>
                        <div class="modal-group">
                            <label for="field-jumlah_rp_display">Jlh Rp.</label>
                            <input id="field-jumlah_rp_display" type="text" class="readonly-display" readonly>
                        </div>
                    </div>
                </section>

                <section class="modal-section">
                    <h4>Administrasi</h4>
                    <div class="modal-grid">
                        <div class="modal-group">
                            <label for="field-utang_pasien">Utang Pasien</label>
                            <input id="field-utang_pasien" name="utang_pasien" type="number" step="0.01" min="0">
                        </div>
                        <div class="modal-group">
                            <label for="field-utang">Utang</label>
                            <input id="field-utang" name="utang" type="number" step="0.01" min="0">
                        </div>
                        <div class="modal-group">
                            <label for="field-bayar_utang_pasien">Bayar Utang Pasien</label>
                            <input id="field-bayar_utang_pasien" name="bayar_utang_pasien" type="number" step="0.01" min="0">
                        </div>
                        <div class="modal-group">
                            <label for="field-derma_solidaritas">Derma & Solidaritas</label>
                            <input id="field-derma_solidaritas" name="derma_solidaritas" type="number" step="0.01" min="0">
                        </div>

                        <div class="modal-group">
                            <label for="field-saldo_kredit">Saldo Kredit</label>
                            <input id="field-saldo_kredit" name="saldo_kredit" type="number" step="0.01" min="0">
                        </div>
                        <div class="modal-group">
                            <label for="field-saldo">Saldo</label>
                            <input id="field-saldo" name="saldo" type="number" step="0.01" min="0">
                        </div>
                        <div class="modal-group span-2">
                            <label>User Login</label>
                            <input type="text" value="{{ $loggedInAdminName }}" readonly class="readonly-display">
                        </div>

                        <div class="modal-group span-4">
                            <label for="field-keterangan">Keterangan</label>
                            <textarea id="field-keterangan" name="keterangan" rows="3"></textarea>
                        </div>
                    </div>
                </section>
            </div>

            <div class="modal-footer">
                <div class="modal-actions">
                    <button type="button" class="btn btn-muted js-close-modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    const visitMap = @json($visitRows->keyBy('map_key')->all());
    const savedMap = @json($savedTransactionData);
    const oldFormState = @json(session()->getOldInput());
    const hasValidationErrors = @json($errors->any());
    const preferredTab = @json($preferredTab);

    const modal = document.getElementById('transaksiModal');
    const form = document.getElementById('transaksiForm');
    const methodField = document.getElementById('field-method');
    const modalModeField = document.getElementById('field-meta-mode');
    const modalTransactionField = document.getElementById('field-meta-transaction-id');
    const activeTabContextField = document.getElementById('field-active-tab-context');
    const modalTitle = document.getElementById('modalTitle');
    const modalSubtitle = document.getElementById('modalSubtitle');
    const summaryPenjamin = document.getElementById('summary-penjamin');
    const summaryNoRawat = document.getElementById('summary-no-rawat');
    const summaryPasien = document.getElementById('summary-pasien');
    const summaryLayanan = document.getElementById('summary-layanan');
    const summaryTotal = document.getElementById('summary-total');
    const tabButtons = Array.from(document.querySelectorAll('[data-tab-target]'));
    const tabPanels = Array.from(document.querySelectorAll('.tab-panel'));
    const tabFilterForms = Array.from(document.querySelectorAll('[data-tab-filter]'));
    const tabStorageKey = 'transaksi-pasien.active-tab';

    const trackedFields = [
        'clinic_profile_id',
        'simrs_no_rawat',
        'simrs_no_reg',
        'layanan_medis',
        'dokter',
        'penjamin',
        'tanggal',
        'bulan',
        'harian',
        'no_rm',
        'nama_pasien',
        'jk',
        'statis',
        'genap',
        'status_pasien',
        'alamat',
        'lab',
        'icd',
        'diagnosa',
        'farmasi',
        'uang_daftar',
        'uang_periksa',
        'uang_obat',
        'uang_bersalin',
        'jasa_dokter',
        'jml_hari',
        'rawat_inap',
        'jml_visit',
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
        'keterangan',
    ];

    const rupiahFields = [
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
    ];

    function formatRupiah(value) {
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(value || 0);
    }

    function isBpjsPenjamin(value) {
        return String(value || '').toLowerCase().includes('bpjs');
    }

    function setFieldValue(name, value) {
        const field = document.getElementById(`field-${name}`);

        if (!field) {
            return;
        }

        field.value = value ?? '';
    }

    function getNumberValue(name) {
        const field = document.getElementById(`field-${name}`);

        if (!field) {
            return 0;
        }

        return parseFloat(field.value) || 0;
    }

    function syncTotal() {
        const total = rupiahFields.reduce((carry, fieldName) => carry + getNumberValue(fieldName), 0);

        document.getElementById('field-jumlah_rp_display').value = formatRupiah(total);
        summaryTotal.textContent = formatRupiah(total);
    }

    function closeModal() {
        modal.hidden = true;
        document.body.style.overflow = '';
    }

    function activateTab(targetId) {
        tabButtons.forEach((button) => {
            const isActive = button.dataset.tabTarget === targetId;

            button.classList.toggle('is-active', isActive);
            button.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });

        tabPanels.forEach((panel) => {
            panel.hidden = panel.id !== targetId;
        });

        tabFilterForms.forEach((filterForm) => {
            filterForm.hidden = filterForm.dataset.tabFilter !== targetId;
        });

        window.localStorage.setItem(tabStorageKey, targetId);
    }

    function currentActiveTab() {
        return tabButtons.find((button) => button.classList.contains('is-active'))?.dataset.tabTarget
            || window.localStorage.getItem(tabStorageKey)
            || tabButtons[0]?.dataset.tabTarget
            || 'panel-data-transaksi';
    }

    function hydrateForm(mode, payload, transactionId = null) {
        const resolvedTransactionId = transactionId || payload.id || '';

        trackedFields.forEach((fieldName) => setFieldValue(fieldName, payload[fieldName]));

        form.action = mode === 'edit'
            ? form.dataset.updateUrl.replace('__ID__', resolvedTransactionId)
            : form.dataset.storeUrl;

        methodField.value = mode === 'edit' ? 'PUT' : 'POST';
        modalModeField.value = mode;
        modalTransactionField.value = resolvedTransactionId;
        activeTabContextField.value = currentActiveTab();
        modalTitle.textContent = mode === 'edit' ? 'Edit Transaksi Pasien' : 'Input Transaksi Pasien';
        modalSubtitle.textContent = mode === 'edit'
            ? 'Ubah data transaksi pasien.'
            : 'Form transaksi pasien.';
        const resolvedPenjamin = payload.penjamin || payload.meta?.penjamin || '-';
        summaryPenjamin.textContent = `Penjamin: ${resolvedPenjamin}`;
        summaryPenjamin.classList.toggle('is-bpjs', isBpjsPenjamin(resolvedPenjamin));

        summaryNoRawat.textContent = payload.simrs_no_rawat || '-';
        summaryPasien.textContent = payload.nama_pasien || '-';
        if (payload.layanan_label && payload.layanan_medis && payload.layanan_label !== payload.layanan_medis) {
            summaryLayanan.textContent = `${payload.layanan_label} (${payload.layanan_medis})`;
        } else {
            summaryLayanan.textContent = payload.layanan_label || payload.layanan_medis || '-';
        }
    }

    function resolveMapKey(mapKey, overrideData = null) {
        if (mapKey) {
            return mapKey;
        }

        if (overrideData?.map_key) {
            return overrideData.map_key;
        }

        if (overrideData?.clinic_profile_id && overrideData?.simrs_no_rawat) {
            return `${overrideData.clinic_profile_id}::${overrideData.simrs_no_rawat}`;
        }

        return overrideData?.simrs_no_rawat || '';
    }

    function openModal(mode, mapKey, transactionId = null, overrideData = null) {
        const resolvedMapKey = resolveMapKey(mapKey, overrideData);
        const sourceData = visitMap[resolvedMapKey] || {};
        const savedData = savedMap[resolvedMapKey] || {};
        const payload = overrideData
            ? (mode === 'edit' ? { ...sourceData, ...savedData, ...overrideData } : { ...sourceData, ...overrideData })
            : (mode === 'edit' ? { ...sourceData, ...savedData } : sourceData);

        hydrateForm(mode, payload, transactionId);

        syncTotal();
        modal.hidden = false;
        document.body.style.overflow = 'hidden';
    }

    document.querySelectorAll('.js-open-modal').forEach((button) => {
        button.addEventListener('click', () => {
            openModal(
                button.dataset.mode,
                button.dataset.mapKey,
                button.dataset.transactionId || null,
            );
        });
    });

    document.querySelectorAll('.js-close-modal').forEach((button) => {
        button.addEventListener('click', closeModal);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.hidden) {
            closeModal();
        }
    });

    document.querySelectorAll('.js-rupiah-field').forEach((input) => {
        input.addEventListener('input', syncTotal);
    });

    tabButtons.forEach((button) => {
        button.addEventListener('click', () => activateTab(button.dataset.tabTarget));
    });

    const fallbackTab = preferredTab || window.localStorage.getItem(tabStorageKey) || tabButtons[0]?.dataset.tabTarget || 'panel-data-transaksi';
    const initialTab = hasValidationErrors && oldFormState._modal_mode
        ? (oldFormState.active_tab_context || fallbackTab)
        : fallbackTab;

    activateTab(initialTab);

    if (hasValidationErrors && oldFormState._modal_mode && oldFormState.simrs_no_rawat) {
        const oldMapKey = oldFormState.map_key
            || (oldFormState.clinic_profile_id && oldFormState.simrs_no_rawat
                ? `${oldFormState.clinic_profile_id}::${oldFormState.simrs_no_rawat}`
                : oldFormState.simrs_no_rawat);

        openModal(
            oldFormState._modal_mode,
            oldMapKey,
            oldFormState._transaction_id || null,
            oldFormState,
        );
    }
</script>
@endsection
