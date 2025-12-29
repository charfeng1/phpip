@php
$mdeps = $matter_dependencies->groupBy('role');
$adeps = $other_dependencies->groupBy('Dependency');
@endphp

<p class="font-semibold my-2">{{ __('Matter Dependencies (only the first few are shown)') }}</p>
@forelse($mdeps as $role => $rmdeps)
  <div class="card bg-base-100 border border-base-300 m-1">
    <div class="px-3 py-1.5 bg-base-200/50 border-b border-base-300">
      <span class="font-semibold text-sm">{{ $role }}</span>
    </div>
    <div class="p-2 flex flex-wrap gap-1">
      @foreach($rmdeps as $mal)
        <a class="badge badge-primary badge-sm" href="/matter/{{$mal->matter_id}}">{{ $mal->matter->uid }}</a>
      @endforeach
    </div>
  </div>
@empty
  <span class="text-base-content/60">{{ __('No dependencies') }}</span>
@endforelse

<p class="font-semibold my-2">{{ __('Inter-Actor Dependencies') }}</p>
@forelse($adeps as $dep => $aadeps)
  <div class="card bg-base-100 border border-base-300 m-1">
    <div class="px-3 py-1.5 bg-base-200/50 border-b border-base-300">
      <span class="font-semibold text-sm">{{ $dep }}</span>
    </div>
    <div class="p-2 flex flex-wrap gap-1">
      @foreach($aadeps as $other)
        <a class="link link-primary link-hover text-sm" href="/actor/{{$other->id}}">{{ $other->Actor }}</a>
      @endforeach
    </div>
  </div>
@empty
  <span class="text-base-content/60">{{ __('No dependencies') }}</span>
@endforelse
