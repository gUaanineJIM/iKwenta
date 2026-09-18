<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Debt;
use App\Models\DebtItem;
use App\Models\Payment;
use App\Services\CustomerAccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerDashboardController extends Controller
{
    /**
     * Section key -> the component view that renders it as a fragment.
     */
    private const SECTIONS = [
        'overview' => 'components.customer.overview',
        'debts' => 'components.customer.debt-overview',
        'items' => 'components.customer.items-list',
        'payments' => 'components.customer.payment-history',
    ];

    private const RECENT_ITEMS_LIMIT = 5;

    private const RECENT_PAYMENTS_LIMIT = 5;

    private const ITEMS_PER_PAGE = 12;

    private const PAYMENTS_PER_PAGE = 8;

    private CustomerAccountService $accounts;

    public function __construct(CustomerAccountService $accounts)
    {
        $this->accounts = $accounts;
    }

    /**
     * Render the customer dashboard shell with the overview section active.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $customer = $this->authenticatedCustomer($request);

        if ($customer === null) {
            return $this->redirectToLogin();
        }

        return view('customer.dashboard', [
            'customer' => $customer,
            'activeSection' => 'overview',
            ...$this->overviewData($customer),
        ]);
    }

    /**
     * Load a single dashboard section.
     *
     * When requested as an AJAX/section request only the section component is
     * returned so the dashboard shell can swap content without a full reload.
     * A plain navigation returns the full shell with that section active.
     */
    public function section(Request $request, string $section): View|JsonResponse|RedirectResponse
    {
        $customer = $this->authenticatedCustomer($request);

        if ($customer === null) {
            if ($request->ajax()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return $this->redirectToLogin();
        }

        $data = $this->sectionData($customer, $section);

        if ($request->ajax()) {
            return view(self::SECTIONS[$section], [
                'customer' => $customer,
                ...$data,
            ]);
        }

        return view('customer.dashboard', [
            'customer' => $customer,
            'activeSection' => $section,
            ...$data,
        ]);
    }

    /**
     * Resolve the logged-in customer from the session.
     *
     * The customer id is never trusted from the URL or request body.
     */
    private function authenticatedCustomer(Request $request): ?Customer
    {
        $customerId = $request->session()->get('customer_id');

        if (! $customerId) {
            return null;
        }

        $customer = Customer::find($customerId);

        if (! $customer) {
            $request->session()->forget('customer_id');

            return null;
        }

        return $customer;
    }

    private function redirectToLogin(): RedirectResponse
    {
        return redirect()->route('landing')
            ->withErrors([
                'code' => 'Please sign in first.',
            ]);
    }

    private function sectionData(Customer $customer, string $section): array
    {
        return match ($section) {
            'debts' => ['debts' => $this->debtsFor($customer)],
            'items' => [
                'items' => $this->itemsFor($customer),
                'summary' => $this->summaryFor($customer),
            ],
            'payments' => ['payments' => $this->paymentsFor($customer)],
            default => $this->overviewData($customer),
        };
    }

    /**
     * Data rendered by the overview section: summary + the most recent items
     * and payments only, so the initial dashboard stays lightweight.
     */
    private function overviewData(Customer $customer): array
    {
        return [
            'summary' => $this->summaryFor($customer),
            'recentItems' => $this->recentItems($customer),
            'recentPayments' => $this->recentPayments($customer),
        ];
    }

    /**
     * Financial summary for the customer's account.
     */
    private function summaryFor(Customer $customer): array
    {
        $totalDebt = $this->accounts->totalCredit($customer);
        $totalPaid = $this->accounts->totalPaid($customer);

        return [
            'totalDebt' => $totalDebt,
            'totalPaid' => $totalPaid,
            'remainingBalance' => $this->accounts->outstandingBalance($customer),
        ];
    }

    private function recentItems(Customer $customer)
    {
        return DebtItem::query()
            ->whereHas('debt', function ($query) use ($customer) {
                $query->where('customer_id', $customer->customer_id);
            })
            ->with(['product', 'debt'])
            ->latest()
            ->limit(self::RECENT_ITEMS_LIMIT)
            ->get();
    }

    private function recentPayments(Customer $customer)
    {
        return Payment::query()
            ->where('customer_id', $customer->customer_id)
            ->latest('payment_date')
            ->limit(self::RECENT_PAYMENTS_LIMIT)
            ->get();
    }

    private function debtsFor(Customer $customer)
    {
        $debts = Debt::query()
            ->where('customer_id', $customer->customer_id)
            ->with(['items.product', 'payments'])
            ->latest()
            ->get();

        $debts->each(function (Debt $debt) {
            $debt->items_total = $this->accounts->transactionTotal($debt);
            $debt->paid_total = $this->accounts->paymentsTotal($debt);
            $debt->remaining_total = $this->accounts->remainingBalance($debt);
        });

        return $debts;
    }

    private function itemsFor(Customer $customer)
    {
        return DebtItem::query()
            ->whereHas('debt', function ($query) use ($customer) {
                $query->where('customer_id', $customer->customer_id);
            })
            ->with(['product', 'debt'])
            ->latest()
            ->paginate(self::ITEMS_PER_PAGE)
            ->withQueryString();
    }

    private function paymentsFor(Customer $customer)
    {
        return Payment::query()
            ->where('customer_id', $customer->customer_id)
            ->latest('payment_date')
            ->paginate(self::PAYMENTS_PER_PAGE)
            ->withQueryString();
    }
}
