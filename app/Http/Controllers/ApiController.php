<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\File;

class ApiController extends Controller
{
    public function getFlights(Request $request) {//max number of api calls per user is 50
        if($request->user()->api_calls_limit<=50){
            $request->user()->api_calls_limit++;
            $request->user()->save();//saving the new update to the database
            $client = new Client();
            $data = [];
            $air_jazz_data = [];
            $air_moon_data = [];
            $air_beam_data = [];
            //air jazz request 
            $air_jazz_req = $client->request('GET', 'https://my.api.mockaroo.com/air-jazz/flights?key=dd764f40');
            if($air_jazz_req->getStatusCode()==502){//if the service is not available
                $air_jazz_data=(array)json_decode(Storage::get('air_jazz_flights.json')); /*load data from the last stored result*/
            }else{
                $flights = (array)json_decode($air_jazz_req->getBody()->getContents());
                foreach($flights as $flight){
                    $air_jazz_data[] = [
                        'provider' => 'AIR_JAZZ',
                        'price' => $flight->price,
                        'departure_time' => $flight->dtime,
                        'arrival_time' => $flight->atime
                    ];
                }
                Storage::disk('public')->put('air_jazz_flights.json',json_encode($air_jazz_data));//always storing the last result to use in case the API is down to provide the user with the last update before the service experienced a downtime 
            }
            
            //air moon request
            $air_moon_req = $client->request('GET', 'https://my.api.mockaroo.com/air-moon/flights?key=dd764f40',['connect_timeout' => 3]); //setting a 3 seconds timeout to wait for the server to respond
            if(!$air_moon_req){
                //in case the service took too long we load the data from the file in which we saved the last response
                $air_moon_data=(array)json_decode(Storage::get('air_moon_flights.json.json')); /*load data from the last stored result*/
                /*it would be great to launch a background job here to update the file because this
                will help us to always get updated data in case the API repeatedly responded after 3 seconds.
                */
            }else{
                $flights = (array)json_decode($air_moon_req->getBody()->getContents());
                foreach($flights as $flight){
                    $air_moon_data[] = [
                        'provider' => 'AIR_MOON',
                        'price' => $flight->price,
                        'departure_time' => $flight->departure_time,
                        'arrival_time' => $flight->arrival_time
                    ];
                }
                Storage::disk('public')->put('air_moon_flights.json',json_encode($air_moon_data));//always storing the last result to use in case the API is down to provide the user with the last update before the service experienced a downtime 
            }
            
            
            //air beam request
            $air_beam_req = $client->request('GET', 'https://my.api.mockaroo.com/air-beam/flights?key=dd764f40');
            $flights = $air_beam_req->getBody()->getContents();
            //delete first line
            $flights = preg_replace('/^.+\n/', '', $flights);

            $line = strtok($flights, "\n");
            while ($line !== false) {
                $line_as_array = explode(',', $line);
                $air_beam_data[] = [
                    'provider' => 'AIR_BEAM',
                    'price' => $line_as_array[1],
                    'departure_time' => $line_as_array[2],
                    'arrival_time' => $line_as_array[3]
                ];
                $line = strtok("\n");
            }
            //concatenating the 3 tables | data aggregation
            $data=array_merge($air_beam_data,$air_jazz_data,$air_moon_data);
            //sorting the data
            usort($data, function ($a, $b) {
                return $a['price'] > $b['price'];
            });
            //limit 50 flights
            $final_data = [];
            for($i = 0; $i < 50; $i++){
                $final_data[] = [
                    'provider' => $data[$i]['provider'],
                    'price' => $data[$i]['price'],
                    'departure_time' => $data[$i]['departure_time'],
                    'arrival_time' => $data[$i]['arrival_time']
                ];
            }

            

            //50 sorted flights from 3 providers
            return response()->json($final_data,200);
        }else{
            return response()->json([
                'message' => 'you have exceeded your allowed number of APIs calls'
            ],405);
        }
        
    }


}
