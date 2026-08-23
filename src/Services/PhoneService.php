<?php

namespace VanDmade\Cuztomisable\Services;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use VanDmade\Cuztomisable\Models\Phone;

class PhoneService
{

    public function setDefault(Model $user, string $number, mixed $countryCode = null): Phone
    {
        $phone = $user->defaultPhone ?? new Phone([
            'user_id' => $user->id,
            'default' => true,
        ]);
        $phone->number = $number;
        $phone->country_code = $countryCode ?? config('cuztomisable.locations.default_country_code', 1);
        $phone->save();
        return $phone;
    }

    public function findByNumber(mixed $countryCode, string $number): ?Phone
    {
        return Phone::where('country_code', '=', $countryCode)
            ->where('number', '=', cleanPhone($number))
            ->first();
    }

    public function find($id): Phone
    {
        $phone = $this->query()
            ->where('id', '=', $id)
            ->withTrashed()
            ->first();
        if (!isset($phone->id)) {
            throw new Exception(__('cuztomisable/user.phone.errors.not_found'), 404);
        }
        return $phone;
    }

    public function table(array $data, $id = null): JsonResponse
    {
        $user = is_null($id) || !Auth::user()->admin ?
            Auth::user() :
            config('auth.providers.users.model')::where('id', '=', $id)->first();
        $query = (Auth::user()->admin && is_null($id) ?
            Phone::query() :
            $user->phones())
            ->select('phones.id', 'phones.number', 'phones.country_code', 'phones.extension',
                'phones.mobile', 'phones.default', 'phones.disable_messages', 'phones.verified_at')
            ->where(function($query) {
                $query->whereNotNull('phones.id');
            });
        if (Auth::user()->admin && !empty($data['filters'])) {
            foreach ($data['filters'] as $filter) {
                if (($filter['key'] ?? null) === 'user_id' && !empty($filter['value'])) {
                    $query->where('phones.user_id', '=', $filter['value']);
                }
            }
        }
        $parameters = [
            'allowed_columns' => [
                'phones.id',
                'phones.number',
                'phones.country_code',
                'phones.mobile',
                'phones.default',
                'phones.verified_at',
            ],
            'search_columns' => ['phones.number'],
            'default_columns' => ['phones.default' => 'desc', 'phones.id' => 'desc'],
        ];
        return TableService::generate($query, array_merge($data, $parameters));
    }

    public function save(array $data, $id = null): Phone
    {
        return DB::transaction(function() use ($data, $id) {
            $isNew = is_null($id);
            $phone = $isNew ? new Phone(['user_id' => Auth::id()]) : $this->query()->where('id', '=', $id)->first();
            if (!$isNew && !isset($phone->id)) {
                throw new Exception(__('cuztomisable/user.phone.errors.not_found'), 404);
            }
            $phone->number = $data['number'];
            $phone->country_code = $data['country_code'] ?? config('cuztomisable.locations.default_country_code', 1);
            $phone->extension = $data['extension'] ?? null;
            $phone->mobile = !empty($data['mobile']) && $data['mobile'] == '1';
            $phone->disable_messages = !empty($data['disable_messages']) && $data['disable_messages'] == '1';
            if ($isNew) {
                // The first number a user adds becomes their default automatically
                $phone->default = is_null($phone->user->defaultPhone);
            }
            $phone->save();
            return $phone;
        });
    }

    public function makeDefault($id): Phone
    {
        return DB::transaction(function() use ($id) {
            $phone = $this->query()->where('id', '=', $id)->first();
            if (!isset($phone->id)) {
                throw new Exception(__('cuztomisable/user.phone.errors.not_found'), 404);
            }
            $phone->user->phones()
                ->where('id', '!=', $phone->id)
                ->update(['default' => false]);
            $phone->default = true;
            $phone->save();
            return $phone;
        });
    }

    public function toggleDelete($id): bool
    {
        return DB::transaction(function() use ($id) {
            $phone = $this->query()->where('id', '=', $id)->withTrashed()->first();
            if (!isset($phone->id)) {
                throw new Exception(__('cuztomisable/user.phone.errors.not_found'), 404);
            }
            $deleted = $phone->trashed();
            if ($deleted) {
                $phone->deleted_by = null;
                $phone->restore();
            } else {
                $phone->delete();
            }
            return $deleted;
        });
    }

    private function query(): Builder|HasMany
    {
        return Auth::user()->admin ?
            Phone::query() :
            Auth::user()->phones();
    }

}
