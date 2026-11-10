<?php

namespace App\Http\Requests\Attendee;

use App\Models\Seat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreHoldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAttendee() === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'seat_ids' => ['required', 'array', 'min:1', 'max:6'],
            'seat_ids.*' => ['required', 'integer', 'distinct'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $event = $this->route('event');
            $seatIds = $this->input('seat_ids', []);

            $count = Seat::query()
                ->where('event_id', $event->id)
                ->whereIn('id', $seatIds)
                ->count();

            if ($count !== count($seatIds)) {
                $validator->errors()->add('seat_ids', 'All seats must belong to this event.');
            }
        });
    }
}
