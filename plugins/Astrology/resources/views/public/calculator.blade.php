@if($publicLayout)
    @include('astrology::public.partials.calculator-extended')
@else
    @include('astrology::public.partials.calculator-standalone')
@endif
