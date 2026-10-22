<?php

namespace VanDmade\Cuztomisable\Services;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use VanDmade\Cuztomisable\Models\Questionnaire\Answer;
use VanDmade\Cuztomisable\Models\Questionnaire\Question;
use VanDmade\Cuztomisable\Questionnaire\OptionSource;

/**
 * Runs the questionnaire: sends out the questions the user hasn't answered yet in their language, checks and stores
 * their answers, and copies each answer onto the record the question points at.
 *
 * Any question without an answer is asked - new and existing accounts alike, and questions added later on. Once a
 * question is answered it's done for good: the stored answer is the user's first answer, while the target column
 * carries on with whatever the app later changes it to. Nothing is stored on the users table by the package.
 */
final class QuestionnaireService
{

    public function enabled(): bool
    {
        return (bool) config('cuztomisable.questionnaire.enabled', false);
    }

    /** Whether the user still has required questions to answer before using the app. */
    public function needsToComplete(Model $user): bool
    {
        return $this->enabled() && $this->pending($user)->where('required', true)->isNotEmpty();
    }

    /** The questions in use that the user hasn't answered yet, in the order they're asked. */
    public function pending(Model $user): Collection
    {
        $answered = Answer::where('user_id', $user->id)->pluck('questionnaire_question_id')->flip();
        return $this->questions()->reject(fn(Question $question) => $answered->has($question->id))->values();
    }

    /** The questions in use, in the order they're asked. @return Collection<int, Question> */
    public function questions(): Collection
    {
        return Question::where('active', true)->orderBy('order')->orderBy('id')->get();
    }

    /** The questions the user still has to answer, as the app shows them. */
    public function forUser(Model $user): array
    {
        return $this->pending($user)->map(function(Question $question) {
            return [
                'key' => $question->key,
                'type' => $question->type,
                'title' => $this->translate($question->title),
                'subtitle' => $this->translate($question->subtitle),
                'description' => $this->translate($question->description),
                'image' => $question->image,
                'required' => $question->required,
                // Fixed choices come along; a source's choices are fetched (and searched) separately
                'options' => $question->options ? $this->translateOptions($question->options) : null,
                'source' => $question->source,
                'allow_other' => (bool) $question->setting('allow_other', false),
                'other_label' => $this->translate($question->setting('other_label', 'cuztomisable/questionnaire.other')),
                'placeholder' => $this->translate($question->setting('placeholder')),
                'min' => $question->setting('min'),
                'max' => $question->setting('max'),
            ];
        })->values()->all();
    }

    /** A source question's choices, optionally narrowed by a search. */
    public function options(string $key, ?string $search = null): array
    {
        $question = $this->find($key);
        if (!$question->source) {
            return $question->options ? $this->translateOptions($question->options) : [];
        }
        return $this->source($question)->options($search);
    }

