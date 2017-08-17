<?php

namespace App\Services\VehicleImport;

use App\Models\Vehicle;
use App\Models\VehiclePhoto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Importer
{
    public function fetch()
    {
        $sources = [
            CarDealer5::class,
        ];

        foreach ($sources as $source) {
            try {
                $this->fetchSource(new $source);
            } catch (\Exception $exception){
                var_dump($exception->getFile(), $exception->getLine());
                echo $exception->getMessage() . "\n";
            }
        }
    }

    private function fetchSource(Source $source)
    {
        try {
            $source->fetchVehicles();
        } catch (\Exception $exception) {
            Log::error('Could not fetch source: ' . $source->getName() . ' - ' . $exception->getMessage());
        }

        $vehicleSourceIds = $source->getSourceIds();
        $vehicles = $source->getVehicles();
        $this->deleteOldSources($source->getName(), $vehicleSourceIds);

        foreach ($vehicles as $vehicleData) {
            $vehicle = $this->getVehicle($vehicleData, $source);

            $model = $source->getVehicleModel($vehicleData);
            $price = $source->getVehiclePrice($vehicleData, $vehicle);
            $year = $source->getVehicleYear($vehicleData, $vehicle);
            $mileage = $source->getVehicleMileage($vehicleData, $vehicle);
            $callNumber = $source->getVehicleCallNumber($vehicleData, $vehicle);
            $smsNumber = $source->getVehicleSmsNumber($vehicleData, $vehicle);
            $email = $source->getVehicleEmail($vehicleData, $vehicle);
            $website = $source->getVehicleWebsite($vehicleData, $vehicle);
            $description = $source->getVehicleDescription($vehicleData, $vehicle);
            $photos = $photos = $source->getVehiclePhotos($vehicleData, $vehicle);

            $missingProperties = $this->hasMissingProperties(
                $model,
                $price,
                $year,
                $mileage,
                $callNumber,
                $smsNumber,
                $email,
                $website,
                $description,
                $photos
            );

            if (!$missingProperties) {
                $vehicle->source_name = $source->getName();
                $vehicle->source_id = $source->getVehicleSourceId($vehicleData);

                $vehicle->model()->associate($model);
                $vehicle->description = $description;
                $vehicle->call_number = $callNumber;
                $vehicle->sms_number = $smsNumber;
                $vehicle->email = $email;
                $vehicle->website = $website;
                $vehicle->price = $price;
                $vehicle->year = $year;
                $vehicle->mileage = $mileage;

                $vehicle->condition()->associate($source->getVehicleCondition($vehicleData, $vehicle));
                $vehicle->color()->associate($source->getVehicleColor($vehicleData, $vehicle));
                $vehicle->bodyType()->associate($source->getVehicleBodyType($vehicleData, $vehicle));
                $vehicle->door()->associate($source->getVehicleDoor($vehicleData, $vehicle));
                $vehicle->size()->associate($source->getVehicleSize($vehicleData, $vehicle));
                $vehicle->fuel()->associate($source->getVehicleFuel($vehicleData, $vehicle));
                $vehicle->transmission()->associate($source->getVehicleTransmission($vehicleData, $vehicle));
                $vehicle->engine()->associate($source->getVehicleEngine($vehicleData, $vehicle));
                $vehicle->taxBand()->associate($source->getVehicleTaxBand($vehicleData, $vehicle));
                $vehicle->ownership()->associate($source->getVehicleOwnership($vehicleData, $vehicle));
                $vehicle->seatCount()->associate($source->getVehicleSeatCount($vehicleData, $vehicle));
                $vehicle->berth()->associate($source->getVehicleBerth($vehicleData, $vehicle));

                if ($location = $source->getVehicleLocation($vehicleData, $vehicle)) {
                    $vehicle->lat = $location['lat'];
                    $vehicle->lon = $location['lon'];
                    $vehicle->location = $location['location'];
                }

                $vehicle->save();
                $vehicle->photos()->delete();

                foreach ($photos as $i => $photo) {
                    $vehiclePhoto = new VehiclePhoto();
                    $vehiclePhoto->vehicle()->associate($vehicle);
                    $vehiclePhoto->index = $i;
                    $vehiclePhoto->type = 'remote';
                    $vehiclePhoto->url = $photo;
                    $vehiclePhoto->save();
                }
            }
        }
    }

    private function hasMissingProperties(
        $model,
        $price,
        $year,
        $mileage,
        $callNumber,
        $smsNumber,
        $email,
        $website,
        $description,
        $photos
    ): bool {
        return (
            is_null($model) || is_null($price) || is_null($year) || is_null($mileage)
            || (is_null($callNumber) && is_null($smsNumber) && is_null($email) && is_null($website))
            || is_null($description) || strlen($description) == 0
            || count($photos) == 0
        );
    }

    private function deleteOldSources(string $sourceName, array $vehicleSourceIds)
    {
        Vehicle
            ::where('source_name', $sourceName)
            ->whereNotIn('source_id', $vehicleSourceIds)
            ->delete();
    }

    private function getVehicle(array $vehicleData, Source $source): Vehicle
    {
        $vehicle = Vehicle::firstOrNew([
            'source_name' => $source->getName(),
            'source_id' => $source->getVehicleSourceId($vehicleData)
        ]);

        return $vehicle;
    }
}