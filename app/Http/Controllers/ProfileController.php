<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\VerificationDocument;
use App\Services\UserRoles\UserRoleHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user()->load(['partnerProfile.documents', 'partnerProfile.reviews.reviewer']);
        $documentTypes = $user->role === User::ROLE_ADMIN ? [] : UserRoleHandler::for($user->role)->allowedDocumentTypes();

        return view('profile.show', compact('user', 'documentTypes'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone_no' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:1000',
        ]);

        $request->user()->update(['full_name' => $data['full_name'], 'phone_no' => $data['phone_no'] ?? null]);
        $request->user()->partnerProfile?->update(['address' => $data['address'] ?? null]);

        return back()->with('message', 'Profile updated.');
    }

    public function uploadDocument(Request $request)
    {
        $user = $request->user();
        abort_if($user->role === User::ROLE_ADMIN, 403);
        $types = UserRoleHandler::for($user->role)->allowedDocumentTypes();
        $data = $request->validate([
            'document_type' => ['required', Rule::in($types)],
            'document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $path = $data['document']->store('verification_documents', 'public');
        VerificationDocument::create([
            'partner_id' => $user->partnerProfile->profile_id,
            'document_type' => $data['document_type'],
            'file_path' => $path,
            'document_status' => 'PENDING',
        ]);
        $user->partnerProfile->update(['verification_status' => 'PENDING']);
        if ($user->account_status !== User::STATUS_ACTIVE) {
            $user->update(['account_status' => User::STATUS_PENDING]);
        }

        return back()->with('message', 'Verification document submitted for review.');
    }

    public function destroy(Request $request)
    {
        $user = $request->user();
        $blocker = UserRoleHandler::for($user->role)->deletionBlocker($user);

        if ($blocker) {
            return back()->with('error', $blocker);
        }

        $user->update(['account_status' => User::STATUS_DELETED]);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login')->with('message', 'Your account has been deleted.');
    }
}