<?php

declare(strict_types=1);

namespace TrustMedical\Toko\Models\Concerns;

use Illuminate\Database\Eloquent\Model;

trait ResolvesUserModel
{
    /**
     * @return class-string<Model>
     */
    protected function userModel(): string
    {
        // auth設定のユーザーモデルを参照し、未設定ならApp\Models\Userにフォールバック
        return (string) (config('auth.providers.users.model') ?: 'App\\Models\\User');
    }
}
