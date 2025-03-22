<?php

namespace App\Http\Controllers;

use App\Http\Requests\Client\StoreClientRequest;
use App\Models\Client;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ClientController extends Controller
{
    public function store(StoreClientRequest $request){
        return $this->tryCatchWrapper(function()use($request){
            $image_url = $request->hasFile('image') ? $request->image->store('assets/images/client','public'):null;
            
            $client = Client::create([
                "name"=>$request->name,
                "address"=>$request->address,
                "state"=>$request->state,
                "pin"=>$request->pin,
                "type"=>$request->type,
                "image"=>$image_url,
                "client_size"=>$request->client_size,
            ]);
            return [
                'message'=>'Client Created successfully',
                'data'=>['client'=>$client]
            ];
        });
    }
    public function update(StoreClientRequest $request,$id){
        return $this->tryCatchWrapper(function()use($request,$id){
            if(!$client = Client::find($id)) throw new Exception('Client not found with id: '.$id,404);
            $image_url = $request->hasFile('image') ? $request->image->store('assets/images/client','public'):null;
            try{
                DB::beginTransaction();
                if ($request->hasFile('image')) {
                    $imageUrl = $request->image->store('assets/images/client','public');
                    $previous_image_location = env('ASSET_URL').$client->image;
                    if(file_exists($previous_image_location)) unlink($previous_image_location);
                } else $imageUrl = $client->image;
                $client->update([
                    "name"=>$request->name, //required in request
                    "address"=>$request->address ?? $client->address,
                    "state"=>$request->state ?? $client->state,
                    "pin"=>$request->pin ?? $client->pin,
                    "type"=>$request->type, //required in request
                    "image"=>$imageUrl, //handled null value
                    "client_size"=>$request->client_size,//required in request
                ]);
                DB::commit();
                return [
                    'message'=>'Client Updated successfully',
                    'data'=>['client'=>$client]
                ];
            }catch(Exception $e){
                DB::rollBack();
                throw new Exception('Could not update Client : '.$e->getMessage());
            }
        });
    }
    public function read($page=1,$offset=10,Request $request){
        return $this->tryCatchWrapper(function()use($page,$offset,$request) {
            $query = Client::query();
            $query->when($request->filled('search_query'), function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search_query . '%')
                  ->orWhere('address', 'like', '%' . $request->search_query . '%')
                  ->orWhere('state', 'like', '%' . $request->search_query . '%')
                  ->orWhere('pin', 'like', '%' . $request->search_query . '%')
                  ->orWhere('type', 'like', '%' . $request->search_query . '%');
            });
            $clients = $query->latest()->orderBy('frequency_of_use', 'desc')->paginate($offset, ['*'], 'page', $page);
            return [
                'message'=>'Clients Fetched Successfully',
                'data'=>[
                    'clients' => $clients->items(),
                    'current_page' => $clients->currentPage(),
                    'per_page' => $clients->perPage(),
                    'total' => $clients->total(),
                    'last_page' => $clients->lastPage(),
                ]
            ];
        });
    }
    public function details($id){
        return $this->tryCatchWrapper(function()use($id){
            if(!$client = Client::find($id)) throw new Exception('Client not found with ID : '.$id,404);
            return [
                'message'=>'Client details Fetched Successfully',
                'data'=>['client' => $client]
            ];
        });
    }
    public function delete($id){
        return $this->tryCatchWrapper(function()use($id){
            if(Auth::user()->role->priority > 2) throw new Exception('Unauthorized Request', 403);
            if(!$client = Client::find($id)) throw new Exception('Client not found with ID : '.$id,404);
            $client->delete();
            return [
                'message'=>'Client deleted Successfully',
                'data'=>['client' => $client]
            ];
        });
    }
}
