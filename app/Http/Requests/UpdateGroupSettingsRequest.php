<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGroupSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'privacy_level' => 'sometimes|in:public,private',
            'is_discoverable' => 'sometimes|boolean',
            'posting_permission' => 'sometimes|in:anyone,admins_only',
            'new_member_restriction_days' => 'sometimes|integer|min:0',
            'require_post_approval' => 'sometimes|boolean',
            'allow_anonymous_posts' => 'sometimes|boolean',
            'is_paused' => 'sometimes|boolean',
            'welcome_message' => 'nullable|string',
            'avatar' => 'nullable|image|max:2048',
            'cover_photo' => 'nullable|image|max:5120',
        ];
    }
}
