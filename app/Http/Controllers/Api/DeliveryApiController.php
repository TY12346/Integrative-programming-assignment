<?php
/**
 * FoodLink - Module 4 Delivery & Impact Tracking
 * Author : KHOO SHENG HAO
 * File   : app/Http/Controllers/Api/DeliveryApiController.php
 * Purpose: REST/JSON web service for creating, viewing and updating deliveries.
 *
 * Web-service mapping:
 *   GET   /api/v1/deliveries                       -> view all visible tasks
 *   GET   /api/v1/deliveries/{delivery}            -> view one task
 *   POST  /api/v1/deliveries                       -> create task
 *   PATCH /api/v1/deliveries/{delivery}/status     -> update task status
 *
 * Read requests require a bearer API token. State-changing requests additionally
 * require HMAC signing, timestamp validation and a unique request ID.
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DeliveryTaskResource;
use App\Models\DeliveryTask;
use App\Models\PartnerProfile;
use App\Models\User;
use App\Services\DeliveryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeliveryApiController extends Controller
{
    public function __construct(private readonly DeliveryService $deliveries)
    {
    }

    /** GET /api/v1/deliveries */
    public function index(Request $request): JsonResponse
    {
        $requestId = $this->validateRequestId($request);
        $this->ensureDeliveryApiRole($request);

        $query = DeliveryTask::query()
            ->with(['volunteer.user', 'impacts'])
            ->orderByDesc('assigned_at');

        if ($request->user()->role === User::ROLE_VOLUNTEER) {
            $query->where('volunteer_id', $request->user()->partnerProfile?->profile_id ?? 0);
        }

        if ($request->filled('status')) {
            $status = strtoupper((string) $request->query('status'));
            abort_unless(in_array($status, DeliveryTask::STATUSES, true), 422, 'Invalid delivery status filter.');
            $query->where('delivery_status', $status);
        }

        $deliveries = $query->limit(100)->get();

        return response()->json([
            'status' => 'success',
            'timestamp' => now()->toIso8601String(),
            'count' => $deliveries->count(),
            'data' => DeliveryTaskResource::collection($deliveries)->resolve($request),
        ]);
    }

    /** GET /api/v1/deliveries/{delivery} */
    public function show(Request $request, DeliveryTask $delivery): JsonResponse
    {
        $requestId = $this->validateRequestId($request);
        $this->ensureDeliveryApiRole($request, $delivery);

        $delivery->load(['volunteer.user', 'impacts']);

        return response()->json([
            'status' => 'success',
            'requestID' => $requestId,
            'timestamp' => now()->toIso8601String(),
            'data' => (
                new DeliveryTaskResource($delivery)
            )->resolve($request),
            ]);
    }

    /* Authored by Ong Tin Yin for module 3.1 consuming API of module 3.4 */
    public function volunteerObligations(
        Request $request,
        int $volunteer
    ): JsonResponse {
        $profile = PartnerProfile::query()
            ->whereHas('user', function ($query) {
                $query->where('role', User::ROLE_VOLUNTEER);
            })
            ->findOrFail($volunteer);

        $activeStatuses = [
            DeliveryTask::ASSIGNED,
            DeliveryTask::PICKED_UP,
        ];

        $activeDeliveryCount = DeliveryTask::query()
            ->where('volunteer_id', $profile->profile_id)
            ->whereIn('delivery_status', $activeStatuses)
            ->count();

        return response()->json([
            'status' => 'success',
            'requestID' => $request->query('requestID'),
            'timestamp' => now()->toISOString(),
            'data' => [
                'volunteerID' => (int) $profile->profile_id,
                'hasActiveDeliveries' => $activeDeliveryCount > 0,
                'activeDeliveryCount' => $activeDeliveryCount,
            ],
        ]);
    }
    
    
    /** POST /api/v1/deliveries */
    public function store(Request $request): JsonResponse
    {
        $this->ensureDeliveryApiRole($request);

        $data = $request->validate([
            'reservation_id' => ['required', 'integer', 'exists:reservations,reservation_id'],
            'volunteer_id' => ['nullable', 'integer', 'exists:partner_profiles,profile_id'],
            'delivery_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $delivery = $this->deliveries->create($request->user(), $data);
        $delivery->load(['volunteer.user', 'impacts']);

        return response()->json([
            'status' => 'success',
            'timestamp' => now()->toIso8601String(),
            'message' => 'Delivery task created successfully.',
            'data' => (new DeliveryTaskResource($delivery))->resolve($request),
        ], 201);
    }

    /** PATCH /api/v1/deliveries/{delivery}/status */
    public function updateStatus(Request $request, DeliveryTask $delivery): JsonResponse
    {
        $this->ensureDeliveryApiRole($request, $delivery);

        $data = $request->validate([
            'delivery_status' => ['required', Rule::in(DeliveryTask::STATUSES)],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $updated = $this->deliveries->updateStatus($delivery, $request->user(), $data);
        $updated->loadMissing(['volunteer.user', 'impacts']);

        return response()->json([
            'status' => 'success',
            'timestamp' => now()->toIso8601String(),
            'message' => 'Delivery status updated successfully.',
            'data' => (new DeliveryTaskResource($updated))->resolve($request),
        ]);
    }

    private function ensureDeliveryApiRole(Request $request, ?DeliveryTask $delivery = null): void
    {
        $user = $request->user();

        abort_unless(
            $user !== null && in_array($user->role, [User::ROLE_ADMIN, User::ROLE_VOLUNTEER], true),
            403,
            'Only administrators and volunteers may use the Delivery API.'
        );

        if ($delivery === null || $user->role === User::ROLE_ADMIN) {
            return;
        }

        abort_unless(
            $user->partnerProfile !== null
            && (int) $delivery->volunteer_id === (int) $user->partnerProfile->profile_id,
            403,
            'You may only access delivery tasks assigned to you.'
        );
    }
    
    private function validateRequestId(Request $request): string
        {
    $validated = $request->validate([
        'requestID' => [
            'required',
            'string',
            'max:100',
            'regex:/^[A-Za-z0-9_-]+$/',
        ],
    ]);

    return $validated['requestID'];
        }
}
