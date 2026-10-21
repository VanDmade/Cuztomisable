<?php

namespace VanDmade\Cuztomisable\Questionnaire;

/**
 * A list of questionnaire choices that comes from the host app's own data (drinks, workouts, ...) rather than being
 * fixed on the question. Register implementations in config('cuztomisable.questionnaire.sources').
 */
interface OptionSource
{

    /**
     * The choices to show, narrowed by what the user typed when the list is long.
     *
     * @return array<int, array{value: int|string, label: string, image?: ?string}>
     */
    public function options(?string $search = null, int $limit = 50): array;

    /** Whether an answer is one of this source's choices. */
    public function exists(int|string $value): bool;

}
