<?php
/**
 * FoodLink - Module 3.3 Food Request Management
 * Author : NG JIA QIN
 * File   : app/Http/Controllers/RequestController.php
 * Purpose: Web controller (the "C" of MVC) for the Food Request Management
 *          module. It covers all ten functions of the module:
 *            1. Create Food Request          -> create() / store()
 *            2. View Request Dashboard       -> index()
 *            3. Edit Request Details         -> edit() / update()
 *            4. Cancel Food Request          -> cancel()
 *            5. Track Reserved Quantity      -> show() / reserve() / releaseReservation()
 *            6. Monitor Fulfillment Deadline -> index() / show()
 *            7. Check Request Status         -> show()
 *            8. Display Active Donations     -> donations()
 *            9. Filter Donation Options      -> donations()
 *           10. Search Specific Donations    -> donations()
 *
 *          The controller holds no business rules: reads go through
 *          FoodRequestRepository, writes go through FoodRequestService, and
 *          donation data is fetched through DonationGateway.
 */

namespace App\Http\Controllers;

use App\Domain\RequestStatus\RequestState;
use App\Filters\Donation\ExpiryWindowFilter;
use App\Http\Requests\BrowseDonationRequest;
use App\Http\Requests\ReserveDonationRequest;
use App\Http\Requests\StoreFoodRequestRequest;
use App\Http\Requests\UpdateFoodRequestRequest;
use App\Models\FoodCategory;
use App\Models\FoodRequest;
use App\Models\PartnerProfile;
use App\Models\Reservation;
use App\Repositories\FoodRequestRepository;
use App\Services\FoodRequestService;
use App\Services\Gateways\DonationGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class RequestController extends Controller
{
    public function __construct(
        private readonly FoodRequestRepository $requests,
        private readonly FoodRequestService $service,
        private readonly DonationGateway $donations,
    ) {
    }

    /* ------------------------------------------------------------------ */
    /* 2 + 6. View Request Dashboard / Monitor Fulfillment Deadline        */
    /* ------------------------------------------------------------------ */

    public function index(Request $request)
    {
        Gate::authorize('viewAny', FoodRequest::class);

        $charity = $this->charity();

        $filters = [
            'scope' => $request->query('scope', FoodRequestRepository::SCOPE_ACTIVE),
            'status' => $request->query('status'),
            'keyword' => $request->query('keyword'),
            'sort' => $request->query('sort', 'deadline_asc'),
        ];

        return view('requests.index', [
            'requests' => $this->requests->dashboard($charity->profile_id, $filters),
            'summary' => $this->requests->summary($charity->profile_id),
            'filters' => $filters,
            'statuses' => RequestState::options(),
            'sorts' => FoodRequestRepository::sortOptions(),
        ]);
    }

    /* ------------------------------------------------------------------ */
    /* 1. Create Food Request                                              */
    /* ------------------------------------------------------------------ */

    public function create()
    {
        Gate::authorize('create', FoodRequest::class);

        return view('requests.form', [
            'foodRequest' => new FoodRequest(),
            'categories' => FoodCategory::orderBy('category_name')->get(),
            'units' => config('foodlink.request.units'),
        ]);
    }

    public function store(StoreFoodRequestRequest $request)
    {
        $foodRequest = $this->service->create($this->charity(), $request->validated());

        return redirect()
            ->route('requests.show', $foodRequest->request_id)
            ->with('message', 'Food request #'.$foodRequest->request_id.' has been submitted.');
    }

    /* ------------------------------------------------------------------ */
    /* 5 + 7. Track Reserved Quantity / Check Request Status               */
    /* ------------------------------------------------------------------ */

    public function show(FoodRequest $foodRequest)
    {
        Gate::authorize('view', $foodRequest);

        $foodRequest->load([
            'category',
            'reservations.donation.category',
            'reservations.donation.donor.user',
            'reservations.deliveryTask',
            'statusHistories.changedBy',
        ]);

        return view('requests.show', [
            'foodRequest' => $foodRequest,
            'progress' => $foodRequest->progress(),
            'history' => $foodRequest->statusHistories->sortByDesc('changed_at'),
        ]);
    }

    /* ------------------------------------------------------------------ */
    /* 3. Edit Request Details                                             */
    /* ------------------------------------------------------------------ */

    public function edit(FoodRequest $foodRequest)
    {
        Gate::authorize('update', $foodRequest);

        return view('requests.form', [
            'foodRequest' => $foodRequest,
            'categories' => FoodCategory::orderBy('category_name')->get(),
            'units' => config('foodlink.request.units'),
        ]);
    }

    public function update(UpdateFoodRequestRequest $request, FoodRequest $foodRequest)
    {
        $this->service->update($foodRequest, $request->validated());

        return redirect()
            ->route('requests.show', $foodRequest->request_id)
            ->with('message', 'Food request #'.$foodRequest->request_id.' has been updated.');
    }

    /* ------------------------------------------------------------------ */
    /* 4. Cancel Food Request                                              */
    /* ------------------------------------------------------------------ */

    public function cancel(Request $request, FoodRequest $foodRequest)
    {
        Gate::authorize('cancel', $foodRequest);

        $reason = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ])['reason'] ?? null;

        $this->service->cancel($foodRequest, $reason);

        return redirect()
            ->route('requests.index')
            ->with('message', 'Food request #'.$foodRequest->request_id.' has been cancelled and any reserved food released.');
    }

    /* ------------------------------------------------------------------ */
    /* 8 + 9 + 10. Display / Filter / Search active donations              */
    /* ------------------------------------------------------------------ */

    public function donations(BrowseDonationRequest $request)
    {
        Gate::authorize('create', FoodRequest::class);

        $charity = $this->charity();
        $criteria = $request->criteria();

        $donations = $this->donations->activeDonations($criteria);
        $openRequests = $this->requests->openRequests($charity->profile_id);

        return view('requests.donations', [
            'donations' => $donations,
            'criteria' => $criteria,
            'categories' => FoodCategory::orderBy('category_name')->get(),
            // Offered as filter values, taken from what is actually on the board.
            'storageTypes' => $donations->pluck('storage_type')->filter()->unique()->sort()->values(),
            'expiryWindows' => ExpiryWindowFilter::options(),
            'openRequests' => $openRequests,
            'selectedRequestId' => $request->integer('request_id') ?: null,
        ]);
    }

    /* ------------------------------------------------------------------ */
    /* 5. Reserve a donation against a request                             */
    /* ------------------------------------------------------------------ */

    public function reserve(ReserveDonationRequest $request, FoodRequest $foodRequest)
    {
        $data = $request->validated();

        $this->service->reserve(
            $foodRequest,
            (int) $data['donation_id'],
            (float) $data['reserved_quantity'],
            $data['pickup_deadline'],
        );

        return redirect()
            ->route('requests.show', $foodRequest->request_id)
            ->with('message', 'Donation reserved. The reserved quantity has been added to this request.');
    }

    /** Withdraw one reservation and return the food to the donor's stock. */
    public function releaseReservation(FoodRequest $foodRequest, Reservation $reservation)
    {
        Gate::authorize('reserve', $foodRequest);

        // Secure coding: confirm the reservation really belongs to this request
        // before acting on an id that came from the URL.
        if ((int) $reservation->request_id !== (int) $foodRequest->request_id) {
            abort(404);
        }

        $this->service->cancelReservation($reservation);

        return redirect()
            ->route('requests.show', $foodRequest->request_id)
            ->with('message', 'Reservation withdrawn and the quantity returned to the donor.');
    }

    /** The partner profile of the signed in charity; 403 when it is missing. */
    private function charity(): PartnerProfile
    {
        $profile = auth()->user()?->partnerProfile;

        abort_if($profile === null, 403, 'Please complete your organisation profile before using the request module.');

        return $profile;
    }
}
