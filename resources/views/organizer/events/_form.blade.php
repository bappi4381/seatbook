@php
    $isEdit = isset($event);
@endphp

<section class="space-y-4">
    <div>
        <h3 class="text-lg font-semibold text-slate-900">{{ __('Event details') }}</h3>
        <p class="mt-1 text-sm text-slate-500">{{ __('These details appear on the public event page.') }}</p>
    </div>

    <div>
        <x-input-label for="title" :value="__('Title')" />
        <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title', $event->title ?? '')" required autofocus />
        <x-input-error class="mt-2" :messages="$errors->get('title')" />
    </div>

    <div>
        <x-input-label for="description" :value="__('Description')" />
        <textarea id="description" name="description" rows="4" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">{{ old('description', $event->description ?? '') }}</textarea>
        <x-input-error class="mt-2" :messages="$errors->get('description')" />
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
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
    </div>
</section>

@if ($isEdit)
    <section class="space-y-4 border-t border-slate-100 pt-6">
        <div>
            <h3 class="text-lg font-semibold text-slate-900">{{ __('Publishing & pricing') }}</h3>
            <p class="mt-1 text-sm text-slate-500">{{ __('Seat layout cannot be changed after creation. Price updates skip held or booked seats.') }}</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="status" :value="__('Status')" />
                <select id="status" name="status" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" required>
                    <option value="published" @selected(old('status', $event->status) === 'published')>{{ __('Published') }}</option>
                    <option value="draft" @selected(old('status', $event->status) === 'draft')>{{ __('Draft') }}</option>
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('status')" />
            </div>

            <div>
                <x-input-label for="price" :value="__('Seat price')" />
                <x-text-input id="price" name="price" type="number" step="0.01" min="0" max="99999" class="mt-1 block w-full" :value="old('price', $event->seats->first()?->price ?? '0')" required />
                <p class="mt-1 text-xs text-slate-500">{{ __('Applies only to seats that are not held or booked.') }}</p>
                <x-input-error class="mt-2" :messages="$errors->get('price')" />
            </div>
        </div>
    </section>
@else
    <section class="space-y-4 border-t border-slate-100 pt-6">
        <div>
            <h3 class="text-lg font-semibold text-slate-900">{{ __('Seat layout') }}</h3>
            <p class="mt-1 text-sm text-slate-500">{{ __('Rows are labeled A–Z. Layout is fixed after the event is created.') }}</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
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
    </section>
@endif
