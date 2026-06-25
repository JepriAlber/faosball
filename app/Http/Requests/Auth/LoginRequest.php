<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;


class LoginRequest extends FormRequest
{

    /**
     * Authorization
     */
    public function authorize(): bool
    {
        return true;
    }



    /**
     * Validation Rules
     */
    public function rules(): array
    {
        return [

            'email' => [
                'required',
                'email',
            ],

            'password' => [
                'required',
            ],

        ];
    }



    /**
     * Validation Messages Bahasa Indonesia
     */
    public function messages(): array
    {
        return [

            'email.required' => 'Email wajib diisi.', 
            'email.email' => 'Format email tidak valid.', 
            'password.required' => 'Password wajib diisi.',

        ];
    }



    /**
     * Handle Authentication
     */
    public function authenticate(): void
    {


        $this->ensureIsNotRateLimited();

        /*
        |--------------------------------------------------------------------------
        | Login Credential
        |--------------------------------------------------------------------------
        */


        if (! Auth::attempt( [ 'email' => $this->email, 'password' => $this->password, ],  $this->boolean('remember') )) {

            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => 'Email atau password tidak valid.',
            ]);

        }

        RateLimiter::clear($this->throttleKey());

        $user = Auth::user();

        /*
        |--------------------------------------------------------------------------
        | Check User Status
        |--------------------------------------------------------------------------
        */

        if (!$user->status) {

            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'Akun Anda sedang dinonaktifkan.',
            ]);

        }

        /*
        |--------------------------------------------------------------------------
        | Super Admin Bypass Academy Check
        |--------------------------------------------------------------------------
        */

        if ($user->hasRole('Super Admin')) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Tenant User Academy Validation
        |--------------------------------------------------------------------------
        */

        if (!$user->id_academy) {

            Auth::logout();

            throw ValidationException::withMessages([
                'email' =>
                'Akun belum terhubung dengan academy.',
            ]);

        }


        $academy = $user->academy;

        if (!$academy) {

            Auth::logout();

            throw ValidationException::withMessages([
                'email' =>
                'Academy tidak ditemukan.',

            ]);

        }

        if (!$academy->status) {

            Auth::logout();

            throw ValidationException::withMessages([
                'email' =>
                'Academy sedang tidak aktif.',
            ]);

        }


    }



    /**
     * Prevent brute force login
     */
    protected function ensureIsNotRateLimited(): void
    {

        if (! RateLimiter::tooManyAttempts( $this->throttleKey(), 5 )) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn(
            $this->throttleKey()
        );

        throw ValidationException::withMessages([
            'email' =>
            "Terlalu banyak percobaan login. Silakan coba lagi dalam {$seconds} detik.",
        ]);

    }



    /**
     * Rate limiter key
     */
    public function throttleKey(): string
    {
        return Str::transliterate(  Str::lower( $this->string('email') ) .'|'. $this->ip()  );

    }

}