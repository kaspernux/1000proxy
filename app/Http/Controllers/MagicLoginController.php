<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;

class MagicLoginController extends Controller
{
    /**
     * Generate a signed login URL for a customer (24h validity by default).
     */
    public static function generateFor(Customer $customer, int $minutes = 60*24): string
    {
        return URL::temporarySignedRoute('magic.login', now()->addMinutes($minutes), [
            'user' => $customer->getKey(),
            'guard' => 'customer',
        ]);
    }

    /**
     * Accept the signed URL and log the customer in, then redirect.
     */
    public function __invoke(Request $request)
    {
        $guard = $request->get('guard', 'customer');
        $id = $request->get('user');
        if (!$request->hasValidSignature()) {
            abort(403);
        }

        if ($guard !== 'customer') {
            abort(403);
        }

        $customer = Customer::findOrFail($id);
        Auth::guard('customer')->login($customer, true);

    return redirect()->intended('/account');
    }
}
