@inject('sharePoint', 'App\Services\SharePointService')
@php
$titles = $matter->titles->groupBy('type_name');
$classifiers = $matter->classifiers->groupBy('type_code');
$actors = $matter->actors->groupBy('role_name');
@endphp

@extends('layouts.app')

@section('content')
<div class="grid grid-cols-12 gap-1 mb-1">
  <div class="col-span-3">
    <div id="refsPanel" class="card bg-base-100 border-2 border-primary h-full">
      <div class="card-title bg-primary text-primary-content px-2 py-1.5 text-sm flex justify-between items-center group">
        <div class="flex items-center gap-2">
          <a class="text-primary-content font-semibold {{ $matter->dead ? 'line-through' : '' }}"
             href="/matter?Ref= {{ $matter->caseref }}"
             title="{{ __('See family') }}"
             target="_blank"
             id="uid">
             {{ $matter->uid }}
          </a>
          <span class="badge badge-ghost badge-sm text-primary-content/80">({{ $matter->category->category }})</span>
        </div>
        <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
          @php
              $sharePointLink = null;
              if ($sharePoint->isEnabled()) {
                  $sharePointLink = $sharePoint->findFolderLink(
                      $matter->caseref,
                      $matter->suffix,
                      ''
                  );
              }
          @endphp
          <a class="btn btn-ghost btn-xs text-warning"
            href="{{ $sharePointLink ?? '/matter?Ref=' . $matter->caseref }}"
            title="{{ $sharePointLink ? __('Go to documents') : __('See family') }}"
            target="_blank">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
            </svg>
          </a>
          @can('readwrite')
          <a class="btn btn-ghost btn-xs text-primary-content"
            data-modal-target="#ajaxModal" href="/matter/{{ $matter->id }}/edit" title="{{ __('Advanced matter edition') }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
          </a>
          @endcan
        </div>
      </div>
      <div class="card-body p-2">
        <dl class="grid grid-cols-12 gap-y-0.5 text-sm mb-2">
          @if ($matter->container_id)
          <dt class="col-span-4 text-right text-base-content/70 pr-2">{{ __('Container') }}:</dt>
          <dd class="col-span-8">
            <a href="/matter/{{ $matter->container_id }}" title="{{ __('See container') }}" class="link link-primary">
              {{ $matter->container->uid }}
            </a>
          </dd>
          @endif
          @if ($matter->parent_id)
          <dt class="col-span-4 text-right text-base-content/70 pr-2">{{ __('Parent') }}:</dt>
          <dd class="col-span-8">
            <a href="/matter/{{ $matter->parent_id }}" title="{{ __('See parent') }}" class="link link-primary">
              {{ $matter->parent->uid }}
            </a>
          </dd>
          @endif
          @if ($matter->alt_ref)
          <dt class="col-span-4 text-right text-base-content/70 pr-2">{{ __('Alt. ref') }}:</dt>
          <dd class="col-span-8">{{ $matter->alt_ref }}</dd>
          @endif
          @if ($matter->expire_date)
          <dt class="col-span-4 text-right text-base-content/70 pr-2">{{ __('Expiry') }}:</dt>
          <dd class="col-span-8">{{ \Carbon\Carbon::parse($matter->expire_date)->isoFormat('L') }}</dd>
          @endif
        </dl>
        <div class="alert alert-info py-1 text-center text-sm">
          <span><b>{{ __('Responsible') }}:</b> {{$matter->responsible}}</span>
        </div>
      </div>
      <div class="card-actions p-2 pt-0">
        @can('readwrite')
        <div class="join w-full">
          <a class="btn btn-info btn-xs join-item flex-1" href="/matter/create?matter_id={{ $matter->id }}&operation=descendant" data-modal-target="#ajaxModal" data-size="modal-sm" title="{{ __('Create descendant') }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            {{ __('Desc') }}
          </a>
          <a class="btn btn-info btn-xs join-item flex-1" href="/matter/create?matter_id={{ $matter->id }}&operation=clone" data-modal-target="#ajaxModal" data-size="modal-sm" title="{{ __('Clone') }}">
            ⧉ {{ __('Clone') }}
          </a>
          <a class="btn btn-info btn-xs join-item flex-1 {{ $matter->countryInfo->goesnational ? '' : 'btn-disabled' }}" href="/matter/{{ $matter->id }}/createN" data-modal-target="#ajaxModal" data-size="modal-sm" title="{{ __('Enter in national phase') }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9" />
            </svg>
            {{ __('Nat.') }}
          </a>
        </div>
        @endcan
      </div>
    </div>
  </div>
  @php
    $imageClassifier = $matter->classifiers->firstWhere('type_code', 'IMG');
  @endphp
  <div class="col-span-9 relative" x-data="imageUpload({
    hasImage: {{ $imageClassifier ? 'true' : 'false' }},
    imageUrl: '{{ $imageClassifier ? "/classifier/{$imageClassifier->id}/img" : "" }}',
    classifierId: {{ $imageClassifier?->id ?? 'null' }},
    matterId: {{ $matter->container_id ?? $matter->id }}
  })">
    <div class="grid gap-1 h-full" :class="expanded ? 'grid-cols-12' : 'grid-cols-1'">
      <div :class="expanded ? 'col-span-9' : 'col-span-12'">
        <div class="card bg-base-100 border border-base-300 p-2 h-full relative">
          <dl id="titlePanel" class="text-sm" x-data="{ showAddTitle: false }">
            @foreach ( $titles as $type_name => $title_group )
              <dt class="mt-2 font-semibold text-base-content/70">
                {{ $type_name }}
              </dt>
              @foreach ( $title_group as $title )
                <dd class="mb-0" data-resource="/classifier/{{ $title->id }}" data-name="value" contenteditable>
                  {{ $title->value }}
                </dd>
              @endforeach
            @endforeach
            @can('readwrite')
            <div class="mt-2">
              <a class="badge badge-primary badge-sm cursor-pointer float-right" role="button" @click="showAddTitle = !showAddTitle">+</a>
            </div>
            @endcan
            <div id="addTitleCollapse" class="mt-2" x-show="showAddTitle" x-transition x-cloak>
              <form id="addTitleForm" autocomplete="off">
                <div class="flex gap-2">
                  <input type="hidden" name="matter_id" value="{{ $matter->container_id ?? $matter->id }}">
                  <div class="w-24">
                    <input type="hidden" name="type_code">
                    <input type="text" class="input input-bordered input-sm w-full" data-ac="/classifier-type/autocomplete/1" data-actarget="type_code" data-aclength="0" placeholder="Type" autocomplete="off">
                  </div>
                  <div class="flex-1">
                    <div class="join w-full">
                      <input type="text" class="input input-bordered input-sm join-item flex-1" name="value" placeholder="Value" autocomplete="off">
                      <button type="button" class="btn btn-primary btn-sm join-item" id="addTitleSubmit">✓</button>
                    </div>
                  </div>
                </div>
              </form>
            </div>
          </dl>
          {{-- Image icon hint when collapsed and no image --}}
          <button type="button"
                  @click="expanded = true"
                  class="btn btn-sm btn-ghost btn-circle absolute top-2 right-2 opacity-0 hover:opacity-100 transition-opacity"
                  x-show="!expanded && !imageUrl">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
          </button>
        </div>
      </div>
      <div class="col-span-3" x-show="expanded" x-transition>
        <div class="card bg-base-200 border border-base-300 p-1 relative h-[150px]">
          {{-- Image display --}}
          <div x-show="imageUrl" class="h-full relative">
            <div class="h-full flex items-center justify-center">
              <img :src="imageUrl" class="max-h-[140px] max-w-full object-contain">
            </div>

            {{-- Edit button on hover --}}
            <button type="button"
                    @click="showControls = true"
                    class="btn btn-xs btn-ghost btn-circle absolute top-1 right-1 opacity-0 hover:opacity-100 transition-opacity"
                    x-show="!showControls">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
            </button>

            {{-- Edit controls overlay --}}
            <div x-ref="dropzone"
                 class="absolute inset-0 bg-base-100/95 flex flex-col items-stretch justify-center gap-2 p-2"
                 x-show="showControls"
                 @mouseleave="showControls = false">
              <div class="border-2 border-dashed border-primary rounded flex-1 flex items-center justify-center cursor-pointer hover:bg-primary/10 transition-colors"
                   @click="$refs.fileInput.click()"
                   @dragover.prevent
                   @drop.prevent="handleDrop($event); showControls = false">
                <div class="text-center text-primary">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                  </svg>
                  <div class="text-xs">{{ __('Drop image') }}</div>
                </div>
              </div>
              <button type="button" @click="deleteImage()" class="btn btn-error btn-xs">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                {{ __('Delete') }}
              </button>
            </div>
          </div>

          {{-- Drop area when no image --}}
          <div class="border-2 border-dashed border-base-300 rounded flex items-center justify-center h-full cursor-pointer hover:border-primary hover:bg-primary/5 transition-colors"
               x-show="!imageUrl"
               @click="$refs.fileInput.click()"
               @dragover.prevent
               @drop.prevent="handleDrop($event)">
            <div class="text-center text-base-content/50">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
              </svg>
              <div class="text-xs">{{ __('Drop image') }}</div>
            </div>
          </div>
          <input type="file" x-ref="fileInput" class="hidden" accept="image/*" @change="uploadImage($event.target.files[0])">
        </div>
      </div>
    </div>
  </div>
