<?php

namespace App\Http\Requests\Organizer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('event'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // Seat layout (rows / seats per row) is not editable after creation
        // to avoid breaking existing bookings and holds.
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'venue' => ['required', 'string', 'max:255'],
            'starts_at' => ['required', 'date'],
            'status' => ['required', Rule::in(['published', 'draft'])],
            'price' => ['required', 'numeric', 'min:0', 'max:99999'],
        ];
    }
}
