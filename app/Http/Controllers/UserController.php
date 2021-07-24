<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use App\User;
use JWTAuth;
use DB;

class UserController extends Controller
{
    public function index($value='')
    {
        return User::get()->toArray();
    }

    public function store(Request $request)
    {
        try
        {

            $data = $request->only('firstname', 'lastname', 'mobile', 'email', 'age', 'gender', 'city', 'password', 'u_id');
            $mobile_rules = 'required|regex:/[0-9]{10}/|numeric|unique:users';
            $email_rules = 'required|regex:/(.+)@(.+)\.(.+)/i|unique:users';
            if(!empty($data['u_id']))
            {
                $mobile_rules = 'required|regex:/[0-9]{10}/|numeric';
                $email_rules = 'required|regex:/(.+)@(.+)\.(.+)/i';
            }

            $validator = Validator::make($data, [
                'firstname' => 'required|regex:/^[a-zA-Z]+$/u|max:255',
                'lastname' => 'required|regex:/^[a-zA-Z]+$/u|max:255',
                'mobile' => $mobile_rules,
                'email' => $email_rules,
                'age' => 'required|min:18|max:200|numeric',
                'gender' => 'required|in:m,f,o',
                'city' => 'required',
                'password' => 'required|Size:6'
            ]);

            //Send failed response if request is not valid
            if ($validator->fails()) {
                return response()->json(['error' => $validator->messages()], 200);
            }

            $data = $request->all();

            $data['password'] = bcrypt($data['password']);

            DB::beginTransaction();

            if(!empty($data['u_id']))
            {
                $user = User::find($data['u_id']);
                if(empty($user))
                {
                    $msg = "User not found.";
                    throw new \Exception($msg);
                }
                else
                {
                    $user_mobile = User::where('mobile', $data['mobile'])->where('u_id', '!=', $user->u_id)->get();
                    $user_email = User::where('email', $data['email'])->where('u_id', '!=', $user->u_id)->get();
                    if(count($user_mobile))
                    {
                        $msg = "Mobile already taken.";
                        throw new \Exception($msg);
                    }
                    if(count($user_email))
                    {
                        $msg = "Email already taken.";
                        throw new \Exception($msg);
                    }
                    $msg = "User updated successfully.";
                }
            }
            else
            {
                $user = new User();
                $msg = "User created successfully.";
            }
            $user->fill($data);
            $user->save();

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => $msg,
                'data' => $user
            ], Response::HTTP_OK);
        }
        catch(\Throwable $th)
        {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        //valid credential
        $validator = Validator::make($credentials, [
            'email' => 'required|email',
            'password' => 'required|string|min:6|max:50'
        ]);

        //Send failed response if request is not valid
        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 200);
        }

        //Request is validated
        //Crean token
        try {
            if (! $token = JWTAuth::attempt($credentials)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Login credentials are invalid.',
                ], 400);
            }
        } catch (JWTException $e) {
        return $credentials;
            return response()->json([
                    'success' => false,
                    'message' => 'Could not create token.',
                ], 500);
        }
    
        //Token created, return with success response and jwt token
        return response()->json([
            'success' => true,
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        //valid credential
        $this->validate($request, [
            'token' => 'required',
        ]);
        //Request is validated, do logout        
        try {
            JWTAuth::invalidate($request->token);
 
            return response()->json([
                'success' => true,
                'message' => 'User has been logged out'
            ]);
        } catch (JWTException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry, user cannot be logged out'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($u_id, Request $request)
    {
        $user = User::find($u_id);
 
        return response()->json(['user' => $user]);
    }

    public function destroy(Request $request)
    {
        try {
            $u_id = $request->u_id;

            User::where('u_id', $u_id)->delete();
 
            return response()->json([
                'success' => true,
                'message' => 'User has been deleted'
            ]);
        } catch (JWTException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry, user cannot be deleted'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
