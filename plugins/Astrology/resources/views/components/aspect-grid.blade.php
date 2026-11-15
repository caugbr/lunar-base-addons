@props([
    'chart',
    'theme' => 'classic',
    'showPositions' => true
])

@php
    $gridService = app(\Plugins\Astrology\Services\AspectGridService::class);
    $data = $gridService->buildGridData($chart, $theme);
    $points = $data['points'];
    $aspectMap = $data['aspectMap'];
    $total = count($points);
@endphp

<div class="aspect-grid-container">
    <div class="table-wrap">
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
                                        {{-- <code>{{ $rowPoint['formatted'] }}</code> --}}
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
    </div>
</div>
