@php
    use App\Models\Subdivision;
    use Illuminate\Support\Str;
    use Illuminate\Support\Facades\Schema;

    $user = auth()->user();
    $brandingSubdivision = null;

    if (Schema::hasTable('subdivisions')) {
        $brandingSubdivision = $user?->subdivision_id
            ? Subdivision::find($user->subdivision_id)
            : null;

        $brandingSubdivision ??= Subdivision::query()
            ->where('status', 'Active')
            ->orderBy('subdivision_name')
            ->first()
            ?? Subdivision::query()->orderBy('subdivision_name')->first();
    }

    $brandName = $brandingSubdivision?->subdivision_name ?? 'Doña Maria Dizon';
    $brandNameUpper = Str::upper($brandName);
    $brandLogo = $brandingSubdivision?->logo_url ?? asset('imgsrc/logo.png');

    $sections = [
        [
            'key' => 'overview',
            'title' => 'Overview',
            'items' => array_values(array_filter([
                ['label' => 'Dashboard', 'href' => route('dashboard'), 'active' => 'dashboard'],
                !$user->isResident() ? ['label' => 'Subdivision', 'href' => route('subdivisions.index'), 'active' => 'subdivisions.*'] : null,
                $user->isAdmin() ? ['label' => 'Users', 'href' => route('users.index'), 'active' => 'users.*'] : null,
            ])),
        ],
        [
            'key' => 'monitoring',
            'title' => 'Monitoring',
            'items' => array_values(array_filter([
                ['label' => 'Incidents', 'href' => route('incidents.index'), 'active' => 'incidents.index'],
                $user->hasRole(['staff']) ? ['label' => 'Residents', 'href' => route('residents.index'), 'active' => 'residents.*'] : null,
                $user->hasRole(['security', 'staff']) ? ['label' => 'Visitors', 'href' => route('visitors.index'), 'active' => 'visitors.*'] : null,
                $user->isResident() ? ['label' => 'Visitors', 'href' => route('resident.visitors.index'), 'active' => 'resident.visitors.*'] : null,
            ])),
        ],
    ];

    $sectionOpenState = [];

    foreach ($sections as $section) {
        $sectionOpenState[$section['key']] = $section['key'] === 'monitoring'
            || collect($section['items'])->contains(
                fn (array $item): bool => request()->routeIs($item['active'])
            );
    }
@endphp

