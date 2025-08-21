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
                    'ifpa_api_key' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Validate that the Matchplay API token is working
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validate Matchplay API token
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

            // Validate IFPA API key
            if ($this->filled('ifpa_api_key')) {
                try {
                    $response = \Illuminate\Support\Facades\Http::withHeaders([
                        'X-API-Key' => $this->ifpa_api_key,
                        'Accept' => 'application/json',
                    ])->get('https://api.ifpapinball.com/player?players=15925');
                    
                    if (!$response->successful()) {
                        $validator->errors()->add('ifpa_api_key', 'The IFPA API key is invalid or cannot connect to the API.');
                    }
                } catch (\Exception $e) {
                    $validator->errors()->add('ifpa_api_key', 'Unable to validate the IFPA API key: ' . $e->getMessage());
                }
            }
        });
    }
}
