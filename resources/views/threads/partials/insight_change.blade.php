<small @class(['insight-change', 'is-up' => $summary['total'] > $summary['previous'], 'is-down' => $summary['total'] < $summary['previous']])>
    @if($summary['change'] !== null)
        {{ $summary['change'] > 0 ? '+' : '' }}{{ number_format($summary['change'], 1, ',', '.') }}%
    @elseif($summary['total'] > 0)
        Mới trong kỳ này
    @else
        0,0%
    @endif
</small>
