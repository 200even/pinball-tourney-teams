<?php

namespace App\Http\Requests\Settings;

use App\Models\User;
use App\Services\MatchplayApiService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],

            'matchplay_api_token' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Validate that the Matchplay API token is working
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->filled('matchplay_api_token')) {
                try {
                    // Create a temporary user instance to test the token
                    $tempUser = new User(['matchplay_api_token' => $this->matchplay_api_token]);
                    $matchplayService = new MatchplayApiService($tempUser);
                    
                    if (!$matchplayService->testConnection()) {
                        $validator->errors()->add('matchplay_api_token', 'The Matchplay API token is invalid or cannot connect to the API.');
                    }
                } catch (\Exception $e) {
                    $validator->errors()->add('matchplay_api_token', 'Unable to validate the Matchplay API token: ' . $e->getMessage());
                }
            }
        });
    }
}
