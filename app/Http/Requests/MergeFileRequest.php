<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class MergeFileRequest extends FormRequest
{
    public function authorize()
    {
        // Only allow users with readwrite permission to upload files
        return Gate::allows('readwrite');
    }

    public function rules()
    {
        return [
            'file' => 'required|file|mimes:docx,dotx|max:10240', // Added max size limit of 10MB
        ];
    }
}
