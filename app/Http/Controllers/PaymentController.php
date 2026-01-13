<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\ActivityLog;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    protected $activityLogger;

    public function __construct(ActivityLogger $activityLogger)
    {
        $this->activityLogger = $activityLogger;
    }

    public function update(Request $request, $id)
    {
        $payment = Payment::findOrFail($id);

        // Validate
        $validated = $request->validate([
            'payment_method' => 'required|in:cash,bank_transfer,card,other',
            'notes' => 'nullable|string|max:255',
        ]);

        $oldMethod = $payment->payment_method;
        $oldNotes = $payment->notes;

        $payment->update([
            'payment_method' => $validated['payment_method'],
            'notes' => $validated['notes'],
        ]);

        // Log activity
        $this->activityLogger->log(
            ActivityLog::TYPE_UPDATE,
            ActivityLog::MODULE_PAYMENTS,
            $payment,
            [
                'old_method' => $oldMethod,
                'new_method' => $payment->payment_method,
                'old_notes' => $oldNotes,
                'new_notes' => $payment->notes,
            ],
            "Cập nhật thông tin thanh toán #{$payment->id}"
        );

        return back()->with('success', 'Cập nhật thông tin thanh toán thành công!');
    }
}
