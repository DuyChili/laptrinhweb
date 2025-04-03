<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Hash;
use Session;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class CrudUserController extends Controller
{
     /**
     * Login page
     */
    public function login()
    {
        return view('crud_user.login');
    }

    /**
     * User submit form login
     */
    public function authUser(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);
    
        $credentials = $request->only('username', 'password');
        if (Auth::attempt($credentials)) {
         
            return redirect()->intended('list')->withSuccess('Signed in');
        }
    
    }
    

    /**
     * Registration page
     */
    public function createUser()
    {
        return view('crud_user.create');
    }

    /**
     * User submit form register
     */
    public function postUser(Request $request)
    {
        $request->validate([
            'username' => 'required|unique:users',
            'age' => 'required|integer|min:1',
            'github' => 'required|url',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6|confirmed',
            'img' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',  // Kiểm tra ảnh
        ]);
    
        // Xử lý upload ảnh
        if ($request->hasFile('img') && $request->file('img')->isValid()) {  // Kiểm tra ảnh có hợp lệ không
            $image = $request->file('img');
            $imageName = time() . '_' . $image->getClientOriginalName();
            $imagePath = $image->storeAs('images', $imageName, 'public');  // Lưu vào storage/app/public/images
        } else {
            $imagePath = null;  // Nếu không có ảnh, giá trị sẽ là null
        }
    
        // Tạo user mới
        $user = User::create([
            'username' => $request->username,
            'age' => $request->age,
            'github' => $request->github,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'img' => $imagePath,  // Lưu đường dẫn ảnh vào DB
        ]);
    
        return redirect("login")->withSuccess('Tạo tài khoản thành công! Vui lòng đăng nhập.');
    }
    

    /**
     * View user detail page
     */
    public function readUser(Request $request) {
        $user_id = $request->get('id');
        $user = User::find($user_id);

        return view('crud_user.read', ['messi' => $user]);
    }

    /**
     * Delete user by id
     */
    public function deleteUser(Request $request) {
        $user_id = $request->get('id');
        $user = User::destroy($user_id);

        return redirect("list")->withSuccess('You have signed-in');
    }

    /**
     * Form update user page
     */
    public function updateUser(Request $request)
    {
        $user_id = $request->get('id');
        $user = User::find($user_id);

        return view('crud_user.update', ['user' => $user]);
    }

    /**
     * Submit form update user
     */
    public function postUpdateUser(Request $request)
    {
        // Xử lý logic cập nhật người dùng
        $input = $request->all();

        $request->validate([
            'username' => 'required',
            'email' => 'required|email|unique:users,id,'.$input['id'],
            'password' => 'required|min:6|confirmed',
            'img' => 'nullable|file|mimes:jpeg,png,jpg,gif|max:2048', // Validation cho ảnh
        ]);

        $user = User::find($input['id']);
        $user->username = $request->username;
        $user->age = $request->age;
        $user->github = $request->github;
        $user->email = $request->email;
        
        // Nếu có ảnh, xử lý lưu ảnh
    if ($request->hasFile('img')) {
        // Nếu người dùng đã có ảnh cũ, xóa nó
        if ($user->img) {
            // Xóa ảnh cũ khỏi storage
            $oldImagePath = storage_path('app/public/' . $user->img);
            if (file_exists($oldImagePath)) {
                unlink($oldImagePath); // Xóa ảnh cũ
            }
        }

        // Lưu ảnh mới
        $image = $request->file('img');
        $imageName = time() . '_' . $image->getClientOriginalName();
        $imagePath = $image->storeAs('images', $imageName, 'public'); // Lưu ảnh vào thư mục public/images
        $user->img = $imagePath; // Lưu đường dẫn ảnh vào DB
    }
        
        // Nếu có mật khẩu mới, cập nhật mật khẩu
        if (!empty($request->password)) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

         return redirect("list")->withSuccess('Cập nhật thành công');

    }
    

    /**
     * List of users
     */
    public function listUser()
    {
        if(Auth::check()){
            $users = User::all();
            return view('crud_user.list', ['users' => $users]);
        }

        return redirect("login")->withSuccess('You are not allowed to access');
    }

    /**
     * Sign out
     */
    public function signOut() {
        Session::flush();
        Auth::logout();

        return Redirect('login');
    }
}