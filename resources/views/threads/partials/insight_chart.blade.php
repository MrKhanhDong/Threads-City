<figure class="insight-chart">
    <svg viewBox="0 0 680 235" role="img" aria-labelledby="insight-chart-title-{{ $chartKey }} insight-chart-description-{{ $chartKey }}">
        <title id="insight-chart-title-{{ $chartKey }}">{{ $label }} theo ngày</title><desc id="insight-chart-description-{{ $chartKey }}">{{ array_sum($values) }} {{ mb_strtolower($label) }} trong {{ count($dates) }} ngày. Xem bảng dữ liệu bên dưới để đọc từng ngày.</desc>
        <defs><linearGradient id="insight-fill-{{ $chartKey }}" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="currentColor" stop-opacity=".18"/><stop offset="100%" stop-color="currentColor" stop-opacity="0"/></linearGradient></defs>
        @foreach($chart['ticks'] as $tick)<line x1="45" x2="665" y1="{{ $tick['y'] }}" y2="{{ $tick['y'] }}" class="insight-chart-grid"/><text x="34" y="{{ $tick['y'] + 4 }}" text-anchor="end" class="insight-chart-label">{{ $tick['label'] }}</text>@endforeach
        <path d="{{ $chart['area'] }}" fill="url(#insight-fill-{{ $chartKey }})"/>
        <path d="{{ $chart['line'] }}" fill="none" stroke="currentColor" stroke-width="2" vector-effect="non-scaling-stroke"/>
        @foreach($chart['points'] as $index => $point)<circle cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="{{ min(10, 310 / (count($values) - 1)) }}" class="insight-chart-point"><title>{{ \Carbon\CarbonImmutable::parse($dates[$index])->format('d/m/Y') }}: {{ $values[$index] }} {{ mb_strtolower($label) }}</title></circle>@endforeach
        <text x="45" y="219" class="insight-chart-label">{{ \Carbon\CarbonImmutable::parse($dates[0])->format('d/m') }}</text><text x="355" y="219" text-anchor="middle" class="insight-chart-label">{{ \Carbon\CarbonImmutable::parse($dates[intdiv(count($dates) - 1, 2)])->format('d/m') }}</text><text x="665" y="219" text-anchor="end" class="insight-chart-label">{{ \Carbon\CarbonImmutable::parse($dates[array_key_last($dates)])->format('d/m') }}</text>
    </svg>
    <figcaption>{{ $label }} mỗi ngày @if(array_sum($values) === 0)· Chưa có số liệu phát sinh trong kỳ này.@endif</figcaption>
    <details class="insight-data-table"><summary>Xem dữ liệu theo ngày</summary><div><table><thead><tr><th scope="col">Ngày</th><th scope="col">{{ $label }}</th></tr></thead><tbody>@foreach($dates as $index => $date)<tr><th scope="row">{{ \Carbon\CarbonImmutable::parse($date)->format('d/m/Y') }}</th><td>{{ number_format($values[$index], 0, ',', '.') }}</td></tr>@endforeach</tbody></table></div></details>
</figure>
