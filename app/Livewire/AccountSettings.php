<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Title;
use App\Livewire\Traits\LivewireAlertV4;
use App\Models\Customer;
use App\Models\Address;
use App\Models\Order;

#[Title('Account Settings - 1000 PROXIES')]
class AccountSettings extends Component
{
    use WithFileUploads, LivewireAlertV4;

    // User profile properties
    public $customer;
    public $name;
    public $email;
    public $phone;
    public $date_of_birth;
    public $avatar;
    public $current_avatar;
    public $bio;
    public $website;
    public $company;
    public $timezone;

    // Loading states
    public $is_loading_profile = false;
    public $is_loading_password = false;
    public $is_loading_avatar = false;
    public $is_loading_address = false;

    // Password change properties
    public $current_password;
    public $new_password;
    public $new_password_confirmation;

    // Address management
    public $addresses = [];
    public $newAddress = [
        'type' => 'billing',
        'first_name' => '',
        'last_name' => '',
        'company' => '',
        'address_line_1' => '',
        'address_line_2' => '',
        'city' => '',
        'state' => '',
        'postal_code' => '',
        'country' => '',
        'phone' => '',
        'is_default' => false,
    ];
    public $editingAddress = null;
    public $showAddressModal = false;

    // Notification preferences
    public $email_notifications = [
        'order_updates' => true,
        'promotional' => true,
        'security' => true,
        'newsletter' => false,
    ];
    public $sms_notifications = [
        'order_updates' => false,
        'security' => true,
    ];

    // Privacy settings
    public $privacy_settings = [
        'profile_visibility' => 'private',
        'show_email' => false,
        'show_phone' => false,
        'data_processing' => true,
    ];

    // Security settings
    public $two_factor_enabled = false;
    public $login_alerts = true;

