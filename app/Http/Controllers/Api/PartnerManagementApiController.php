<?php

/**
 Author: Ong Tin Yin
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PartnerProfile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PartnerManagementApiController extends Controller
{
    public function status(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->query(), [
            'requestID' => 'required|string|max:100',
            'timestamp' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'requestID' => $request->query('requestID'),
                'timestamp' => now()->toISOString(),
                'message' => 'Invalid API request.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $profile = PartnerProfile::with('user')->find($id);

        if (! $profile) {
            return response()->json([
                'status' => 'error',
                'requestID' => $request->query('requestID'),
                'timestamp' => now()->toISOString(),
                'message' => 'Partner profile not found.',
            ], 404);
        }

        $viewer = $request->user();

        $isAdministrator =
            $viewer?->role === User::ROLE_ADMIN;

        $isOwner = (int) (
            $viewer?->partnerProfile?->profile_id ?? 0
        ) === $profile->profile_id;

        $isModule32 =
            $request->attributes->get('authenticated_module')
            === 'module-3-2';

        if (! $isAdministrator && ! $isOwner && ! $isModule32) {
            return response()->json([
                'status' => 'error',
                'requestID' => $request->query('requestID'),
                'timestamp' => now()->toISOString(),
                'message' => 'Access denied. You cannot view this partner.',
            ], 403);
        }

        $eligible = $profile->user->account_status === User::STATUS_ACTIVE
            && $profile->verification_status === 'APPROVED';

        return response()->json([
            'status' => 'success',
            'requestID' => $request->query('requestID'),
            'timestamp' => now()->toISOString(),
            'data' => [
                'partnerID' => $profile->profile_id,
                'role' => $profile->user->role,
                'accountStatus' => $profile->user->account_status,
                'verificationStatus' => $profile->verification_status,
                'eligibleForRoleFeatures' => $eligible,
            ],
        ]);
    }
}