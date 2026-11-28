<x-layouts.app title="Katalog Mobil">
    @php
        $dateStr = $date->toDateString();
        $isToday = $date->isToday();
        $filterLink = fn (string $status) => route('katalog', ['date' => $dateStr, 'status' => $status]);
        $tabs = [
            'semua' => ['Semua', 'bg-white/20 text-white', 'bg-surface-container text-on-surface-variant'],
            'tersedia' => ['Tersedia', 'bg-[#dcfce7] text-[#166534]', 'bg-[#dcfce7] text-[#166534]'],
            'dibooking' => ['Sudah Dibooking', 'bg-[#fef3c7] text-[#92400e]', 'bg-[#fef3c7] text-[#92400e]'],
        ];
    @endphp

    <div class="mx-auto max-w-container-max px-margin-mobile pb-stack-lg pt-stack-lg md:px-margin-desktop">
        <div class="mb-stack-md">
            <h1 class="mb-2 text-headline-lg text-on-background">Katalog Kendaraan Dinas</h1>
            <p class="text-body-md text-on-surface-variant">Pilih kendaraan yang tersedia untuk mendukung operasional layanan kesehatan di Kabupaten Purbalingga.</p>
        </div>

        {{-- Filter tanggal + status --}}
        <div class="mb-stack-lg rounded-xl border border-outline-variant bg-surface-container-lowest p-4 shadow-sm md:p-5">
            <form method="GET" action="{{ route('katalog') }}" class="flex flex-col gap-4">
                <input type="hidden" name="status" value="{{ $filter }}">

                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex flex-wrap items-center gap-3">
                        <label for="filter-date" class="flex items-center gap-1.5 text-label-md font-semibold text-on-surface">
                            <span class="material-symbols-outlined text-[20px] text-primary">calendar_month</span>
                            Pilih Tanggal:
                        </label>
                        <input type="date" id="filter-date" name="date" value="{{ $dateStr }}" min="{{ today()->toDateString() }}"
                               onchange="this.form.submit()"
                               class="h-10 rounded-lg border border-outline-variant bg-surface-container-lowest px-3 py-1.5 text-body-sm text-on-surface focus:border-primary focus:ring-2 focus:ring-primary/20">

                        <button type="submit" class="rounded-lg bg-primary px-3 py-2 text-label-sm text-white transition-colors hover:bg-primary-container">
                            Cek Tanggal
                        </button>

                        @if ($isToday)
                            <span class="rounded-full bg-primary-fixed px-3 py-1 text-label-sm font-medium text-primary">Hari Ini</span>
                        @else
                            <a href="{{ route('katalog', ['status' => $filter]) }}" class="text-label-sm font-medium text-primary hover:underline">Kembali ke hari ini</a>
                        @endif
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-label-sm font-medium text-on-surface-variant">Filter:</span>
                        @foreach ($tabs as $key => [$label, $activeCount, $idleCount])
                            @php $active = $filter === $key; @endphp
                            <a href="{{ $filterLink($key) }}"
                               @class([
                                   'flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-label-sm font-medium transition-all',
                                   'bg-primary text-white shadow-xs' => $active,
                                   'bg-surface-container-low text-on-surface-variant hover:bg-surface-container' => ! $active,
                               ])>
                                {{ $label }}
                                <span class="rounded-full px-1.5 py-0.5 text-[11px] font-semibold leading-none {{ $active ? $activeCount : $idleCount }}">{{ $counts[$key] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>

                <div class="flex items-center gap-2 border-t border-outline-variant/60 pt-3 text-body-sm text-on-surface-variant">
                    <span class="material-symbols-outlined text-[18px] text-primary">event_available</span>
                    <span>Menampilkan status kendaraan untuk tanggal:
                        <strong class="text-on-surface">{{ $date->locale('id')->translatedFormat('l, j F Y') }}</strong>
                    </span>
                </div>
            </form>
        </div>

        <div class="grid grid-cols-1 gap-gutter sm:grid-cols-2 lg:grid-cols-4">
            @forelse ($vehicles as $vehicle)
                <x-vehicle-card :vehicle="$vehicle" :state="$states[$vehicle->id]" />
            @empty
                <p class="col-span-full py-stack-lg text-center text-on-surface-variant">
                    {{ $filter === 'semua' ? 'Belum ada kendaraan terdaftar.' : 'Tidak ada kendaraan dengan status ini pada tanggal tersebut.' }}
                </p>
            @endforelse
        </div>

        {{ $vehicles->links() }}
    </div>
</x-layouts.app>
