<?php

namespace App\Http\Controllers;

use App\Enums\VehicleStatus;
use App\Models\Booking;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class VehicleController extends Controller
{
    /** Four cards per row on desktop, two full rows per page. */
    private const PER_PAGE = 8;

    private const FILTERS = ['semua', 'tersedia', 'dibooking'];

    public function index(Request $request): View
    {
        $date = $this->resolveDate($request->query('date'));
        $filter = in_array($request->query('status'), self::FILTERS, true) ? $request->query('status') : 'semua';

        $day = $date->toDateString();

        // Hanya muat booking & jadwal servis yang menyentuh tanggal yang dipilih.
        $vehicles = Vehicle::query()
            ->with([
                'photos',
                'bookings' => fn ($q) => $q->blocking()
                    ->whereDate('departs_on', '<=', $day)
                    ->whereDate('returns_on', '>=', $day),
                'maintenances' => fn ($q) => $q->overlappingDates($date, $date),
            ])
            ->orderBy('id')
            ->get();

        $states = $vehicles->mapWithKeys(fn (Vehicle $v) => [$v->id => $this->stateOn($v, $date)]);

        $counts = [
            'semua' => $vehicles->count(),
            'tersedia' => $states->where('key', 'tersedia')->count(),
            'dibooking' => $states->where('key', 'dibooking')->count(),
        ];

        $visible = $vehicles
            ->filter(fn (Vehicle $v) => $filter === 'semua' || $states[$v->id]['key'] === $filter)
            ->values();

        $page = LengthAwarePaginator::resolveCurrentPage();
        $paginator = (new LengthAwarePaginator(
            $visible->forPage($page, self::PER_PAGE)->values(),
            $visible->count(),
            self::PER_PAGE,
            $page,
            ['path' => $request->url()],
        ))->withQueryString();

        return view('katalog', [
            'vehicles' => $paginator,
            'states' => $states,
            'counts' => $counts,
            'filter' => $filter,
            'date' => $date,
        ]);
    }

    /** Tanggal dari query string; kosong/tidak valid/sudah lewat → hari ini. */
    private function resolveDate(?string $value): Carbon
    {
        $today = today();

        try {
            $date = $value ? Carbon::createFromFormat('Y-m-d', $value)->startOfDay() : $today;
        } catch (\Throwable) {
            return $today;
        }

        return $date->lt($today) ? $today : $date;
    }

    /**
     * Status kendaraan pada $date.
     *
     * @return array{key: string, slots: ?string}
     */
    private function stateOn(Vehicle $vehicle, Carbon $date): array
    {
        if ($vehicle->status === VehicleStatus::Perawatan || $vehicle->maintenances->isNotEmpty()) {
            return ['key' => 'perawatan', 'slots' => null];
        }

        if ($vehicle->status === VehicleStatus::Dipakai) {
            return ['key' => 'dipakai', 'slots' => null];
        }

        if ($vehicle->bookings->isNotEmpty()) {
            // Hari pertama mulai dari jam berangkat, hari terakhir sampai jam kembali, hari tengah seharian.
            $slots = $vehicle->bookings
                ->sortBy('departs_time')
                ->map(fn (Booking $b) => ($b->departs_on->isSameDay($date) ? $b->departsTime() : '00:00')
                    .' – '
                    .($b->returns_on->isSameDay($date) ? $b->returnsTime() : '23:59').' WIB')
                ->implode(', ');

            return ['key' => 'dibooking', 'slots' => $slots];
        }

        return ['key' => 'tersedia', 'slots' => null];
    }
}