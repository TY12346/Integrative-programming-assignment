<?php
/**
 * FoodLink - Module 3.3 Food Request Management
 * Author : NG JIA QIN
 * File   : app/Http/Controllers/Api/FoodRequestApiController.php
 * Purpose: Web service PROVIDER for the Food Request Management module. It
 *          publishes the module as a JSON REST API so that a mobile client, or
 *          another FoodLink module, can create requests, read their status and
 *          reserve donations without going through the Blade interface.
 *
 *          Every endpoint reuses the same Form Requests, policies, repository
 *          and service as the web controller, so the two entry points cannot
 *          drift apart.
 *
 *          Endpoints (all under /api/v1, bearer token + rate limited):
 *            GET    /requests                        list own requests
 *            POST   /requests                        create a request
 *            GET    /requests/{id}                   request detail
 *            PATCH  /requests/{id}                   edit a pending request
 *            POST   /requests/{id}/cancel            cancel a request
 *            GET    /requests/{id}/status            status + tracked quantities
 *            GET    /requests/{id}/reservations      reservations of a request
 *            POST   /requests/{id}/reservations      reserve a donation
 *            GET    /donations                       active donations (filter/search)
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BrowseDonationRequest;
use App\Http\Requests\ReserveDonationRequest;
use App\Http\Requests\StoreFoodRequestRequest;
use App\Http\Requests\UpdateFoodRequestRequest;
use App\Http\Resources\DonationResource;
use App\Http\Resources\FoodRequestResource;
use App\Http\Resources\ReservationResource;
use App\Models\FoodRequest;
use App\Models\PartnerProfile;
use App\Repositories\FoodRequestRepository;
use App\Services\FoodRequestService;
use App\Services\Gateways\DonationGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class FoodRequestApiController extends Controller
{
    public function __construct(
        private readonly FoodRequestRepository $requests,
        private readonly FoodRequestService $service,
        private readonly DonationGateway $donations,
    ) {
    }

    /** GET /api/v1/requests */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', FoodRequest::class);

        $page = $this->requests->dashboard($this->charityId($request), [
            'scope' => $request->query('scope', FoodRequestRepository::SCOPE_ALL),
            'status' => $request->query('status'),
            'keyword' => $request->query('keyword'),
            'sort' => $request->query('sort', 'deadline_asc'),
        ]);

        return $this->respond(FoodRequestResource::collection($page->getCollection()), [
            'page' => $page->currentPage(),
            'per_page' => $page->perPage(),
            'total' => $page->total(),
            'last_page' => $page->lastPage(),
        ]);
    }

    /** POST /api/v1/requests */
    public function store(StoreFoodRequestRequest $request): JsonResponse
    {
        $foodRequest = $this->service->create($this->charityProfile($request), $request->validated());

        return $this->respond(new FoodRequestResource($foodRequest->load('category')), status: 201);
    }

    /** GET /api/v1/requests/{foodRequest} */
    public function show(FoodRequest $foodRequest): JsonResponse
    {
        Gate::authorize('view', $foodRequest);

        return $this->respond(new FoodRequestResource(
            $foodRequest->load(['category', 'reservations.donation.category', 'reservations.deliveryTask'])
        ));
    }

    /** PATCH /api/v1/requests/{foodRequest} */
    public function update(UpdateFoodRequestRequest $request, FoodRequest $foodRequest): JsonResponse
    {
        $this->service->update($foodRequest, $request->validated());

        return $this->respond(new FoodRequestResource($foodRequest->fresh()->load('category')));
    }

    /** POST /api/v1/requests/{foodRequest}/cancel */
    public function cancel(Request $request, FoodRequest $foodRequest): JsonResponse
    {
        Gate::authorize('cancel', $foodRequest);

        $reason = $request->validate(['reason' => ['nullable', 'string', 'max:255']])['reason'] ?? null;

        return $this->respond(new FoodRequestResource($this->service->cancel($foodRequest, $reason)->load('category')));
    }

    /**
     * GET /api/v1/requests/{foodRequest}/status
     *
     * Lightweight endpoint published for the other modules: the Delivery and
     * Impact Tracking module (3.4) uses it to check whether a request still
     * needs food before it schedules a delivery.
     */
    public function status(FoodRequest $foodRequest): JsonResponse
    {
        Gate::authorize('view', $foodRequest);

        $progress = $foodRequest->progress();

        return $this->respond([
            'request_id' => (int) $foodRequest->request_id,
            'status' => $foodRequest->state()->code(),
            'label' => $foodRequest->state()->label(),
            'requested_quantity' => (float) $foodRequest->requested_quantity,
            'reserved_quantity' => round($progress->reserved, 2),
            'fulfilled_quantity' => (float) $foodRequest->fulfilled_quantity,
            'outstanding_quantity' => round($progress->outstanding(), 2),
            'progress_percentage' => $progress->percentage(),
            'unit' => $foodRequest->unit,
            'request_deadline' => $foodRequest->request_deadline?->toIso8601String(),
            'urgency' => $foodRequest->urgency,
        ]);
    }

    /** GET /api/v1/requests/{foodRequest}/reservations */
    public function reservations(FoodRequest $foodRequest): JsonResponse
    {
        Gate::authorize('view', $foodRequest);

        return $this->respond(ReservationResource::collection(
            $foodRequest->reservations()->with(['donation.category', 'deliveryTask'])->get()
        ));
    }

    /** POST /api/v1/requests/{foodRequest}/reservations */
    public function reserve(ReserveDonationRequest $request, FoodRequest $foodRequest): JsonResponse
    {
        $data = $request->validated();

        $reservation = $this->service->reserve(
            $foodRequest,
            (int) $data['donation_id'],
            (float) $data['reserved_quantity'],
            $data['pickup_deadline'],
        );

        return $this->respond(new ReservationResource($reservation->load('donation.category')), status: 201);
    }

    /** GET /api/v1/donations - active donations with filter and keyword search. */
    public function donations(BrowseDonationRequest $request): JsonResponse
    {
        Gate::authorize('create', FoodRequest::class);

        return $this->respond(DonationResource::collection($this->donations->activeDonations($request->criteria())));
    }

    /* ------------------------------------------------------------------ */

    /** Uniform {status, timestamp, data} envelope used by all FoodLink APIs. */
    private function respond(mixed $data, array $meta = [], int $status = 200): JsonResponse
    {
        $payload = [
            'status' => 'success',
            'timestamp' => now()->toIso8601String(),
            'data' => $data,
        ];

        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status);
    }

    /** The partner profile behind the authenticated token. */
    private function charityProfile(Request $request): PartnerProfile
    {
        $profile = $request->user()?->partnerProfile;

        if ($profile === null) {
            throw ValidationException::withMessages([
                'profile' => 'This account has no partner profile.',
            ]);
        }

        return $profile;
    }

    private function charityId(Request $request): int
    {
        return (int) $this->charityProfile($request)->profile_id;
    }
}
