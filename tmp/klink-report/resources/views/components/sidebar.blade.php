@php
    $currentUser = auth()->user();
    $pegawaiProfile = $currentUser?->pegawaiProfile;
    $clinicProfile = $sharedClinicProfile ?? null;
    $clinicTitle = $clinicProfile?->nama_pendek ?: $clinicProfile?->nama_klinik ?: config('app.name', 'Klink Report');
    $clinicSubtitle = $clinicProfile?->tagline ?: 'Laporan operasional klinik';
    $initials = collect(preg_split('/\s+/', trim((string) ($currentUser?->name ?: 'KA'))))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
    $masterClinicQuery = $currentUser?->isMaster() && request()->has('clinic_id')
        ? ['clinic_id' => request()->query('clinic_id')]
        : [];
    $menuSections = [
        [
            'title' => 'Utama',
            'items' => [
                [
                    'label' => 'Dashboard',
                    'route' => 'dashboard',
                    'icon' => 'dashboard',
                    'status' => 'live',
                ],
                [
                    'label' => 'Transaksi Pasien',
                    'route' => 'transaksi-pasien',
                    'icon' => 'pharmacy',
                    'status' => 'live',
                ],
            ],
        ],
        [
            'title' => 'Keuangan',
            'items' => array_values(array_filter([
                [
                    'label' => 'Input Pengeluaran',
                    'route' => 'input-pengeluaran',
                    'icon' => 'wallet',
                    'status' => 'live',
                ],
                $currentUser?->canViewReports() ? [
                    'label' => 'Rekap Bulanan',
                    'route' => 'rekap-bulanan',
                    'icon' => 'calendar',
                    'status' => 'live',
                ] : null,
                $currentUser?->canViewReports() ? [
                    'label' => 'Rekap Tahunan',
                    'route' => 'rekap-tahunan',
                    'icon' => 'chart',
                    'status' => 'live',
                ] : null,
            ])),
        ],
        [
            'title' => 'Administrasi',
            'items' => array_values(array_filter([
                $currentUser?->canManageMasters() ? [
                    'label' => 'Kode Yayasan',
                    'route' => null,
                    'icon' => 'shield',
                    'status' => 'live',
                    'group_id' => 'kode-yayasan',
                    'children' => [
                        [
                            'label' => 'Kode Layanan',
                            'route' => 'kode-layanan',
                        ],
                        [
                            'label' => 'Kode Pengeluaran',
                            'route' => 'kode-pengeluaran',
                        ],
                    ],
                ] : null,
                $currentUser?->canManageClinicProfile() ? [
                    'label' => 'Profile Klinik',
                    'route' => 'profile-klinik',
                    'icon' => 'profile',
                    'status' => 'live',
                ] : null,
                $currentUser?->canManageUsers() ? [
                    'label' => 'Manajemen User',
                    'route' => 'manajemen-user',
                    'icon' => 'users',
                    'status' => 'live',
                ] : null,
            ])),
        ],
    ];

    $menuSections = collect($menuSections)
        ->filter(fn ($section) => ! empty($section['items']))
        ->values()
        ->all();

@endphp

