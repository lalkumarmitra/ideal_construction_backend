<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Exception;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
    protected $http_rc = [404, 422, 200, 500, 302, 401, 403,419];
    protected function handle_error_message($message){
        return $message;
    }
    protected function build_response($status, $response, $status_code){
        if(isset($response['type']) && $response['type'] == 'download'){
            $fileData = $response['data'];
            return response()->download($fileData['path'], $fileData['filename'], ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])->deleteFileAfterSend(true);
        }
        $res = array(
            'status' => $status ? 'true' : 'false',
            'message' => $response['message'] ?? null,
            '_token' => $response['_token'] ?? null,
            'error' => $response['errors'] ?? null,
            'data' => $response['data'] ?? null,
            'warning' => $response['warning'] ?? null,
        );
        foreach ($res as $key => $val) if ($val == null) unset($res[$key]);
        return response()->json($res, $status_code);
    }
    protected function tryCatchWrapper(callable $callback){

        $status = false;
        try {
            $response = $callback();
            $status = true;
            $status_code = 200;
        } catch (\Illuminate\Validation\ValidationException $e) {
            $response = $this->handleException($e);
            $status_code = 422; 
        } catch (Exception $e) {
            $response = $this->handleException($e);
            $status_code = (in_array($e->getCode(),$this->http_rc))?$e->getCode():500; 
        }
        return $this->build_response($status,$response,$status_code);
    }

    private function handleException($e){
        $response['message'] = ($e instanceof \Illuminate\Database\QueryException) ? 'Internal Processing Error' : $e->getMessage();
        $response['errors'] = $this->handle_error_message($e->getMessage());
        return $response;
    }
    protected function getCurrentSession(){
        $current_month = date('n');
        $current_year = date('Y');
        $session_start = $current_month <= 3 ? $current_year -1 : $current_year;   
        return $session_start . '-' . ($session_start + 1);
    }
}
