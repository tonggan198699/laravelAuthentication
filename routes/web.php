<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

//Route::get('/', function () {
//    return view('welcome');
//});

Route::post('/register',
    [UserController::class, 'register']
);

Route::get('/all',
    [UserController::class, 'show']
);

Route::get('/verify/{token}',
   [UserController::class, 'verify']
);

Route::get('/resend',
   [UserController::class, 'resend']
);

Route::get('/login',
   [UserController::class, 'login']
);

Route::get('logout',
   [UserController::class, 'logout']
);

//Route::get('/check',
//   [UserController::class, 'checkIfUserIsLoggedIn']
//);

//Route::get('/check1',
//           [UserController::class, 'check1']
//);

Route::get('/test',
           [UserController::class, 'test']
);


