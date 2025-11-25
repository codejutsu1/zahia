<?php

namespace App\Http\Controllers\Api\Transaction;

use App\Http\Controllers\Controller;
use App\Services\Transaction\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VerifyTransactionController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $request->validate([
            'reference' => 'required|string|max:255',
        ]);

        try {
            $transaction = DB::transaction(function () use ($request) {
                return app(TransactionService::class)->verifyTransaction($request->reference);
            });

            return response()->json([
                'message' => 'Transaction verified successfully',
                'data' => $transaction,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error verifying transaction',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
