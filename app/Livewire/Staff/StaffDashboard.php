<?php

namespace App\Livewire\Staff;

use Livewire\Component;
use App\Models\User;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class StaffDashboard extends Component
{
    use WithPagination;

    public $search = '';
    public $roleFilter = '';
    public $statusFilter = '';
    public $showStats = true;

    protected $queryString = [
        'search' => ['except' => ''],
        'roleFilter' => ['except' => ''],
        'statusFilter' => ['except' => ''],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingRoleFilter()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function getStaffStatsProperty()
    {
        return [
            'total_staff' => User::count(),
            'active_staff' => User::where('is_active', true)->count(),
            'inactive_staff' => User::where('is_active', false)->count(),
            'admins' => User::where('role', 'admin')->count(),
            'support_managers' => User::where('role', 'support_manager')->count(),
            'sales_support' => User::where('role', 'sales_support')->count(),
            'with_telegram' => User::whereNotNull('telegram_chat_id')->count(),
            'recent_logins' => User::where('last_login_at', '>=', now()->subDays(7))->count(),
        ];
    }

    public function getStaffMembersProperty()
    {
        return User::query()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('email', 'like', '%' . $this->search . '%')
                      ->orWhere('username', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->roleFilter, function ($query) {
                $query->where('role', $this->roleFilter);
            })
            ->when($this->statusFilter !== '', function ($query) {
                $query->where('is_active', $this->statusFilter === 'active');
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);
    }

    public function toggleUserStatus($userId)
    {
        $currentUser = Auth::user();
        $user = User::findOrFail($userId);

        // Check permissions
        if (!Gate::allows('toggleStatus', $user)) {
            session()->flash('error', 'You do not have permission to perform this action.');
            return;
        }

        // Don't allow users to deactivate themselves
        if ($currentUser->id === $user->id) {
            session()->flash('error', 'You cannot deactivate your own account.');
            return;
        }

        $user->update(['is_active' => !$user->is_active]);

        $status = $user->is_active ? 'activated' : 'deactivated';
        session()->flash('success', "Staff member {$user->name} has been {$status}.");
    }

    public function resetPassword($userId)
    {
        $currentUser = Auth::user();
        $user = User::findOrFail($userId);

        if (!Gate::allows('resetPassword', $user)) {
            session()->flash('error', 'You do not have permission to reset this password.');
            return;
        }

        // In a real implementation, you would:
        // 1. Generate a secure temporary password
        // 2. Hash and save it
        // 3. Send it via email/notification
        // 4. Flag account for password reset on next login

        session()->flash('success', "Password reset initiated for {$user->name}. They will receive instructions via email.");
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->roleFilter = '';
        $this->statusFilter = '';
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.staff.staff-dashboard', [
            'staffStats' => $this->staffStats,
            'staffMembers' => $this->staffMembers,
        ]);
    }
}
