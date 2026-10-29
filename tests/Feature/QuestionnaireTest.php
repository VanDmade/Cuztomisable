<?php

namespace VanDmade\Cuztomisable\Tests\Feature;

use VanDmade\Cuztomisable\Models\Questionnaire\Answer;
use VanDmade\Cuztomisable\Models\Questionnaire\Question;
use VanDmade\Cuztomisable\Tests\Concerns\ActsAsAdmin;
use VanDmade\Cuztomisable\Tests\TestCase;

/**
 * The sign-up questionnaire: the admin page (list / add / edit / reorder / delete questions, behind
 * permission:manage-questionnaire) and what a user sees and answers - answers are final, and each one is copied onto
 * its target column only when that table/column is in questionnaire.allowed_tables.
 */
class QuestionnaireTest extends TestCase
{

    use ActsAsAdmin;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $app['config']->set('cuztomisable.questionnaire.enabled', true);
        // The only place answers may be written to in these tests
        $app['config']->set('cuztomisable.questionnaire.allowed_tables', ['users' => ['name']]);
    }

    private function question(string $key, array $attributes = []): Question
    {
        return Question::create(array_merge([
            'key' => $key,
            'order' => Question::count() + 1,
            'type' => 'text',
            'title' => ucfirst(str_replace('_', ' ', $key)).'?',
            'target_key' => 'id',
            'required' => true,
            'active' => true,
        ], $attributes));
    }

    private function adminForm(array $overrides = []): array
    {
        return array_merge([
            'key' => 'favorite_color',
            'order' => 1,
            'type' => 'select',
            'title' => 'Favorite color?',
            'options' => [['value' => 'red', 'label' => 'Red'], ['value' => 'blue', 'label' => 'Blue']],
            'target_key' => 'id',
            'required' => true,
            'active' => true,
        ], $overrides);
    }

    // --- Admin ----------------------------------------------------------------------------------------------------

    public function test_the_admin_routes_turn_away_a_user_without_manage_questionnaire(): void
    {
        $question = $this->question('nickname');
        $this->actingAsUserWithout();

        $this->getJson('/api/questionnaire/questions')->assertForbidden();
        $this->getJson("/api/questionnaire/question/{$question->id}")->assertForbidden();
        $this->postJson('/api/questionnaire/question', $this->adminForm())->assertForbidden();
        $this->postJson('/api/questionnaire/questions/order', ['ids' => [$question->id]])->assertForbidden();
        $this->deleteJson("/api/questionnaire/question/{$question->id}")->assertForbidden();

        $this->assertDatabaseMissing('questionnaire_questions', ['key' => 'favorite_color']);
        $this->assertNotSoftDeleted($question);
    }

    public function test_admin_adds_a_question_with_its_key_tidied_and_blank_text_left_empty(): void
    {
        $this->actingAsUserWith('manage-questionnaire');

        $this->postJson('/api/questionnaire/question', $this->adminForm(['key' => ' Favorite Color! ', 'subtitle' => '   ']))
            ->assertOk();

        $question = Question::where('key', 'favorite_color')->firstOrFail();
        $this->assertNull($question->subtitle);
        $this->assertSame('select', $question->type);
        $this->assertSame([['value' => 'red', 'label' => 'Red'], ['value' => 'blue', 'label' => 'Blue']], $question->options);
    }

    public function test_admin_edits_a_question(): void
    {
        $this->actingAsUserWith('manage-questionnaire');
        $question = $this->question('nickname');

        $this->postJson("/api/questionnaire/question/{$question->id}", $this->adminForm([
            'key' => 'nickname', 'type' => 'text', 'title' => 'What do people call you?', 'options' => [],
        ]))->assertOk();

        $this->assertSame('What do people call you?', $question->fresh()->title);
    }

    public function test_a_key_already_in_use_is_rejected(): void
    {
        $this->actingAsUserWith('manage-questionnaire');
        $this->question('favorite_color');

        $this->postJson('/api/questionnaire/question', $this->adminForm())
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['key']);
    }

    public function test_saving_answers_to_a_table_that_isnt_allowed_is_rejected(): void
    {
        $this->actingAsUserWith('manage-questionnaire');

        $this->postJson('/api/questionnaire/question', $this->adminForm(['target_table' => 'personal_access_tokens', 'target_column' => 'token']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['target_table']);
    }

    public function test_admin_list_is_in_order_with_how_many_have_answered(): void
    {
        $this->actingAsUserWith('manage-questionnaire');
        $second = $this->question('second', ['order' => 2]);
        $first = $this->question('first', ['order' => 1]);
        Answer::create(['user_id' => $this->createUser()->id, 'questionnaire_question_id' => $first->id, 'value' => 'x']);

        $questions = $this->getJson('/api/questionnaire/questions')->assertOk()->json('questions');

        $this->assertSame(['first', 'second'], array_column($questions, 'key'));
        $this->assertSame(1, $questions[0]['answers']);
        $this->assertSame(0, $questions[1]['answers']);
    }

    public function test_admin_reorders_the_questions(): void
    {
        $this->actingAsUserWith('manage-questionnaire');
        $a = $this->question('a');
        $b = $this->question('b');
        $c = $this->question('c');

        $this->postJson('/api/questionnaire/questions/order', ['ids' => [$c->id, $a->id, $b->id]])->assertOk();

        $this->assertSame([1, 2, 3], [$c->fresh()->order, $a->fresh()->order, $b->fresh()->order]);
    }

    public function test_admin_deletes_a_question_keeping_its_answers_and_freeing_the_key(): void
    {
        $this->actingAsUserWith('manage-questionnaire');
        $question = $this->question('nickname');
        $answer = Answer::create(['user_id' => $this->createUser()->id, 'questionnaire_question_id' => $question->id, 'value' => 'Ace']);

        $this->deleteJson("/api/questionnaire/question/{$question->id}")->assertOk();

        $this->assertSoftDeleted('questionnaire_questions', ['id' => $question->id]);
        $this->assertDatabaseHas('questionnaire_answers', ['id' => $answer->id]);
        // The key can be used again
        $this->postJson('/api/questionnaire/question', $this->adminForm(['key' => 'nickname', 'type' => 'text', 'options' => []]))
            ->assertOk();
    }

    // --- Answering ------------------------------------------------------------------------------------------------

    public function test_a_user_sees_only_the_active_questions_they_havent_answered_in_order(): void
    {
        $user = $this->actingAsUserWithout();
        $this->question('second', ['order' => 2]);
        $this->question('first', ['order' => 1]);
        $this->question('retired', ['order' => 3, 'active' => false]);
        $answered = $this->question('answered', ['order' => 4]);
        Answer::create(['user_id' => $user->id, 'questionnaire_question_id' => $answered->id, 'value' => 'done']);

        $response = $this->getJson('/api/questionnaire')->assertOk()->assertJsonPath('completed', false);
        $this->assertSame(['first', 'second'], array_column($response->json('questions'), 'key'));
    }

    public function test_answering_saves_the_answer_and_copies_it_to_an_allowed_column(): void
    {
        $user = $this->actingAsUserWithout();
        $this->question('display_name', ['target_table' => 'users', 'target_column' => 'name']);

        $this->postJson('/api/questionnaire', ['answers' => ['display_name' => 'Maverick']])
            ->assertOk()
            ->assertJsonPath('completed', true);

        $this->assertSame('Maverick', $user->fresh()->name);
        $this->assertSame('Maverick', Answer::where('user_id', $user->id)->sole()->value);
    }

    public function test_answers_are_final(): void
    {
        $user = $this->actingAsUserWithout();
        $this->question('display_name', ['target_table' => 'users', 'target_column' => 'name']);

        $this->postJson('/api/questionnaire', ['answers' => ['display_name' => 'First']])->assertOk();
        $this->postJson('/api/questionnaire', ['answers' => ['display_name' => 'Second']])->assertOk();

        $this->assertSame('First', $user->fresh()->name);
        $this->assertSame(1, Answer::where('user_id', $user->id)->count());
    }

    public function test_a_choice_that_isnt_one_of_the_options_is_rejected(): void
    {
        $this->actingAsUserWithout();
        $this->question('favorite_color', [
            'type' => 'select',
            'options' => [['value' => 'red', 'label' => 'Red']],
        ]);

        $this->postJson('/api/questionnaire', ['answers' => ['favorite_color' => 'green']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['answers.favorite_color']);
        $this->assertSame(0, Answer::count());
    }

    public function test_a_required_question_cant_be_left_blank(): void
    {
        $this->actingAsUserWithout();
        $this->question('nickname');

        $this->postJson('/api/questionnaire', ['answers' => ['nickname' => '']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['answers.nickname']);
    }

    public function test_an_unknown_question_is_rejected(): void
    {
        $this->actingAsUserWithout();

        $this->postJson('/api/questionnaire', ['answers' => ['made_up' => 'x']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['answers.made_up']);
    }

    public function test_optional_questions_dont_hold_up_completion(): void
    {
        $this->actingAsUserWithout();
        $this->question('nickname', ['required' => false]);

        $this->getJson('/api/questionnaire')->assertOk()->assertJsonPath('completed', true);
    }

    public function test_nothing_is_asked_when_the_questionnaire_is_turned_off(): void
    {
        config(['cuztomisable.questionnaire.enabled' => false]);
        $this->actingAsUserWithout();
        $this->question('nickname');

        $this->getJson('/api/questionnaire')
            ->assertOk()
            ->assertJsonPath('enabled', false)
            ->assertJsonPath('completed', true);
    }

}
