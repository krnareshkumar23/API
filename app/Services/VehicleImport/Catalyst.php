<?php

namespace App\Services\VehicleImport;

use App\Models\BodyType;
use App\Models\Color;
use App\Models\Condition;
use App\Models\Door;
use App\Models\Engine;
use App\Models\Fuel;
use App\Models\Make;
use App\Models\Model;
use App\Models\Transmission;
use App\Models\Vehicle;
use GuzzleHttp\Client;

class Catalyst implements Source
{

    public function getName(): string
    {
        return 'catalyst';
    }

    public function getSourceIds(): array
    {
        $sourceIds = array_map(function($vehicle) {
            return $vehicle[1];
        }, $this->csv);

        return $sourceIds;
    }

    public function fetchVehicles()
    {
        $client = new Client();
        $response = $client->get('https://www.cardealer5.co.uk/carcliq/export_cars.csv');
        $csv = trim($response->getBody()->getContents());
        $csv = array_map('str_getcsv', explode("\n", $csv));
        unset($csv[0]);

        $this->csv = $csv;
    }

    public function getVehicles(): array
    {
        return $this->csv;
    }

    public function getVehicleSourceId(array $vehicleData): string
    {
        return trim($vehicleData[1]);
    }

    public function getVehicleVendorId(array $vehicleData): string
    {
        return trim($vehicleData[0]);
    }

    public function getVehicleModel(array $vehicleData)
    {
        $makeValue = trim($vehicleData[9]);
        $modelValue = trim($vehicleData[10]);
        $variantValue = trim($vehicleData[11]);

        $makes = Make::where('value', $makeValue)->get();

        foreach ($makes as $make) {
            $model = Model::where('make_id', $make->id)->where('value', $modelValue)->first();
            if ($model) {
                return $model;
            }
            $model = Model::where('make_id', $make->id)->where('value', $modelValue . ' ' . $variantValue)->first();
            if ($model) {
                return $model;
            }
        }

        return  null;
    }

    public function getVehicleCondition(array $vehicleData, Vehicle $vehicle)
    {
        if ($vehicleData[22] == 'Yes' || $vehicleData[23] == 'Yes') {
            $condition = $vehicleData[22] == 'Yes' ? 'New' : 'Used';
            return Condition
                ::where('category_id', $vehicle->model->category->id)
                ->where('value', $condition)
                ->first();
        }

        return null;
    }

    public function getVehicleColor(array $vehicleData, Vehicle $vehicle)
    {
        return Color
            ::where('category_id', $vehicle->model->category->id)
            ->where('value', trim($vehicleData[3]))
            ->first();
    }

    public function getVehicleBodyType(array $vehicleData, Vehicle $vehicle)
    {
        return BodyType
            ::where('category_id', $vehicle->model->category->id)
            ->where('value', trim($vehicleData[7]))
            ->first();
    }

    public function getVehicleDoor(array $vehicleData, Vehicle $vehicle)
    {
        return Door
            ::where('category_id', $vehicle->model->category->id)
            ->where('value', trim($vehicleData[8]))
            ->first();
    }

    public function getVehicleSize(array $vehicleData, Vehicle $vehicle)
    {
        return null;
    }

    public function getVehicleFuel(array $vehicleData, Vehicle $vehicle)
    {
        return Fuel
            ::where('category_id', $vehicle->model->category->id)
            ->where('value', trim($vehicleData[4]))
            ->first();
    }

    public function getVehicleTransmission(array $vehicleData, Vehicle $vehicle)
    {
        return Transmission
            ::where('category_id', $vehicle->model->category->id)
            ->where('value', trim($vehicleData[14]))
            ->first();
    }

    public function getVehicleEngine(array $vehicleData, Vehicle $vehicle)
    {
        $engineSize = trim($vehicleData[12]);

        if (!empty($engineSize)) {
            $engineSize = intval($engineSize) / 1000;

            if ($engineSize > 1) {
                $engineSize = round($engineSize, 1);
            }

            return Engine
                ::where('category_id', $vehicle->model->category->id)
                ->where('litres', $engineSize)
                ->first();
        }

        return null;
    }

    public function getVehicleTaxBand(array $vehicleData, Vehicle $vehicle)
    {
        return null;
    }

    public function getVehicleOwnership(array $vehicleData, Vehicle $vehicle)
    {
        return null;
    }

    public function getVehicleSeatCount(array $vehicleData, Vehicle $vehicle)
    {
        return null;
    }

    public function getVehicleBerth(array $vehicleData, Vehicle $vehicle)
    {
        return null;
    }

    public function getVehiclePrice(array $vehicleData, Vehicle $vehicle)
    {
        $price = trim($vehicleData[13]);
        return !empty($price) ? $price : null;
    }

    public function getVehicleYear(array $vehicleData, Vehicle $vehicle)
    {
        $year = trim($vehicleData[5]);
        return !empty($year) ? $year : null;
    }

    public function getVehicleMileage(array $vehicleData, Vehicle $vehicle)
    {
        $mileage = trim($vehicleData[6]);
        return !empty($mileage) ? $mileage : null;
    }

    public function getVehicleLocation(array $vehicleData, Vehicle $vehicle)
    {
        return null;
    }

    public function getVehicleDescription(array $vehicleData, Vehicle $vehicle)
    {
        $description = trim($vehicleData[21]);
        $description = preg_replace('/[\x{10000}-\x{10FFFF}]/u', "\xEF\xBF\xBD", $description);
        $description = str_replace(";", "\n", $description);

        return !empty($description) ? $description : null;
    }

    public function getVehicleCallNumber(array $vehicleData, Vehicle $vehicle)
    {
        return null;
    }

    public function getVehicleSmsNumber(array $vehicleData, Vehicle $vehicle)
    {
        return null;
    }

    public function getVehicleEmail(array $vehicleData, Vehicle $vehicle)
    {
        return null;
    }

    public function getVehicleWebsite(array $vehicleData, Vehicle $vehicle)
    {
        $url = trim($vehicleData[24]);

        if (filter_var($url, FILTER_VALIDATE_URL)  !== false) {
            return $url;
        }

        return null;
    }

    public function getVehiclePhotos(array $vehicleData, Vehicle $vehicle)
    {
        $photos = explode(',', $vehicleData[15]);
        $photos = array_filter($photos, function ($photo) {
            return !empty($photo);
        });
        $photos = array_map(function ($photo) {
            return trim($photo);
        }, $photos);

        return $photos;
    }
}
