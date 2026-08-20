<?php

namespace VanDmade\Cuztomisable\Http\Requests\Authentication;

use VanDmade\Cuztomisable\Http\Requests\CuztomisableRequest;

class LoginRequest extends CuztomisableRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        $email = config('cuztomisable.login.login_with.email', false);
        $phone = config('cuztomisable.login.login_with.phone', false);
        $type = $phone && strpos($this->input('username'), '@') === false ? 'phone' : ($email ? 'email' : 'username');
        $username = trim((string) $this->input('username'));
        if ($type == 'phone') {
            foreach (['/', '_', '-', '(', ')', ' '] as $i => $key) {
                $username = str_replace($key, '', $username);
            }
            // Converts the value back into an email if for some reason the value is not a cleaned phone number
            if (!is_numeric($username)) {
                $type = 'email';
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
        $params = [
            'type' => 'required|in:email,phone,username',
            'username' => 'required'.($this->input('type') == 'email' ? '|email' : ''),
            'password' => 'required',
        ];
        if (config('cuztomisable.login.remember', false)) {
            $params['remember'] = 'required|in:0,1';
        }
        return $params;
    }

}
