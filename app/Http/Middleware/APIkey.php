<?php

namespace App\Http\Middleware;

use App\User;

use Closure;

class APIkey
{ 

    public function handle($request, Closure $next)

    { 

       if ($request->access_key == '') {

            return redirect('/');


        } else { 


            $users = User::where('access_key', $request->access_key)->count();
            if ($users != 1) { 

              return response("Invalid access key");
            
            } else { 

              return $next($request);

            }

        } 

   }
}   