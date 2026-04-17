<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Jetstream\Jetstream;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        $validated = Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'nip' => ['nullable', 'string', 'max:255', 'unique:users'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => ['required', 'string', 'min:8', 'max:20', 'regex:/^[0-9]+$/', 'unique:users'],
            'gender' => ['required', 'string', 'in:male,female'],
            'address' => ['required', 'string', 'max:255'],
            'provinsi_kode' => ['required', 'string', 'max:13'],
            'kabupaten_kode' => ['required', 'string', 'max:13'],
            'kecamatan_kode' => ['required', 'string', 'max:13'],
            'kelurahan_kode' => ['required', 'string', 'max:13'],
            'password' => $this->passwordRules(),
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
        ], [
            'phone.regex' => __('Phone number must contain digits only.'),
        ])->validate();

        return User::create([
            'name' => $validated['name'],
            'nip' => $validated['nip'] ?? null,
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'gender' => $validated['gender'],
            'address' => $validated['address'],
            'group' => 'user',
            'language' => 'id',
            'provinsi_kode' => $validated['provinsi_kode'],
            'kabupaten_kode' => $validated['kabupaten_kode'],
            'kecamatan_kode' => $validated['kecamatan_kode'],
            'kelurahan_kode' => $validated['kelurahan_kode'],
            'password' => Hash::make($validated['password']),
        ]);
    }
}
