<?php

namespace VanDmade\Cuztomisable\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use VanDmade\Cuztomisable\Requests\FormoraRequest;
use VanDmade\Cuztomisable\Models\Formora;

class FormoraController extends Controller
{

    public function get($page): JsonResponse
    {
        try {
            if (!Auth::check()) {
                throw new Exception('Unauthenticated', 401);
            }
            $this->rateLimit(
                'cuztomisable:formora:get:'.implode(':', [request()->ip(), $this->actorId(), (string) $page]),
                'cuztomisable/global.server_broken'
            );
            $form = Formora::where('user_id', Auth::id())
                ->where('current', '=', $page)
                ->latest('id')
                ->first();
            return $this->success([
                'form' => $form,
            ]);
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    public function save(FormoraRequest $request, $page): JsonResponse
    {
        try {
            if (!Auth::check()) {
                throw new Exception('Unauthenticated', 401);
            }
            return DB::transaction(function () use ($request, $page) {
                $data = $request->validated();
                $this->rateLimit(
                    'cuztomisable:formora:save:'.implode(':', [
                        $request->ip(),
                        $this->actorId(),
                        (string) ($data['current'] ?? $page),
                    ]),
                    'cuztomisable/global.server_broken'
                );
                $payload = [
                    'to' => $data['to'] ?? null,
                    'to_params' => $this->normalizeJson($data['to_params'] ?? null),
                    'current' => $data['current'] ?? $page,
                    'current_params' => $this->normalizeJson($data['current_params'] ?? null),
                    'form' => $this->normalizeJson($data['form'] ?? null),
                ];
                $formora = Formora::updateOrCreate(
                    [
                        'user_id' => Auth::id(),
                        'current' => $payload['current'],
                    ],
                    $payload
                );
                return $this->success([
                    'form' => $formora,
                ]);
            });
        } catch (Exception $error) {
            return $this->error($error);
        }
    }

    private function normalizeJson(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_array($value)) {
            return json_encode($value);
        }
        return is_string($value) ? $value : json_encode($value);
    }


}