    /**
     * Stores the given answers (keyed by question key) and copies them onto their target columns. A question that
     * already has an answer is skipped - answers are final.
     *
     * @param array<string, mixed> $answers An answer is the value itself, or ['other' => true] for "I can't find it"
     * @return array{completed: bool, other: string[]}
     */
    public function save(Model $user, array $answers): array
    {
        $questions = $this->questions()->keyBy('key');
        $errors = [];
        $clean = [];
        foreach ($answers as $key => $answer) {
            $question = $questions->get($key);
            if (!$question) {
                $errors["answers.$key"] = __('cuztomisable/questionnaire.errors.unknown_question');
                continue;
            }
            try {
                $clean[$key] = $this->check($question, $answer);
            } catch (Exception $error) {
                $errors["answers.$key"] = $error->getMessage();
            }
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
        $answered = Answer::where('user_id', $user->id)->pluck('questionnaire_question_id')->flip();
        DB::transaction(function() use ($user, $questions, $clean, $answered) {
            foreach ($clean as $key => [$value, $other]) {
                $question = $questions->get($key);
                if ($answered->has($question->id)) {
                    continue;
                }
                Answer::create([
                    'user_id' => $user->id,
                    'questionnaire_question_id' => $question->id,
                    'value' => $value,
                    'other' => $other,
                ]);
                if (!$other) {
                    $this->writeTarget($user, $question, $value);
                }
            }
        });
        $other = Answer::where('user_id', $user->id)->where('other', true)
            ->whereIn('questionnaire_question_id', $questions->pluck('id'))
            ->with('question:id,key')
            ->get()
            ->pluck('question.key')
            ->values()
            ->all();
        return [
            'completed' => !$this->needsToComplete($user),
            'other' => $other,
        ];
    }

    /* ---------------- Admin ---------------- */

    /** Every question, in use or not, with its text translated and how many people have answered it. */
    public function adminList(): array
    {
        return Question::withCount('answers')->orderBy('order')->orderBy('id')->get()
            ->map(fn(Question $question) => [
                'id' => $question->id,
                'key' => $question->key,
                'order' => $question->order,
                'type' => $question->type,
                'title' => $this->translate($question->title),
                'title_key' => $question->title,
                'target' => $question->target_table ? $question->target_table.'.'.$question->target_column : null,
                'required' => $question->required,
                'active' => $question->active,
                'answers' => $question->answers_count,
            ])->values()->all();
    }

    /** What the admin form can pick from - question types, option sources and the tables answers may go into. */
    public function adminChoices(): array
    {
        return [
            'types' => Question::TYPES,
            'sources' => array_keys((array) config('cuztomisable.questionnaire.sources', [])),
            // table => the columns allowed on it
            'tables' => $this->allowedTables(),
        ];
    }

    /** A question as the admin form edits it - the stored language keys, with its settings laid flat. */
    public function adminGet(int $id): array
    {
        $question = Question::findOrFail($id);
        return [
            'id' => $question->id,
            'key' => $question->key,
            'order' => $question->order,
            'type' => $question->type,
            'title' => $question->title,
            'subtitle' => $question->subtitle,
            'description' => $question->description,
            'image' => $question->image,
            'source' => $question->source,
            'options' => array_map(fn($option) => [
                'value' => is_bool($option['value']) ? ($option['value'] ? 'true' : 'false') : (string) $option['value'],
                'label' => $option['label'] ?? '',
            ], $question->options ?? []),
            'allow_other' => (bool) $question->setting('allow_other', false),
            'other_label' => $question->setting('other_label'),
            'placeholder' => $question->setting('placeholder'),
            'min' => $question->setting('min'),
            'max' => $question->setting('max'),
            'target_table' => $question->target_table,
            'target_column' => $question->target_column,
            'target_key' => $question->target_key,
            'required' => $question->required,
            'active' => $question->active,
        ];
    }

    /** Puts the questions in the given order - ids first to last. */
    public function adminReorder(array $ids): void
    {
        DB::transaction(function() use ($ids) {
            foreach (array_values($ids) as $position => $id) {
                Question::whereKey($id)->update(['order' => $position + 1]);
            }
        });
    }

    /**
     * Soft-deletes a question - it's no longer asked or listed, and the answers to it stay. Its key gets a suffix so
     * the key can be used again for a new question.
     */
    public function adminDelete(int $id): void
    {
        DB::transaction(function() use ($id) {
            $question = Question::findOrFail($id);
            $question->key = mb_substr($question->key, 0, 200).'__deleted_'.$question->id;
            $question->save();
            $question->softDelete();
        });
    }

    /** Adds or updates a question from the admin form. */
    public function adminSave(array $data, ?int $id = null): Question
    {
        $question = $id ? Question::findOrFail($id) : new Question();
        // Form posts send empty fields as empty text - those are stored as nothing
        $data = array_map(fn($value) => $value === '' ? null : $value, $data);
        // A new image is uploaded and its address stored; otherwise the one already there is kept (or removed)
        if (($data['image_file'] ?? null) instanceof UploadedFile) {
            $upload = app(ImageService::class)->upload($data['image_file'], 'questionnaire');
            if (!$upload) {
                throw new Exception(__('cuztomisable/questionnaire.admin.errors.image_failed'), 422);
            }
            $data['image'] = $upload->full_url;
        }
        $choices = in_array($data['type'], ['select', 'multi_select'], true);
        $settings = array_filter([
            'allow_other' => !empty($data['allow_other']) ? true : null,
            'other_label' => $data['other_label'] ?? null,
            'placeholder' => $data['placeholder'] ?? null,
            'min' => $data['min'] ?? null,
            'max' => $data['max'] ?? null,
        ], fn($value) => $value !== null && $value !== '');
        $question->fill([
            'key' => $data['key'],
            'order' => (int) $data['order'],
            'type' => $data['type'],
            'title' => $data['title'],
            'subtitle' => $data['subtitle'] ?? null,
            'description' => $data['description'] ?? null,
            'image' => $data['image'] ?? null,
            'source' => $choices ? ($data['source'] ?? null) : null,
            // A yes / no question's choices are true and false, not the words typed in
            'options' => $choices || $data['type'] === 'boolean'
                ? (empty($data['options']) || !empty($data['source']) ? null : array_map(fn($option) => [
                    'value' => $data['type'] === 'boolean' ? filter_var($option['value'], FILTER_VALIDATE_BOOLEAN) : $option['value'],
                    'label' => $option['label'],
                ], $data['options']))
                : null,
            'settings' => $settings ?: null,
            'target_table' => $data['target_table'] ?? null,
            'target_column' => !empty($data['target_table']) ? ($data['target_column'] ?? null) : null,
            'target_key' => $data['target_key'],
            'required' => (bool) ($data['required'] ?? false),
            'active' => (bool) ($data['active'] ?? false),
        ]);
        $question->save();
        return $question;
    }

    private function find(string $key): Question
    {
        $question = Question::where('active', true)->where('key', $key)->first();
        if (!$question) {
            throw new Exception(__('cuztomisable/questionnaire.errors.unknown_question'), 404);
        }
        return $question;
    }

    private function source(Question $question): OptionSource
    {
        $class = config('cuztomisable.questionnaire.sources.'.$question->source);
        if (!$class || !is_a($class, OptionSource::class, true)) {
            throw new Exception(__('cuztomisable/questionnaire.errors.unknown_source', ['source' => $question->source]), 500);
        }
        return app($class);
    }

    /**
     * Checks one answer against its question and turns it into the value that gets stored.
     *
     * @return array{0: mixed, 1: bool} The value, and whether they picked "I can't find it"
     */
    private function check(Question $question, mixed $answer): array
    {
        if (is_array($answer) && !empty($answer['other']) && $question->type !== 'multi_select') {
            if (!$question->setting('allow_other', false)) {
                throw new Exception(__('cuztomisable/questionnaire.errors.invalid_choice'));
            }
            return [null, true];
        }
        $empty = $answer === null || $answer === '' || $answer === [];
        if ($empty) {
            if ($question->required) {
                throw new Exception(__('cuztomisable/questionnaire.errors.required'));
            }
            return [null, false];
        }
        $value = match ($question->type) {
            'boolean' => $this->boolean($answer),
            'number' => $this->number($question, $answer),
            'date' => $this->date($answer),
            'text' => $this->text($question, $answer),
            'select' => $this->choice($question, $answer),
            'multi_select' => array_values(array_map(fn($choice) => $this->choice($question, $choice), (array) $answer)),
            default => throw new Exception(__('cuztomisable/questionnaire.errors.invalid_type')),
        };
        return [$value, false];
    }

    private function boolean(mixed $answer): bool
    {
        $value = filter_var($answer, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($value === null) {
            throw new Exception(__('cuztomisable/questionnaire.errors.invalid_boolean'));
        }
        return $value;
    }

    private function number(Question $question, mixed $answer): int|float
    {
        if (!is_numeric($answer)) {
            throw new Exception(__('cuztomisable/questionnaire.errors.invalid_number'));
        }
        $value = $answer + 0;
        $min = $question->setting('min');
        $max = $question->setting('max');
        if (($min !== null && $value < $min) || ($max !== null && $value > $max)) {
            throw new Exception(__('cuztomisable/questionnaire.errors.out_of_range', ['min' => $min ?? '-', 'max' => $max ?? '-']));
        }
        return $value;
    }

    private function date(mixed $answer): string
    {
        $time = is_string($answer) ? strtotime($answer) : false;
        if ($time === false) {
            throw new Exception(__('cuztomisable/questionnaire.errors.invalid_date'));
        }
        return date('Y-m-d', $time);
    }

    private function text(Question $question, mixed $answer): string
    {
        if (!is_scalar($answer)) {
            throw new Exception(__('cuztomisable/questionnaire.errors.invalid_text'));
        }
        $value = trim((string) $answer);
        if (mb_strlen($value) > (int) $question->setting('max', 255)) {
            throw new Exception(__('cuztomisable/questionnaire.errors.too_long', ['max' => $question->setting('max', 255)]));
        }
        return $value;
    }

    private function choice(Question $question, mixed $answer): int|string
    {
        if (!is_int($answer) && !is_string($answer)) {
            throw new Exception(__('cuztomisable/questionnaire.errors.invalid_choice'));
        }
        $valid = $question->source
            ? $this->source($question)->exists($answer)
            : collect($question->options ?? [])->contains(fn($option) => (string) $option['value'] === (string) $answer);
        if (!$valid) {
            throw new Exception(__('cuztomisable/questionnaire.errors.invalid_choice'));
        }
        return $answer;
    }

    /** config('cuztomisable.questionnaire.allowed_tables'), as table => list of column names. */
    public function allowedTables(): array
    {
        return collect((array) config('cuztomisable.questionnaire.allowed_tables', []))
            ->map(fn($columns) => array_values((array) $columns))
            ->all();
    }

    /** Whether answers may be written into this table's column. */
    public function allowedColumn(?string $table, ?string $column): bool
    {
        return $table !== null && $column !== null && in_array($column, $this->allowedTables()[$table] ?? [], true);
    }

    /** Copies the answer onto the column the question points at, creating the row if the table needs one. */
    private function writeTarget(Model $user, Question $question, mixed $value): void
    {
        if (!$question->target_table || !$question->target_column) {
            return;
        }
        $table = $question->target_table;
        if (!$this->allowedColumn($table, $question->target_column) ||
            !Schema::hasColumn($table, $question->target_column) ||
            !Schema::hasColumn($table, $question->target_key)) {
            throw new Exception(__('cuztomisable/questionnaire.errors.invalid_target', ['key' => $question->key]), 500);
        }
        // Through the model when it's the user's own row, so its casts apply (a multi-select stored as an array cast)
        if ($table === $user->getTable() && $question->target_key === $user->getKeyName()) {
            $column = $question->target_column;
            $user->forceFill([$column => is_array($value) && !$user->hasCast($column) ? json_encode($value) : $value])->save();
            return;
        }
        DB::table($table)->updateOrInsert(
            [$question->target_key => $user->id],
            [$question->target_column => is_array($value) ? json_encode($value) : $value]
        );
    }

    private function translateOptions(array $options): array
    {
        return array_map(fn($option) => [...$option, 'label' => $this->translate($option['label'] ?? (string) $option['value'])], $options);
    }

    // A language key becomes its text; anything that isn't a key is shown as written
    private function translate(?string $key): ?string
    {
        return $key === null || $key === '' ? null : __($key);
    }

}
