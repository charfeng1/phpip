<form id="createMatterForm" autocomplete="off">
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
    :required="true"
    label-class="col-4 col-form-label fw-bold" />
  @if ( $operation == 'ops' )
  <div class="row mb-2">
    <label for="docnum" class="col-4 col-form-label fw-bold">Pub Number</label>
    <div class="col-8">
      <input type="text" name="docnum" class="form-control" placeholder="CCNNNNNN">
    </div>
    <small class="form-text text-muted">
      Publication number prefixed with the country code and optionally suffixed with the kind code. 
      No spaces nor non-alphanumeric characters. 
      {{-- Application number in DOCDB format: country code followed by the number (only digits, no spaces and without the ending ".n"). 
      For numbers without a two-digit year (like the US), insert YY. For PCTs: CCYYYY012345W. --}}
    </small>
  </div>
  <div class="row mb-2">
    <label for="client_id" class="col-4 col-form-label fw-bold">{{ __('Client') }}</label>
    <div class="col-8">
      <input type="hidden" name="client_id">
      <input type="text" class="form-control" data-ac="/actor/autocomplete" data-actarget="client_id" autocomplete="off">
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
    :required="true"
    label-class="col-4 col-form-label fw-bold" />
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
  <div class="row mb-2">
    <label for="caseref" class="col-4 col-form-label fw-bold">{{ __('Caseref') }}</label>
    <div class="col-8">
      @if ( $operation == 'descendant' )
      <input type="text" class="form-control" name="caseref" value="{{ $parent_matter->caseref ?? '' }}" readonly>
      @else
      <input type="text" class="form-control" data-ac="/matter/new-caseref" name="caseref" value="{{ $parent_matter->caseref ?? ( $category['next_caseref'] ?? '') }}" autocomplete="off">
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
    :required="true"
    row-class="row"
    label-class="col-4 col-form-label fw-bold" />

  @if ( $operation == 'descendant' )
  <fieldset>
    <legend>{{ __('Use original matter as') }}</legend>
    <div class="form-check my-1">
      <input class="form-check-input mt-0" type="radio" name="priority" value="1" checked>
      <label class="form-check-label">{{ __('Priority application') }}</label>
    </div>
    <div class="form-check my-1">
      <input class="form-check-input mt-0" type="radio" name="priority" value="0">
      <label class="form-check-label">{{ __('Parent application') }}</label>
    </div>
  </fieldset>
  @endif

  <div class="d-grid">
    @if ( $operation == 'ops' )
    <button type="button" id="createFamilySubmit" class="btn btn-primary">{{ __('Create') }}</button>
    @else
    <button type="button" id="createMatterSubmit" class="btn btn-primary">{{ __('Create') }}</button>
    @endif
  </div>
</form>
