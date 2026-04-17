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
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'nip' => ['string', 'max:255', 'unique:users'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => ['required', 'string', 'max:64', 'unique:users'],
            'gender' => ['required', 'string', 'in:male,female'],
            'address' => ['required', 'string', 'max:255'],
            'provinsi_kode' => ['required', 'string', 'max:13'],
            'kabupaten_kode' => ['required', 'string', 'max:13'],
            'kecamatan_kode' => ['required', 'string', 'max:13'],
            'kelurahan_kode' => ['required', 'string', 'max:13'],
            'password' => $this->passwordRules(),
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
        ])->validate();

        return User::create([
            'name' => $input['name'],
            'nip' => $input['nip'],
            'email' => $input['email'],
            'phone' => $input['phone'],
            'gender' => $input['gender'],
            'address' => $input['address'],
            'group' => 'user',
            'language' => 'id',
            'provinsi_kode' => $input['provinsi_kode'],
            'kabupaten_kode' => $input['kabupaten_kode'],
            'kecamatan_kode' => $input['kecamatan_kode'],
            'kelurahan_kode' => $input['kelurahan_kode'],
            'password' => Hash::make($input['password']),
        ]);
    }
}
