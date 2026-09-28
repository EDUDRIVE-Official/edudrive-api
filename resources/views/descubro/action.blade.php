<form method="POST" action="{{ route('descubro.crossing.update') }}">
    @csrf
    <input type="hidden" name="revision" value="{{ $run['revision'] }}">
    <input type="hidden" name="action" value="{{ $action }}">
    @isset($choice)<input type="hidden" name="choice" value="{{ $choice }}">@endisset
    <button type="submit" class="dc-button {{ isset($choice) ? 'dc-choice' : '' }}">
        @isset($choice)@include('descubro.choice-picture')@endisset
        <span class="dc-choice-label" @isset($choice) data-dc-read @endisset>{{ $label }}</span>
        @isset($choice)<span class="dc-choice-arrow" aria-hidden="true">→</span>@endisset
    </button>
</form>
