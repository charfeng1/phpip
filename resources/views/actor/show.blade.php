<div class="card bg-base-100 shadow-sm relative" style="height: 480px;" x-data="{ activeTab: 'main', usedInLoaded: false }">
  {{-- DaisyUI Tabs --}}
  <div class="tabs tabs-bordered tabs-lg w-full">
    <button type="button" class="tab flex-1" :class="activeTab === 'main' && 'tab-active'" @click="activeTab = 'main'">{{ __('Main') }}</button>
    <button type="button" class="tab flex-1" :class="activeTab === 'contact' && 'tab-active'" @click="activeTab = 'contact'">{{ __('Contact') }}</button>
    <button type="button" class="tab flex-1" :class="activeTab === 'other' && 'tab-active'" @click="activeTab = 'other'">{{ __('Other') }}</button>
    <button type="button" class="tab flex-1" :class="activeTab === 'usedin' && 'tab-active'"
      @click="activeTab = 'usedin'; if (!usedInLoaded) { fetchInto('/actor/{{ $actorInfo->id }}/usedin', document.getElementById('actorUsedIn')); usedInLoaded = true; }">{{ __('Used in') }}</button>
  </div>

  <div class="p-2 overflow-y-auto flex-1" data-resource="/actor/{{ $actorInfo->id }}">
    {{-- Main Tab --}}
    <fieldset x-show="activeTab === 'main'" x-cloak>
      <table class="table table-zebra table-sm">
        <tr>
          <th class="w-1/3" title="{{ $actorComments['name'] }}">{{ __('Name') }}</th>
          <td><input class="noformat input input-bordered input-sm w-full" name="name" value="{{ $actorInfo->name }}"></td>
        </tr>
        <tr>
          <th title="{{ $actorComments['first_name'] }}">{{ __('First name') }}</th>
          <td><input class="noformat input input-bordered input-sm w-full" name="first_name" value="{{ $actorInfo->first_name }}" placeholder="-"></td>
        </tr>
        <tr>
          <th title="{{ $actorComments['display_name'] }}">{{ __('Display name') }}</th>
          <td><input type="text" class="noformat input input-bordered input-sm w-full" name="display_name" value="{{ $actorInfo->display_name }}" placeholder="-"></td>
        </tr>
        <tr>
          <th>{{ __('Address') }}</th>
          <td><textarea class="noformat textarea textarea-bordered textarea-sm w-full" name="address">{{ $actorInfo->address }}</textarea></td>
        </tr>
        <tr>
          <th>{{ __('Country') }}</th>
          <td><input type='text' class="noformat input input-bordered input-sm w-full" name="country" data-ac="/country/autocomplete" value="{{ empty($actorInfo->countryInfo) ? '' : $actorInfo->countryInfo->name }}" placeholder="-" autocomplete="off"></td>
        </tr>
        <tr>
          <th>{{ __('Nationality') }}</th>
          <td><input type="text" class="noformat input input-bordered input-sm w-full" name="nationality" data-ac="/country/autocomplete" value="{{ empty($actorInfo->nationalityInfo) ? '' : $actorInfo->nationalityInfo->name }}" placeholder="-" autocomplete="off"></td>
        </tr>
        <tr>
          <th>{{ __('Language') }}</th>
          <td><input type="text" class="noformat input input-bordered input-sm w-full" name="language" placeholder="fr/en/de" value="{{ $actorInfo->language }}" autocomplete="off"></td>
        </tr>
        <tr>
          <th title="{{ $actorComments['function'] }}">{{ __('Function') }}</th>
          <td><input type="text" class="noformat input input-bordered input-sm w-full" name="function" value="{{ $actorInfo->function }}" placeholder="-"></td>
        </tr>
        <tr>
          <th title="{{ $actorComments['company_id'] }}">{{ __('Employer') }}</th>
          <td><input type="text" class="noformat input input-bordered input-sm w-full" name="company_id" data-ac="/actor/autocomplete" value="{{ empty($actorInfo->company) ? '' : $actorInfo->company->name }}" placeholder="-" autocomplete="off"></td>
        </tr>
        <tr>
          <th title="{{ $actorComments['phy_person'] }}">{{ __('Physical Person') }}</th>
          <td><input type="checkbox" class="noformat checkbox checkbox-primary checkbox-sm" name="phy_person" {{ $actorInfo->phy_person ? 'checked' : '' }}></td>
        </tr>
        <tr>
          <th title="{{ $actorComments['small_entity'] }}">{{ __('Small Entity') }}</th>
          <td><input type="checkbox" class="noformat checkbox checkbox-primary checkbox-sm" name="small_entity" {{ $actorInfo->small_entity ? 'checked' : '' }}></td>
        </tr>
      </table>
    </fieldset>

    {{-- Contact Tab --}}
    <fieldset x-show="activeTab === 'contact'" x-cloak>
      <table class="table table-zebra table-sm">
        <tr>
          <th class="w-1/3">{{ __('Address mailing') }}</th>
          <td><textarea class="noformat textarea textarea-bordered textarea-sm w-full" name="address_mailing">{{ $actorInfo->address_mailing }}</textarea></td>
        </tr>
        <tr>
          <th>{{ __('Country mailing') }}</th>
          <td><input type='text' class="noformat input input-bordered input-sm w-full" name="country_mailing" data-ac="/country/autocomplete" value="{{ empty($actorInfo->country_mailingInfo ) ? '' : $actorInfo->country_mailingInfo->name }}" placeholder="-" autocomplete="off"></td>
        </tr>
        <tr>
          <th>{{ __('Address billing') }}</th>
          <td><textarea class="noformat textarea textarea-bordered textarea-sm w-full" name="address_billing">{{ $actorInfo->address_billing }}</textarea></td>
        </tr>
        <tr>
          <th>{{ __('Country billing') }}</th>
          <td><input class="noformat input input-bordered input-sm w-full" name="country_billing" data-ac="/country/autocomplete" value="{{ empty($actorInfo->country_billingInfo ) ? '' : $actorInfo->country_billingInfo->name }}" placeholder="-" autocomplete="off"></td>
        </tr>
        <tr>
          <th>{{ __('Email') }}</th>
          <td><input type='email' class="noformat input input-bordered input-sm w-full" name="email" value="{{ $actorInfo->email }}" placeholder="-"></td>
        </tr>
        <tr>
          <th>{{ __('Phone') }}</th>
          <td><input type='text' class="noformat input input-bordered input-sm w-full" name="phone" value="{{ $actorInfo->phone }}" placeholder="-"></td>
        </tr>
      </table>
    </fieldset>

    {{-- Other Tab --}}
    <fieldset x-show="activeTab === 'other'" x-cloak>
      <table class="table table-zebra table-sm">
        <tr>
          <th class="w-1/3" title="{{ $actorComments['login'] }}">{{ __('User name') }}</th>
          <td><input type="text" class="noformat input input-bordered input-sm w-full" name="login" value="{{ $actorInfo->login }}" placeholder="-"></td>
        </tr>
        <tr>
          <th title="{{ $actorComments['default_role'] }} Login needs to be null for changing the role">{{ __('Default role') }}</th>
          <td><input type="text" class="noformat input input-bordered input-sm w-full" name="default_role" data-ac="/role/autocomplete" value="{{ empty($actorInfo->droleInfo) ? '' : $actorInfo->droleInfo->name }}" {{ $actorInfo->login ? 'disabled' : 'autocomplete=off' }} placeholder="-"></td>
        </tr>
        <tr>
          <th>
            <div class="mb-0" title="{{ $actorComments['ren_discount'] }}">{{ __('Discount for renewals') }}</div>
            <div class="text-xs text-base-content/60">
              {{ __('Enter a multiplier rate (e.g. 0.5) or a fixed fee (e.g. 150)') }}
            </div>
          </th>
          <td><input type="text" class="noformat input input-bordered input-sm w-full" name="ren_discount" value="{{ $actorInfo->ren_discount ? $actorInfo->ren_discount : '' }}" placeholder="{{ __('Fixed fee or rate') }}"></td>
        </tr>
        <tr>
          <th title="{{ $actorComments['warn'] }}">{{ __('Warn') }}</th>
          <td><input type="checkbox" class="noformat checkbox checkbox-primary checkbox-sm" name="warn" {{ $actorInfo->warn ? 'checked' : '' }}></td>
        </tr>
        <tr>
          <th title="{{ $actorComments['legal_form'] }}">{{ __('Legal form') }}</th>
          <td><input type='text' class="noformat input input-bordered input-sm w-full" name="legal_form" value="{{ $actorInfo->legal_form }}" placeholder="-"></td>
        </tr>
        <tr>
          <th title="{{ $actorComments['registration_no'] }}">{{ __('Registration no.') }}</th>
          <td><input type='text' class="noformat input input-bordered input-sm w-full" name="registration_no" value="{{ $actorInfo->registration_no }}" placeholder="-"></td>
        </tr>
        <tr>
          <th title="{{ $actorComments['VAT_number'] }}">{{ __('VAT no.') }}</th>
          <td><input type='text' class="noformat input input-bordered input-sm w-full" name="VAT_number" value="{{ $actorInfo->VAT_number }}" placeholder="-"></td>
        </tr>
        <tr>
          <th title="{{ $actorComments['parent_id'] }}">{{ __('Parent company') }}</th>
          <td><input type="text" class="noformat input input-bordered input-sm w-full" name="parent_id" data-ac="/actor/autocomplete" value="{{ empty($actorInfo->parent) ? '' : $actorInfo->parent->name }}" placeholder="-" autocomplete="off"></td>
        </tr>
        <tr>
          <th title="{{ $actorComments['site_id'] }}">{{ __('Work site') }}</th>
          <td><input type="text" class="noformat input input-bordered input-sm w-full" name="site_id" data-ac="/actor/autocomplete" value="{{ empty($actorInfo->site) ? '' : $actorInfo->site->name }}" placeholder="-" autocomplete="off"></td>
        </tr>
      </table>
    </fieldset>

    {{-- Used In Tab --}}
    <div x-show="activeTab === 'usedin'" x-cloak id="actorUsedIn">
      <span class="loading loading-spinner loading-lg text-primary"></span>
    </div>

    {{-- Notes (always visible) --}}
    <div class="mt-2 pt-2 border-t border-base-300">
      <label class="label py-1">
        <span class="label-text font-semibold" title="{{ $actorComments['notes'] }}">{{ __('Notes') }}</span>
      </label>
      <textarea class="noformat textarea textarea-bordered textarea-sm w-full" name="notes">{{ $actorInfo->notes }}</textarea>
    </div>
  </div>

  @can('readwrite')
  <button type="button" class="btn btn-outline btn-error btn-sm absolute bottom-2 right-2" id="deleteActor" title="{{ __('Delete actor') }}" data-url='/actor/{{ $actorInfo->id }}' data-message="{{ __('the actor') }} {{ $actorInfo->name }}">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
    </svg>
    {{ __('Delete') }}
  </button>
  @endcan
</div>
