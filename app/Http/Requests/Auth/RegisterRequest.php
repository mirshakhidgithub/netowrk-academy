><?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Unique;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'min:2', 'max:255', 'regex:/^[a-zA-Z\s\'-]+$/'],
            'username' => [
                'nullable',
                'string',
                'max:100',
                'regex:/^[a-zA-Z0-9_-]+$/',
                (new Unique('users', 'username'))->whereNull('deleted_at'),
            ],
            'email'    => [
                'required',
                'string',
                'email:rfc,dns',
                'max:255',
                (new Unique('users', 'email'))->whereNull('deleted_at'),
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[a-zA-Z\d@$!%*?&]+$/',
                'confirmed',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => 'Name can only contain letters, spaces, hyphens, and apostrophes.',
            'username.regex' => 'Username can only contain letters, numbers, hyphens, and underscores.',
            'username.unique' => 'This username is already taken.',
            'email.unique' => 'This email is already registered.',
            'email.email' => 'Please provide a valid email address.',
            'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character (@$!%*?&).',
            'password.confirmed' => 'Password confirmation does not match.',
        ];
    }
}
