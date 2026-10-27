<?php

namespace App\Services;

use App\Models\SettlementPayment;
use App\Models\Trip;
use Illuminate\Support\Facades\DB;

class VietQrService
{
    /**
     * Common Vietnamese Banks with BIN code for VietQR.
     */
    public const SUPPORTED_BANKS = [
        '970436' => ['code' => 'VCB', 'name' => 'Vietcombank'],
        '970422' => ['code' => 'MB', 'name' => 'MBBank'],
        '970407' => ['code' => 'TCB', 'name' => 'Techcombank'],
        '970415' => ['code' => 'CTG', 'name' => 'VietinBank'],
        '970418' => ['code' => 'BIDV', 'name' => 'BIDV'],
        '970416' => ['code' => 'ACB', 'name' => 'ACB'],
        '970432' => ['code' => 'VPB', 'name' => 'VPBank'],
        '970423' => ['code' => 'TPB', 'name' => 'TPBank'],
        '970405' => ['code' => 'VBA', 'name' => 'Agribank'],
        '970403' => ['code' => 'STB', 'name' => 'Sacombank'],
        '970441' => ['code' => 'VIB', 'name' => 'VIB'],
        '970448' => ['code' => 'OCB', 'name' => 'OCB'],
    ];

    /**
     * Generate VietQR Napas quick link URL.
     *
     * @param  string  $bankBin  Bank BIN code (e.g., '970436' for Vietcombank, '970422' for MBBank)
     * @param  string  $accountNumber  Beneficiary account number
     * @param  float  $amount  Transfer amount in VND
     * @param  string  $memo  Transfer description/content
     * @param  string  $accountName  Beneficiary account holder name
     */
    public function generateQrUrl(
        string $bankBin,
        string $accountNumber,
        float $amount,
        string $memo,
        string $accountName = ''
    ): string {
        $cleanBin = trim($bankBin);
        $cleanAccount = trim($accountNumber);
        $intAmount = (int) round($amount);

        // Sanitize memo to avoid invalid characters in URL
        $cleanMemo = urlencode(substr(trim($memo), 0, 50));
        $cleanAccountName = urlencode(substr(trim($accountName), 0, 50));

        $url = "https://img.vietqr.io/image/{$cleanBin}-{$cleanAccount}-compact2.png?amount={$intAmount}&addInfo={$cleanMemo}";

        if (! empty($cleanAccountName)) {
            $url .= "&accountName={$cleanAccountName}";
        }

        return $url;
    }

    /**
     * Generate default memo format for a trip settlement payment.
     */
    public function formatSettlementMemo(int $tripId, ?int $fromUserId = null, ?int $toUserId = null): string
    {
        if ($fromUserId && $toUserId) {
            return "WND_SETTLE_{$tripId}_U{$fromUserId}_TO_U{$toUserId}";
        }

        return "WND_SETTLE_{$tripId}";
    }

    /**
     * Create or retrieve a settlement payment record.
     */
    public function createOrUpdateSettlementPayment(
        Trip $trip,
        int $fromUserId,
        int $toUserId,
        float $amount,
        ?string $bankBin = null,
        ?string $accountNumber = null,
        ?string $note = null
    ): SettlementPayment {
        return SettlementPayment::updateOrCreate(
            [
                'trip_id' => $trip->id,
                'from_user_id' => $fromUserId,
                'to_user_id' => $toUserId,
            ],
            [
                'amount' => $amount,
                'bank_bin' => $bankBin,
                'bank_account_number' => $accountNumber,
                'note' => $note,
            ]
        );
    }

    /**
     * Mark a settlement payment as completed.
     */
    public function confirmPayment(SettlementPayment $payment, ?string $proofImagePath = null): bool
    {
        return DB::transaction(function () use ($payment, $proofImagePath) {
            $payment->update([
                'status' => 'completed',
                'proof_image' => $proofImagePath ?? $payment->proof_image,
                'completed_at' => now(),
            ]);

            return true;
        });
    }

    /**
     * Return list of supported banks with code and name.
     */
    public function getBankList(): array
    {
        return self::SUPPORTED_BANKS;
    }
}
