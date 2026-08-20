<?php

namespace VanDmade\Cuztomisable\Http\Requests\Authentication\Passwords;

use VanDmade\Cuztomisable\Http\Requests\CuztomisableRequest;

class ForgotRequest extends CuztomisableRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        $email = config('cuztomisable.account.passwords.reset_with.email', false);
        $phone = config('cuztomisable.account.passwords.reset_with.phone', false);
        $type = $phone && strpos($this->input('username'), '@') === false ? 'phone' : ($email ? 'email' : 'username');
        $username = trim((string) $this->input('username'));
        if ($type == 'phone') {
            foreach (['/', '_', '-', '(', ')', ' '] as $i => $key) {
                $username = str_replace($key, '', $username);
            }
        } elseif ($type === 'email') {
            $username = strtolower($username);
        }
        $this->merge([
            'type' => $type,
            'username' => $username,
        ]);
    }

    public function rules(): array
    {
        return [
            'type' => 'required|in:email,phone,username',
            'username' => 'required'.($this->input('type') == 'email' ? '|email' : ''),
        ];
    }

}
