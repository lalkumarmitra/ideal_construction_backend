<?php

namespace App\Http\Controllers;

use App\Http\Requests\Role\CreateRoleRequest;
use App\Models\Role;
use Exception;
use Illuminate\Support\Facades\Auth;

class RoleController extends Controller
{
    public function store(CreateRoleRequest $request){
        return $this->tryCatchWrapper(function()use($request){
            $role = Role::create([
                'name'=>$request->name, 
                'type'=>$request->type, 
                'priority'=>$request->priority
            ]);
            return [
                'message'=>'Role Created Successfully',
                'data'=>['role'=>$role]
            ];
        });
    }
    public function update(CreateRoleRequest $request, $id){
        return $this->tryCatchWrapper(function()use($request, $id){
            if(!$role = Role::find($id)) throw new Exception('Role not found', 404);
            $role->update([
                'name' => $request->name,
                'type' => $request->type,
                'priority' => $request->priority
            ]);
            return [
                'message' => 'Role Updated Successfully',
                'data' => ['role' => $role]
            ];
        });
    }
    public function read($id=null){
        return $this->tryCatchWrapper(function()use($id){
            if($id===null) return [
                'message'=>'Roles Fetched Successfully',
                'data'=>['roles'=>Role::all()]
            ];
            if(Auth::user()->role->priority <=2) throw new Exception('Unauthorized request');
            if(!$role=Role::find($id)) throw new Exception('Invalid role id',404);
            return [
                'message'=>'Roles Fetched Successfully',
                'data'=>['role'=>$role->load('users')]
            ];
        });
    }
    public function delete($id){
        return $this->tryCatchWrapper(function()use($id){
            if(Auth::user()->role->priority === 1 ) throw new Exception('Unauthorized request');
            if(!$role = Role::find($id)) throw new Exception('Role not found', 404);
            $role->delete();
            return [
                'message' => 'Role Deleted Successfully',
                'data' => null
            ];
        });
    }
}