</div>

<div class="grid grid-cols-12 gap-1 mt-1">
  <div class="col-span-3">
    <div id="actorPanel" class="card bg-base-100 border border-base-300 h-full max-h-[600px]">
      <div class="card-title bg-secondary text-secondary-content px-2 py-1.5 text-sm flex justify-between items-center group">
        <span>{{ __('Actors') }}</span>
        @can('readwrite')
        <button type="button" class="btn btn-ghost btn-xs opacity-0 group-hover:opacity-100 transition-opacity add-actor-btn" title="{{ __('Add Actor') }}">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
          </svg>
        </button>
        @endcan
      </div>
      <div class="card-body bg-base-200/50 p-1 overflow-auto">
        @foreach ( $actors as $role_name => $role_group )
        <div class="card bg-base-100 border border-base-300 mb-1 group/role">
          <div class="bg-primary text-primary-content px-2 py-1 text-xs flex justify-between items-center">
            <span class="font-medium">{{ $role_name }}</span>
            @can('readwrite')
            <div class="flex gap-1 opacity-0 group-hover/role:opacity-100 transition-opacity">
              <button type="button" class="btn btn-ghost btn-xs add-actor-btn" title="Add {{ $role_name }}"
                data-role_name="{{ $role_name }}"
                data-role_code="{{ $role_group->first()->role_code }}"
                data-shareable="{{ $role_group->first()->shareable }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
              </button>
              <a class="btn btn-ghost btn-xs" data-modal-target="#ajaxModal" data-size="modal-lg" title="Edit actors in {{ $role_group->first()->role_name }} group" href="/matter/{{ $matter->id }}/roleActors/{{ $role_group->first()->role_code }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
              </a>
            </div>
            @endcan
          </div>
          <div class="p-1 max-h-20 overflow-auto">
            <ul class="text-xs space-y-0.5">
              @foreach ( $role_group as $actor )
              <li class="truncate {{ $actor->inherited ? 'italic' : '' }}">
                @if ( $actor->warn && $actor->role_code == 'CLI')
                <span class="text-error" title="Special instructions">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                  </svg>
                </span>
                @endif
                <a class="{{ $actor->warn && $actor->role_code == 'CLI' ? 'text-error' : 'link link-hover' }}"
                  href="/actor/{{ $actor->actor_id }}"
                  data-modal-target="#ajaxModal"
                  title="Actor data">
                {{ $actor->display_name }}
                </a>
                @if ( $actor->show_ref && $actor->actor_ref )
                <span class="text-base-content/60">({{ $actor->actor_ref }})</span>
                @endif
                @if ( $actor->show_company && $actor->company )
                <span class="text-base-content/60">- {{ $actor->company }}</span>
                @endif
                @if ( $actor->show_date && $actor->date )
                <span class="text-base-content/60">({{ \Carbon\Carbon::parse($actor->date)->isoFormat('L') }})</span>
                @endif
                @if ( $actor->show_rate && $actor->rate != '100' )
                <span class="text-base-content/60">- {{ $actor->rate }}</span>
                @endif
              </li>
              @endforeach
            </ul>
          </div>
        </div>
        @endforeach
      </div>
    </div>
  </div>
  <div class="col-span-9">
    <div id="multiPanel" class="space-y-1">
      <div class="grid grid-cols-2 gap-1" style="min-height: 138px;">
        <div class="card bg-base-100 border-2 border-primary h-full group">
          <a class="bg-primary text-primary-content px-2 py-1.5 grid grid-cols-12 text-xs font-medium hover:bg-primary-focus transition-colors"
             href="/matter/{{ $matter->id }}/events" data-modal-target="#ajaxModal" data-size="modal-lg" title="{{ __('All events') }}">
            <span class="col-span-5">{{ __('Status') }}</span>
            <span class="col-span-3">{{ __('Date') }}</span>
            <span class="col-span-4 flex justify-between">
              {{ __('Number') }}
              <span class="opacity-0 group-hover:opacity-100 transition-opacity">≡</span>
            </span>
          </a>
          <div class="p-1.5 overflow-auto text-xs" id="statusPanel">
            @foreach ( $matter->events->where('info.status_event', 1) as $event )
            <div class="grid grid-cols-12 gap-0 py-0.5 hover:bg-base-200/50">
              <span class="col-span-5">{{ $event->info->name }}</span>
              @if ( $event->alt_matter_id )
              <span class="col-span-3 text-base-content/70">{{ \Carbon\Carbon::parse($event->link->event_date ?? $event->event_date)->isoFormat('L') }}</span>
              <span class="col-span-4">
                <a href="/matter/{{ $event->alt_matter_id }}" title="{{ $event->altMatter->uid }}" target="_blank" class="link link-primary">{{ $event->altMatter->country }} {{ $event->link->detail ?? $event->detail }}</a>
              </span>
              @else
              <span class="col-span-3 text-base-content/70">{{ \Carbon\Carbon::parse($event->event_date)->isoFormat('L') }}</span>
              <span class="col-span-4">
                @if ( $event->publicUrl() )
                <a href="{{ $event->publicUrl() }}" target="_blank" class="link link-primary">{{ $event->detail }}</a>
                @else
                {{ $event->detail }}
                @endif
              </span>
              @endif
            </div>
            @endforeach
          </div>
        </div>
        <div class="card bg-base-100 border-2 border-primary h-full group">
          <div class="bg-primary px-2 py-1.5 text-xs font-medium flex justify-between items-center {{ $matter->tasksPending->count() ? 'text-warning' : 'text-primary-content' }}">
            {{ __('Open Tasks Due') }}
            <a class="text-warning opacity-0 group-hover:opacity-100 transition-opacity" href="/matter/{{ $matter->id }}/tasks" data-modal-target="#ajaxModal" data-size="modal-lg" title="{{ __('History') }}">
              ≡
            </a>
          </div>
          <div class="p-1.5 overflow-auto text-xs" id="opentask-panel">
            @foreach ( $matter->tasksPending as $task )
            <div class="grid grid-cols-12 gap-0 py-0.5 hover:bg-base-200/50">
              <span class="col-span-9">{{ $task->info->name }}: {{ $task->detail }}</span>
              <span class="col-span-3 text-base-content/70">{{ \Carbon\Carbon::parse($task->due_date)->isoFormat('L') }}</span>
            </div>
            @endforeach
          </div>
        </div>
      </div>
      <div class="grid grid-cols-12 gap-1" style="min-height: 138px;">
        <div class="col-span-2">
          <div class="card bg-base-100 border-2 border-primary h-full group">
            <div class="bg-primary px-2 py-1.5 text-xs font-medium flex justify-between items-center {{ $matter->renewalsPending->count() ? 'text-warning' : 'text-primary-content' }}">
              {{ __('Renewals Due') }}
              <a class="text-warning opacity-0 group-hover:opacity-100 transition-opacity" href="/matter/{{ $matter->id }}/renewals" data-modal-target="#ajaxModal" data-size="modal-lg" title="{{ __('All renewals') }}">
                ≡
              </a>
            </div>
            <div class="p-1.5 overflow-auto text-xs" id="renewal-panel">
              @foreach ( $matter->renewalsPending->take(3) as $task )
              <div class="grid grid-cols-12 gap-0 py-0.5">
                <span class="col-span-4 font-medium">{{ $task->detail }}</span>
                <span class="col-span-8 text-base-content/70">{{ \Carbon\Carbon::parse($task->due_date)->isoFormat('L') }}</span>
              </div>
              @endforeach
            </div>
          </div>
        </div>
        <div class="col-span-6">
          <div class="card bg-base-100 border-2 border-primary h-full group">
            <a class="bg-primary text-primary-content px-2 py-1.5 text-xs font-medium flex justify-between items-center hover:bg-primary-focus transition-colors"
               href="/matter/{{ $matter->id }}/classifiers" data-modal-target="#ajaxModal" title="{{ __('Classifier detail') }}">
              {{ __('Classifiers') }}
              <span class="opacity-0 group-hover:opacity-100 transition-opacity">≡</span>
            </a>
            <div class="p-1.5 overflow-auto" id="classifierPanel">
              @foreach ( $classifiers as $type_code => $classifier_group )
                @if ( $type_code != 'IMG' )
                <div class="flex flex-wrap items-center gap-1 py-0.5">
                  <span class="font-semibold text-xs text-base-content/80">{{ $classifier_group[0]->type_name }}:</span>
                  @foreach ( $classifier_group as $classifier )
                    @if ( $classifier->url )
                      <a href="{{ $classifier->url }}" class="badge badge-primary badge-sm font-normal" target="_blank">{{ $classifier->value }}</a>
                    @elseif ( $classifier->lnk_matter_id )
                      <a href="/matter/{{ $classifier->lnk_matter_id }}" class="badge badge-primary badge-sm font-normal">{{ $classifier->linkedMatter->uid }}</a>
                    @else
                      <span class="badge badge-ghost badge-sm font-normal">{{ $classifier->value }}</span>
                    @endif
                  @endforeach
                  @if ( $type_code == 'LNK' )
                    @foreach ( $matter->linkedBy as $linkedBy )
                      <a href="/matter/{{ $linkedBy->id }}" class="badge badge-primary badge-sm font-normal">{{ $linkedBy->uid }}</a>
                    @endforeach
                  @endif
                </div>
                @endif
              @endforeach
              @if ( !in_array('LNK', $classifiers->keys()->all()) && !$matter->linkedBy->isEmpty() )
              <div class="flex flex-wrap items-center gap-1 py-0.5">
                <span class="font-semibold text-xs text-base-content/80">Link:</span>
                  @foreach ( $matter->linkedBy as $linkedBy )
                    <a href="/matter/{{ $linkedBy->id }}" class="badge badge-primary badge-sm font-normal">{{ $linkedBy->uid }}</a>
                  @endforeach
              </div>
              @endif
            </div>
          </div>
        </div>
        <div class="col-span-4">
          <div class="card bg-base-100 border-2 border-info h-full">
            <div class="bg-info text-info-content px-2 py-1.5 text-xs font-medium flex justify-between items-center">
              {{ __('Related Matters') }}
              <span>ⓘ</span>
            </div>
            <div class="p-1.5 overflow-auto text-xs" id="relationsPanel">
              @php
                // Use the new variables from the controller
                $familyList = isset($family) ? $family : ($matter->family ?? collect());
                $externalMatters = isset($externalPriorityMatters) ? $externalPriorityMatters : collect();
              @endphp
              @if ($familyList->count())
              <div class="mb-1">
                <span class="font-semibold">{{ __('Fam') }}:</span>
                <div class="flex flex-wrap gap-1 mt-0.5">
                  @foreach ($familyList as $member)
                  <a class="badge badge-sm font-normal {{ $member->suffix == $matter->suffix ? 'badge-ghost' : 'badge-primary' }}" href="/matter/{{ $member->id }}">{{ $member->suffix }}</a>
                  @endforeach
                </div>
              </div>
              @endif
              @php
                // Exclude matters from the current family from externalMatters
                $familyIds = $familyList->pluck('id')->push($matter->id)->unique();
                $externalMattersFiltered = $externalMatters->filter(function($ext) use ($familyIds) {
                  return !$familyIds->contains($ext->id);
                });
                // Group by external family (caseref), and pick the first filed (by event FIL date or created_at)
                $externalFirstFiled = $externalMattersFiltered->groupBy('caseref')->map(function($group) {
                  return $group->sortBy(function($m) {
                    // Prefer filing event date, fallback to created_at
                    $filing = $m->filing ?? null;
                    return $filing && $filing->event_date ? $filing->event_date : $m->created_at;
                  })->first();
                });
              @endphp
              @if ($externalFirstFiled->count())
              <div class="mb-1">
                <span class="font-semibold">{{ __('External priorities') }}:</span>
                <div class="flex flex-wrap gap-1 mt-0.5">
                  @foreach ($externalFirstFiled as $ext)
                    <a class="badge badge-primary badge-sm font-normal" href="/matter/{{ $ext->id }}">{{ $ext->uid }}</a>
                  @endforeach
                </div>
              </div>
              @endif
            </div>
          </div>
        </div>
      </div>
      <div class="grid grid-cols-12 gap-1" style="min-height: 100px;">
        <div class="col-span-10">
          <div class="card bg-base-100 border border-base-300 h-full">
            <div class="bg-secondary text-secondary-content px-2 py-1.5 text-xs font-medium">
              {{ __('Notes') }}
            </div>
            <div class="p-1.5 overflow-auto flex-1">
              @can('readwrite')
              <textarea id="notes" class="textarea textarea-bordered w-full h-full text-sm" name="notes" data-resource="/matter/{{ $matter->id }}">{{ $matter->notes }}</textarea>
              @else
              <div class="text-sm whitespace-pre-wrap">{{ $matter->notes }}</div>
              @endcan
            </div>
            <div class="px-2 py-1.5 border-t border-base-300 text-xs flex flex-wrap items-center gap-2">
              <span class="font-medium">{{ __('Summaries') }}:</span>
              <a class="badge badge-primary badge-sm"
                  href="/matter/{{ $matter->id }}/description/en"
                  data-modal-target="#ajaxModal"
                  data-size="modal-lg"
                  title="{{ __('Copy a summary in English') }}">
                  ⧉ EN
              </a>
              <a class="badge badge-primary badge-sm"
                  href="/matter/{{ $matter->id }}/description/fr"
                  data-modal-target="#ajaxModal"
                  data-size="modal-lg"
                  title="{{ __('Copy a summary in French') }}">
                  ⧉ FR
              </a>
              <span class="font-medium ml-2">{{ __('Email') }}:</span>
              <a class="badge badge-primary badge-sm"
                  href="/document/select/{{ $matter->id }}?Language=en"
                  data-modal-target="#ajaxModal"
                  data-size="modal-lg"
                  title="{{ __('Send email') }} EN">
                  ✉ EN
              </a>
              <a class="badge badge-primary badge-sm"
                  href="/document/select/{{ $matter->id }}?Language=fr"
                  data-modal-target="#ajaxModal"
                  data-size="modal-lg"
                  title="{{ __('Send email') }} FR">
                  ✉ FR
              </a>
            </div>
          </div>
        </div>
        <div class="col-span-2">
          <div class="card bg-info h-full">
            <div id="dropZone" class="card-body text-info-content text-center flex flex-col items-center justify-center" data-url="/matter/{{ $matter->id }}/mergeFile">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
              </svg>
              <div class="text-xs font-medium mb-2">{{ __('Drop File to Merge') }}</div>
              <a class="btn btn-ghost btn-xs btn-circle" href="https://github.com/jjdejong/phpip/wiki/Templates-(email-and-documents)#document-template-usage" target="_blank">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<template id="actorPopoverTemplate">
  <form id="addActorForm" autocomplete="off" class="space-y-2 p-2">
      <input type="hidden" name="role">
      <input type="hidden" name="shared">
      <input type="hidden" name="actor_id">
      <input type="text" class="input input-bordered input-sm w-full" id="roleName" data-ac="/role/autocomplete" data-actarget="role" placeholder="{{ __('Role') }}">
      <input type="text" class="input input-bordered input-sm w-full" id="actorName" data-ac="/actor/autocomplete/1" data-actarget="actor_id" placeholder="{{ __('Name') }}">
      <input type="text" class="input input-bordered input-sm w-full" name="actor_ref" placeholder="{{ __('Reference') }}">
      <label class="label cursor-pointer justify-start gap-2">
          <input class="radio radio-sm radio-primary" type="radio" id="actorShared" name="matter_id" value="{{ $matter->container_id ?? $matter->id }}">
          <span class="label-text text-xs">{{ __('Add to container and share') }}</span>
      </label>
      <label class="label cursor-pointer justify-start gap-2">
          <input class="radio radio-sm radio-primary" type="radio" id="actorNotShared" name="matter_id" value="{{ $matter->id }}">
          <span class="label-text text-xs">{{ __('Add to this matter only (not shared)') }}</span>
      </label>
      <div class="join w-full">
        <button type="button" class="btn btn-info btn-sm join-item flex-1" id="addActorSubmit">✓</button>
        <button type="button" class="btn btn-ghost btn-sm join-item flex-1" id="popoverCancel">✕</button>
      </div>
      <div class="alert alert-error hidden text-xs" role="alert"></div>
   </form>
</template>

@endsection
