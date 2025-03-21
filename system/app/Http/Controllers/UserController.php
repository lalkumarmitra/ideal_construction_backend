<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Requests\User\CreateNewUserRequest;
use App\Http\Requests\User\UpdatePasswordRequest;
use App\Http\Requests\User\UserDetailsRequest;
use App\Models\Transaction;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function store(CreateNewUserRequest $request) {
        return $this->tryCatchWrapper(function()use($request){
            $imageUrl = $request->hasFile('avatar') ? $request->avatar->store('assets/images/avatar','public'):null;
            $role = Role::find($request->role_id);
            $user = User::create([
                'name'=>$request->name,
                'phone'=>$request->phone,
                'role_id'=>$role->id,
                'gender'=>$request->gender ?? 'male',
                'dob'=>$request->dob,
                'email'=>$request->email,
                'password'=>$request->password ?? '123456',
                'avatar'=>$imageUrl,
            ]);
            return [
                'message'=>'User Created successfully',
                'data'=>['user'=>$user->load('role')]
            ];
        });
    }

    public function update(UpdateUserRequest $request,$id) {
        return $this->tryCatchWrapper(function()use($request,$id){
            if(!$user = User::find($id)) throw new \Exception('User not found with ID : '.$id,404);
            if(Auth::user()->role->priority > 2 && $user->id !== Auth::user()->id) throw new Exception('Unauthorized Request', 403);
            DB::beginTransaction();
            try{
                $role = Role::find($request->role_id);
                if ($request->hasFile('avatar')) {
                    $imageUrl = $request->avatar->store('assets/images/avatar','public');
                    $previous_image_location = env('ASSET_URL').$user->avatar;
                    if(file_exists($previous_image_location)) unlink($previous_image_location);
                } else $imageUrl = $user->avatar;
                // Prepare update data
                $updateData = [
                    'name' => $request->name ?? $user->name,
                    'gender' => $request->gender ?? $user->gender,
                    'dob' => $request->dob ?? $user->dob,
                    'avatar' => $imageUrl,
                    'role_id' => $role ? $role->id : $user->role_id,
                ];
                if ($request->filled('password') && Auth::user()->role->priority <= 2) {
                    $updateData['password'] = bcrypt($request->password);
                }
                $user->update($updateData);
                DB::commit();
                return [
                    'message'=>'User Details Updated Successfully',
                    'data'=>['user'=>$user->load('role')]
                ];
            }catch(\Exception $e){
                DB::rollBack();
                throw new Exception('Something went wrong , '.$e->getMessage());
            }
        });
    }
    public function read($page=1,$offset=10,$byroleid=null) {
        return $this->tryCatchWrapper(function()use($page,$offset,$byroleid){
            $query = User::query();
            if($byroleid)$query->where('role_id',$byroleid);
            $users = $query->with('role')->latest()->paginate($offset, ['*'], 'page', $page);
            return [
                'message'=>'Users Fetched Successfully',
                'data'=>[
                    'users' => $users->items(),
                    'current_page' => $users->currentPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                    'last_page' => $users->lastPage(),
                ]
            ];
        });
    }
    public function details(UserDetailsRequest $request, $id) {
        return $this->tryCatchWrapper(function() use($request, $id) {
            if (!$user = User::find($id)) throw new Exception('User not found with ID : '.$id, 404);
            if (Auth::user()->role->priority > 2 && $user->id !== Auth::user()->id) throw new Exception('Unauthorized Request', 403);
        
            $baseQuery = Transaction::where(function($query) use ($id) {
                $query->where('loading_driver_id', $id)->orWhere('unloading_driver_id', $id);
            });
            if ($request->filled('from_date') && $request->filled('to_date')) {
                $baseQuery->whereBetween('loading_date', [$request->from_date, $request->to_date]);
            }
            $totalsQuery = clone $baseQuery;
            $totalTransactions = $totalsQuery->count();
            $totalExpense = $totalsQuery->sum('transport_expense');
            $totalUnloadedQuantity = $totalsQuery->sum('unloading_quantity');
            $totalPrice = $totalsQuery->selectRaw('SUM(unloading_quantity * unloading_rate) as total_price')->value('total_price') ?? 0;
            
            $perPage = $request->offset ?? 10;
            $page = $request->page ?? 1;
            $paginatedTransactions = $baseQuery->with(['product', 'loadingPoint', 'unloadingPoint'])->paginate($perPage, ['*'], 'page', $page);
            
            return [
                'message' => 'User details Fetched Successfully',
                'data' => [
                    'user' => $user->load(['role']),
                    'transactions' => $paginatedTransactions,
                    'total_transactions' => $totalTransactions,
                    'total_expense' => $totalExpense,
                    'total_unloaded_quantity' => $totalUnloadedQuantity,
                    'total_price' => $totalPrice,
                    'pagination' => [
                        'current_page' => $paginatedTransactions->currentPage(),
                        'per_page' => $paginatedTransactions->perPage(),
                        'total' => $paginatedTransactions->total(),
                        'last_page' => $paginatedTransactions->lastPage(),
                    ]
                ]
            ];
        });
    }
    public function delete($id) {
        return $this->tryCatchWrapper(function()use($id){
            if(Auth::user()->role->priority > 2) throw new Exception('Unauthorized Request', 403);
            if(!$user = User::find($id)) throw new Exception('User not found with ID : '.$id,404);
            $user->delete();
            return [
                'message'=>'User deleted Successfully',
                'data'=>['user' => $user->load(['role'])]
            ];
        });
    }
    public function toggleStatus($id) {
        return $this->tryCatchWrapper(function()use($id){
            if(Auth::user()->role->priority > 2) throw new Exception('Unauthorized Request', 403);
            if(!$user = User::find($id)) throw new Exception('User not found with ID : '.$id,404);
            $user->is_active = !$user->is_active;
            $user->save();
            return [
                'message'=>$user->is_active ? 'User Activated Successfully' : 'User Deactivated successfully',
                'data'=>['user' => $user->load(['role'])]
            ];
        });
    }
    public function toggleBlock($id) {
        return $this->tryCatchWrapper(function()use($id){
            if(Auth::user()->role->priority > 2) throw new Exception('Unauthorized Request', 403);
            if(!$user = User::find($id)) throw new Exception('User not found with ID : '.$id,404);
            $user->is_blocked = !$user->is_blocked;
            $user->save();
            return [
                'message'=>$user->is_blocked?'User Blocked Successfully':'User Unblocked Successfully',
                'data'=>['user' => $user->load(['role'])]
            ];
        });
    }
    public function changePhone($id,$phone) {
        return $this->tryCatchWrapper(function()use($id,$phone){
            if(!is_numeric($phone)) throw new Exception('Invalid phone number',422);
            if(strlen($phone) != 10) throw new Exception('Phone number must be 10 digits',422);
            if(!$user = User::find($id)) throw new Exception('User not found with ID : '.$id,404);
            if (Auth::user()->role->priority > 2 && $user->id !== Auth::user()->id) throw new Exception('Unauthorized Request', 403);
            $phone_user = User::where('phone',$phone)->first();
            if($phone_user && $user->id !== $phone_user->id) throw new Exception('Phone number is already taken',422);
            $user->phone = $phone;
            $user->save();
            return [
                'message'=>'Phone number updated Successfully',
                'data'=>['user' => $user->load(['role'])]
            ];
        });
    }

    public function changeEmail($id,$email) {
        return $this->tryCatchWrapper(function()use($id,$email){
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new Exception('Invalid email format', 422);
            if(!$user = User::find($id)) throw new Exception('User not found with ID : '.$id,404);
            if (Auth::user()->role->priority > 2 && $user->id !== Auth::user()->id) throw new Exception('Unauthorized Request', 403);
            $email_user = User::where('email', $email)->first();
            if ($email_user && $email_user->id !== $user->id) throw new Exception('Email is already taken', 422);
            $user->email = $email;
            $user->save();

            return [
                'message'=>'Email updated Successfully',
                'data'=>['user' => $user->load(['role'])]
            ];
        });
    }

    public function changePassword(UpdatePasswordRequest $request) {
        return $this->tryCatchWrapper(function()use($request){
            $user = User::find(Auth::user()->id);
            if(Hash::check($user->password,$request->old_password)) throw new Exception('Invalid Password',403);
            $user->password = $request->new_password;
            $user->save();
            return [
                'message'=>'Password updated Successfully',
            ];
        });
    }

}
