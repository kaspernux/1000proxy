<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Title;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use App\Models\User;
use App\Models\Address;
use App\Models\Order;

#[Title('Account Settings - 1000 PROXIES')]
class AccountSettings extends Component
{
    use WithFileUploads, LivewireAlert;

    // User profile properties
    public $user;
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

    protected $rules = [
        'name' => 'required|string|max:255',
        'email' => 'required|email|max:255',
        'phone' => 'nullable|string|max:20',
        'date_of_birth' => 'nullable|date|before:today',
        'bio' => 'nullable|string|max:500',
        'website' => 'nullable|url|max:255',
        'company' => 'nullable|string|max:255',
        'avatar' => 'nullable|image|max:2048',
    ];

    public function mount()
    {
        $this->user = Auth::guard('customer')->user();
        $this->name = $this->user->name;
        $this->email = $this->user->email;
        $this->phone = $this->user->phone;
        $this->date_of_birth = $this->user->date_of_birth?->format('Y-m-d');
        $this->current_avatar = $this->user->avatar;
        $this->bio = $this->user->bio;
        $this->website = $this->user->website;
        $this->company = $this->user->company;
        $this->timezone = $this->user->timezone ?? 'UTC';

        // Load addresses
        $this->loadAddresses();

        // Load notification preferences
        $this->loadNotificationPreferences();

        // Load privacy settings
        $this->loadPrivacySettings();

        // Load security settings
        $this->loadSecuritySettings();
    }

    public function loadAddresses()
    {
        $this->addresses = Address::where('user_id', $this->user->id)->get()->toArray();
    }

    public function loadNotificationPreferences()
    {
        $preferences = $this->user->notification_preferences ?? [];
        $this->email_notifications = array_merge($this->email_notifications, $preferences['email'] ?? []);
        $this->sms_notifications = array_merge($this->sms_notifications, $preferences['sms'] ?? []);
    }

    public function loadPrivacySettings()
    {
        $settings = $this->user->privacy_settings ?? [];
        $this->privacy_settings = array_merge($this->privacy_settings, $settings);
    }

    public function loadSecuritySettings()
    {
        $this->two_factor_enabled = $this->user->two_factor_secret !== null;
        $this->login_alerts = $this->user->login_alerts ?? true;
    }

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function updateProfile()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $this->user->id,
            'phone' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date|before:today',
            'bio' => 'nullable|string|max:500',
            'website' => 'nullable|url|max:255',
            'company' => 'nullable|string|max:255',
        ]);

        $this->user->update([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'date_of_birth' => $this->date_of_birth,
            'bio' => $this->bio,
            'website' => $this->website,
            'company' => $this->company,
            'timezone' => $this->timezone,
        ]);

        $this->alert('success', 'Profile updated successfully!', [
            'position' => 'bottom-end',
            'timer' => 3000,
            'toast' => true,
        ]);
    }

    public function updateAvatar()
    {
        $this->validate(['avatar' => 'required|image|max:2048']);

        // Delete old avatar if exists
        if ($this->current_avatar) {
            Storage::disk('public')->delete($this->current_avatar);
        }

        // Store new avatar
        $path = $this->avatar->store('avatars', 'public');

        $this->user->update(['avatar' => $path]);
        $this->current_avatar = $path;
        $this->avatar = null;

        $this->alert('success', 'Avatar updated successfully!', [
            'position' => 'bottom-end',
            'timer' => 3000,
            'toast' => true,
        ]);
    }

    public function removeAvatar()
    {
        if ($this->current_avatar) {
            Storage::disk('public')->delete($this->current_avatar);
            $this->user->update(['avatar' => null]);
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
        $this->validate([
            'current_password' => 'required',
            'new_password' => ['required', 'confirmed', Password::defaults()],
        ]);

        if (!Hash::check($this->current_password, (string) $this->user->password)) {
            $this->addError('current_password', 'Current password is incorrect.');
            return;
        }

        $this->user->update([
            'password' => Hash::make($this->new_password)
        ]);

        $this->current_password = '';
        $this->new_password = '';
        $this->new_password_confirmation = '';

        $this->alert('success', 'Password changed successfully!', [
            'position' => 'bottom-end',
            'timer' => 3000,
            'toast' => true,
        ]);
    }

    public function addAddress()
    {
        $this->resetAddressForm();
        $this->showAddressModal = true;
    }

    public function editAddress($id)
    {
        $address = Address::find($id);
        if ($address && $address->user_id === $this->user->id) {
            $this->editingAddress = $id;
            $this->newAddress = $address->toArray();
            $this->showAddressModal = true;
        }
    }

    public function saveAddress()
    {
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

        if ($this->editingAddress) {
            Address::where('id', $this->editingAddress)
                   ->where('user_id', $this->user->id)
                   ->update($this->newAddress);
            $message = 'Address updated successfully!';
        } else {
            Address::create(array_merge($this->newAddress, ['user_id' => $this->user->id]));
            $message = 'Address added successfully!';
        }

        $this->loadAddresses();
        $this->showAddressModal = false;
        $this->resetAddressForm();

        $this->alert('success', $message, [
            'position' => 'bottom-end',
            'timer' => 3000,
            'toast' => true,
        ]);
    }

    public function deleteAddress($id)
    {
        Address::where('id', $id)->where('user_id', $this->user->id)->delete();
        $this->loadAddresses();

        $this->alert('success', 'Address deleted successfully!', [
            'position' => 'bottom-end',
            'timer' => 3000,
            'toast' => true,
        ]);
    }

    public function setDefaultAddress($id)
    {
        Address::where('user_id', $this->user->id)->update(['is_default' => false]);
        Address::where('id', $id)->where('user_id', $this->user->id)->update(['is_default' => true]);
        $this->loadAddresses();

        $this->alert('success', 'Default address updated!', [
            'position' => 'bottom-end',
            'timer' => 3000,
            'toast' => true,
        ]);
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

        $this->user->update(['notification_preferences' => $preferences]);

        $this->alert('success', 'Notification preferences updated!', [
            'position' => 'bottom-end',
            'timer' => 3000,
            'toast' => true,
        ]);
    }

    public function updatePrivacySettings()
    {
        $this->user->update(['privacy_settings' => $this->privacy_settings]);

        $this->alert('success', 'Privacy settings updated!', [
            'position' => 'bottom-end',
            'timer' => 3000,
            'toast' => true,
        ]);
    }

    public function updateSecuritySettings()
    {
        $this->user->update(['login_alerts' => $this->login_alerts]);

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
            'total_orders' => Order::where('customer_id', $this->user->id)->count(),
            'total_spent' => Order::where('customer_id', $this->user->id)->where('status', 'delivered')->sum('grand_total'),
            'account_age_days' => $this->user->created_at->diffInDays(now()),
            'last_order' => Order::where('customer_id', $this->user->id)->latest()->first(),
        ];

        return view('livewire.account-settings', [
            'accountStats' => $accountStats,
        ]);
    }
}
