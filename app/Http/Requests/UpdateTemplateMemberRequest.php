<?php

namespace App\Http\Requests;

use App\Models\TemplateMember;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTemplateMemberRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $templateMember = $this->route('template_member');

        if (! $templateMember instanceof TemplateMember) {
            return false;
        }

        return $this->user()->can('update', $templateMember);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'class_id' => 'sometimes',
            'language' => 'sometimes',
        ];
    }
}
