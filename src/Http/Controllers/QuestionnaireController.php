<?php

namespace VanDmade\Cuztomisable\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;
use VanDmade\Cuztomisable\Http\Requests\QuestionnaireOptionsRequest;
use VanDmade\Cuztomisable\Http\Requests\QuestionnaireOrderRequest;
use VanDmade\Cuztomisable\Http\Requests\QuestionnaireQuestionRequest;
use VanDmade\Cuztomisable\Http\Requests\QuestionnaireRequest;
use VanDmade\Cuztomisable\Services\QuestionnaireService;

/**
 * The questionnaire - the questions a user still has to answer, searching a question's choices and saving answers,
 * and managing the questions in the admin area.
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

    /* ---------------- Admin ---------------- */

    public function adminList(): JsonResponse
    {
        try {
            return $this->success([
                'questions' => $this->questionnaireService->adminList(),
                'choices' => $this->questionnaireService->adminChoices(),
            ]);
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

    public function adminGet(int $id): JsonResponse
    {
        try {
            return $this->success([
                'question' => $this->questionnaireService->adminGet($id),
            ]);
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

    public function adminReorder(QuestionnaireOrderRequest $request): JsonResponse
    {
        try {
            $this->questionnaireService->adminReorder($request->validated()['ids']);
            return $this->success([
                'message' => __('cuztomisable/questionnaire.admin.reordered'),
            ]);
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

    public function adminDelete(int $id): JsonResponse
    {
        try {
            $this->questionnaireService->adminDelete($id);
            return $this->success([
                'message' => __('cuztomisable/questionnaire.admin.deleted'),
            ]);
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

    public function adminSave(QuestionnaireQuestionRequest $request, ?int $id = null): JsonResponse
    {
        try {
            $this->questionnaireService->adminSave([...$request->validated(), 'image_file' => $request->file('image_file')], $id);
            return $this->success([
                'message' => __('cuztomisable/questionnaire.admin.saved'),
            ]);
        } catch (Throwable $error) {
            return $this->error($error);
        }
    }

}
