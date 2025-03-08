<?php

namespace App\Http\Controllers;

use App\Http\Requests\Product\StoreNewProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Models\Product;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function store(StoreNewProductRequest $request){
        return $this->tryCatchWrapper(function()use($request){
            $imageUrl = $request->hasFile('image') ? $request->image->store('assets/images/products','public'):null;
            $product = Product::create([
                "name"=>$request->name,
                "rate"=>$request->rate,
                "unit"=>$request->unit,
                "image"=>$imageUrl,
                "description"=>$request->description,
                "frequency_of_use"=>0,
            ]);
            return [
                'message'=>'Product Saved successfully',
                'data'=>['product'=>$product]
            ];
        });
    }

    public function update(UpdateProductRequest $request, $id) {
        return $this->tryCatchWrapper(function() use ($request, $id) {
            if(!$product = Product::find($id)) throw new Exception('Product not found with ID : '.$id,404);
            DB::beginTransaction();
            try {
                if ($request->hasFile('image')) {
                    $imageUrl = $request->image->store('assets/images/products','public');
                    $previous_image_location = env('ASSET_URL').$product->image;
                    if(file_exists($previous_image_location)) unlink($previous_image_location);
                } else $imageUrl = $product->image;
                $product->update([
                    "name" => $request->name ?? $product->name,
                    "rate" => $request->rate ?? $product->rate,
                    "unit" => $request->unit ?? $product->unit,
                    "image" => $imageUrl,
                    "description" => $request->description ?? $product->description,
                ]);
                DB::commit();
                return [
                    'message' => 'Product updated successfully',
                    'data' => ['product' => $product]
                ];
            } catch (\Exception $e) {
                DB::rollBack();
                throw new \Exception("Failed to update product: " . $e->getMessage());
            }
        });
    }
    
    

    public function read($page=1,$offset=10){
        return $this->tryCatchWrapper(function()use($page,$offset){
            $products = Product::latest()->orderBy('frequency_of_use', 'desc')->paginate($offset, ['*'], 'page', $page);
            return [
                'message'=>'Products Fetched Successfully',
                'data'=>[
                    'products' => $products->items(),
                    'current_page' => $products->currentPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                    'last_page' => $products->lastPage(),
                ]
            ];
        });
    }
    public function details($id){
        return $this->tryCatchWrapper(function()use($id){
            if(!$product = Product::find($id)) throw new Exception('Product not found with ID : '.$id,404);
            return [
                'message'=>'Product details Fetched Successfully',
                'data'=>['product' => $product]
            ];
        });
    }
    public function delete($id){
        return $this->tryCatchWrapper(function()use($id){
            if(Auth::user()->role->priority > 2) throw new Exception('Unauthorized Request', 403);
            if(!$product = Product::find($id)) throw new Exception('Product not found with ID : '.$id,404);
            $product->delete();
            return [
                'message'=>'Product deleted Successfully',
                'data'=>['product' => $product]
            ];
        });
    }
}