<aside class="sidebar">
    <div class="sidebar-inner">
        <div class="sidebar-header">
            <div class="sidebar-header-main">
                <div class="brand-mark" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path d="M8.5 5.5a3.5 3.5 0 0 1 5 0l4 4a3.5 3.5 0 1 1-5 5l-4-4a3.5 3.5 0 0 1 0-5Z"></path>
                        <path d="M10 10l4 4"></path>
                        <path d="M19 5v4"></path>
                        <path d="M17 7h4"></path>
                    </svg>
                </div>
                <div class="brand-copy">
                    <h2>{{ $clinicTitle }}</h2>
                    <p>{{ \Illuminate\Support\Str::limit($clinicSubtitle, 40) }}</p>
                </div>
            </div>

            <button type="button" class="sidebar-toggle" data-sidebar-toggle aria-label="Toggle sidebar">
                <svg viewBox="0 0 24 24">
                    <path d="M15 18l-6-6 6-6"></path>
                </svg>
            </button>
        </div>

        @foreach ($menuSections as $section)
            <section class="sidebar-section">
                <p class="sidebar-section-title">{{ $section['title'] }}</p>

                <div class="sidebar-menu">
                    @foreach ($section['items'] as $item)
                        @php
                            $children = collect($item['children'] ?? []);
                            $routePatterns = $children->pluck('route')
                                ->filter()
                                ->when(filled($item['route']), fn ($collection) => $collection->prepend($item['route']));
                            $isActive = $routePatterns->contains(fn ($route) => request()->routeIs($route));
                            $isDisabled = blank($item['route']) && $children->isEmpty();
                            $groupId = $item['group_id'] ?? \Illuminate\Support\Str::slug($item['label']);
                        @endphp

                        @if ($children->isNotEmpty())
                            <div
                                class="sidebar-group {{ $isActive ? 'is-active is-open' : '' }}"
                                data-sidebar-group="{{ $groupId }}"
                            >
                                <button
                                    type="button"
                                    class="sidebar-link sidebar-link-group {{ $isActive ? 'is-active' : '' }}"
                                    data-sidebar-group-toggle
                                    aria-expanded="{{ $isActive ? 'true' : 'false' }}"
                                    aria-controls="sidebar-submenu-{{ $groupId }}"
                                >
                                    <span class="sidebar-icon" aria-hidden="true">
                                        @switch($item['icon'])
                                            @case('dashboard')
                                                <svg viewBox="0 0 24 24">
                                                    <rect x="3" y="3" width="7" height="7" rx="1.5"></rect>
                                                    <rect x="14" y="3" width="7" height="5" rx="1.5"></rect>
                                                    <rect x="14" y="12" width="7" height="9" rx="1.5"></rect>
                                                    <rect x="3" y="14" width="7" height="7" rx="1.5"></rect>
                                                </svg>
                                                @break
                                            @case('pharmacy')
                                                <svg viewBox="0 0 24 24">
                                                    <path d="M8.5 5.5a3.5 3.5 0 0 1 5 0l4 4a3.5 3.5 0 1 1-5 5l-4-4a3.5 3.5 0 0 1 0-5Z"></path>
                                                    <path d="M10 10l4 4"></path>
                                                    <path d="M19 5v4"></path>
                                                    <path d="M17 7h4"></path>
                                                </svg>
                                                @break
                                            @case('wallet')
                                                <svg viewBox="0 0 24 24">
                                                    <path d="M5 4h14"></path>
                                                    <path d="M7 4v16"></path>
                                                    <path d="M17 4v16"></path>
                                                    <path d="M7 8h10"></path>
                                                    <path d="M7 13h10"></path>
                                                    <path d="M10 17h4"></path>
                                                </svg>
                                                @break
                                            @case('calendar')
                                                <svg viewBox="0 0 24 24">
                                                    <path d="M8 2v4"></path>
                                                    <path d="M16 2v4"></path>
                                                    <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                                                    <path d="M3 10h18"></path>
                                                    <path d="M8 14h3"></path>
                                                    <path d="M13 14h3"></path>
                                                </svg>
                                                @break
                                            @case('chart')
                                                <svg viewBox="0 0 24 24">
                                                    <path d="M4 19h16"></path>
                                                    <path d="M7 16V10"></path>
                                                    <path d="M12 16V6"></path>
                                                    <path d="M17 16v-3"></path>
                                                </svg>
                                                @break
                                            @case('shield')
                                                <svg viewBox="0 0 24 24">
                                                    <path d="M12 3l7 3v5c0 4.5-2.9 8.7-7 10-4.1-1.3-7-5.5-7-10V6l7-3Z"></path>
                                                    <path d="M9.5 12.5 11 14l3.5-3.5"></path>
                                                </svg>
                                                @break
                                            @case('users')
                                                <svg viewBox="0 0 24 24">
                                                    <path d="M16 19a4 4 0 0 0-8 0"></path>
                                                    <circle cx="12" cy="9" r="3"></circle>
                                                    <path d="M20 19a3.5 3.5 0 0 0-3-3.46"></path>
                                                    <path d="M17.5 6.5a2.5 2.5 0 0 1 0 5"></path>
                                                    <path d="M4 19a3.5 3.5 0 0 1 3-3.46"></path>
                                                    <path d="M6.5 6.5a2.5 2.5 0 0 0 0 5"></path>
                                                </svg>
                                                @break
                                            @case('profile')
                                                <svg viewBox="0 0 24 24">
                                                    <path d="M12 21s-6.5-4.35-6.5-10.25V5.8L12 3l6.5 2.8v4.95C18.5 16.65 12 21 12 21Z"></path>
                                                    <path d="M9.5 12.2h5"></path>
                                                    <path d="M12 9.5v5.4"></path>
                                                </svg>
                                                @break
                                            @default
                                                <svg viewBox="0 0 24 24">
                                                    <circle cx="12" cy="8" r="3.2"></circle>
                                                    <path d="M6.5 19a5.5 5.5 0 0 1 11 0"></path>
                                                </svg>
                                        @endswitch
                                    </span>

                                    <span class="sidebar-link-content">
                                        <span class="sidebar-link-label">{{ $item['label'] }}</span>
                                        <span class="sidebar-group-caret" aria-hidden="true">
                                            <svg viewBox="0 0 24 24">
                                                <path d="M6 9l6 6 6-6"></path>
                                            </svg>
                                        </span>
                                    </span>
                                </button>

                                <div
                                    class="sidebar-submenu"
                                    id="sidebar-submenu-{{ $groupId }}"
                                    data-sidebar-submenu
                                    @unless($isActive) hidden @endunless
                                >
                                    @foreach ($children as $child)
                                        @php
                                            $isChildActive = request()->routeIs($child['route']);
                                        @endphp
                                        <a
                                            href="{{ route($child['route'], $masterClinicQuery) }}"
                                            class="sidebar-sublink {{ $isChildActive ? 'is-active' : '' }}"
                                        >
                                            <span class="sidebar-subdot" aria-hidden="true"></span>
                                            <span class="sidebar-sublink-label">{{ $child['label'] }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <a
                                href="{{ $isDisabled ? '#' : route($item['route'], $masterClinicQuery) }}"
                                class="sidebar-link {{ $isActive ? 'is-active' : '' }} {{ $isDisabled ? 'is-disabled' : '' }}"
                                @if ($isDisabled)
                                    aria-disabled="true"
                                    onclick="return false;"
                                @endif
                            >
                                <span class="sidebar-icon" aria-hidden="true">
                                    @switch($item['icon'])
                                        @case('dashboard')
                                            <svg viewBox="0 0 24 24">
                                                <rect x="3" y="3" width="7" height="7" rx="1.5"></rect>
                                                <rect x="14" y="3" width="7" height="5" rx="1.5"></rect>
                                                <rect x="14" y="12" width="7" height="9" rx="1.5"></rect>
                                                <rect x="3" y="14" width="7" height="7" rx="1.5"></rect>
                                            </svg>
                                            @break
                                        @case('pharmacy')
                                            <svg viewBox="0 0 24 24">
                                                <path d="M8.5 5.5a3.5 3.5 0 0 1 5 0l4 4a3.5 3.5 0 1 1-5 5l-4-4a3.5 3.5 0 0 1 0-5Z"></path>
                                                <path d="M10 10l4 4"></path>
                                                <path d="M19 5v4"></path>
                                                <path d="M17 7h4"></path>
                                            </svg>
                                            @break
                                        @case('wallet')
                                            <svg viewBox="0 0 24 24">
                                                <path d="M5 4h14"></path>
                                                <path d="M7 4v16"></path>
                                                <path d="M17 4v16"></path>
                                                <path d="M7 8h10"></path>
                                                <path d="M7 13h10"></path>
                                                <path d="M10 17h4"></path>
                                            </svg>
                                            @break
                                        @case('calendar')
                                            <svg viewBox="0 0 24 24">
                                                <path d="M8 2v4"></path>
                                                <path d="M16 2v4"></path>
                                                <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                                                <path d="M3 10h18"></path>
                                                <path d="M8 14h3"></path>
                                                <path d="M13 14h3"></path>
                                            </svg>
                                            @break
                                        @case('chart')
                                            <svg viewBox="0 0 24 24">
                                                <path d="M4 19h16"></path>
                                                <path d="M7 16V10"></path>
                                                <path d="M12 16V6"></path>
                                                <path d="M17 16v-3"></path>
                                            </svg>
                                            @break
                                        @case('shield')
                                            <svg viewBox="0 0 24 24">
                                                <path d="M12 3l7 3v5c0 4.5-2.9 8.7-7 10-4.1-1.3-7-5.5-7-10V6l7-3Z"></path>
                                                <path d="M9.5 12.5 11 14l3.5-3.5"></path>
                                            </svg>
                                            @break
                                        @case('users')
                                            <svg viewBox="0 0 24 24">
                                                <path d="M16 19a4 4 0 0 0-8 0"></path>
                                                <circle cx="12" cy="9" r="3"></circle>
                                                <path d="M20 19a3.5 3.5 0 0 0-3-3.46"></path>
                                                <path d="M17.5 6.5a2.5 2.5 0 0 1 0 5"></path>
                                                <path d="M4 19a3.5 3.5 0 0 1 3-3.46"></path>
                                                <path d="M6.5 6.5a2.5 2.5 0 0 0 0 5"></path>
                                            </svg>
                                            @break
                                        @case('profile')
                                            <svg viewBox="0 0 24 24">
                                                <path d="M12 21s-6.5-4.35-6.5-10.25V5.8L12 3l6.5 2.8v4.95C18.5 16.65 12 21 12 21Z"></path>
                                                <path d="M9.5 12.2h5"></path>
                                                <path d="M12 9.5v5.4"></path>
                                            </svg>
                                            @break
                                        @default
                                            <svg viewBox="0 0 24 24">
                                                <circle cx="12" cy="8" r="3.2"></circle>
                                                <path d="M6.5 19a5.5 5.5 0 0 1 11 0"></path>
                                            </svg>
                                    @endswitch
                                </span>

                                <span class="sidebar-link-content">
                                    <span class="sidebar-link-label">{{ $item['label'] }}</span>

                                    @if ($item['status'] === 'soon')
                                        <span class="sidebar-badge is-soon">Soon</span>
                                    @endif
                                </span>
                            </a>
                        @endif
                    @endforeach
                </div>
            </section>
        @endforeach

        @if ($currentUser)
            <div class="sidebar-user-card">
                <div class="sidebar-user-top">
                    <div class="sidebar-user-avatar">{{ $initials ?: 'KA' }}</div>
                    <div class="sidebar-user-meta">
                        <strong>{{ $currentUser->name }}</strong>
                        <span>{{ $pegawaiProfile?->jabatan ?: 'Petugas Admin' }}</span>
                    </div>
                </div>

                <div class="sidebar-user-foot">
                    <span class="sidebar-user-handle">{{ $currentUser->username ?: $currentUser->email }}</span>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="sidebar-logout">Logout</button>
                    </form>
                </div>
            </div>
        @endif
    </div>
</aside>
