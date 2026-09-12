@if (request()->boolean('chart_filter'))
    <input type="hidden" name="chart_filter" value="1">
    @if (request()->filled('weekday'))
        <input type="hidden" name="weekday" value="{{ request('weekday') }}">
    @endif
@endif
