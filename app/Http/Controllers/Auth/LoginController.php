<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Shop;
use DB, Auth, File;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
	 
	public function postLogin(Request $request)
	{
		$request->validate([
            'email' => 'required',
            'password' => 'required',
        ]);
		 
        // Fetch user by email
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return back()->withInput()->with('error', 'These credentials do not match our records.');
        }

        // Check active status for non-admin users
        if (!$user->isAdminLevel() && $user->status == 0) {
            return back()->withInput()->with('error', 'This user is not active. Please contact the admin.');
        }

        // Attempt login
        if (Auth::attempt($request->only('email', 'password'))) {
            $user = Auth::user();
            $shop = $request->shop ?? '';
            $host = $request->host ?? '';

            if (!empty($shop) && !empty($host)) {
                $decodedShop = $shop;
                $decodedHost = $host;
                
                $registeredShop = Shop::where('shopify_domain', $decodedShop)->first();

                if ($registeredShop) {
                    $user->update(['shop' => $decodedShop]);
                    Shop::where('shopify_domain', $decodedShop)->update(['is_charge' => 1]);
                    return redirect('/apps/home?shop=' . $decodedShop . '&host=' . $decodedHost);
                }
            }

            return redirect()->intended('home')->with('success', 'You have successfully logged in.');
        }

        return back()->withInput()->with('error', 'These credentials do not match our records.');
	}
	
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    protected function loggedOut(Request $request)
    {
        return redirect('/login');
    }
}
