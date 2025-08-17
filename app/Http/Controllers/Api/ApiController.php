<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\UserEarnings;
use App\Models\Spin;
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
    protected $surveyAnswer;
    protected $userEarnings;
    protected $spin;

    public function __construct(Request $res , User $users , Survey $survey , SurveyAnswer $surveyAnswer , UserEarnings $userEarnings , Spin $spin){
        $this->res = $res;
        $this->users = $users;
        $this->survey = $survey;
        $this->surveyAnswer = $surveyAnswer;
        $this->userEarnings = $userEarnings;
        $this->spin = $spin;
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

            // Get token from headers
            $token = $request->header('token');
            $user = $this->users->where('token', $token)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or missing token',
                ], 401);
            }

            // Fetch surveys ordered by latest first
            $surveys = $this->survey->orderBy('id', 'desc')->get();

            // Filter surveys that the user has not filled
            $filtered = $surveys->filter(function ($survey) use ($user) {
                $userIds = $survey->user_id ? json_decode($survey->user_id, true) : [];
                return !in_array($user->id, $userIds);
            });

            // Take only the latest single survey
            $latestSurvey = $filtered->first();

            if (!$latestSurvey) {
                return response()->json([
                    'success' => true,
                    'message' => 'No new surveys available',
                    'data'    => null,
                ], 200);
            }

            // Decode survey_data JSON into array
            $latestSurvey->survey_data = json_decode($latestSurvey->survey_data, true);

            return response()->json([
                'success' => true,
                'message' => 'Survey data retrieved successfully',
                'data'    => $latestSurvey,
            ], 200);

        } catch (\Exception $ex) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error'   => $ex->getMessage(),
            ], 500);
        }
    }


    public function ten(Request $request)
    {
        try {
            if (!$request->isMethod('post')) {
                return response()->json(['message' => 'Invalid Method'], 405);
            }

            // Get token from headers
            $token = $request->header('token');
            $user = $this->users->where('token', $token)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or missing token',
                ], 401);
            }

            // Validate incoming request
            $validated = $request->validate([
                'survey_id'          => 'required',
                'survey_answer_data' => 'required|array', // expecting array of Q&A
            ]);

            // Find survey
            $survey = $this->survey->find($validated['survey_id']);
            if (!$survey) {
                return response()->json(['success' => false, 'message' => 'Survey not found'], 404);
            }

            // --- Step 1: Update Survey table with user_id list ---
            $userIds = $survey->user_id ? json_decode($survey->user_id, true) : [];
            if (!in_array($user->id, $userIds)) {
                $userIds[] = $user->id;
            }
            $survey->user_id = json_encode($userIds);
            $survey->save();

            // --- Step 2: Save survey answers in SurveyAnswer table ---
            $this->surveyAnswer->create([
                'survey_id'          => $validated['survey_id'],
                'user_id'            => $user->id,
                'survey_answer_data' => json_encode($validated['survey_answer_data']),
            ]);

            // --- Step 3: Calculate earnings ---
            $questionCount = count($validated['survey_answer_data']); 
            $surveyAmount = $questionCount * 0.10; // each Q worth 0.10

            // Save in earnings table
            $this->userEarnings->create([
                'user_id' => $user->id,
                'amount'  => $surveyAmount,
                'status'  => '1', //1 for complete
            ]);

            // --- Step 4: Update user wallet ---
            $oldWallet = $user->wallet ?? 0;
            $newWallet = $oldWallet + $surveyAmount;
            $user->wallet = $newWallet;
            $user->save();

            // --- Response ---
            return response()->json([
                'success'       => true,
                'message'       => 'Survey answer submitted successfully',
                'survey_amount' => $surveyAmount,
                'wallet_total'  => $newWallet,
            ], 200);

        } catch (\Exception $ex) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error'   => $ex->getMessage(),
            ], 500);
        }
    }


    public function eleven(Request $request)
    {
        try {
            // Ensure method is GET
            if (!$request->isMethod('get')) {
                return response()->json(['message' => 'Invalid Method'], 405);
            }

            // Get token from headers
            $token = $request->header('token');
            $user = $this->users->where('token', $token)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or missing token',
                ], 401);
            }

            // Fetch earnings for this user
            $getEarning = $this->userEarnings
                ->where('user_id', $user->id)
                ->orderBy('id', 'desc')
                ->get();

            // Calculate total earning
            $getTotalEarning = $this->userEarnings
                ->where('user_id', $user->id)
                ->sum('amount');

            return response()->json([
                'success'        => true,
                'message'        => 'User earnings retrieved successfully',
                'data'           => $getEarning,
                'total_earning'  => $getTotalEarning,
            ], 200);

        } catch (\Exception $ex) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error'   => $ex->getMessage(),
            ], 500);
        }
    }

   public function twelve(Request $request)
    {
        try {
            $spins = $this->spin->get();

            return response()->json([
                'success' => true,
                'message' => 'Spins details retrieved successfully',
                'data'    => $spins,
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

     