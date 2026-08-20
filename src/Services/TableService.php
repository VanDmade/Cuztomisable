<?php

namespace VanDmade\Cuztomisable\Services;

use Illuminate\Database\Query\Expression;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Exception;

class TableService
{

    public static function run(mixed $query, array $parameters, array $searchColumns = []): JsonResponse
    {
        $parameters = self::cleanParameters($parameters);
        $searchColumns = $parameters['search_columns'] ?? $searchColumns;
        $allowedFilters = $parameters['allowed_filters'] ?? [];
        try {
            $table = $query->getModel()->getTable();
            // Only apply a default select if the query does not already have one
            if (empty($query->getQuery()->columns)) {
                $query = $query->select($table . '.*');
            }
            $query = $query->distinct();
        } catch (Exception $error) {}
        // Appends the search information and calculates the pre-filter total
        list($total, $query) = self::filter($query, $parameters, $searchColumns, $allowedFilters);
        if ($parameters['size'] === 'all') {
            $maxAll = (int) config('cuztomisable.tablelify.max_size', 100);
            if ($maxAll > 0) {
                // paginate() can't express "no limit" - fall back to the configured ceiling instead
                $parameters['size'] = $maxAll;
                $parameters['page'] = 1;
            }
        }
        // Sorts the results
        $query = self::sort(
            $query,
            $parameters['columns'] ?? $parameters['column'],
            $parameters['direction'],
            $parameters['allowed_columns'] ?? null,
            $parameters['default_columns'] ?? null
        );
        if ($parameters['size'] === 'all') {
            $data = $query->get();
            $filteredTotal = $data->count();
        } else {
            $paginator = $query->paginate($parameters['size'], ['*'], 'page', $parameters['page']);
            $data = $paginator->items();
            $filteredTotal = $paginator->total();
        }
        return self::response(
            $data,
            $parameters['size'],
            $parameters['page'],
            $parameters['search'],
            $total,
            $filteredTotal
        );
    }

    public static function filter(
        mixed $query,
        array $parameters,
        array $searchColumns = [],
        array $allowedFilters = []
    ): array
    {
        $search = $parameters['search'] ?? null;
        if (isset($parameters['additional']['group_by'])) {
            $query = $query->groupBy($parameters['additional']['group_by']);
        }
        // Total rows prior to search / filters
        $total = (clone $query)->count();
        $query = $query->where(function ($query) use ($search, $searchColumns) {
            if (!is_null($search) && $search !== '') {
                foreach ($searchColumns as $column) {
                    $query->orWhere($column, 'LIKE', $search);
                }
            }
        });
        if (!empty($parameters['filters'])) {
            foreach ($parameters['filters'] as $filter) {
                $key = $filter['key'] ?? null;
                $value = $filter['value'] ?? null;
                if (empty($key)) {
                    continue;
                }
                if (!empty($allowedFilters) && !in_array($key, $allowedFilters, true)) {
                    continue;
                }
                if (is_array($value)) {
                    $query->whereIn($key, $value);
                } elseif ($value === null) {
                    $query->whereNull($key);
                } else {
                    $query->where($key, '=', $value);
                }
            }
        }
        return [$total, $query];
    }

    public static function sort(
        mixed $query,
        mixed $columns,
        ?string $direction = 'asc',
        ?array $allowedColumns = null,
        ?array $defaultColumns = null
    ): mixed
    {
        if (is_null($columns) || empty($columns)) {
            if (is_array($defaultColumns) && !empty($defaultColumns)) {
                foreach ($defaultColumns as $column => $dir) {
                    $query->orderBy($column, $dir ?? config('cuztomisable.tablelify.default.order_direction', 'asc'));
                }
                return $query;
            }
            if (is_null(config('cuztomisable.tablelify.default.order_by'))) {
                return $query;
            }
            return $query->orderBy(
                config('cuztomisable.tablelify.default.order_by'),
                config('cuztomisable.tablelify.default.order_direction', 'asc')
            );
        }
        $columns = is_array($columns) ? $columns : [$columns];
        foreach ($columns as $i => $column) {
            if (!($column instanceof Expression) && isset($column['direction'])) {
                if (!is_array($allowedColumns) || in_array($column['column'], $allowedColumns, true)) {
                    $query->orderBy($column['column'], $column['direction']);
                }
            } else {
                if (!is_array($allowedColumns) || in_array($column, $allowedColumns, true)) {
                    $query = $query->orderBy($column, $direction ?? 'asc');
                }
            }
        }
        return $query;
    }

    public static function response(
        mixed $data,
        mixed $size,
        mixed $page,
        mixed $search,
        mixed $total,
        mixed $filteredTotal = 0
    ): JsonResponse {
        $totalEntriesReturned = sizeof($data);
        $totalResults = !is_null($search) ? $filteredTotal : $total;
        $safeSize = (is_numeric($size) && (int) $size > 0) ? (int) $size : null;
        $totalPages = $size == 'all' || $safeSize === null ? 1 :
            (int) ceil(($totalResults ?? $totalEntriesReturned) / $safeSize);
        $response = [
            'page' => $page,
            'total_pages' => $totalPages,
            'size' => $size,
            'total' => $total ?? $totalEntriesReturned,
            'data' => $data,
        ];
        if (config('cuztomisable.tablelify.page_details', true)) {
            $response = array_merge($response, [
                'next_page' => $totalPages <= $page ? null : ($page + 1),
                'previous_page' => $page == 1 ? null : ($page - 1),
            ]);
        }
        if (config('cuztomisable.tablelify.filtered', true)) {
            $response = array_merge($response, [
                'filtered' => !is_null($search) ? true : false,
                'filtered_total' => $filteredTotal ?? $totalEntriesReturned,
            ]);
        }
        return response()->json($response, 200);
    }

    public static function cleanParameters(array $parameters): array
    {
        $parameters['page'] = $parameters['page'] ?? 1;
        $parameters['size'] = $parameters['size'] ?? config('cuztomisable.tablelify.default.size', 10);
        $parameters['column'] = $parameters['column'] ?? null;
        $parameters['direction'] = $parameters['direction'] ?? (!is_null($parameters['column']) ? 'asc' : null);
        $rawSearch = $parameters['search'] ?? null;
        $parameters['search'] = is_null($rawSearch) || $rawSearch === '' ? null :
            ('%'.trim($rawSearch, '%').'%');
        return $parameters;
    }

    public static function cleanRequest(array $list): array
    {
        foreach ($list as $key => &$value) {
            if (is_array($value)) {
                $value = self::cleanRequest($value);
            } else {
                if (is_string($value)) {
                    $value = in_array(strtolower($value), ['null', 'undefined', null]) ? null : $value;
                }
                if ($key != 'search' && !is_null($value)) {
                    $value = in_array($value, ['true', 1, '1']) || $value === true ? 1 : $value;
                    $value = in_array($value, ['false', 0, '0']) || $value === false ? 0 : $value;
                }
            }
        }
        return $list;
    }

}
