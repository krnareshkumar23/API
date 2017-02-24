<?php

namespace App\Http\Controllers\API\V1;


use App\Models\Model;
use App\Models\Price;
use App\Models\Vehicle;
use App\Models\VehiclePhoto;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Intervention\Image\Facades\Image;
use Symfony\Component\HttpFoundation\JsonResponse;

class UploadController extends ApiController
{
    /**
     * Upload a new vehicle
     * @param Request $request
     * @return JsonResponse
     */
    public function upload(Request $request)
    {
        $this->validate($request, [
            'model' => 'required|exists:models,id',
            'photos' => 'required|array|min:1',
            'photos.*' => 'image',
            'lat' => 'required|numeric',
            'lon' => 'required|numeric',
            'price' => 'required|exists:prices,id',
            'condition' => 'exists:conditions,id',
            'year' => 'exists:years,id',
            'color' => 'exists:colors,id',
            'body_type' => 'exists:body_types,id',
            'doors' => 'exists:doors,id',
            'size' => 'exists:sizes,id',
            'mileage' => 'exists:mileages,id',
            'fuel' => 'exists:fuels,id',
            'transmission' => 'exists:transmissions,id',
            'engine' => 'exists:engines,id',
            'tax_band' => 'exists:tax_bands,id',
            'ownership' => 'exists:ownership,id',
            'description' => 'required',
            'call_number' => 'required_without_all:sms_number,email',
            'sms_number' => 'required_without_all:email,call_number',
            'email' => 'required_without_all:sms_number,call_number',
        ]);

        $user = $request->user();
        $model = Model::findOrFail($request->input('model'));

        if ($user->vehicles()->active()->count() != $user->max_vehicles) {
            return $this->api_response([],
                'You already have an active vehicle. Become a dealer to upload more.', false, 403);
        }

        $vehicle = new Vehicle();
        $vehicle->user()->associate($user);
        $vehicle->model()->associate($model);

        $vehicle->lat = $request->input('lat');
        $vehicle->lon = $request->input('lon');
        $vehicle->price = Price::findOrFail($request->input('price'))->value;
        $vehicle->description = $request->input('description');

        $vehicle->condition_id = $request->input('condition');
        $vehicle->year_id = $request->input('year');
        $vehicle->color_id = $request->input('color');
        $vehicle->body_type_id = $request->input('body_type');
        $vehicle->door_id = $request->input('doors');
        $vehicle->size_id = $request->input('size');
        $vehicle->mileage_id = $request->input('mileage');
        $vehicle->fuel_id = $request->input('fuel');
        $vehicle->transmission_id = $request->input('transmission');
        $vehicle->engine_id = $request->input('engine');
        $vehicle->tax_band_id = $request->input('tax_band');
        $vehicle->ownership_id = $request->input('ownership');

        $vehicle->sms_number = $request->input('sms_number');
        $vehicle->call_number = $request->input('call_number');
        $vehicle->email = $request->input('email');

        $vehicle->save();

        $photoFiles = $request->file('photos');

        foreach ($photoFiles as $file) {
            $file = Image::make($file);
            $photo = new VehiclePhoto();
            $photo->upload($file);
            $vehicle->photos()->save($photo);
        }

        if ($user->type == 'dealer') {
            $vehicle->paid_at = Carbon::now();
        }

        $vehicle->save();

        return $this->api_response([
            'vehicle' => $vehicle->fresh()
        ]);
    }

