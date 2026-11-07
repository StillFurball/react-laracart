<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\VoucherType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VoucherRequest;
use App\Models\Voucher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class VoucherController extends Controller
{
    public function index(): Response
    {
        $vouchers = Voucher::withCount(['orders as used_count' => fn ($query) => $query
            ->whereNotIn('status', OrderStatus::voidStatuses())])
            ->latest('id')
            ->paginate(15)
            ->through(fn (Voucher $voucher): array => [
                ...self::formData($voucher),
                'id' => $voucher->id,
                'used_count' => $voucher->used_count,
            ]);

        return Inertia::render('admin/vouchers/index', [
            'vouchers' => $vouchers,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/vouchers/form', [
            'voucher' => null,
            'types' => array_column(VoucherType::cases(), 'value'),
        ]);
    }

    public function store(VoucherRequest $request): RedirectResponse
    {
        Voucher::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Voucher dibuat.']);

        return to_route('admin.vouchers.index');
    }

    public function edit(Voucher $voucher): Response
    {
        return Inertia::render('admin/vouchers/form', [
            'voucher' => ['id' => $voucher->id, ...self::formData($voucher)],
            'types' => array_column(VoucherType::cases(), 'value'),
        ]);
    }

    public function update(VoucherRequest $request, Voucher $voucher): RedirectResponse
    {
        $voucher->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Voucher diperbarui.']);

        return to_route('admin.vouchers.index');
    }

    /**
     * A voucher that was ever used stays for history; deactivate it instead.
     */
    public function destroy(Voucher $voucher): RedirectResponse
    {
        if ($voucher->orders()->exists()) {
            throw ValidationException::withMessages(['voucher' => 'Voucher sudah pernah dipakai; nonaktifkan saja.']);
        }

        $voucher->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Voucher dihapus.']);

        return to_route('admin.vouchers.index');
    }

    /**
     * @return array<string, mixed>
     */
    private static function formData(Voucher $voucher): array
    {
        return [
            ...$voucher->only(['code', 'value', 'max_discount', 'min_purchase', 'quota', 'per_user_limit', 'is_active']),
            'type' => $voucher->type->value,
            'starts_at' => $voucher->starts_at?->format('Y-m-d\TH:i'),
            'ends_at' => $voucher->ends_at?->format('Y-m-d\TH:i'),
        ];
    }
}
