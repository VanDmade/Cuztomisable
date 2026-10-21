<?php

namespace VanDmade\Cuztomisable\Services;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use VanDmade\Cuztomisable\Models\Questionnaire\Answer;
use VanDmade\Cuztomisable\Models\Questionnaire\Question;
use VanDmade\Cuztomisable\Questionnaire\OptionSource;

/**
 * Runs the sign-up questionnaire: sends out the questions in the user's language, checks and stores their answers,
 * copies each answer onto the record the question points at, and marks the questionnaire done.
 *
 * Answers given before the questionnaire is finished can still be changed. After that the stored answer is the
 * user's first answer for good, while the target column carries on with whatever they change it to.
 */
final class QuestionnaireService
{

    public function enabled(): bool
    {
        return (bool) config('cuztomisable.questionnaire.enabled', false);
    }

    /** Whether the user still has to finish the questionnaire before using the app. */
    public function needsToComplete(Model $user): bool
    {
        return $this->enabled() && $user->questionnaire_completed_at === null && $this->questions()->isNotEmpty();
    }

    /** The intro shown before the first question, translated. */
    public function intro(): array
    {
        return [
            'title' => $this->translate(config('cuztomisable.questionnaire.title')),
            'subtitle' => $this->translate(config('cuztomisable.questionnaire.subtitle')),
            'description' => $this->translate(config('cuztomisable.questionnaire.description')),
            'image' => config('cuztomisable.questionnaire.image'),
        ];
    }

    /** @return Collection<int, Question> */
    public function questions(): Collection
    {
        return Question::where('active', true)->orderBy('order')->orderBy('id')->get();
    }

    /** Every active question as the app shows it, with the user's answer so far. */
    public function forUser(Model $user): array
    {
        $answers = Answer::where('user_id', $user->id)->get()->keyBy('questionnaire_question_id');
        return $this->questions()->map(function(Question $question) use ($answers) {
            $answer = $answers->get($question->id);
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
                'answer' => $answer ? ['value' => $answer->value, 'other' => $answer->other] : null,
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
     * Stores the given answers (keyed by question key), copies them onto their target columns, and finishes the
     * questionnaire once every required question has an answer.
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
        $finished = $user->questionnaire_completed_at !== null;
        DB::transaction(function() use ($user, $questions, $clean, $finished) {
            foreach ($clean as $key => [$value, $other]) {
                $question = $questions->get($key);
                $existing = Answer::where('user_id', $user->id)->where('questionnaire_question_id', $question->id)->first();
                // Once the questionnaire is done the first answer stays as it was
                if (!$existing || !$finished) {
                    Answer::updateOrCreate(
                        ['user_id' => $user->id, 'questionnaire_question_id' => $question->id],
                        ['value' => $value, 'other' => $other]
                    );
                }
                if (!$other) {
                    $this->writeTarget($user, $question, $value);
                }
            }
            if (!$finished && $this->allRequiredAnswered($user, $questions)) {
                $user->questionnaire_completed_at = now();
                $user->save();
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
            'completed' => $user->questionnaire_completed_at !== null,
            'other' => $other,
        ];
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

    /** Copies the answer onto the column the question points at, creating the row if the table needs one. */
    private function writeTarget(Model $user, Question $question, mixed $value): void
    {
        if (!$question->target_table || !$question->target_column) {
            return;
        }
        $table = $question->target_table;
        if (!in_array($table, (array) config('cuztomisable.questionnaire.writable_tables', []), true) ||
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

    private function allRequiredAnswered(Model $user, Collection $questions): bool
    {
        $required = $questions->where('required', true)->pluck('id');
        $answered = Answer::where('user_id', $user->id)->whereIn('questionnaire_question_id', $required)->count();
        return $answered >= $required->count();
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
