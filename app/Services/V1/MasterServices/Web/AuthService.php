<?php

namespace App\Services\V1\MasterServices\Web;

use App\Http\Resources\V1\Master\Web\UserResource;
use App\Models\User;
use App\Models\LoginSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;

class AuthService
{
    // public function register(array $payload): array
    //     {
    //         $user_id=Auth::user()->id;
    //         foreach (['company','warehouse','route','region','area','outlet_channel'] as $field) {
    //             if (!isset($payload[$field]) || is_null($payload[$field]) || $payload[$field] === '' || $payload[$field] === '?') {
    //                 $payload[$field] = null;
    //             } else {
    //                 $payload[$field] = is_array($payload[$field]) ? $payload[$field] : [$payload[$field]];
    //             }
    //         }
    //         $user = User::create([
    //             'name'            => $payload['name'] ?? null,
    //             'email'           => $payload['email'] ?? null,
    //             'username'        => $payload['username'] ?? null,
    //             'contact_number'  => $payload['contact_number'],
    //             'password'        => Hash::make($payload['password']),
    //             'profile_picture' => $payload['profile_picture'] ?? null,
    //             'role'            => $payload['role'] ?? 0,
    //             'status'          => $payload['status'] ?? 1,
    //             'street'          => $payload['street'] ?? null,
    //             'city'          => $payload['city'] ?? null,
    //             'zip'          => $payload['zip'] ?? null,
    //             'dob'          => $payload['dob'] ?? null,
    //             'country_id'          => $payload['country_id'] ?? null,
    //             'company'         => $payload['company'],
    //             'warehouse'       => $payload['warehouse'],
    //             'route'           => $payload['route'],
    //             // 'vehicle'         => $payload['vehicle'], // include if using
    //             'salesman'        => $payload['salesman'],
    //             'region'          => $payload['region'],
    //             'area'            => $payload['area'],
    //             'outlet_channel'  => $payload['outlet_channel'],
    //             'created_by'    => $user_id,
    //             'updated_user'    => $payload['updated_user'] ?? 0,
    //             'Created_Date'    => $payload['Created_Date'] ?? now(),
    //         ]);
    //         $tokenResult = $user->createToken('api-token');
    //         return [
    //             'user'         => new UserResource($user),
    //             'token_type'   => 'Bearer',
    //             'access_token' => $tokenResult->accessToken,
    //         ];
    //     }
    public function register(array $payload): array
    {
        $user_id = Auth::user()->id;

        $existing = User::where('email', $payload['email'])
            ->orWhere('username', $payload['email'])
            ->orWhere('email', $payload['username'])
            ->orWhere('username', $payload['username'])
            ->first();

        if ($existing) {
            throw ValidationException::withMessages([
                'email' => ['Email or Username already exists.'],
                'username' => ['Email or Username already exists.'],
            ]);
        }

        foreach (['company', 'warehouse', 'route', 'region', 'area', 'outlet_channel'] as $field) {
            if (!isset($payload[$field]) || is_null($payload[$field]) || $payload[$field] === '' || $payload[$field] === '?') {
                $payload[$field] = null;
            } else {
                $payload[$field] = is_array($payload[$field]) ? $payload[$field] : [$payload[$field]];
            }
        }
        $user = User::create([
            'name'            => $payload['name'] ?? null,
            'email'           => $payload['email'] ?? null,
            'username'        => $payload['username'] ?? null,
            'contact_number'  => $payload['contact_number'],
            'password'        => Hash::make($payload['password']),
            'profile_picture' => $payload['profile_picture'] ?? null,
            'role'            => $payload['role'] ?? 0,
            'status'          => $payload['status'] ?? 1,
            'street'          => $payload['street'] ?? null,
            'city'            => $payload['city'] ?? null,
            'zip'             => $payload['zip'] ?? null,
            'dob'             => $payload['dob'] ?? null,
            'country_id'      => $payload['country_id'] ?? null,
            'company'         => $payload['company'],
            'warehouse'       => $payload['warehouse'],
            'route'           => $payload['route'],
            'salesman'        => $payload['salesman'],
            'region'          => $payload['region'],
            'area'            => $payload['area'],
            'outlet_channel'  => $payload['outlet_channel'],
            'created_by'      => $user_id,
            'updated_user'    => $payload['updated_user'] ?? 0,
            'Created_Date'    => $payload['Created_Date'] ?? now(),
        ]);

        $tokenResult = $user->createToken('api-token');

        return [
            'user'         => new UserResource($user),
            'token_type'   => 'Bearer',
            'access_token' => $tokenResult->accessToken,
        ];
    }

