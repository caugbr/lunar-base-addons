@props([
    'chart' => null,
    'biwheel' => null,
    'outer' => null,
    'crossAspects' => [],
    'houseOverlays' => [],
    'includeInnerAngles' => true,
    'includeOuterAngles' => true,
    'theme' => 'classic',
    'showPositions' => true
])

@php
    $gridService = app(\Plugins\Astrology\Services\AspectGridService::class);
    $data = $gridService->resolveGridData(
        chart: $chart,
        biwheel: $biwheel,
        outer: $outer,
        crossAspects: $crossAspects,
        houseOverlays: $houseOverlays,
        themeSlug: $theme,
        options: [
            'include_inner_angles' => $includeInnerAngles,
            'include_outer_angles' => $includeOuterAngles,
        ]
    );
    $mode = $data['mode'] ?? 'single';
    $aspectMap = $data['aspectMap'] ?? [];
@endphp

<div class="aspect-grid-container">
    <div class="table-wrap">
        @if($mode === 'biwheel')
            @php
                $rowPoints = $data['rowPoints'] ?? [];
                $colPoints = $data['colPoints'] ?? [];
                $innerLabel = $data['innerLabel'] ?? 'Interno';
                $outerLabel = $data['outerLabel'] ?? 'Externo';
            @endphp

            @if(count($rowPoints) > 0 && count($colPoints) > 0)
                <table class="aspect-grid-table aspect-grid-table-biwheel">
                    <thead>
                        <tr>
                            @if($showPositions)
                                <th class="aspect-grid-corner">
                                    <span class="aspect-corner-outer" title="Colunas: {{ $outerLabel }}">
                                        {{ \Illuminate\Support\Str::limit($outerLabel, 18) }} →
                                    </span>
                                    <span class="aspect-corner-inner" title="Linhas: {{ $innerLabel }}">
                                        ↓ {{ \Illuminate\Support\Str::limit($innerLabel, 18) }}
                                    </span>
                                </th>
                            @endif

                            @foreach($colPoints as $colPoint)
                                @php
                                    $colTitle = trim(
                                        "{$outerLabel} — {$colPoint['label']}: "
                                        . astro_pos_pt($colPoint['formatted'])
                                        . ($colPoint['retrograde'] ? ' (R)' : '')
                                        . ($colPoint['house'] ? " — Casa {$colPoint['house']} interna" : '')
                                    );
                                @endphp
                                <th class="aspect-grid-col-header" title="{{ $colTitle }}">
                                    @if($colPoint['iconUri'])
                                        <img src="{{ $colPoint['iconUri'] }}" alt="{{ $colPoint['label'] }}" class="aspect-point-img" />
                                    @else
                                        <span class="aspect-glyph-text">{{ $colPoint['glyph'] }}</span>
                                    @endif

                                    @if($colPoint['house'])
                                        <span class="aspect-col-house">C{{ $colPoint['house'] }}</span>
                                    @endif
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rowPoints as $rowPoint)
                            <tr>
                                @if($showPositions)
                                    <td class="aspect-grid-summary">
                                        <div class="aspect-point-meta">
                                            <span class="aspect-point-glyph">
                                                @if($rowPoint['iconUri'])
                                                    <img src="{{ $rowPoint['iconUri'] }}" alt="{{ $rowPoint['label'] }}" class="aspect-point-img" />
                                                @else
                                                    {{ $rowPoint['glyph'] }}
                                                @endif
                                            </span>
                                            <span class="aspect-point-name">{{ $rowPoint['label'] }}</span>
                                            <span class="aspect-point-pos">
                                                <x-astrology-position :value="$rowPoint['formatted']" :theme="$theme" />
                                            </span>
                                            @if($rowPoint['retrograde'])
                                                <span class="aspect-point-retro">R</span>
                                            @endif
                                            @if($rowPoint['house'])
                                                <span class="aspect-point-house">C{{ $rowPoint['house'] }}</span>
                                            @endif
                                        </div>
                                    </td>
                                @endif

                                @foreach($colPoints as $colPoint)
                                    @php
                                        $aspect = $aspectMap[$rowPoint['key']][$colPoint['key']] ?? null;
                                    @endphp

                                    @if($aspect)
                                        <td class="aspect-grid-cell aspect-grid-cell-active aspect-type-{{ $aspect['type'] }}" title="{{ $aspect['title'] }}">
                                            <div class="aspect-cell-symbol">{{ $aspect['symbol'] }}</div>
                                            <div class="aspect-cell-orb">
                                                {{ $aspect['orb'] }}<span class="aspect-cell-state">{{ $aspect['state'] }}</span>
                                            </div>
                                        </td>
                                    @else
                                        <td class="aspect-grid-cell aspect-grid-cell-empty"></td>
                                    @endif
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        @else
            @php
                $points = $data['points'] ?? [];
                $total = count($points);
            @endphp

            <table class="aspect-grid-table">
                <tbody>
                    @for($i = 0; $i < $total; $i++)
                        @php $rowPoint = $points[$i]; @endphp
                        <tr>
                            @if($showPositions)
                                <td class="aspect-grid-summary">
                                    <div class="aspect-point-meta">
                                        <span class="aspect-point-glyph">
                                            @if($rowPoint['iconUri'])
                                                <img src="{{ $rowPoint['iconUri'] }}" alt="{{ $rowPoint['label'] }}" class="aspect-point-img" />
                                            @else
                                                {{ $rowPoint['glyph'] }}
                                            @endif
                                        </span>
                                        <span class="aspect-point-name">{{ $rowPoint['label'] }}</span>
                                        <span class="aspect-point-pos">
                                            <x-astrology-position :value="$rowPoint['formatted']" :theme="$theme" />
                                        </span>
                                        @if($rowPoint['retrograde'])
                                            <span class="aspect-point-retro">R</span>
                                        @endif
                                        @if($rowPoint['house'])
                                            <span class="aspect-point-house">C{{ $rowPoint['house'] }}</span>
                                        @endif
                                    </div>
                                </td>
                            @endif

                            @for($j = 0; $j <= $i; $j++)
                                @php
                                    $colPoint = $points[$j];
                                    $isDiagonal = ($i === $j);
                                    $aspect = !$isDiagonal ? ($aspectMap[$rowPoint['key']][$colPoint['key']] ?? null) : null;
                                @endphp

                                @if($isDiagonal)
                                    <td class="aspect-grid-cell aspect-grid-cell-diagonal" title="{{ $rowPoint['label'] }}">
                                        @if($rowPoint['iconUri'])
                                            <img src="{{ $rowPoint['iconUri'] }}" alt="{{ $rowPoint['label'] }}" class="aspect-point-img" />
                                        @else
                                            <span class="aspect-glyph-text">{{ $rowPoint['glyph'] }}</span>
                                        @endif
                                    </td>
                                @elseif($aspect)
                                    <td class="aspect-grid-cell aspect-grid-cell-active aspect-type-{{ $aspect['type'] }}" title="{{ $aspect['title'] }}">
                                        <div class="aspect-cell-symbol">{{ $aspect['symbol'] }}</div>
                                        <div class="aspect-cell-orb">
                                            {{ $aspect['orb'] }}<span class="aspect-cell-state">{{ $aspect['state'] }}</span>
                                        </div>
                                    </td>
                                @else
                                    <td class="aspect-grid-cell aspect-grid-cell-empty"></td>
                                @endif
                            @endfor
                        </tr>
                    @endfor
                </tbody>
            </table>
        @endif
    </div>
</div>
