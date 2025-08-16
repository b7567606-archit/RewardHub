<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Traits\ImageUpload;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use DB;

class ApiController extends Controller
{
    use ImageUpload;
    protected $res;
    protected $users;

    public function __construct(Request $res , User $users){
        $this->res = $res;
        $this->users = $users;
    }

    public function check(){
        echo"Wroking";
    }

    public function one(Request $request)
    {
        try {
            // Ensure the method is POST
            if (!$request->isMethod('post')) {
                return response()->json(['message' => 'Invalid Method'], 405);
            }

            // Validate incoming request data
            $data = $request->only(['id', 'firstName', 'lastName', 'email', 'number', 'country', 'state', 'city', 'age', 'password']);
            
            // Check if user already exists by email or number
            $existing = $this->users
                ->where('email', $data['email'])
                ->orWhere('number', $data['number'])
                ->first();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'User already registered',
                ], 200); // 409 Conflict is more appropriate
            }

            $id = isset($data['id']) ? $data['id'] : null;
            // Create new user
            $user = $this->users->create([
                'id' => $id,
                'first_name' => $data['firstName'],
                'last_name'  => $data['lastName'],
                'email'      => $data['email'],
                'number'     => $data['number'],
                'country'    => $data['country'] ?? null,
                'state'      => $data['state'],
                'city'       => $data['city'],
                'age'        => $data['age'],
                'password'   => Hash::make($data['password']),
            ]);

            // Log the user in
            Auth::login($user);

            // Generate API token
            $token = $user->createToken('API Token')->accessToken;

            // Optionally save token to database (not recommended unless necessary)
            $user->active_status = '1';
            $user->token = $token;
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Successfully registered and logged in',
                'user_id' => $user->id,
                'token'   => $token,
            ], 200);

        } catch (\Exception $ex) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error'   => $ex->getMessage(),
            ], 500);
        }
    }

    public function two(Request $request)
    {
        try {
            // Ensure the method is POST
            if (!$request->isMethod('post')) {
                return response()->json(['message' => 'Invalid Method'], 405);
            }

            // Get email and password from request
            $email = $request->input('email');
            $password = $request->input('password');

            $creds = [
                'email' => $email,
                'password' => $password,
            ];

            // Attempt to authenticate user
            if (Auth::attempt($creds)) {
                $user = Auth::user();

                // Generate API token
                $token = $user->createToken('API Token')->accessToken;

                // Optionally save token to DB, if required
                $user->active_status = '1';
                $user->token = $token;
                $user->save();

                return response()->json([
                    'success' => true,
                    'message' => 'Login successful',
                    'user_id' => $user->id,
                    'token'   => $token,
                ], 200);
            }

            // Authentication failed
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 200);

        } catch (\Exception $ex) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error'   => $ex->getMessage(),
            ], 500);
        }
    }


    public function three(Request $request)
    {
        try {
            // Ensure the method is POST
            if (!$request->isMethod('post')) {
                return response()->json(['message' => 'Invalid Method'], 405);
            }

            $token = $request->header('token');
            $user = $this->users->where('token', $token)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found or invalid token',
                ], 200);
            }

            if ($request->hasFile('image')) {
                if ($user->image && file_exists(public_path($user->image))) {
                    unlink(public_path($user->image));
                }

                $imagePath = $this->upload($request->file('image'), 'userImages');
                $user->image = $imagePath;
            }

            $data = $request->only([
                'firstName', 'lastName', 'email', 'number',
                'country', 'state', 'city', 'age',
            ]);

            $updateData = [
                'first_name' => $data['firstName'] ?? $user->first_name,
                'last_name'  => $data['lastName'] ?? $user->last_name,
                'email'      => $data['email'] ?? $user->email,
                'number'     => $data['number'] ?? $user->number,
                'country'    => $data['country'] ?? $user->country,
                'state'      => $data['state'] ?? $user->state,
                'city'       => $data['city'] ?? $user->city,
                'age'        => $data['age'] ?? $user->age,
            ];

            // Update user record
            $user->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Profile Updated Successfully',
            ], 200);

        } catch (\Exception $ex) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error'   => $ex->getMessage(),
            ], 500);
        }
    }

    public function four(){
        try{

            // Ensure the method is POST
            if (!$this->res->isMethod('get')) {
                return response()->json(['message' => 'Invalid Method'], 405);
            }

            $token = $this->res->header('token');

            $existing = $this->users->select('first_name' ,'last_name' ,'email' ,'number' ,'country' ,'state' ,'city' ,'age')
                ->where('token', $token)
                ->first();

            if (!$existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not registered',
                ], 200); // 409 Conflict is more appropriate
            }
            else{
                return response()->json([
                    'success' => true,
                    'message' => 'Profile Get Successfully',
                    'details' => $existing,
                ], 200);
            }


        }
        catch (\Exception $ex) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error'   => $ex->getMessage(),
            ], 500);
        }
    }

    public function six(Request $request)
    {
        try {
            // Ensure the method is POST
            if (!$request->isMethod('post')) {
                return response()->json(['message' => 'Invalid Method'], 405);
            }

            // Get token from headers
            $token = $request->header('token');

            // Find the user by token
            $user = $this->users->where('token', $token)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found or invalid token',
                ], 200);
            }

            // Get passwords from request
            $oldPassword     = $request->input('old_password');
            $newPassword     = $request->input('new_password');
            $confirmPassword = $request->input('confirm_password');

            // Check if old password matches
            if (!Hash::check($oldPassword, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Old password is incorrect',
                ], 200);
            }

            // Check if new and confirm passwords match
            if ($newPassword !== $confirmPassword) {
                return response()->json([
                    'success' => false,
                    'message' => 'New password and confirm password do not match',
                ], 200);
            }

            // Update the user's password
            $user->password = Hash::make($newPassword);
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Password changed successfully',
            ], 200);

        } catch (\Exception $ex) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error'   => $ex->getMessage(),
            ], 500);
        }
    }

    public function seven(Request $request)
    {
        try {
            // Ensure the method is POST
            if (!$request->isMethod('post')) {
                return response()->json(['message' => 'Invalid Method'], 405);
            }

            // Get token from headers
            $token = $request->header('token');

            // Find the user by token
            $user = $this->users->where('token', $token)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found or invalid token',
                ], 200);
            }

            // Invalidate token (remove from DB)
            $user->token = null;
            $user->active_status = '0';
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully',
            ], 200);

        } catch (\Exception $ex) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error'   => $ex->getMessage(),
            ], 500);
        }
    }

}

     