    // public function login(array $payload): array
    //     {
    //         DB::beginTransaction();
    //         try {
    //             if (!Auth::attempt(['email' => $payload['email'], 'password' => $payload['password']])) {
    //                 throw ValidationException::withMessages([
    //                     'email' => ['The provided credentials are incorrect.'],
    //                 ]);
    //             }
    //             $user = Auth::user()->load([
    //                         'roleDetails:id,name' 
    //                     ]);
    //             $tokenResult = $user->createToken('api-token');
    //             $user->update(['Login_Date' => now()]);
    //             DB::commit();
    //             return [
    //                 'user'         => new UserResource($user),
    //                 'token_type'   => 'Bearer',
    //                 'access_token' => $tokenResult->accessToken,
    //                 'expires'      => $tokenResult->token->expires_at,
    //                 'tokenResult'  => $tokenResult,
    //             ];
    //         } catch (\Throwable $e) {
    //             DB::rollBack();
    //             throw $e;
    //         }
    //     }
    public function login(array $payload): array
    {
        DB::beginTransaction();
        try {
            $credentials = [
                filter_var($payload['email'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username'
                => $payload['email'], // the value entered by the user
                'password' => $payload['password']
            ];

            if (!Auth::attempt($credentials)) {
                throw ValidationException::withMessages([
                    'email' => ['The provided credentials are incorrect.'],
                ]);
            }

            $user = Auth::user()->load('roleDetails:id,name');
            $tokenResult = $user->createToken('api-token');
            $user->update(['Login_Date' => now()]);
            DB::commit();

            return [
                'user'         => new UserResource($user),
                'token_type'   => 'Bearer',
                'access_token' => $tokenResult->accessToken,
                'expires'      => $tokenResult->token->expires_at,
                'tokenResult'  => $tokenResult,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function me(User $id): UserResource
    {
        return new UserResource($id);
    }
    public function logout(User $user): void
    {
        $user->token()->revoke();
    }
    public function checkToken()
    {
        $user = Auth::user();
        $token = $user?->token();
        if (!$user || !$token) {
            return false;
        }
        return LoginSession::where('user_id', $user->id)
            ->where('token_id', $token->id)
            ->exists();
    }
    public function getUserList()
    {
        $user_id = Auth::user()->id;
        $users = User::with('roleDetails:id,name')
            // ->where('created_by',$user_id)
            ->orderBy('created_at', 'desc')
            ->get();
        return \App\Http\Resources\V1\Master\Web\UserResource::collection($users);
    }

    public function updateUser($uuid, array $payload): array
    {
        // dd($payload);
        $user = User::where('uuid', $uuid)->firstorFail();
        foreach (['company', 'warehouse', 'route', 'region', 'area', 'outlet_channel'] as $field) {
            if (isset($payload[$field])) {
                $payload[$field] = is_array($payload[$field]) ? $payload[$field] : [$payload[$field]];
            }
        }
        if (isset($payload['password'])) {
            $payload['password'] = \Hash::make($payload['password']);
        } else {
            unset($payload['password']);
        }
        // dd($payload);
        $user->update($payload);
        return [
            'user' => new \App\Http\Resources\V1\Master\Web\UserResource($user),
        ];
    }


    public function getUserbyUuid($uuid)
    {
        $user = User::where('uuid', $uuid)->firstorFail();
        return [
            'user' => new \App\Http\Resources\V1\Master\Web\UserResource($user),
        ];
    }

    public function checkUser(string $query): bool
    {
        return User::where(function ($q) use ($query) {
            $q->where('email', $query)
                ->orWhere('username', $query);
        })->exists();
    }

    public function changePassword(User $user, string $oldPassword, string $newPassword): array
    {
        // 🔐 Check old password
        if (!Hash::check($oldPassword, $user->password)) {
            return [
                'status'  => 'error',
                'message' => 'Old password does not match',
            ];
        }

        // 🔁 Update password
        $user->update([
            'password'      => Hash::make($newPassword),
            'updated_user'  => $user->id,
            'Modifier_Id'   => $user->id,
            'Modifier_Name' => $user->name,
            'Modifier_Date' => now(),
        ]);

        return [
            'status'  => 'success',
            'message' => 'Password changed successfully',
        ];
    }
}