<nav x-data="sidebarNavigation({
    sectionOpenState: {{ \Illuminate\Support\Js::from($sectionOpenState) }}
})">
    <div class="sticky top-0 z-30 border-b border-white/10 bg-[var(--shell-sidebar)] text-white lg:hidden">
        <div class="flex items-center justify-between gap-3 px-4 py-4">
            <a href="{{ route('dashboard') }}" class="sidebar-brand sidebar-brand-mobile">
                <img src="{{ $brandLogo }}" alt="{{ $brandName }} logo" class="h-12 w-12 flex-none rounded-full object-cover bg-transparent p-0 shadow-none ring-0">
                <span class="sidebar-brand-text">{{ $brandNameUpper }}</span>
            </a>

            <button
                type="button"
                @click="open = ! open"
                class="rounded-xl bg-white/5 p-2 text-[var(--shell-sidebar-text)] ring-1 ring-white/10 transition hover:bg-white/10 hover:text-white"
            >
                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                    <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6h16M4 12h16M4 18h16" />
                    <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>

    <div
        x-cloak
        x-show="open"
        x-transition.opacity
        @click="open = false"
        class="fixed inset-0 z-30 bg-slate-950/60 lg:hidden"
    ></div>

    <aside
        :class="open ? 'translate-x-0' : '-translate-x-full'"
        class="sidebar-panel fixed inset-y-0 left-0 z-40 flex w-72 transform flex-col transition duration-200 ease-out lg:translate-x-0"
    >
        <div class="flex min-h-44 items-center justify-center border-b border-white/10 px-6 py-7">
            <a href="{{ route('dashboard') }}" class="sidebar-brand sidebar-brand-desktop">
                <img src="{{ $brandLogo }}" alt="{{ $brandName }} logo" class="h-24 w-24 flex-none rounded-full object-cover bg-transparent p-0 shadow-none ring-0">
                <span class="sidebar-brand-text sidebar-brand-text-desktop">{{ $brandNameUpper }}</span>
            </a>
        </div>

        <div class="flex-1 space-y-6 overflow-y-auto px-4 py-6">
            @foreach ($sections as $section)
                @if (count($section['items']) > 0)
                    <section>
                        <button
                            type="button"
                            @click="toggleSection('{{ $section['key'] }}')"
                            class="flex w-full items-center justify-between px-4 text-left text-[11px] font-semibold uppercase tracking-[0.24em] text-[var(--shell-sidebar-muted)] transition hover:text-white"
                        >
                            <span>{{ $section['title'] }}</span>
                            <svg
                                class="h-4 w-4 transition duration-200 ease-out"
                                :class="sections['{{ $section['key'] }}'] ? 'rotate-180 text-white' : 'rotate-0'"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                                aria-hidden="true"
                            >
                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <div x-cloak x-show="sections['{{ $section['key'] }}']" x-transition.origin.top.duration.200ms class="mt-3 space-y-1.5">
                            @foreach ($section['items'] as $item)
                                <a
                                    href="{{ $item['href'] }}"
                                    @click="open = false"
                                    class="sidebar-link {{ request()->routeIs($item['active']) ? 'sidebar-link-active' : '' }}"
                                >
                                    <span>{{ $item['label'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </section>
                @endif
            @endforeach
        </div>

        <div class="border-t border-white/10 px-5 py-5">
            <div @click.away="accountOpen = false" class="rounded-2xl bg-white/5 px-4 py-4 ring-1 ring-white/10">
                <div class="flex items-center justify-between gap-3">
                    <p class="truncate text-sm font-semibold text-white">{{ $user->full_name }}</p>

                    <button
                        type="button"
                        @click="toggleAccountMenu()"
                        class="rounded-xl bg-white/5 p-2 text-[var(--shell-sidebar-text)] ring-1 ring-white/10 transition hover:bg-white/10 hover:text-white"
                        aria-label="Open account menu"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path d="M11.983 1.878a1 1 0 00-1.966 0l-.164.984a1 1 0 01-.76.804l-.967.22a1 1 0 00-.512 1.67l.67.67a1 1 0 01.27.93l-.137.803a1 1 0 001.45 1.054l.86-.45a1 1 0 01.928 0l.86.45a1 1 0 001.45-1.054l-.136-.803a1 1 0 01.27-.93l.67-.67a1 1 0 00-.513-1.67l-.966-.22a1 1 0 01-.761-.804l-.163-.984z" />
                            <path d="M10 7.25a2.75 2.75 0 100 5.5 2.75 2.75 0 000-5.5z" />
                            <path d="M3 10a7 7 0 1114 0A7 7 0 013 10zm7-5.5a5.5 5.5 0 00-4.58 8.547c.537-.82 1.44-1.394 2.488-1.548a3.75 3.75 0 014.184 0c1.048.154 1.95.729 2.488 1.548A5.5 5.5 0 0010 4.5z" />
                        </svg>
                    </button>
                </div>

                <div x-cloak x-show="accountOpen" x-transition.origin.bottom.duration.200ms class="mt-4 space-y-4">
                    <div>
                        <p class="text-sm text-[var(--shell-sidebar-muted)]">{{ ucfirst($user->role) }}</p>
                        <p class="mt-1 truncate text-xs text-[var(--shell-sidebar-muted)]">{{ $user->email }}</p>
                    </div>

                    <div class="flex gap-2">
                        <a href="{{ route('profile.edit') }}" class="sidebar-ghost-button flex-1 text-center">
                            Profile
                        </a>

                        <form method="POST" action="{{ route('logout') }}" class="flex-1">
                            @csrf
                            <button type="submit" class="sidebar-primary-button w-full">
                                Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </aside>
</nav>
