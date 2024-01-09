<?php

use App\Models\Token;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/clients/saldo', function () {

        // Validações da request do front-end
        $cnpj = '123';

        // Obter token
        $token = Token::first();

        if (!$token || $token->created_at->addSeconds($token->expires_in) > now()) {
            $tokenResponse = Http::post(
                env('BACK_URL') . '/oauth/token',
                [
                    'grant_type' => 'client_credentials',
                    'client_id' => env('BACK_CLIENT_ID'),
                    'client_secret' => env('BACK_CLIENT_SECRET'),
                ]
            )->json();

            Token::updateOrCreate([
                'id' => $token->id ?? null,
            ], [
                'access_token' => $tokenResponse['access_token'],
                'token_type' => $tokenResponse['token_type'],
                'expires_in' => $tokenResponse['expires_in'],
            ]);
        }

        $token = Token::first();

        // Obter saldo
        $saldoResponse = Http::withHeaders([
            'Authorization' => $token->token_type . ' ' . $token->access_token,
        ])
            ->post(env('BACK_URL') . '/api/clients/' . $cnpj . '/balance')
            ->json();

        dd($saldoResponse);

    })->name('saldo');
});
