<?php

namespace App\Http\Requests;

use App\Models\Employee;
use App\Services\BookingWindow;
use Closure;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nip' => ['required', 'digits:18'],
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            // The day of use itself (hari H) can be requested; only past dates are rejected.
            'departs_on' => ['bail', 'required', 'date_format:Y-m-d', function (string $attribute, mixed $value, Closure $fail) {
                if (Carbon::parse($value)->startOfDay()->lt(BookingWindow::earliestDeparture())) {
                    $fail('Tanggal Keberangkatan tidak boleh sebelum hari ini.');
                }
            }],
            // Whole days: returning on the day of departure (a same-day trip) is fine.
            'returns_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:departs_on'],
            // Booked for today: the departure time must not already be past (Asia/Jakarta, see config/app.php).
            'departs_time' => ['bail', 'required', 'date_format:H:i', function (string $attribute, mixed $value, Closure $fail) {
                $date = $this->input('departs_on');

                if (is_string($date) && $date === now()->toDateString() && $value < now()->format('H:i')) {
                    $fail('Jam Keberangkatan sudah lewat. Pilih jam '.now()->format('H:i').' atau sesudahnya untuk pemakaian hari ini.');
                }
            }],
            // A same-day trip must end after it starts; on a multi-day trip any return time is fine.
            'returns_time' => ['bail', 'required', 'date_format:H:i', function (string $attribute, mixed $value, Closure $fail) {
                $sameDay = $this->input('departs_on') === $this->input('returns_on');

                if ($sameDay && is_string($this->input('departs_time')) && $value <= $this->input('departs_time')) {
                    $fail('Jam Kembali harus setelah Jam Keberangkatan.');
                }
            }],
            'destination' => ['required', 'string', 'max:255'],
            'purpose' => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'nip.required' => 'NIP wajib diisi.',
            'nip.digits' => 'NIP harus terdiri dari 18 digit angka.',
            'vehicle_id.required' => 'Kendaraan belum dipilih — pilih dari Katalog Mobil.',
            'vehicle_id.exists' => 'Kendaraan yang dipilih tidak ditemukan.',
            'departs_on.required' => 'Tanggal Keberangkatan wajib diisi.',
            'departs_on.date_format' => 'Tanggal Keberangkatan tidak valid.',
            'returns_on.required' => 'Tanggal Kembali wajib diisi.',
            'returns_on.date_format' => 'Tanggal Kembali tidak valid.',
            'returns_on.after_or_equal' => 'Tanggal Kembali tidak boleh sebelum Tanggal Keberangkatan.',
            'departs_time.required' => 'Jam Keberangkatan wajib diisi.',
            'departs_time.date_format' => 'Jam Keberangkatan tidak valid (format JJ:MM).',
            'returns_time.required' => 'Jam Kembali wajib diisi.',
            'returns_time.date_format' => 'Jam Kembali tidak valid (format JJ:MM).',
            'destination.required' => 'Lokasi Tujuan wajib diisi.',
            'purpose.required' => 'Keperluan wajib diisi.',
        ];
    }

    /** The employee must still be an active record when the form is finally submitted. */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->has('nip') || ! $this->filled('nip')) {
                    return;
                }
                if (! Employee::findActiveByNip($this->string('nip')->toString())) {
                    $validator->errors()->add('nip', 'NIP tidak terdaftar dalam Database Pegawai Dinas Kesehatan atau status NIP non-aktif. Harap hubungi Pengelola Admin Dinkes.');
                }
            },
        ];
    }
}
