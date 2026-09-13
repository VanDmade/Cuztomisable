<?php

namespace VanDmade\Cuztomisable\Services\Users;

use VanDmade\Cuztomisable\Models\Users\Code;

/**
 * Looks up an MFA code by its token.
 */
class CodeService
{

    public function findByToken(
        string $token,
        bool $unusedOnly = false,
        bool $unexpiredOnly = false
    ): ?Code {
        $query = Code::where('token', '=', $token)->whereHas('user');
        if ($unusedOnly) {
            $query->whereNull('used_at');
        }
        if ($unexpiredOnly) {
            $query->where('expires_at', '>=', now());
        }
        return $query->first();
    }

}
