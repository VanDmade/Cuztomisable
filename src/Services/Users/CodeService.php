<?php

namespace VanDmade\Cuztomisable\Services\Users;

use VanDmade\Cuztomisable\Models\Users\Code;

/**
 * Lookup for MFA codes by token - the base "token + has a user" query was duplicated across
 * MfaService's send()/verify()/save(), each with a slightly different extra filter on top
 * (send/save only want an unused, unexpired code; verify wants any code so it can report
 * used/expired as distinct states). Kept flexible rather than throwing here, since callers
 * decide different things about a missing code (verify reports it differently than send/save do).
 */
class CodeService
{

    public function getByToken(string $token, bool $unusedOnly = false, bool $unexpiredOnly = false): ?Code
    {
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
