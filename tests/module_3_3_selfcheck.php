<?php
/**
 * FoodLink - Module 3.3 Food Request Management
 * Author : NG JIA QIN
 * File   : tests/module_3_3_selfcheck.php
 * Purpose: Self-check for the pure logic of the module: the request state
 *          machine (function 7 "Check Request Status"), the deadline driven
 *          transition (function 6 "Monitor Fulfillment Deadline"), the reserved
 *          and outstanding quantity arithmetic (function 5 "Track Reserved
 *          Quantity") and the LIKE escaping used by the donation keyword search
 *          (function 10 "Search Specific Donations").
 *
 *          These classes hold no Eloquent or framework dependency on purpose,
 *          so the check runs with plain PHP and no database:
 *
 *              php tests/module_3_3_selfcheck.php
 */

declare(strict_types=1);

$base = dirname(__DIR__);

require $base.'/app/Domain/RequestStatus/RequestProgress.php';
require $base.'/app/Domain/RequestStatus/RequestState.php';
require $base.'/app/Domain/RequestStatus/PendingState.php';
require $base.'/app/Domain/RequestStatus/PartiallyFulfilledState.php';
require $base.'/app/Domain/RequestStatus/CompletedState.php';
require $base.'/app/Domain/RequestStatus/CancelledState.php';
require $base.'/app/Domain/RequestStatus/ExpiredState.php';
require $base.'/app/Filters/Donation/DonationFilter.php';
require $base.'/app/Filters/Donation/KeywordFilter.php';

use App\Domain\RequestStatus\RequestProgress;
use App\Domain\RequestStatus\RequestState;
use App\Filters\Donation\KeywordFilter;

$checks = 0;

function check(string $what, bool $passed): void
{
    global $checks;
    $checks++;

    if (! $passed) {
        fwrite(STDERR, "FAILED: $what\n");
        exit(1);
    }

    echo "  ok  $what\n";
}

/** Shorthand: state code after applying the given progress numbers. */
function nextCode(string $from, float $requested, float $fulfilled, float $reserved, bool $late = false): string
{
    return RequestState::for($from)
        ->next(new RequestProgress($requested, $fulfilled, $reserved, $late))
        ->code();
}

echo "Module 3.3 Food Request Management - self check\n";

echo "\n[1] Request lifecycle (state pattern)\n";
check('a new request with nothing committed stays PENDING',
    nextCode(RequestState::PENDING, 100, 0, 0) === RequestState::PENDING);

check('a reservation moves PENDING to PARTIALLY_FULFILLED',
    nextCode(RequestState::PENDING, 100, 0, 30) === RequestState::PARTIALLY_FULFILLED);

check('full delivery moves PARTIALLY_FULFILLED to COMPLETED',
    nextCode(RequestState::PARTIALLY_FULFILLED, 100, 100, 0) === RequestState::COMPLETED);

check('withdrawing every reservation returns the request to PENDING',
    nextCode(RequestState::PARTIALLY_FULFILLED, 100, 0, 0) === RequestState::PENDING);

check('a passed deadline expires an unfulfilled request',
    nextCode(RequestState::PENDING, 100, 0, 0, true) === RequestState::EXPIRED);

check('a late but complete delivery still completes an EXPIRED request',
    nextCode(RequestState::EXPIRED, 100, 100, 0, true) === RequestState::COMPLETED);

check('CANCELLED is terminal even when quantities arrive later',
    nextCode(RequestState::CANCELLED, 100, 100, 0) === RequestState::CANCELLED);

check('COMPLETED is terminal',
    nextCode(RequestState::COMPLETED, 100, 100, 0, true) === RequestState::COMPLETED);

echo "\n[2] What each state allows\n";
check('only a PENDING request may be edited',
    RequestState::for(RequestState::PENDING)->canEdit()
    && ! RequestState::for(RequestState::PARTIALLY_FULFILLED)->canEdit()
    && ! RequestState::for(RequestState::COMPLETED)->canEdit());

check('a completed request may not be cancelled',
    ! RequestState::for(RequestState::COMPLETED)->canCancel()
    && RequestState::for(RequestState::PENDING)->canCancel());

check('donations may not be reserved for a cancelled request',
    ! RequestState::for(RequestState::CANCELLED)->canReserve()
    && RequestState::for(RequestState::PARTIALLY_FULFILLED)->canReserve());

echo "\n[3] Tracked quantities\n";
$progress = new RequestProgress(requested: 60, fulfilled: 10, reserved: 25);
check('committed = delivered + reserved', $progress->committed() === 35.0);
check('outstanding = requested - committed', $progress->outstanding() === 25.0);
check('progress percentage is rounded to a whole number', $progress->percentage() === 58);

$over = new RequestProgress(requested: 10, fulfilled: 12, reserved: 0);
check('an over delivery never reports more than 100%', $over->percentage() === 100);
check('an over delivery never reports a negative outstanding', $over->outstanding() === 0.0);

echo "\n[4] Donation keyword search escaping\n";
check('percent signs are escaped', KeywordFilter::escapeLike('100%') === '100\%');
check('underscores are escaped', KeywordFilter::escapeLike('rice_pack') === 'rice\_pack');
check('backslashes are escaped first', KeywordFilter::escapeLike('a\\b') === 'a\\\\b');
check('ordinary text is untouched', KeywordFilter::escapeLike('fresh bread') === 'fresh bread');

echo "\nAll $checks checks passed.\n";
