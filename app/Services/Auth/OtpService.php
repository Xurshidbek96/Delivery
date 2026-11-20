<?php

namespace App\Services\Auth;

use App\Contracts\SmsSender;
use App\Exceptions\BusinessRuleException;
use App\Models\OtpCode;
use Illuminate\Support\Facades\Hash;

class OtpService
{
    public function __construct(private readonly SmsSender $sms) {}

    /** Kod yuboradi va amal qilish muddatini (soniya) qaytaradi. */
    public function send(string $phone): int
    {
        $config = config('delivery.otp');

        $last = OtpCode::query()->where('phone', $phone)->latest('id')->first();
        if ($last && $last->created_at->gt(now()->subSeconds($config['resend_seconds']))) {
            $wait = $config['resend_seconds'] - (int) $last->created_at->diffInSeconds(now());
            throw new BusinessRuleException("Kodni qayta yuborish uchun {$wait} soniya kuting.");
        }

        $code = $this->generateCode($config['length'], $config['fixed_code']);

        OtpCode::query()->create([
            'phone' => $phone,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addSeconds($config['ttl_seconds']),
        ]);

        $this->sms->send($phone, "Delivery: tasdiqlash kodi {$code}. Kodni hech kimga bermang.");

        return $config['ttl_seconds'];
    }

    /** Kod to'g'ri bo'lsa uni ishlatilgan deb belgilaydi. */
    public function verify(string $phone, string $code): bool
    {
        $otp = OtpCode::query()
            ->where('phone', $phone)
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if ($otp === null || $otp->attempts >= config('delivery.otp.max_attempts')) {
            return false;
        }

        $otp->increment('attempts');

        if (! Hash::check($code, $otp->code_hash)) {
            return false;
        }

        $otp->forceFill(['consumed_at' => now()])->save();

        return true;
    }

    private function generateCode(int $length, ?string $fixed): string
    {
        if ($fixed !== null && $fixed !== '') {
            return $fixed;
        }

        return str_pad((string) random_int(0, 10 ** $length - 1), $length, '0', STR_PAD_LEFT);
    }
}