    public function edit(Request $request)
    {
        $this->validate($request, [
            'id' => 'required|exists:vehicles,id',
            'model' => 'exists:models,id',
            'photos' => 'array|min:1',
            'lat' => 'numeric',
            'lon' => 'numeric',
            'price' => 'exists:prices,id',
            'condition' => 'exists:conditions,id',
            'year' => 'exists:years,id',
            'color' => 'exists:colors,id',
            'body_type' => 'exists:body_types,id',
            'doors' => 'exists:doors,id',
            'size' => 'exists:sizes,id',
            'mileage' => 'exists:mileages,id',
            'fuel' => 'exists:fuels,id',
            'transmission' => 'exists:transmissions,id',
            'engine' => 'exists:engines,id',
            'tax_band' => 'exists:tax_bands,id',
            'ownership' => 'exists:ownerships,id',
            'description' => '',
            'call_number' => '',
            'sms_number' => '',
            'email' => '',
        ]);
        
        $user = $request->user();
        $vehicle = Vehicle
            ::where('user_id', $user->id)
            ->findOrFail($request->input('id'));

        $model = Model::findOrFail($request->input('model', $vehicle->model_id));
        $vehicle->model()->associate($model);

        $vehicle->lat = $request->input('lat', $vehicle->lat);
        $vehicle->lon = $request->input('lon', $vehicle->lon);
        $vehicle->price = Price::findOrFail($request->input('price'))->value;
        $vehicle->description = $request->input('description', $vehicle->description);

        $vehicle->condition_id = $request->input('condition', $vehicle->condition_id);
        $vehicle->year_id = $request->input('year', $vehicle->year_id);
        $vehicle->color_id = $request->input('color', $vehicle->color_id);
        $vehicle->body_type_id = $request->input('body_type', $vehicle->body_type_id);
        $vehicle->door_id = $request->input('door', $vehicle->door_id);
        $vehicle->size_id = $request->input('size', $vehicle->size_id);
        $vehicle->mileage_id = $request->input('mileage', $vehicle->mileage_id);
        $vehicle->fuel_id = $request->input('fuel', $vehicle->fuel_id);
        $vehicle->transmission_id = $request->input('transmission', $vehicle->transmission_id);
        $vehicle->engine_id = $request->input('engine', $vehicle->engine_id);
        $vehicle->tax_band_id = $request->input('tax_band', $vehicle->tax_band_id);
        $vehicle->ownership_id = $request->input('ownership', $vehicle->ownership_id);

        $vehicle->sms_number = $request->input('sms_number', $vehicle->sms_number);
        $vehicle->call_number = $request->input('call_number', $vehicle->call_number);
        $vehicle->email = $request->input('email', $vehicle->email);

        if ($vehicle->sms_number == null && $vehicle->call_number == null && $vehicle->email == null) {
            return $this->api_response([], 'You must provide either a email, phone number or sms number.', false, 400);
        }

        $existingPhotos = VehiclePhoto::where('vehicle_id', $vehicle->id)->get()->pluck('id');

        if ($request->has('photos')) {
            foreach ($request->input('photos') as $photoId) {

                $photo = new VehiclePhoto();
                $existingPhoto = VehiclePhoto::find($photoId);

                if (!$existingPhoto) {
                    return $this->api_response([], 'Invalid image (id ' . $photoId . ')', false, 400);
                }

                $photo->url = $existingPhoto->name;
                $vehicle->photos()->save($photo);
            }
        }

        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photoFile) {
                $photo = new VehiclePhoto();
                $file = Image::make($photoFile);
                $photo->upload($file);

                $vehicle->photos()->save($photo);
            }
        }

        VehiclePhoto::whereIn('id', $existingPhotos)->delete();

        if ($user->type == 'dealer') {
            $vehicle->paid_at = Carbon::now();
        }

        $vehicle->save();

        return $this->api_response([
            'vehicle' => $vehicle->fresh()
        ]);
    }

    public function delete(Request $request)
    {
        $this->validate($request, [
            'id' => 'required|exists:vehicles,id'
        ]);

        $vehicle = Vehicle::findOrFail($request->input('id'));

        if ($vehicle->user->id != $request->user()->id) {
            return $this->api_response([], 'You cannot delete this advert.', false, 401);
        }

        $vehicle->delete();

        return $this->api_response([]);
    }
}