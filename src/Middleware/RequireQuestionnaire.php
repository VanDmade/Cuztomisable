<?php

namespace VanDmade\Cuztomisable\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use VanDmade\Cuztomisable\Services\QuestionnaireService;

/**
 * Blocks a request until the user has finished the sign-up questionnaire, when the questionnaire is enabled and
 * marked required.
 */
class RequireQuestionnaire
{

    public function __construct(
        protected readonly QuestionnaireService $questionnaireService
    ) {
    }

    // The questionnaire itself has to stay reachable, along with what the frontend needs to identify or sign out
    // the user and to accept the terms first
    protected array $exempt = ['api/questionnaire', 'api/questionnaire/*', 'api/terms/*', 'api/me', 'api/logout', 'api/refresh', 'api/refresh/token'];

    public function handle(Request $request, Closure $next): Response
    {
        if (!config('cuztomisable.questionnaire.required', true) || $request->is($this->exempt)) {
            return $next($request);
        }
        if (Auth::check() && $this->questionnaireService->needsToComplete(Auth::user())) {
            abort(403, __('cuztomisable/questionnaire.errors.must_complete'));
        }
        return $next($request);
    }

}
