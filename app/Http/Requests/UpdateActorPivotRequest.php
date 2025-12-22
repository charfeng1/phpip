<?php

namespace App\Http\Requests;

use App\Models\ActorPivot;
use Illuminate\Foundation\Http\FormRequest;

class UpdateActorPivotRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Authorization is based on the parent Matter - users who can update
     * a matter can also manage its actor relationships.
     */
    public function authorize(): bool
    {
        $actorPivot = $this->route('actor_pivot');

        if (! $actorPivot instanceof ActorPivot) {
            return false;
        }

        return $this->user()->can('update', $actorPivot->matter);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'date' => 'date',
        ];
    }
}
