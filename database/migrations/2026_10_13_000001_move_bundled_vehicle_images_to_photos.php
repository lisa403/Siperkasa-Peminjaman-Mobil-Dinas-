<?php

use App\Models\Vehicle;
use App\Services\VehiclePhotoStore;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /** Vehicles that still point at a bundled picture get it as a real front photo (editable in the admin panel). */
    public function up(): void
    {
        $store = app(VehiclePhotoStore::class);

        Vehicle::query()->whereNotNull('image_path')->each(fn (Vehicle $vehicle) => $store->adoptBundledImage($vehicle));
    }

    public function down(): void
    {
        // The copied photos stay; moving them back is not needed.
    }
};
