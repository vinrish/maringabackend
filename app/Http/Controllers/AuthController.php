<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{

    /**
     * Create user
     *
     * @param  [string] name
     * @param  [string] email
     * @param  [string] password
     * @param  [string] password_confirmation
     * @return [string] message
     */
    public function register(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|',
            'c_password'=>'required|same:password',
        ]);

        $user = new User([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password)
        ]);
        if($user->save()){
            return response()->json([
                'message' => 'Successfully created user!'
            ], 201);
        }else{
            return response()->json(['error'=>'Provide proper details']);
        }
    }

    /**
     * Login user and create token
     *
     * @param  [string] email
     * @param  [string] password
     * @param  [boolean] remember_me
     * @return [string] access_token
     * @return [string] token_type
     * @return [string] expires_at
     */
    public function login(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
            'remember_me' => 'boolean'
        ]);

        $loginType = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' :
            (is_numeric($request->login) && strlen($request->login) == 10 ? 'phone' : 'adm_no');

//        $credentials = request(['email', 'password']);
        $credentials = [
            $loginType => $request->login,
            'password' => $request->password
        ];

        if(!Auth::attempt($credentials))
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        $user = $request->user();
        $tokenResult = $user->createToken('Personal Access Token');
        $token = $tokenResult->token;
        if ($request->remember_me)
            $token->expires_at = Carbon::now()->addWeeks(1);
        $token->save();

//        $permissions = auth()->user()->roles()
//            ->with('permissions')  // Eager load permissions with roles
//            ->get()
//            ->pluck('permissions') // Get permissions from roles
//            ->flatten()  // Flatten the nested collection of permissions
//            ->map(function ($permission) {
//                return [
//                    'action' => $permission->action,
//                    'subject' => $permission->subject,
////                    'conditions' => $permission->conditions, // Include conditions (optional)
//                ];
//            })
//            ->toArray();

        return response()->json([
            'accessToken' => $tokenResult->accessToken,
            'token_type' => 'Bearer',
            'expires_at' => Carbon::parse(
                $tokenResult->token->expires_at
            )->toDateTimeString(),
            'userData' => $user,
//            'userAbilityRules' => $permissions,
//            'session_id' => session()->getId(),
        ]);
    }

    public function profile(): \Illuminate\Http\JsonResponse
    {
        $userData = auth()->user();

        return response()->json([
            "status" => true,
            "message" => "Profile information",
            "data" => $userData,
            "id" => auth()->user()->id
        ]);
    }

    /**
     * Get the authenticated User
     *
     * @return [json] user object
     */
    public function user(Request $request): \Illuminate\Http\JsonResponse
    {
        $userData = auth()->user();
        $permissions = auth()->user()->roles()
            ->with('permissions')  // Eager load permissions with roles
            ->get()
            ->pluck('permissions') // Get permissions from roles
            ->flatten()  // Flatten the nested collection of permissions
            ->map(function ($permission) {
                return [
                    'action' => $permission->action,
                    'subject' => $permission->subject,
//                    'conditions' => $permission->conditions, // Include conditions (optional)
                ];
            })
            ->toArray();
        return response()->json([
            'userData' => $userData,
            'userAbilityRules' => $permissions,
        ]);
    }

    /**
     * Logout user (Revoke the token)
     *
     * @return [string] message
     */
    public function logout(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->user()->token()->revoke();
        return response()->json([
            'message' => 'Successfully logged out',
            'status' => 200
        ]);
    }

//    public function logout(): \Illuminate\Http\JsonResponse
//    {
//
//        $token = auth()->user()->token();
//        $token->revoke();
//
//        return response()->json([
//            "status" => true,
//            "message" => "User Logged out successfully"
//        ]);
//    }
}