    // Current active tab
    public $activeTab = 'profile';

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255|min:2',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date|before:today',
            'bio' => 'nullable|string|max:500',
            'website' => 'nullable|url|max:255',
            'company' => 'nullable|string|max:255',
            'avatar' => 'nullable|image|max:2048',
            'current_password' => 'required_with:new_password',
            'new_password' => 'required_with:current_password|min:8|confirmed',
        ];
    }

    public function mount()
    {
        // Check authentication
        if (!Auth::guard('customer')->check()) {
            return redirect('/login');
        }

        $this->customer = Auth::guard('customer')->user();
        $this->name = $this->customer->name;
        $this->email = $this->customer->email;
        $this->phone = $this->customer->phone;
        $this->date_of_birth = $this->customer->date_of_birth?->format('Y-m-d');
        $this->current_avatar = $this->customer->avatar;
        $this->bio = $this->customer->bio;
        $this->website = $this->customer->website;
        $this->company = $this->customer->company;
        $this->timezone = $this->customer->timezone ?? 'UTC';

        // Load addresses
        $this->loadAddresses();

        // Load notification preferences
        $this->loadNotificationPreferences();

        // Load privacy settings
        $this->loadPrivacySettings();

        // Load security settings
        $this->loadSecuritySettings();
    }

    public function getAccountStatsProperty()
    {
        $orders = $this->customer->orders();
        $totalOrders = $orders->count();
        $totalSpent = $orders->where('status', 'delivered')->sum('grand_amount');
        $lastOrder = $orders->latest()->first();
        
        // Calculate account age in days and hours
        $created = $this->customer->created_at;
        $now = now();
        $totalHours = $created->diffInHours($now);
        
        $days = intval($totalHours / 24);
        $hours = $totalHours % 24;
        
        $accountAgeFormatted = sprintf('%d days %d hours', $days, $hours);

        return [
            'total_orders' => $totalOrders,
            'total_spent' => $totalSpent,
            'last_order' => $lastOrder,
            'account_age_days' => $accountAgeFormatted,
        ];
    }

    public function loadAddresses()
    {
        $this->addresses = Address::where('customer_id', $this->customer->id)
            ->with(['countryRelation','cityRelation','postalCodeRelation'])
            ->get()
            ->map(function($a){
                $arr = $a->toArray();
                // provide normalized fields for UI convenience
                $arr['country_iso2'] = $a->countryRelation?->iso2 ?? $a['country'] ?? null;
                $arr['city_name'] = $a->cityRelation?->name ?? $a['city'] ?? null;
                $arr['postal_code_value'] = $a->postalCodeRelation?->postal_code ?? $a['postal_code'] ?? null;
                return $arr;
            })->toArray();
    }

    public function loadNotificationPreferences()
    {
        $preferences = $this->customer->notification_preferences ?? [];
        $this->email_notifications = array_merge($this->email_notifications, $preferences['email'] ?? []);
        $this->sms_notifications = array_merge($this->sms_notifications, $preferences['sms'] ?? []);
    }

    public function loadPrivacySettings()
    {
        $settings = $this->customer->privacy_settings ?? [];
        $this->privacy_settings = array_merge($this->privacy_settings, $settings);
    }

    public function loadSecuritySettings()
    {
        $this->two_factor_enabled = $this->customer->two_factor_secret !== null;
        $this->login_alerts = $this->customer->login_alerts ?? true;
    }

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function updateProfile()
    {
        $this->is_loading_profile = true;

        try {
            // Rate limiting
            $key = 'profile_update.' . $this->customer->id;
            if (RateLimiter::tooManyAttempts($key, 5)) {
                $seconds = RateLimiter::availableAt($key) - time();
                throw ValidationException::withMessages([
                    'name' => ["Too many update attempts. Please try again in {$seconds} seconds."],
                ]);
            }

            $this->validate([
                'name' => 'required|string|max:255|min:2',
                'email' => 'required|email|max:255|unique:customers,email,' . $this->customer->id,
                'phone' => 'nullable|string|max:20',
                'date_of_birth' => 'nullable|date|before:today',
                'bio' => 'nullable|string|max:500',
                'website' => 'nullable|url|max:255',
                'company' => 'nullable|string|max:255',
            ]);

            RateLimiter::hit($key, 300); // 5-minute window

            $this->customer->update([
                'name' => trim($this->name),
                'email' => strtolower(trim($this->email)),
                'phone' => $this->phone,
                'date_of_birth' => $this->date_of_birth,
                'bio' => $this->bio,
                'website' => $this->website,
                'company' => $this->company,
                'timezone' => $this->timezone,
            ]);

            // Clear rate limit on success
            RateLimiter::clear($key);

            $this->is_loading_profile = false;

            $this->alert('success', 'Profile updated successfully!', [
                'position' => 'bottom-end',
                'timer' => 3000,
                'toast' => true,
            ]);

            // Security logging
            Log::info('Customer profile updated', [
                'customer_id' => $this->customer->id,
                'email' => $this->customer->email,
                'ip' => request()->ip(),
            ]);

        } catch (ValidationException $e) {
            $this->is_loading_profile = false;
            throw $e;
        } catch (\Exception $e) {
            $this->is_loading_profile = false;
            Log::error('Profile update error', [
                'error' => $e->getMessage(),
                'customer_id' => $this->customer->id,
                'ip' => request()->ip()
            ]);
            
            $this->addError('name', 'An error occurred while updating your profile. Please try again.');
        }
    }

    public function updateAvatar()
    {
        $this->is_loading_avatar = true;

        try {
            // Rate limiting
            $key = 'avatar_update.' . $this->customer->id;
            if (RateLimiter::tooManyAttempts($key, 3)) {
                $seconds = RateLimiter::availableAt($key) - time();
                throw ValidationException::withMessages([
                    'avatar' => ["Too many upload attempts. Please try again in {$seconds} seconds."],
                ]);
            }

            $this->validate(['avatar' => 'required|image|max:2048']);

            RateLimiter::hit($key, 300);

            // Delete old avatar if exists
            if ($this->current_avatar) {
                Storage::disk('public')->delete($this->current_avatar);
            }

            // Store new avatar
            $path = $this->avatar->store('avatars', 'public');

            $this->customer->update(['avatar' => $path]);
            $this->current_avatar = $path;
            $this->avatar = null;

            RateLimiter::clear($key);
            $this->is_loading_avatar = false;

            $this->alert('success', 'Avatar updated successfully!', [
                'position' => 'bottom-end',
                'timer' => 3000,
                'toast' => true,
            ]);

        } catch (ValidationException $e) {
            $this->is_loading_avatar = false;
            throw $e;
        } catch (\Exception $e) {
            $this->is_loading_avatar = false;
            Log::error('Avatar update error', [
                'error' => $e->getMessage(),
                'customer_id' => $this->customer->id,
                'ip' => request()->ip()
            ]);
            
            $this->addError('avatar', 'Failed to update avatar. Please try again.');
        }
    }

    public function removeAvatar()
    {
        if ($this->current_avatar) {
            Storage::disk('public')->delete($this->current_avatar);
            $this->customer->update(['avatar' => null]);
            $this->current_avatar = null;

            $this->alert('success', 'Avatar removed successfully!', [
                'position' => 'bottom-end',
                'timer' => 3000,
                'toast' => true,
            ]);
        }
    }

    public function changePassword()
    {
        $this->is_loading_password = true;

        try {
            // Rate limiting
            $key = 'password_change.' . $this->customer->id;
            if (RateLimiter::tooManyAttempts($key, 3)) {
                $seconds = RateLimiter::availableAt($key) - time();
                throw ValidationException::withMessages([
                    'current_password' => ["Too many password change attempts. Please try again in {$seconds} seconds."],
                ]);
            }

            $this->validate([
                'current_password' => 'required',
                'new_password' => 'required|min:8|confirmed',
            ]);

            if (!Hash::check($this->current_password, (string) $this->customer->password)) {
                RateLimiter::hit($key, 300);
                $this->addError('current_password', 'Current password is incorrect.');
                $this->is_loading_password = false;
                return;
            }

            $this->customer->update([
                'password' => Hash::make($this->new_password)
            ]);

            $this->current_password = '';
            $this->new_password = '';
            $this->new_password_confirmation = '';

            RateLimiter::clear($key);
            $this->is_loading_password = false;

            $this->alert('success', 'Password changed successfully!', [
                'position' => 'bottom-end',
                'timer' => 3000,
                'toast' => true,
            ]);

            // Security logging
            Log::info('Customer password changed', [
                'customer_id' => $this->customer->id,
                'email' => $this->customer->email,
                'ip' => request()->ip(),
            ]);

        } catch (ValidationException $e) {
            $this->is_loading_password = false;
            throw $e;
        } catch (\Exception $e) {
            $this->is_loading_password = false;
            Log::error('Password change error', [
                'error' => $e->getMessage(),
                'customer_id' => $this->customer->id,
                'ip' => request()->ip()
            ]);
            
            $this->addError('current_password', 'An error occurred while changing your password. Please try again.');
        }
    }

    public function addAddress()
    {
        $this->resetAddressForm();
        $this->showAddressModal = true;
    }

    public function editAddress($id)
    {
        $address = Address::with(['countryRelation','cityRelation','postalCodeRelation'])->find($id);
        if ($address && $address->customer_id === $this->customer->id) {
            $this->editingAddress = $id;
            $arr = $address->toArray();
            $arr['country'] = $address->countryRelation?->iso2 ?? $arr['country'] ?? '';
            $arr['city'] = $address->cityRelation?->name ?? $arr['city'] ?? '';
            $arr['postal_code'] = $address->postalCodeRelation?->postal_code ?? $arr['postal_code'] ?? '';
            $this->newAddress = $arr;
            $this->showAddressModal = true;
        }
    }

    public function saveAddress()
    {
        $this->is_loading_address = true;

        try {
            // Rate limiting
            $key = 'address_save.' . $this->customer->id;
            if (RateLimiter::tooManyAttempts($key, 5)) {
                $seconds = RateLimiter::availableAt($key) - time();
                throw ValidationException::withMessages([
                    'newAddress.first_name' => ["Too many address update attempts. Please try again in {$seconds} seconds."],
                ]);
            }

            $this->validate([
                'newAddress.type' => 'required|in:billing,shipping',
                'newAddress.first_name' => 'required|string|max:255',
                'newAddress.last_name' => 'required|string|max:255',
                'newAddress.address_line_1' => 'required|string|max:255',
                'newAddress.city' => 'required|string|max:255',
                'newAddress.state' => 'required|string|max:255',
                'newAddress.postal_code' => 'required|string|max:20',
                'newAddress.country' => 'required|string|max:255',
            ]);

            RateLimiter::hit($key, 300);

            // Resolve normalized IDs when possible
            $addrData = $this->newAddress;
            try {
                if (!empty($addrData['country'])) {
                    $countryModel = \App\Models\Country::where('iso2', $addrData['country'])->orWhere('name', $addrData['country'])->first();
                    if ($countryModel) $addrData['country_id'] = $countryModel->id;
                }
                if (!empty($addrData['city']) && !empty($addrData['country_id'])) {
                    $cityModel = \App\Models\City::where('country_id', $addrData['country_id'])->where('name', $addrData['city'])->first();
                    if ($cityModel) $addrData['city_id'] = $cityModel->id;
                }
                if (!empty($addrData['postal_code']) && !empty($addrData['country_id'])) {
                    $pcModel = \App\Models\PostalCode::where('country_id', $addrData['country_id'])->where('postal_code', $addrData['postal_code'])->first();
                    if ($pcModel) $addrData['postal_code_id'] = $pcModel->id;
                }
            } catch (\Throwable $e) {
                Log::debug('Address normalization failed (saveAddress)', ['error' => $e->getMessage()]);
            }

            if ($this->editingAddress) {
                Address::where('id', $this->editingAddress)
                       ->where('customer_id', $this->customer->id)
                       ->update($addrData);
                $message = 'Address updated successfully!';
            } else {
                Address::create(array_merge($addrData, ['customer_id' => $this->customer->id]));
                $message = 'Address added successfully!';
            }

            $this->loadAddresses();
            $this->showAddressModal = false;
            $this->resetAddressForm();

            RateLimiter::clear($key);
            $this->is_loading_address = false;

            $this->alert('success', $message, [
                'position' => 'bottom-end',
                'timer' => 3000,
                'toast' => true,
            ]);

        } catch (ValidationException $e) {
            $this->is_loading_address = false;
            throw $e;
        } catch (\Exception $e) {
            $this->is_loading_address = false;
            Log::error('Address save error', [
                'error' => $e->getMessage(),
                'customer_id' => $this->customer->id,
                'ip' => request()->ip()
            ]);
            
            $this->addError('newAddress.first_name', 'An error occurred while saving the address. Please try again.');
        }
    }

    public function deleteAddress($id)
    {
        try {
            Address::where('id', $id)->where('customer_id', $this->customer->id)->delete();
            $this->loadAddresses();

            $this->alert('success', 'Address deleted successfully!', [
                'position' => 'bottom-end',
                'timer' => 3000,
                'toast' => true,
            ]);

        } catch (\Exception $e) {
            Log::error('Address deletion error', [
                'error' => $e->getMessage(),
                'customer_id' => $this->customer->id,
                'address_id' => $id,
                'ip' => request()->ip()
            ]);
            
            $this->alert('error', 'Failed to delete address. Please try again.', [
                'position' => 'bottom-end',
                'timer' => 3000,
                'toast' => true,
            ]);
        }
    }

    public function setDefaultAddress($id)
    {
        try {
            Address::where('customer_id', $this->customer->id)->update(['is_default' => false]);
            Address::where('id', $id)->where('customer_id', $this->customer->id)->update(['is_default' => true]);
            $this->loadAddresses();

            $this->alert('success', 'Default address updated!', [
                'position' => 'bottom-end',
                'timer' => 3000,
                'toast' => true,
            ]);

        } catch (\Exception $e) {
            Log::error('Default address update error', [
                'error' => $e->getMessage(),
                'customer_id' => $this->customer->id,
                'address_id' => $id,
                'ip' => request()->ip()
            ]);
            
            $this->alert('error', 'Failed to update default address. Please try again.', [
                'position' => 'bottom-end',
                'timer' => 3000,
                'toast' => true,
            ]);
        }
    }

    private function resetAddressForm()
    {
        $this->editingAddress = null;
        $this->newAddress = [
            'type' => 'billing',
            'first_name' => '',
            'last_name' => '',
            'company' => '',
            'address_line_1' => '',
            'address_line_2' => '',
            'city' => '',
            'state' => '',
            'postal_code' => '',
            'country' => '',
            'phone' => '',
            'is_default' => false,
        ];
    }

    public function updateNotificationPreferences()
    {
        $preferences = [
            'email' => $this->email_notifications,
            'sms' => $this->sms_notifications,
        ];

        $this->customer->update(['notification_preferences' => $preferences]);

        $this->alert('success', 'Notification preferences updated!', [
            'position' => 'bottom-end',
            'timer' => 3000,
            'toast' => true,
        ]);
    }

    public function updatePrivacySettings()
    {
        $this->customer->update(['privacy_settings' => $this->privacy_settings]);

        $this->alert('success', 'Privacy settings updated!', [
            'position' => 'bottom-end',
            'timer' => 3000,
            'toast' => true,
        ]);
    }

    public function updateSecuritySettings()
    {
        $this->customer->update(['login_alerts' => $this->login_alerts]);

        $this->alert('success', 'Security settings updated!', [
            'position' => 'bottom-end',
            'timer' => 3000,
            'toast' => true,
        ]);
    }

    public function downloadDataExport()
    {
        // Generate data export
        $this->alert('info', 'Data export started. You will receive an email when ready.', [
            'position' => 'bottom-end',
            'timer' => 5000,
            'toast' => true,
        ]);
    }

    public function deleteAccount()
    {
        // This would typically require additional confirmation
        $this->alert('error', 'Account deletion requires additional verification. Please contact support.', [
            'position' => 'bottom-end',
            'timer' => 5000,
            'toast' => true,
        ]);
    }

    public function render()
    {
        $accountStats = [
            'total_orders' => Order::where('customer_id', $this->customer->id)->count(),
            'total_spent' => Order::where('customer_id', $this->customer->id)->where('status', 'delivered')->sum('grand_amount'),
            'account_age_days' => $this->customer->created_at->diffInDays(now()),
            'last_order' => Order::where('customer_id', $this->customer->id)->latest()->first(),
        ];

        return view('livewire.account-settings', [
            'accountStats' => $accountStats,
        ]);
    }
}
