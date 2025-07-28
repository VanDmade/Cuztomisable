<?php

namespace VanDmade\Cuztomisable\Requests\Authentication;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function messages(): array
    {
        return [
            'required' => __('cuztomisable/global.form.required'),
            'email' => __('cuztomisable/global.form.email'),
            'exists' => __('cuztomisable/global.form.exists'),
            'in' => __('cuztomisable/global.form.in'),
        ];
    }

    public function prepareForValidation(): void
    {
        $email = config('cuztomisable.login.login_with.email', false);
        $phone = config('cuztomisable.login.login_with.phone', false);
        $type = $phone && strpos($this->input('username'), '@') == false ? 'phone' : ($email ? 'email' : 'username');
        $username = $this->input('username');
        if ($type == 'phone') {
            foreach (['/', '_', '-', '(', ')', ' '] as $i => $key) {
                $username = str_replace($key, '', $username);
            }
            // Converts the value back into an email if for some reason the value is not a cleaned phone number
            if (!is_numeric($username)) {
                $type = 'email';
            }
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
