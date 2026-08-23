<?php

namespace VanDmade\Cuztomisable\Services;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use VanDmade\Cuztomisable\Events\TermsPublished;
use VanDmade\Cuztomisable\Models\Terms\Acceptance;
use VanDmade\Cuztomisable\Models\Terms\TermsAndConditions;

class TermsService
{

    public function current(): ?TermsAndConditions
    {
        return TermsAndConditions::whereNotNull('published_at')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->first();
    }

    public function find($id): TermsAndConditions
    {
        $terms = TermsAndConditions::with([
            'createdBy' => fn($query) => $query->select('id', 'name', 'email'),
            'publishedBy' => fn($query) => $query->select('id', 'name', 'email'),
        ])->where('id', '=', $id)->first();
        if (!isset($terms->id)) {
            throw new Exception(__('cuztomisable/terms.errors.not_found'), 404);
        }
        return $terms;
    }

    public function table(array $data): JsonResponse
    {
        $query = TermsAndConditions::select('id', 'version', 'published_at', 'requires_reacceptance', 'created_at')
            ->where(function($query) {
                $query->whereNotNull('id');
            });
        $parameters = [
            'allowed_columns' => ['id', 'version', 'published_at', 'created_at'],
            'search_columns' => ['version'],
            'default_columns' => ['id' => 'desc'],
        ];
        return TableService::generate($query, array_merge($data, $parameters));
    }

    public function acceptanceTable(array $data): JsonResponse
    {
        $lastMandatory = $this->lastMandatory();
        $query = config('auth.providers.users.model')::query()
            ->select('users.id', 'users.name', 'users.email')
            ->selectRaw('MAX(ta.accepted_at) as accepted_at')
            ->leftJoin('terms_acceptances as ta', function($join) use ($lastMandatory) {
                $join->on('ta.user_id', '=', 'users.id');
                if (isset($lastMandatory->id)) {
                    $join->where('ta.terms_and_conditions_id', '>=', $lastMandatory->id);
                }
            })
            ->where(function($query) {
                $query->whereNotNull('users.id');
            })
            ->groupBy('users.id', 'users.name', 'users.email');
        $parameters = [
            'allowed_columns' => ['users.id', 'users.name', 'users.email', 'accepted_at'],
            'search_columns' => ['users.name', 'users.email'],
            'default_columns' => ['users.name' => 'asc'],
        ];
        return TableService::generate($query, array_merge($data, $parameters));
    }

    public function create(array $data): TermsAndConditions
    {
        return TermsAndConditions::create([
            'version' => $data['version'],
            'content' => $data['content'],
            'requires_reacceptance' => !empty($data['requires_reacceptance']) && $data['requires_reacceptance'] == '1',
        ]);
    }

    public function publish($id): TermsAndConditions
    {
        $terms = $this->find($id);
        $terms->published_at = now();
        $terms->published_by = Auth::id();
        $terms->save();
        TermsPublished::dispatch($terms);
        return $terms;
    }

    public function accept(Model $user): Acceptance
    {
        $terms = $this->current();
        if (!isset($terms->id)) {
            throw new Exception(__('cuztomisable/terms.errors.no_terms'), 404);
        }
        return Acceptance::firstOrCreate(
            ['user_id' => $user->id, 'terms_and_conditions_id' => $terms->id],
            ['accepted_at' => now()]
        );
    }

    public function needsToAccept(Model $user): bool
    {
        $terms = $this->current();
        if (!isset($terms->id)) {
            // Nothing has been published yet - there's nothing to accept
            return false;
        }
        $lastMandatory = $this->lastMandatory();
        if (!isset($lastMandatory->id)) {
            // No version has ever required reacceptance - only users who've never accepted anything need to
            return !Acceptance::where('user_id', '=', $user->id)->exists();
        }
        // Determines if the last accepted version is older than the last mandatory one
        return !Acceptance::where('user_id', '=', $user->id)
            ->where('terms_and_conditions_id', '>=', $lastMandatory->id)
            ->exists();
    }

    private function lastMandatory(): ?TermsAndConditions
    {
        return TermsAndConditions::whereNotNull('published_at')
            ->where('requires_reacceptance', '=', true)
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->first();
    }

}
