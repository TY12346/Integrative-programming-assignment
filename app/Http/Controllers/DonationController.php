<?php

namespace App\Http\Controllers;

use App\Filters\Donation\CategoryFilter;
use App\Filters\Donation\DonationFilterPipeline;
use App\Filters\Donation\DonationStatusFilter;
use App\Filters\Donation\ExpiryWindowFilter;
use App\Filters\Donation\KeywordFilter;
use App\Filters\Donation\LocationFilter;
use App\Filters\Donation\MinQuantityFilter;
use App\Filters\Donation\StorageTypeFilter;
use App\Models\DonationPhoto;
use App\Models\DonationStatusHistory;
use App\Models\FoodCategory;
use App\Models\FoodDonation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class DonationController extends Controller
{
    public function index()
    {
        $donations = FoodDonation::with('category')
            ->where('donor_id', auth()->user()->partnerProfile->profile_id)
            ->get();

        return view('donations.index', compact('donations'));
    }

    public function available(Request $request)
    {
        $criteria = $this->availableCriteria($request);

        $query = FoodDonation::query()->with(['category', 'donor.user']);

        // Default available board: only AVAILABLE and not past expiry.
        // A explicit status filter may widen the list; CANCELLED/EXPIRED are
        // never shown unless the user selects that status.
        if (empty($criteria['donation_status'])) {
            $query->where('donation_status', 'AVAILABLE')
                ->where('expiry_datetime', '>', now());
        } elseif ($criteria['donation_status'] === 'AVAILABLE') {
            $query->where('expiry_datetime', '>', now());
        }

        $this->browseFilterPipeline()->apply($query, $criteria);

        $donations = $query->orderBy('expiry_datetime')->get();

        return view('donations.available', [
            'donations' => $donations,
            'categories' => FoodCategory::orderBy('category_name')->get(),
            'storageTypes' => FoodDonation::query()
                ->whereNotNull('storage_type')
                ->where('storage_type', '!=', '')
                ->distinct()
                ->orderBy('storage_type')
                ->pluck('storage_type'),
            'expiryWindows' => ExpiryWindowFilter::options(),
            'statuses' => DonationStatusFilter::options(),
            'criteria' => $criteria,
        ]);
    }

    /**
     * Browse pipeline for /donations/available.
     * Reuses the shared DonationFilter strategy classes without changing the
     * Module 3.3 FoodRequestServiceProvider singleton registration.
     */
    private function browseFilterPipeline(): DonationFilterPipeline
    {
        return new DonationFilterPipeline(
            new KeywordFilter(),
            new CategoryFilter(),
            new LocationFilter(),
            new StorageTypeFilter(),
            new MinQuantityFilter(),
            new ExpiryWindowFilter(),
            new DonationStatusFilter(),
        );
    }

    /** @return array<string, mixed> */
    private function availableCriteria(Request $request): array
    {
        if ($request->filled('keyword')) {
            $keyword = preg_replace('/[\x00-\x1F\x7F]/u', '', (string) $request->input('keyword'));
            $request->merge(['keyword' => trim((string) $keyword)]);
        }

        $validated = $request->validate([
            'keyword' => ['nullable', 'string', 'max:60'],
            'category_id' => ['nullable', 'integer', 'exists:food_categories,category_id'],
            'location' => ['nullable', 'string', 'max:255'],
            'min_quantity' => ['nullable', 'numeric', 'gte:0'],
            'expires_within_hours' => ['nullable', 'integer', Rule::in(ExpiryWindowFilter::options())],
            'donation_status' => ['nullable', 'string', Rule::in(DonationStatusFilter::options())],
            'storage_type' => ['nullable', 'string', 'max:60'],
        ]);

        return array_filter(
            $validated,
            fn ($value) => $value !== null && $value !== ''
        );
    }

    public function create()
    {
        return view('donations.form', [
            'donation' => new FoodDonation(),
            'categories' => FoodCategory::orderBy('category_name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedDonationData($request, creating: true);

        $donation = new FoodDonation($data);
        $donation->donor_id = auth()->user()->partnerProfile->profile_id;
        $donation->current_quantity = $data['donation_quantity'];
        $donation->donation_status = 'AVAILABLE';
        $donation->save();

        DonationStatusHistory::create([
            'donation_id' => $donation->donation_id,
            'new_status' => 'AVAILABLE',
            'changed_by' => auth()->id(),
            'remarks' => 'Donation created',
        ]);

        if ($request->hasFile('photo')) {
            DonationPhoto::create([
                'donation_id' => $donation->donation_id,
                'file_path' => $request->file('photo')->store('donation_photos', 'public'),
            ]);
        }

        return redirect('/donations')->with('message', 'Donation created.');
    }

    public function show(FoodDonation $donation)
    {
        $this->ensureDonorOwns($donation);
        $donation->load(['category', 'photos', 'statusHistories.changedBy']);

        return view('donations.show', compact('donation'));
    }

    public function edit(FoodDonation $donation)
    {
        $this->ensureDonorOwns($donation);
        $this->ensureDonationEditable($donation);

        return view('donations.form', [
            'donation' => $donation,
            'categories' => FoodCategory::orderBy('category_name')->get(),
        ]);
    }

    public function update(Request $request, FoodDonation $donation)
    {
        $this->ensureDonorOwns($donation);
        $this->ensureDonationEditable($donation);

        $donation->update($this->validatedDonationData($request, creating: false));

        return redirect('/donations')->with('message', 'Donation updated.');
    }

    public function cancel(FoodDonation $donation)
    {
        $this->ensureDonorOwns($donation);
        $this->ensureDonationCancellable($donation);

        $old = $donation->donation_status;
        $donation->donation_status = 'CANCELLED';
        $donation->save();

        DonationStatusHistory::create([
            'donation_id' => $donation->donation_id,
            'old_status' => $old,
            'new_status' => 'CANCELLED',
            'changed_by' => auth()->id(),
            'remarks' => 'Cancelled by donor',
        ]);

        return back()->with('message', 'Donation cancelled.');
    }

    /** Stage 2: upload one or more photos for the owning donor's donation. */
    public function storePhotos(Request $request, FoodDonation $donation)
    {
        $this->ensureDonorOwns($donation);

        $data = $request->validate([
            'photos' => ['required', 'array', 'min:1'],
            'photos.*' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ]);

        foreach ($data['photos'] as $file) {
            DonationPhoto::create([
                'donation_id' => $donation->donation_id,
                'file_path' => $file->store('donation_photos', 'public'),
            ]);
        }

        return redirect('/donations/'.$donation->donation_id)
            ->with('message', 'Photo(s) uploaded.');
    }

    /** Stage 2: delete a photo that belongs to the owning donor's donation. */
    public function destroyPhoto(FoodDonation $donation, DonationPhoto $photo)
    {
        $this->ensureDonorOwns($donation);
        abort_unless((int) $photo->donation_id === (int) $donation->donation_id, 403);

        if ($photo->file_path && Storage::disk('public')->exists($photo->file_path)) {
            Storage::disk('public')->delete($photo->file_path);
        }

        $photo->delete();

        return redirect('/donations/'.$donation->donation_id)
            ->with('message', 'Photo deleted.');
    }

    /**
     * Same ownership scope as index(): donor_id must match the logged-in
     * partner profile.
     */
    private function ensureDonorOwns(FoodDonation $donation): void
    {
        $profileId = auth()->user()->partnerProfile?->profile_id;
        abort_unless($profileId !== null && (int) $donation->donor_id === (int) $profileId, 403);
    }

    /** Donors may only edit donations that are still AVAILABLE. */
    private function ensureDonationEditable(FoodDonation $donation): void
    {
        abort_unless(
            $donation->donation_status === 'AVAILABLE',
            403,
            'This donation can no longer be edited.'
        );
    }

    /** Donors may only cancel donations that are still AVAILABLE. */
    private function ensureDonationCancellable(FoodDonation $donation): void
    {
        abort_unless(
            $donation->donation_status === 'AVAILABLE',
            403,
            'This donation can no longer be cancelled.'
        );
    }

    /**
     * Validated editable donation fields only.
     * Never accepts donor_id, current_quantity, donation_status, or donation_id.
     *
     * @return array<string, mixed>
     */
    private function validatedDonationData(Request $request, bool $creating): array
    {
        $maxQuantity = (float) config('foodlink.request.max_quantity', 100000);
        $units = config('foodlink.request.units', []);

        $rules = [
            'category_id' => ['required', 'integer', 'exists:food_categories,category_id'],
            'food_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'donation_quantity' => ['required', 'numeric', 'gt:0', 'max:'.$maxQuantity],
            'measurement_unit' => ['required', 'string', 'max:255', Rule::in($units)],
            'expiry_datetime' => ['required', 'date', 'after:now'],
            'pickup_address' => ['required', 'string', 'max:1000'],
            'storage_type' => ['nullable', 'string', 'max:255'],
            'halal_status' => ['nullable', 'string', 'max:255'],
        ];

        if ($creating) {
            $rules['photo'] = ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'];
        }

        $validated = $request->validate($rules);

        // Never mass-assign ownership, stock, or status from the request.
        unset(
            $validated['donor_id'],
            $validated['donation_id'],
            $validated['current_quantity'],
            $validated['donation_status'],
            $validated['photo']
        );

        return $validated;
    }
}
