@if (request()->boolean('chart_filter'))
    @foreach (request()->only(['chart_filter', 'category', 'status', 'house_id']) as $name => $value)
        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
    @endforeach
    @if ($historyView === 'all')
        <input type="hidden" name="view" value="all">
    @endif
@endif
