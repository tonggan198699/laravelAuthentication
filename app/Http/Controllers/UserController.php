<?php

namespace App\Http\Controllers;

use DateTimeZone;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use DateTime;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Crypt;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\Redis;

class UserController extends Controller
{

    public function show() {
        return User::all();
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['string', 'required'],
            'email' => ['required', 'email', 'unique:users'],
            'password' => ['required']
        ]);

        if ($validator->fails()) {
              return $validator->errors();
        }

        $password = $this->generatePassword($request->get('password'));
        $token = $this->generateToken();

        $expiryTime = env('EMAIL_VERIFICATION_TOKEN_LIFETIME');

        $user = new User([
            'name' => $request->get('name'),
            'email' => $request->get('email'),
            'password' => $password,
            'token' => $token,
            'token_expire_at' => $this->setTokenExpiralTime("+ {$expiryTime} seconds")
        ]);

        $expiryTimeInMinutes = $expiryTime / 60;

        $user->save();
        $user = User::all()->find($user->id);
        if ($user) {
            Auth::login($user);
            Mail::to($user->email)->send(new \App\Mail\MyMail($user, $token, $expiryTimeInMinutes));
            return 'success - please check your email to finish the verification process';
        }
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
              'email' => ['required', 'email'],
              'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            if ($user = Auth::user()) {
                if (is_null($user->email_verified_at)) {
                    return 'You email is not verified, please check your email!';
                }
                return 'You have successfully logged in!';
            }
            return 'your logging session has expired!';
        }

        return 'The provided credentials do not match our records!';
    }

    public function verify(Request $request)
    {
        $token = $request->segment(2);
        $users = User::where('token', $token)->get();

        foreach ($users as $user) {
            if (!$user) {
                return 'you have already verified';
            }

            if ($this->checkIfTokenAlreadyExipred($user->token_expire_at)) {
                return 'you token already expired, please request to resend verification email again';
            }

            if (is_null($user->token)) {
                $record = User::all()->find($user->id);
                $record->token = $token;
                $record->save();
            }
            if (is_null($user->email_verified_at)) {
                $record = User::all()->find($user->id);
                $record->email_verified_at = $this->getNow();
                $record->save();
                return 'you have successfully verified your email';
            }
        }

        return 'could not verify';
    }

    public function resend(Request $request)
    {
        $email = $request->input('email');
        $users = User::where('email', $email)->get();
        $expiryTime = env('EMAIL_VERIFICATION_TOKEN_LIFETIME');
        $expiryTimeInMinutes = $expiryTime / 60;

        foreach ($users as $user) {
            $token = is_null($user->token) ? $this->generateToken() : $user->token;
            $record = User::all()->find($user->id);
            $record->token_expire_at = $this->setTokenExpiralTime("+ {$expiryTime} seconds");
            $record->save();
            Mail::to($user->email)->send(new \App\Mail\MyMail($user, $token, $expiryTimeInMinutes));
            return 'success - your verification email has been sent out again!';
        }
    }

    public function generateToken()
    {
        return Uuid::uuid1()->toString();
    }

    public function generatePassword($password)
    {
        return Hash::make($password);
    }

//    public function checkIfUserIsLoggedIn(Request $request)
//    {
//        if ($user = Auth::user()) {
//            $request->session()->put($user->id, 'tggerdgdgdfgf');
//            return $request->session()->all();
//            //return $user->email;
//        }
//        return 'not logged in';
//    }

    public function getNow()
    {
        $now = new DateTime('now', new DateTimeZone('Europe/London'));
        return $now->format('Y-m-d H:i:s');
    }

    public function setTokenExpiralTime($time)
    {
        $now = new DateTime($time, new DateTimeZone('Europe/London'));
        return $now->format('Y-m-d H:i:s');
    }

    public function getTokenExpireAtTimestamp($time)
    {
        $londonTime = new DateTime(date('Y-m-d H:i:s', strtotime($time)), new DateTimeZone('Europe/London'));
        return $londonTime->getTimestamp();
    }

    public function checkIfTokenAlreadyExipred($time)
    {
        $now = new DateTime('now', new DateTimeZone('Europe/London'));
        $nowTimestamp = $now->getTimestamp();
        $expiryTimeStamp = $this->getTokenExpireAtTimestamp($time);

        if ($expiryTimeStamp < $nowTimestamp) {
            return true;
        }

        return false;
    }

    public function logout()
    {
        if (Auth::user()) {
            Auth::logout();
            return 'you have logged out';
        }
    }

}
