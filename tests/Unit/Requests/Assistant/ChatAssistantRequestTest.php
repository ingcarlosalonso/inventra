<?php

namespace Tests\Unit\Requests\Assistant;

use App\Http\Requests\Assistant\ChatAssistantRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ChatAssistantRequestTest extends TestCase
{
    private function rules(): array
    {
        return (new ChatAssistantRequest)->rules();
    }

    public function test_messages_is_required(): void
    {
        $v = Validator::make([], $this->rules());

        $this->assertFalse($v->passes());
        $this->assertArrayHasKey('messages', $v->errors()->toArray());
    }

    public function test_messages_must_be_array(): void
    {
        $v = Validator::make(['messages' => 'not-an-array'], $this->rules());

        $this->assertFalse($v->passes());
        $this->assertArrayHasKey('messages', $v->errors()->toArray());
    }

    public function test_messages_must_have_at_least_one_item(): void
    {
        $v = Validator::make(['messages' => []], $this->rules());

        $this->assertFalse($v->passes());
        $this->assertArrayHasKey('messages', $v->errors()->toArray());
    }

    public function test_messages_cannot_exceed_50_items(): void
    {
        $messages = array_fill(0, 51, ['role' => 'user', 'content' => 'Hola']);

        $v = Validator::make(['messages' => $messages], $this->rules());

        $this->assertFalse($v->passes());
        $this->assertArrayHasKey('messages', $v->errors()->toArray());
    }

    public function test_message_role_is_required(): void
    {
        $v = Validator::make([
            'messages' => [['content' => 'Hola']],
        ], $this->rules());

        $this->assertFalse($v->passes());
        $this->assertArrayHasKey('messages.0.role', $v->errors()->toArray());
    }

    public function test_message_role_must_be_user_or_assistant(): void
    {
        $v = Validator::make([
            'messages' => [['role' => 'system', 'content' => 'Hola']],
        ], $this->rules());

        $this->assertFalse($v->passes());
        $this->assertArrayHasKey('messages.0.role', $v->errors()->toArray());
    }

    public function test_message_content_is_required(): void
    {
        $v = Validator::make([
            'messages' => [['role' => 'user']],
        ], $this->rules());

        $this->assertFalse($v->passes());
        $this->assertArrayHasKey('messages.0.content', $v->errors()->toArray());
    }

    public function test_message_content_max_2000_chars(): void
    {
        $v = Validator::make([
            'messages' => [['role' => 'user', 'content' => str_repeat('a', 2001)]],
        ], $this->rules());

        $this->assertFalse($v->passes());
        $this->assertArrayHasKey('messages.0.content', $v->errors()->toArray());
    }

    public function test_valid_payload_passes(): void
    {
        $v = Validator::make([
            'messages' => [
                ['role' => 'user', 'content' => 'Hola'],
                ['role' => 'assistant', 'content' => 'Hola, ¿en qué puedo ayudarte?'],
            ],
        ], $this->rules());

        $this->assertTrue($v->passes());
    }
}
