<form id="createMatterForm" autocomplete="off" class="space-y-3">
  @php
    $selectedCategoryCode = old('category_code', $parent_matter->category_code ?? ($category['code'] ?? ''));
    $categoriesCollection = collect($categoriesList);
    $selectedCategoryName = optional($categoriesCollection->firstWhere('code', $selectedCategoryCode))->category;

    $countriesCollection = collect($countries);
    $selectedCountryCode = old('country', $parent_matter->country ?? '');
    $selectedCountryName = optional($countriesCollection->firstWhere('iso', $selectedCountryCode))->name;

    $selectedOriginCode = old('origin', $parent_matter->origin ?? '');
    $selectedOriginName = optional($countriesCollection->firstWhere('iso', $selectedOriginCode))->name;

    $typesCollection = collect($matterTypes);
    $selectedTypeCode = old('type_code', $parent_matter->type_code ?? '');
    $selectedTypeName = optional($typesCollection->firstWhere('code', $selectedTypeCode))->type;

    $responsibleCollection = collect($responsibleUsers);
    $selectedResponsibleLogin = old('responsible', $defaultResponsible);
    $selectedResponsibleEntry = $responsibleCollection->firstWhere('login', $selectedResponsibleLogin);
    $selectedResponsibleDisplay = $selectedResponsibleEntry
        ? trim($selectedResponsibleEntry->name . ' (' . $selectedResponsibleEntry->login . ')')
        : '';
    $responsibleOptions = $responsibleCollection->map(function ($user) {
        return [
            'login' => $user->login,
            'display' => trim($user->name.' ('.$user->login.')'),
        ];
    });
  @endphp
  <input type="hidden" name="operation" value="{{ $operation ?? 'new' }}">
  <x-autocomplete-field
    id="category"
    name="category_code"
    :label="__('Category')"
    :options="$categoriesList"
    option-value="code"
    option-label="category"
    :selected-value="$selectedCategoryCode"
    :selected-label="$selectedCategoryName"
    :required="true" />
  @if ( $operation == 'ops' )
  <div class="flex items-center gap-2 mb-2">
    <label for="docnum" class="w-1/3 font-semibold text-sm">Pub Number</label>
    <div class="flex-1">
      <input type="text" name="docnum" class="input input-bordered input-sm w-full" placeholder="CCNNNNNN">
      <div class="text-xs text-base-content/60 mt-1">
        Publication number prefixed with the country code and optionally suffixed with the kind code.
        No spaces nor non-alphanumeric characters.
      </div>
    </div>
  </div>
  <div class="flex items-center gap-2 mb-2">
    <label for="client_id" class="w-1/3 font-semibold text-sm">{{ __('Client') }}</label>
    <div class="flex-1">
      <input type="hidden" name="client_id">
      <input type="text" class="input input-bordered input-sm w-full" data-ac="/actor/autocomplete" data-actarget="client_id" autocomplete="off">
    </div>
  </div>
  @else
  <input type="hidden" name="parent_id" value="{{ $parent_matter->id ?? '' }}">
  <x-autocomplete-field
    id="country"
    name="country"
    :label="__('Country')"
    :options="$countries"
    option-value="iso"
    option-label="name"
    :selected-value="$selectedCountryCode"
    :selected-label="$selectedCountryName"
    :required="true" />
  <x-autocomplete-field
    id="origin"
    name="origin"
    :label="__('Origin')"
    :options="$countries"
    option-value="iso"
    option-label="name"
    :selected-value="$selectedOriginCode"
    :selected-label="$selectedOriginName"
    :none-label="__('None')" />
  <x-autocomplete-field
    id="type"
    name="type_code"
    :label="__('Type')"
    :options="$matterTypes"
    option-value="code"
    option-label="type"
    :selected-value="$selectedTypeCode"
    :selected-label="$selectedTypeName"
    :required="true" />
  @endif
  <div class="flex items-center gap-2 mb-2">
    <label for="caseref" class="w-1/3 font-semibold text-sm">{{ __('Caseref') }}</label>
    <div class="flex-1">
      @if ( $operation == 'descendant' )
      <input type="text" class="input input-bordered input-sm w-full" name="caseref" value="{{ $parent_matter->caseref ?? '' }}" readonly>
      @else
      <input type="text" class="input input-bordered input-sm w-full" data-ac="/matter/new-caseref" name="caseref" value="{{ $parent_matter->caseref ?? ( $category['next_caseref'] ?? '') }}" autocomplete="off">
      @endif
    </div>
  </div>
  <x-autocomplete-field
    id="responsible"
    name="responsible"
    :label="__('Responsible')"
    :options="$responsibleOptions"
    option-value="login"
    option-label="display"
    :selected-value="$selectedResponsibleLogin"
    :selected-label="$selectedResponsibleDisplay"
    :required="true" />

  @if ( $operation == 'descendant' )
  <fieldset class="border border-base-300 rounded-lg p-3 mt-3">
    <legend class="font-semibold text-sm px-2">{{ __('Use original matter as') }}</legend>
    <div class="form-control">
      <label class="label cursor-pointer justify-start gap-3">
        <input type="radio" name="priority" value="1" class="radio radio-primary radio-sm" checked>
        <span class="label-text">{{ __('Priority application') }}</span>
      </label>
    </div>
    <div class="form-control">
      <label class="label cursor-pointer justify-start gap-3">
        <input type="radio" name="priority" value="0" class="radio radio-primary radio-sm">
        <span class="label-text">{{ __('Parent application') }}</span>
      </label>
    </div>
  </fieldset>
  @endif

  <div class="mt-4">
    @if ( $operation == 'ops' )
    <button type="button" id="createFamilySubmit" class="btn btn-primary w-full">{{ __('Create') }}</button>
    @else
    <button type="button" id="createMatterSubmit" class="btn btn-primary w-full">{{ __('Create') }}</button>
    @endif
  </div>
</form>
