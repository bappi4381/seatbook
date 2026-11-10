@php
    $isEdit = isset($event);
@endphp

<div>
    <x-input-label for="title" :value="__('Title')" />
    <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title', $event->title ?? '')" required autofocus />
    <x-input-error class="mt-2" :messages="$errors->get('title')" />
</div>

<div>
    <x-input-label for="description" :value="__('Description')" />
    <textarea id="description" name="description" rows="4" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('description', $event->description ?? '') }}</textarea>
    <x-input-error class="mt-2" :messages="$errors->get('description')" />
</div>

<div>
    <x-input-label for="venue" :value="__('Venue')" />
    <x-text-input id="venue" name="venue" type="text" class="mt-1 block w-full" :value="old('venue', $event->venue ?? '')" required />
    <x-input-error class="mt-2" :messages="$errors->get('venue')" />
</div>

<div>
    <x-input-label for="starts_at" :value="__('Starts at')" />
    <x-text-input
        id="starts_at"
        name="starts_at"
        type="datetime-local"
        class="mt-1 block w-full"
        :value="old('starts_at', isset($event) ? $event->starts_at->format('Y-m-d\\TH:i') : '')"
        required
    />
    <x-input-error class="mt-2" :messages="$errors->get('starts_at')" />
</div>

@if ($isEdit)
    <div>
        <x-input-label for="status" :value="__('Status')" />
        <select id="status" name="status" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
            <option value="published" @selected(old('status', $event->status) === 'published')>{{ __('Published') }}</option>
            <option value="draft" @selected(old('status', $event->status) === 'draft')>{{ __('Draft') }}</option>
        </select>
        <x-input-error class="mt-2" :messages="$errors->get('status')" />
    </div>

    <div>
        <x-input-label for="price" :value="__('Seat price')" />
        <x-text-input id="price" name="price" type="number" step="0.01" min="0" max="99999" class="mt-1 block w-full" :value="old('price', $event->seats->first()?->price ?? '0')" required />
        <p class="mt-1 text-sm text-gray-600">{{ __('Applies only to seats that are not held or booked. Layout cannot be changed after creation.') }}</p>
        <x-input-error class="mt-2" :messages="$errors->get('price')" />
    </div>
@else
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div>
            <x-input-label for="rows" :value="__('Rows (1-26)')" />
            <x-text-input id="rows" name="rows" type="number" min="1" max="26" class="mt-1 block w-full" :value="old('rows', 5)" required />
            <x-input-error class="mt-2" :messages="$errors->get('rows')" />
        </div>

        <div>
            <x-input-label for="seats_per_row" :value="__('Seats per row (1-30)')" />
            <x-text-input id="seats_per_row" name="seats_per_row" type="number" min="1" max="30" class="mt-1 block w-full" :value="old('seats_per_row', 10)" required />
            <x-input-error class="mt-2" :messages="$errors->get('seats_per_row')" />
        </div>

        <div>
            <x-input-label for="price" :value="__('Seat price')" />
            <x-text-input id="price" name="price" type="number" step="0.01" min="0" max="99999" class="mt-1 block w-full" :value="old('price', 500)" required />
            <x-input-error class="mt-2" :messages="$errors->get('price')" />
        </div>
    </div>
@endif
