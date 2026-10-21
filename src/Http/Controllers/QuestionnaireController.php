<?php

namespace VanDmade\Cuztomisable\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;
use VanDmade\Cuztomisable\Http\Requests\QuestionnaireOptionsRequest;
use VanDmade\Cuztomisable\Http\Requests\QuestionnaireRequest;
use VanDmade\Cuztomisable\Services\QuestionnaireService;

/**
 * The sign-up questionnaire - the questions with the user's answers so far, searching a question's choices, and
 * saving answers.
 */
class QuestionnaireController extends CuztomisableController
{

    public function __construct(
        protected readonly QuestionnaireService $questionnaireService
    ) {
    }

    public function show(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            return $this->success([
                'enabled' => $this->questionnaireService->enabled(),
                'completed' => !$this->questionnaireService->needsToComplete($user),
                'questions' => $this->questionnaireService->forUser($user),
            ]);
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

    public function options(QuestionnaireOptionsRequest $request, string $key): JsonResponse
    {
        try {
            return $this->success([
                'options' => $this->questionnaireService->options($key, $request->validated()['search'] ?? null),
            ]);
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

    public function save(QuestionnaireRequest $request): JsonResponse
    {
        try {
            $result = $this->questionnaireService->save($request->user(), $request->validated()['answers']);
            return $this->success([
                'message' => __($result['completed'] ?
                    'cuztomisable/questionnaire.completed' : 'cuztomisable/questionnaire.saved'),
                ...$result,
            ]);
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

}
