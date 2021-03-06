<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Tymon\JWTAuth\JWTAuth;
use Gate;
use Validator;
use App\User;
use App\Repositories\UserRepository;

class UserController extends Controller
{
    /**
     * The user repository instance.
     * 
     * @var \App\Repository\UserRepository
     */
    protected $repository;

    /**
     * The authentication guard factory instance.
     *
     * @var \Tymon\JWTAuth\JWTAuth
     */
    protected $auth;

    /**
     * Create a new controller instance.
     *
     * @param \App\Repositories\UserRepository $repository
     * @param \Tymon\JWTAuth\JWTAuth  $auth
     * @return void
     */
    public function __construct(UserRepository $repository, JWTAuth $auth)
    {
        $this->repository = $repository;
        $this->auth = $auth;
    }

    /**
     * Users.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function users(Request $request)
    {
        $user = new User;

        if (Gate::denies("users", $user)) {
            return response()->json([
                "errs" => [],
                "errFor" => [],
                "msg" => trans("http.status.403"),
                "success" => false
            ], 403);
        }
        $query = $request->query();
        $page = isset($query["page"]) ? (preg_match("/[\d]+/", $query["page"]) ? $query["page"] : 1): 1;
        $per = isset($query["per"]) ? (preg_match("/[\d]+/", $query["per"]) ? $query["per"] : 10): 10;;
        $users = $this->repository->users($per, ["*"], "page", $page, [User::STATUS_ACTIVATED, User::STATUS_BANNED]);
        return response()->json([
            "errs" => [],
            "errFor" => [],
            "msg" => "",
            "success" => true,
            "users" => $users
        ]);
    }
    
    /**
     * Create user api.
     * 
     * @param \Illuminate\Http\Request $request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request)
    {
        $data = $request->all();
        $validator = $this->validateCreate($data);

        if ($validator->fails()) {
            return response()->json([
                "errs" => [],
                "errFor" => $validator->errors(),
                "msg" => trans("info.failed.validate"),
                "success" => false
            ], 400);
        }
        if ($this->repository->create($data)) {
            return response()->json([
                "errs" => [],
                "errFor" => [],
                "msg" => trans("info.success.create"),
                "success" => true
            ]);
        }
        return response()->json([
            "errs" => [],
            "errFor" => [],
            "msg" => trans("info.failed.create"),
            "success" => false
        ], 500);
    }

    /**
     * Read user.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $uid
     * @return \Illuminate\Http\JsonResponse
     */
    public function read(Request $request, $uid)
    {
        if (!$this->validateUid($uid)) {
            return response()->json([
                "errs" => [],
                "errFor" => [],
                "msg" => trans("http.status.400"),
                "success" => false
            ], 400);
        }
        
        $user = $this->repository->read($uid, [User::STATUS_ACTIVATED, User::STATUS_BANNED]);

        if (empty($user)) {
            return response()->json([
                "errs" => [],
                "errFor" => [],
                "msg" => trans("http.status.404"),
                "success" => false
            ], 404);
        }
        if (Gate::denies("read", $user)) {
            return response()->json([
                "errs" => [],
                "errFor" => [],
                "msg" => trans("http.status.403"),
                "success" => false
            ], 403);
        }
        $roles = $user->roles()->with("role")->get()->pluck("role")->pluck("name");

        return response()->json([
            "errs" => [],
            "errFor" => [],
            "msg" => "",
            "user" => $user,
            "roles" => $roles,
            "success" => true
        ]);
    }
    
    /**
     * Update user.
     * 
     * @param \Illuminate\Http\Request $request
     * @param string $uid
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $uid)
    {
        if (!$this->validateUid($uid)) {
            return response()->json([
                "errs" => [],
                "errFor" => [],
                "msg" => trans("http.status.400"),
                "success" => false
            ], 400);
        }
        
        $user = $this->repository->read($uid, [User::STATUS_ACTIVATED, User::STATUS_BANNED]);

        if (empty($user)) {
            return response()->json([
                "errs" => [],
                "errFor" => [],
                "msg" => trans("http.status.404"),
                "success" => false
            ], 404);
        }
        if (Gate::denies("update", $user)) {
            return response()->json([
                "errs" => [],
                "errFor" => [],
                "msg" => trans("http.status.403"),
                "success" => false
            ], 403);
        }
        $data = $request->all();
        $validator = $this->validateUpdate($data, $user->id);

        if ($validator->fails()) {
            return response()->json([
                "errs" => [],
                "errFor" => $validator->errors(),
                "msg" => trans("info.failed.validate"),
                "success" => false
            ], 400);
        }
        if (!$this->auth->user()->isAdmin() && isset($data["roles"])) {
            unset($data["roles"]);
        }
        if ($this->repository->update($user, $data)) {
            return response()->json([
                "errs" => [],
                "errFor" => [],
                "msg" => trans("info.success.update"),
                "success" => true
            ]);
        }
        return response()->json([
            "errs" => [],
            "errFor" => [],
            "msg" => trans("info.failed.update"),
            "success" => false
        ], 500);
    }

    /**
     * Delete user.
     * 
     * @param \Illuminate\Http\Request $request
     * @param string $uid
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request, $uid)
    {
        if (!$this->validateUid($uid)) {
            return response()->json([
                "errs" => [],
                "errFor" => [],
                "msg" => trans("http.status.400"),
                "success" => false
            ], 400);
        }
        
        $user = $this->repository->read($uid, [User::STATUS_BANNED, User::STATUS_ACTIVATED]);

        if (empty($user)) {
            return response()->json([
                "errs" => [],
                "errFor" => [],
                "msg" => trans("http.status.404"),
                "success" => false
            ], 404);
        }
        if (Gate::denies("delete", $user)) {
            return response()->json([
                "errs" => [],
                "errFor" => [],
                "msg" => trans("http.status.403"),
                "success" => false
            ], 403);
        }
        if ($this->repository->delete($user)) {
            return response()->json([
                "errs" => [],
                "errFor" => [],
                "msg" => trans("info.success.delete"),
                "success" => true
            ]);
        }
        return response()->json([
            "errs" => [],
            "errFor" => [],
            "msg" => trans("info.failed.delete"),
            "success" => false
        ], 500);
    }

    /**
     * Validate user uid.
     * 
     * @param string $uid
     * @return boolean
     */
    protected function validateUid($uid="")
    {
        if (preg_match("/[a-zA-Z0-9]{32}/", $uid)) {
            return true;
        }
        return false;
    }
    
    /**
     * Validate create request data.
     * 
     * @param array $data
     * @return Validator
     */
    protected function validateCreate($data)
    {
        return Validator::make($data, [
            "name" => "required|string|max:255",
            "thumbnail" => "max:255|active_url",
            "email" => "required|email|string|max:255|unique:users,email",
            "pass" => "required",
            "pass_verify" => "required|same:pass"
        ]);
    }

    /**
     * Validate update request data.
     * 
     * @param array $data
     * @param integer $userId
     * @return Validator
     */
    protected function validateUpdate($data, $userId)
    {
        $rules = [
            "name" => "string|max:255",
            "thumbnail" => "max:255|active_url",
            "email" => [
                "email",
                "string",
                "max:255",
                Rule::unique('users')->ignore($userId),
            ],
        ];

        if (isset($data["pass"]) || isset($data["pass_verify"])) {
            $rules += [
                "pass" => "required",
                "pass_verify" => "required|same:pass"
            ];
        }
        if (isset($data["roles"])) {
            $rules += [
                "roles.*" => "regex:/^[a-zA-Z0-9]+$/|exists:roles,name"
            ];
        }
        return Validator::make($data, $rules);
    }
}
