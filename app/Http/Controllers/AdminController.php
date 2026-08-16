<?php
namespace App\Http\Controllers;

use App\Models\PartnerProfile;
use App\Models\User;
use App\Models\VerificationReview;
use App\Services\UserRoles\UserRoleFactoryResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function __construct(private readonly UserRoleFactoryResolver $roleFactories)
    {
    }
    
    public function users(Request $request)
    {
        $sort = in_array($request->sort, ['full_name', 'email', 'role', 'account_status', 'created_at'], true)
            ? $request->sort : 'created_at';
        $direction = $request->direction === 'asc' ? 'asc' : 'desc';
        $users = User::query()
            ->with('partnerProfile')
            ->when($request->role, fn ($q, $role) => $q->where('role', $role))
            ->when($request->status, fn ($q, $status) => $q->where('account_status', $status))
            ->when($request->verification_status, fn ($q, $status) => $q->whereHas('partnerProfile', fn ($p) => $p->where('verification_status', $status)))
            ->when($request->search, fn ($q, $search) => $q->where(fn ($query) => $query
                ->where('full_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->orderBy($sort, $direction)
            ->paginate(20)
            ->withQueryString();

        return view('admin.users', compact('users'));
    }

    public function storeAdmin(Request $request)
    {
        $data = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'phone_no' => 'nullable|string|max:50',
        ]);
        $data['role'] = User::ROLE_ADMIN;
        $this->roleFactories->resolve(User::ROLE_ADMIN)->register($data, true);

        return back()->with('message', 'Administrator account created.');
    }

    public function updateStatus(Request $request, User $user)
    {
        $data = $request->validate(['account_status' => ['required', Rule::in([
            User::STATUS_PENDING, User::STATUS_ACTIVE, User::STATUS_INACTIVE,
            User::STATUS_SUSPENDED, User::STATUS_DELETED,
        ])]]);

        if ($request->user()->is($user) && $data['account_status'] !== User::STATUS_ACTIVE) {
            return back()->with('error', 'You cannot deactivate your own administrator account.');
        }

        if ($data['account_status'] === User::STATUS_ACTIVE
            && $user->role !== User::ROLE_ADMIN
            && $user->partnerProfile?->verification_status !== 'APPROVED') {
            return back()->with('error', 'A partner account can only be activated after verification is approved.');
        }

        $user->update($data);

        return back()->with('message', 'Account status updated.');
    }

    public function verifications(Request $request)
    {
        $sort = in_array($request->sort, ['verification_status', 'created_at'], true)
            ? $request->sort : 'created_at';
        $direction = $request->direction === 'asc' ? 'asc' : 'desc';
        $profiles = PartnerProfile::query()
            ->with(['user', 'documents'])
            ->when($request->status, fn ($q, $status) => $q->where('verification_status', $status))
            ->when($request->role, fn ($q, $role) => $q->whereHas('user', fn ($u) => $u->where('role', $role)))
            ->when($request->search, fn ($q, $search) => $q->whereHas('user', fn ($u) => $u
                ->where('full_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->orderBy($sort, $direction)
            ->paginate(20)
            ->withQueryString();

        return view('admin.verifications', compact('profiles'));
    }

    public function verificationHistory(PartnerProfile $profile)
    {
        $profile->load(['user', 'documents', 'reviews.reviewer']);

        return view('admin.verification-history', compact('profile'));
    }

    public function review(Request $request, PartnerProfile $profile)
    {
        $data = $request->validate([
            'decision' => 'required|in:APPROVED,REJECTED',
            'remarks' => 'nullable|string|max:1000',
        ]);

        if (! $profile->documents()->where('document_status', 'PENDING')->exists()) {
            return back()->with('error', 'There are no pending documents to review.');
        }

        DB::transaction(function () use ($data, $profile, $request) {
            $profile->update(['verification_status' => $data['decision']]);
            $profile->documents()->where('document_status', 'PENDING')->update(['document_status' => $data['decision']]);
            $profile->user->update(['account_status' => $data['decision'] === 'APPROVED'
                ? User::STATUS_ACTIVE : User::STATUS_PENDING]);
            VerificationReview::create([
                'partner_id' => $profile->profile_id,
                'reviewed_by' => $request->user()->user_id,
                'decision' => $data['decision'],
                'remarks' => $data['remarks'] ?? null,
            ]);
        });

        return back()->with('message', 'Verification request reviewed.');
    }
}