# Sign-up Questionnaire

A short set of questions a user answers once, right after signing up, before they reach the app. Each question
can write its answer onto a column of the host app's choosing (e.g. `users.favorite_drink_id`), while the
package keeps a separate record of what the user first said. That gives you both numbers later: "they ran 1 mile
a week when they joined, and 10 now".

## How it fits together

| Piece | Where | What it holds |
|---|---|---|
| Settings | `config/questionnaire.php` → `cuztomisable.questionnaire` | On/off, required or not, option sources, the tables and columns answers may be written into |
| Questions | `questionnaire_questions` table | One row per question, seeded by the host app. Text fields hold **language keys** |
| Answers | `questionnaire_answers` table | The user's answer to each question - final once given |
| Current values | The question's `target_table.target_column` | Updated every time the answer is saved, so the app can change it later |

The questions live in the database rather than the config so a host app can seed them, reorder them or switch
them off without a code change, but every piece of text is still a language key, so each language gets its own
copy through Laravel's normal `lang/` files.

## Configuration

Publish or create `config/cuztomisable/questionnaire.php` in the host app (it replaces the package defaults
as a whole, so copy every key):

```php
return [
    'enabled' => env('CUZTOMISABLE_QUESTIONNAIRE', true),
    // When true, the require-questionnaire middleware blocks the API until it's finished
    'required' => true,
    // Choices that come from your own data - see "Option sources"
    'sources' => [
        'drinks' => App\Questionnaire\Sources\Drinks::class,
    ],
    // The only tables and columns answers may be written into - nothing else is ever touched
    'allowed_tables' => [
        'users' => ['over_21', 'favorite_drink_id'],
        'runner_profiles' => ['weekly_miles'],
    ],
];
```

## Questions

List them in the host app's `config/cuztomisable/questionnaire.php` under `questions`, keyed by question key and in
the order they're asked, then load them with the package seeder (call it from your `DatabaseSeeder`):

```
php artisan db:seed --class="VanDmade\Cuztomisable\Database\Seeders\QuestionnaireSeeder"
```

It matches questions by key, so re-running it updates them, and questions added in the admin area that aren't in the
config are left alone. Questions can also be added, edited, reordered and deleted in the admin area (`manage-questionnaire`).

```php
'questions' => [
    'weekly_miles' => [
        'type' => 'number',
        'title' => 'questionnaire.weekly_miles.title',
        'description' => 'questionnaire.weekly_miles.description',
        'settings' => ['min' => 0, 'max' => 500],
        // The answer is also written to runner_profiles.weekly_miles where user_id is the user
        'target_table' => 'runner_profiles',
        'target_column' => 'weekly_miles',
        'target_key' => 'user_id',
        'required' => true,
    ],
],
```

| Column | Notes |
|---|---|
| `type` | `boolean`, `select`, `multi_select`, `text`, `number` or `date` |
| `title`, `subtitle`, `description` | Language keys. Anything that isn't a key is shown as written |
| `image` | Optional image path / URL for the question's screen |
| `options` | Fixed choices for `select` / `multi_select`: `[{"value": "weekly", "label": "questionnaire.frequency.weekly"}]` |
| `source` | Or a key from `cuztomisable.questionnaire.sources` for choices from your own data |
| `settings` | `allow_other` + `other_label` (an "I can't find it" choice), `placeholder`, `min` / `max` (numbers, or the max length of text) |
| `target_table`, `target_column` | Where the current value lives - must be listed in `allowed_tables`, or nothing is written. Leave empty to only keep the first answer |
| `target_key` | The column on `target_table` holding the user's id - `id` when the target is `users` itself. A missing row is created |
| `required`, `active` | Optional questions don't hold up finishing; inactive ones aren't asked |

Multi-select answers are stored as JSON - give the target column an `array` cast on the model when it's the user.

## Option sources

A long or changing list of choices (every drink, every gym) comes from a class implementing
`VanDmade\Cuztomisable\Questionnaire\OptionSource`:

```php
final class Drinks implements OptionSource
{
    public function options(?string $search = null, int $limit = 50): array
    {
        return Drink::when($search, fn($q) => $q->where('name', 'like', "%$search%"))
            ->orderBy('name')->limit($limit)->get()
            ->map(fn($drink) => ['value' => $drink->id, 'label' => $drink->name])
            ->all();
    }

    public function exists(int|string $value): bool
    {
        return Drink::whereKey($value)->exists();
    }
}
```

Register it under a name in `sources`, and set that name as the question's `source`.

## API

All behind `auth:sanctum`.

| Route | What it does |
|---|---|
| `GET /questionnaire` | `enabled`, `completed`, and every active question in `order`, with the user's `answer` so far |
| `GET /questionnaire/{key}/options?search=` | A question's choices - searched, for a source question |
| `POST /questionnaire` | `answers: { key: value }`. Any subset, so the app can save one screen at a time |

An answer is the value itself (`true`, `"weekly"`, `42`, `["sour", "herbal"]`), or `{"other": true}` for the
"I can't find it" choice. Bad answers come back as a 422 with errors under `answers.{key}`.

`POST` returns `completed` and `other` - the keys of questions answered with "I can't find it", so the app can
follow up when it's done (Mixing Maverick offers to create the drink the user couldn't find).

A question is asked until the user answers it - new accounts and existing ones alike, and a question added
later is asked on its own. Once answered it's final: re-sending an answer for it is skipped, and the answer on
record stays as the user's first answer while the host app changes the target column however it likes.

The package adds nothing to the `users` table. "Done" is worked out from the answers: no active, required
question left without one.

## Making it required

1. Turn `required` on in the config.
2. Add the `require-questionnaire` middleware to the routes the user shouldn't reach first. It returns 403
   with `cuztomisable/questionnaire.errors.must_complete` until they finish, and leaves `/questionnaire`,
   `/terms/*`, `/me`, `/logout` and `/refresh` open.
3. On the frontend, check `questionnaire_completed` on the user from `/me` after login and send them to the
   questionnaire when it's `false`.

## Language files

The package's own messages (errors, "I can't find it") are in
`resources/languages/en/questionnaire.php`, published to `lang/en/cuztomisable/questionnaire.php`. The questions'
text lives in the host app's own language files.
