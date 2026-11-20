<?php

namespace App\Services\Auth;

use App\Enums\UserRole;
use App\Exceptions\BusinessRuleException;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function __construct(private readonly OtpService $otp) {}

    /**
     * Mijoz: SMS kod bilan kirish/ro'yxatdan o'tish.
     *
     * @return array{user: User, token: string, is_new: bool}
     */
    public function loginClientWithOtp(string $phone, string $code, ?string $name, ?string $deviceName): array
    {
        if (! $this->otp->verify($phone, $code)) {
            throw new BusinessRuleException('Kod noto\'g\'ri yoki muddati o\'tgan.');
        }

        $user = User::withTrashed()->where('phone', $phone)->first();
        $isNew = $user === null;

        if ($isNew) {
            $user = User::query()->create([
                'name' => $name ?: 'Mijoz',
                'phone' => $phone,
                'role' => UserRole::Client,
                'is_active' => true,
            ]);
        }

        if ($user->trashed() || $user->role !== UserRole::Client) {
            throw new AuthorizationException('Bu raqam mijoz ilovasi uchun ro\'yxatdan o\'tmagan.');
        }

        return ['user' => $user, 'token' => $this->issueToken($user, $deviceName), 'is_new' => $isNew];
    }

    /**
     * Kuryer: admin bergan telefon va parol bilan kirish.
     *
     * @return array{user: User, token: string}
     */
    public function loginCourier(string $phone, string $password, ?string $deviceName): array
    {
        $user = User::query()->where('phone', $phone)->role(UserRole::Courier)->first();

        if ($user === null || $user->password === null || ! Hash::check($password, $user->password)) {
            throw new AuthenticationException('Telefon raqam yoki parol noto\'g\'ri.');
        }

        return ['user' => $user, 'token' => $this->issueToken($user, $deviceName)];
    }

    private function issueToken(User $user, ?string $deviceName): string
    {
        if (! $user->is_active) {
            throw new AuthorizationException('Akkauntingiz bloklangan. Qo\'llab-quvvatlash xizmatiga murojaat qiling.');
        }

        $user->forceFill(['last_login_at' => now()])->save();

        return $user->createToken($deviceName ?: $user->role->value.'-app', [$user->role->value])->plainTextToken;
    }
}
