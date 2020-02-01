<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;

class ApiController extends Controller
{
    public function getFlights() {
        $client = new Client();
        $data = [];
        //air jazz request 
        $air_jazz_req = $client->request('GET', 'https://my.api.mockaroo.com/air-jazz/flights?key=dd764f40');
        $flights = (array)json_decode($air_jazz_req->getBody()->getContents());
        foreach($flights as $flight){
            $data[] = [
                'provider' => 'AIR_JAZZ',
                'price' => $flight->price,
                'departure_time' => $flight->dtime,
                'arrival_time' => $flight->atime
            ];
        }
        //air moon request
        $air_moon_req = $client->request('GET', 'https://my.api.mockaroo.com/air-moon/flights?key=dd764f40');
        $flights = (array)json_decode($air_moon_req->getBody()->getContents());
        foreach($flights as $flight){
            $data[] = [
                'provider' => 'AIR_MOON',
                'price' => $flight->price,
                'departure_time' => $flight->departure_time,
                'arrival_time' => $flight->arrival_time
            ];
        }
        //air beam request
        $air_beam_req = $client->request('GET', 'https://my.api.mockaroo.com/air-beam/flights?key=dd764f40');
        $flights = $air_beam_req->getBody()->getContents();
        //delete first line
        $flights = preg_replace('/^.+\n/', '', $flights);

        $line = strtok($flights, "\n");
        while ($line !== false) {
            $line_as_array = explode(',', $line);
            $data[] = [
                'provider' => 'AIR_BEAM',
                'price' => $line_as_array[1],
                'departure_time' => $line_as_array[2],
                'arrival_time' => $line_as_array[3]
            ];
            $line = strtok("\n");
        }
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
        return $final_data;
    }
}
