@props(['title', 'description' => null])

<div {{ $attributes->merge(['class' => 'rounded-xl border border-dashed border-slate-300 bg-slate-50 px-6 py-12 text-center']) }}>
    <h3 class="text-lg font-semibold text-slate-900">{{ $title }}</h3>
    @if ($description)
        <p class="mx-auto mt-2 max-w-md text-sm text-slate-600">{{ $description }}</p>
    @endif
    @isset($actions)
        <div class="mt-6 flex justify-center gap-3">
            {{ $actions }}
        </div>
    @endisset
</div>
