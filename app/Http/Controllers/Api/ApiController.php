<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Survey;
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
    protected $survey;

    public function __construct(Request $res , User $users , Survey $survey){
        $this->res = $res;
        $this->users = $users;
        $this->survey = $survey;
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

            // Validate input (basic, no unique rule because we check manually)
            $validated = $request->validate([
                'firstName' => 'required|string|max:255',
                'lastName'  => 'required|string|max:255',
                'email'     => 'required|email',
                'number'    => 'required|string',
                'country'   => 'nullable|string|max:255',
                'state'     => 'nullable|string|max:255',
                'city'      => 'nullable|string|max:255',
                'age'       => 'nullable|integer|min:1',
                'password'  => 'required|string|min:6',
            ]);

            // 🔎 Check if email already exists
            if ($this->users->where('email', $validated['email'])->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'User already registered with this email',
                ], 200);
            }

            // Create new user
            $user = $this->users->create([
                'first_name' => $validated['firstName'],
                'last_name'  => $validated['lastName'],
                'email'      => $validated['email'],
                'number'     => $validated['number'],
                'country'    => $validated['country'] ?? null,
                'state'      => $validated['state'] ?? null,
                'city'       => $validated['city'] ?? null,
                'age'        => $validated['age'] ?? null,
                'password'   => Hash::make($validated['password']),
            ]);

            // Generate API token
            $token = $user->createToken('API Token')->accessToken;

            // Save status & token
            $user->active_status = '1';
            $user->token = $token;
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Successfully registered',
                'user_id' => $user->id,
                'token'   => $token,
            ], 201);

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

            // Collect update data
            $updateData = [];

            // Handle image upload
            if ($request->hasFile('image')) {
                // if ($user->image && file_exists(public_path($user->image))) {
                //     unlink(public_path($user->image));
                // }

                $imagePath = $this->upload($request->file('image'), 'userImages');
                $updateData['image'] = $imagePath;
            }

            // Handle other fields
            $data = $request->only([
                'firstName', 'lastName', 'email', 'number',
                'country', 'state', 'city', 'age',
            ]);

            $updateData['first_name'] = $data['firstName'] ?? $user->first_name;
            $updateData['last_name']  = $data['lastName'] ?? $user->last_name;
            $updateData['email']      = $data['email'] ?? $user->email;
            $updateData['number']     = $data['number'] ?? $user->number;
            $updateData['country']    = $data['country'] ?? $user->country;
            $updateData['state']      = $data['state'] ?? $user->state;
            $updateData['city']       = $data['city'] ?? $user->city;
            $updateData['age']        = $data['age'] ?? $user->age;

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

            $existing = $this->users->select('first_name' ,'last_name' ,'email' ,'number' ,'country' ,'state' ,'city' ,'age' , 'image')
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

    public function eight(Request $request)
    {
        try {
            // Ensure the method is POST
            if (!$request->isMethod('post')) {
                return response()->json(['message' => 'Invalid Method'], 405);
            }

            $survey_data = $request->survey_data;

            // Ensure options are arrays, not strings
            $formatted = [];
            foreach ($survey_data as $item) {
                $formatted[] = [
                    'question' => $item['question'],
                    'options'  => is_string($item['options']) 
                                    ? json_decode($item['options'], true) 
                                    : $item['options'],
                ];
            }

            // Save in DB as JSON
            $this->survey->create([
                'survey_data' => json_encode($formatted),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Survey data posted successfully',
            ], 200);

        } catch (\Exception $ex) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error'   => $ex->getMessage(),
            ], 500);
        }
    }

    public function nine(Request $request)
    {
        try {
            // Ensure the method is GET
            if (!$request->isMethod('get')) {
                return response()->json(['message' => 'Invalid Method'], 405);
            }

            // Fetch all surveys
            $surveys = $this->survey->get();

            // Decode survey_data JSON into array
            $surveys->transform(function ($survey) {
                $survey->survey_data = json_decode($survey->survey_data, true);
                return $survey;
            });

            return response()->json([
                'success' => true,
                'message' => 'Survey data retrieved successfully',
                'data'    => $surveys,
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

     