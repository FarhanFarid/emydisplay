<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Inertia\Inertia;
use Carbon\Carbon;
use Log;

class DisplayController extends Controller
{
    public function index(Request $request){

        return Inertia::render('Display/Index');

    }

    public function getEmyPatient(Request $request){

        try
        { 
            $uri = env('EMYPATIENT');

            $client = new \GuzzleHttp\Client(['defaults' => ['verify' => false]]);

            $response = $client->request('GET', $uri);

            $statusCode = $response->getStatusCode();
            $content = json_decode($response->getBody(), true);

            return $response = response()->json(
                [
                    'status'  => 'success',
                    'data' => array_values($content['emy']),
                ], 200
            );
        }
        catch (\Exception $e){
            Log::error($e->getMessage(), [
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            );

            $response = response()->json(
                [
                    'status'  => 'failed',
                    'message' => 'Internal error happened. Try again'
                ], 200
            );

            return $response;
        }

    }


}
