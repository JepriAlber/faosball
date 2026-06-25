<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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

        /**
         * Ambil user berdasarkan email
         */
            $user = User::with('academy')
                ->where('email', $this->email)
                ->first();

        /**
         * Email tidak ditemukan
         */
            if (!$user) {

                RateLimiter::hit( $this->throttleKey() );

                throw ValidationException::withMessages([ 
                    'email' => 'Email atau password tidak sesuai.'
                ]);

            }



        /**
         * Cek status user
         */
            if (!$user->status) {

                throw ValidationException::withMessages([
                    'email' => 'Akun Anda sedang tidak aktif. Silakan hubungi administrator.'
                ]);

            }



        /**
         * Cek status academy
         */
            if (!$user->academy || !$user->academy->status) {

                throw ValidationException::withMessages([
                    'email' => 'Academy Anda sedang tidak aktif.'
                ]);

            }



        /**
         * Cek password dan login
         */
            if (!Hash::check( $this->password, $user->password )) {


                RateLimiter::hit($this->throttleKey());


                throw ValidationException::withMessages([

                    'email' => 'Email atau password tidak sesuai.'

                ]);

            }



        /**
         * Login user
         */
            Auth::login( $user,  $this->boolean('remember') );



        /**
         * Update last login
         */
            $user->update([
                'last_login_at' => now()
            ]);



        /**
         * Clear rate limiter
         */
            RateLimiter::clear( $this->throttleKey() );

    }



    /**
     * Prevent brute force login
     */
    protected function ensureIsNotRateLimited(): void
    {

        if (!RateLimiter::tooManyAttempts( $this->throttleKey(), 5 )) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => "Terlalu banyak percobaan login. Silakan coba lagi dalam {$seconds} detik."
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