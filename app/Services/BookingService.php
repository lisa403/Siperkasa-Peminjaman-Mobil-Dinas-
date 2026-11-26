<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Employee;
use App\Models\Vehicle;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingService
{
    /**
     * Create a pending booking.
     *
     * The vehicle row is locked for the duration of the transaction so two people submitting
     * the same vehicle/time at once cannot both pass the overlap check.
     *
     * @param  array{departs_on: string, returns_on: string, departs_time: string, returns_time: string, destination: string, purpose: string}  $data
     *
     * @throws ValidationException when the vehicle is not bookable or already held for that time
     */
    public function create(Employee $employee, int $vehicleId, array $data): Booking
    {
        return DB::transaction(function () use ($employee, $vehicleId, $data) {
            $vehicle = Vehicle::query()->lockForUpdate()->findOrFail($vehicleId);

            if (! $vehicle->isBookable()) {
                throw ValidationException::withMessages([
                    'vehicle_id' => 'Kendaraan ini sedang tidak tersedia untuk dipinjam.',
                ]);
            }

            $departs = Carbon::parse($data['departs_on'])->startOfDay();
            $returns = Carbon::parse($data['returns_on'])->startOfDay();

            if ($vehicle->maintenances()->overlappingDates($departs, $returns)->exists()) {
                throw ValidationException::withMessages([
                    'departs_on' => 'Kendaraan dijadwalkan perawatan pada rentang tanggal tersebut. Pilih tanggal lain atau kendaraan lain.',
                ]);
            }

            // Time-aware check: the same vehicle can be booked twice on one day as long as the two bookings
            // are at least Booking::BUFFER_MINUTES (one hour) apart.
            $start = $departs->copy()->setTimeFromTimeString($data['departs_time']);
            $end = $returns->copy()->setTimeFromTimeString($data['returns_time']);

            $clash = Booking::findClash($vehicle->bookings()->blocking(), $start, $end);
            if ($clash) {
                throw ValidationException::withMessages([
                    'departs_on' => 'Kendaraan sudah diajukan atau dipesan pada '.$clash->scheduleLabel().'. '
                        .'Peminjaman kendaraan yang sama harus berjarak minimal 1 jam dari peminjaman lain. '
                        .'Pilih jam atau tanggal lain, atau kendaraan lain.',
                ]);
            }

            return Booking::create([
                'code' => Booking::nextCode(),
                'employee_id' => $employee->id,
                'vehicle_id' => $vehicle->id,
                'departs_on' => $departs,
                'returns_on' => $returns,
                'departs_time' => $data['departs_time'],
                'returns_time' => $data['returns_time'],
                'destination' => $data['destination'],
                'purpose' => $data['purpose'],
                'status' => BookingStatus::Menunggu,
            ]);
        });
    }
}
