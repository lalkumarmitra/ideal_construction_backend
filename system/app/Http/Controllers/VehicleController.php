<?php

namespace App\Http\Controllers;

use App\Http\Requests\Vehicle\CreateVehicleRequest;
use App\Http\Requests\Vehicle\UpdateVehicleRequest;
use App\Models\Vehicle;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VehicleController extends Controller
{
    public function store(CreateVehicleRequest $request){
        return $this->tryCatchWrapper(function()use($request){
            if(Vehicle::where('number',$request->number)->where('type',$request->type)->first())
            throw new Exception('Vehicle with same number/id and type already exists',403);
            $vehicle = Vehicle::create([
                "number"=>$request->number,
                "type"=>$request->type,
                "frequency_of_use"=>0
            ]);
            return [
                'message'=>'Vehicle Created Successfully',
                'data'=>['vehicle'=>$vehicle]
            ];
        });
    }
    public function update(UpdateVehicleRequest $request,$id){
        return $this->tryCatchWrapper(function() use($request,$id){
            if(!$vehicle = Vehicle::find($id)) throw new \Exception('Vehicle not found with ID : '.$id,404);
            $vehicle->update([
                "number"=>$request->number ?? $vehicle->number,
                "type"=>$request->type ?? $vehicle->type,
            ]);
            return [
                'message' => 'Vehicle updated successfully',
                'data' => ['vehicle' => $vehicle]
            ];
        });
    }
    public function read($page=1,$offset=10){
        return $this->tryCatchWrapper(function()use($page,$offset) {
            $vehicles = Vehicle::latest()->orderBy('frequency_of_use', 'desc')->paginate($offset, ['*'], 'page', $page);
            return [
                'message'=>'Vehicles Fetched Successfully',
                'data'=>[
                    'vehicles' => $vehicles->items(),
                    'current_page' => $vehicles->currentPage(),
                    'per_page' => $vehicles->perPage(),
                    'total' => $vehicles->total(),
                    'last_page' => $vehicles->lastPage(),
                ]
            ];
        });
    }
    public function readAll(){
        return $this->tryCatchWrapper(function(){
            return [
                'message'=>'Vehicles Fetched Successfully',
                'data'=>[ 'vehicles' => Vehicle::latest()->get() ]
            ];
        });
    }
    public function details($id){
        return $this->tryCatchWrapper(function()use($id){
            if(!$vehicle = Vehicle::find($id)) throw new \Exception('Product not found with ID : '.$id,404);
            return [
                'message'=>'Vehicle details Fetched Successfully',
                'data'=>['vehicle' => $vehicle]
            ];
        });
    }
    public function delete($id){
        return $this->tryCatchWrapper(function()use($id){
            if(Auth::user()->role->priority > 2) throw new Exception('Unauthorized Request', 403);
            if(!$vehicle = Vehicle::find($id)) throw new \Exception('Product not found with ID : '.$id,404);
            $vehicle->delete();
            return [
                'message'=>'Vehicle deleted Successfully',
                'data'=>['vehicle' => $vehicle]
            ];
        });
    }

}
