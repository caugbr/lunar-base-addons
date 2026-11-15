<?php

namespace Plugins\Astrology\DTOs;

class BiwheelChartData
{
    public function __construct(
        public readonly ChartLayerData $inner,
        public readonly ChartLayerData $outer,
        public readonly array $crossAspects = [],
        public readonly ?string $title = null,
        public readonly array $houseOverlays = [],
    ) {
    }

    public function isReady(): bool
    {
        return $this->inner->isReady() && $this->outer->isReady();
    }
}
