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
  @endphp
  <input type="hidden" name="operation" value="{{ $operation ?? 'new' }}">
  <div class="row mb-2">
    <label for="categoryInput" class="col-4 col-form-label fw-bold">{{ __('Category') }}</label>
    <div class="col-8">
      <div class="combobox">
        <input type="search"
               class="form-control form-control-sm combobox-input"
               placeholder="{{ __('Filter options...') }}"
               list="categoryOptions"
               data-combobox-target="#categorySelect"
               id="categoryInput"
               value="{{ $selectedCategoryName ?? '' }}"
               autocomplete="off">
        <datalist id="categoryOptions">
          @foreach ($categoriesList as $item)
            <option value="{{ $item->category }}" data-code="{{ $item->code }}"></option>
          @endforeach
        </datalist>
      </div>
      <select class="form-select d-none combobox-select"
              id="categorySelect"
              name="category_code"
              required>
        <option value="" disabled {{ empty(old('category_code', $parent_matter->category_code ?? ($category['code'] ?? ''))) ? 'selected' : '' }}>
          {{ __('Select an option') }}
        </option>
        @foreach ($categoriesList as $item)
          <option value="{{ $item->code }}"
            @selected(old('category_code', $parent_matter->category_code ?? ($category['code'] ?? '')) === $item->code)>
            {{ $item->category }}
          </option>
        @endforeach
      </select>
    </div>
  </div>
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
  <div class="row mb-2">
    <label for="countryInput" class="col-4 col-form-label fw-bold">{{ __('Country') }}</label>
    <div class="col-8">
      <div class="combobox">
        <input type="search"
               class="form-control form-control-sm combobox-input"
               placeholder="{{ __('Filter options...') }}"
               list="countryOptions"
               data-combobox-target="#countrySelect"
               id="countryInput"
               value="{{ $selectedCountryName ?? '' }}"
               autocomplete="off">
        <datalist id="countryOptions">
          @foreach ($countries as $item)
            <option value="{{ $item->name }}" data-code="{{ $item->iso }}"></option>
          @endforeach
        </datalist>
      </div>
      <select class="form-select d-none combobox-select"
              id="countrySelect"
              name="country"
              required>
        <option value="" disabled {{ empty($selectedCountryCode) ? 'selected' : '' }}>
          {{ __('Select an option') }}
        </option>
        @foreach ($countries as $item)
          <option value="{{ $item->iso }}"
            @selected($selectedCountryCode === $item->iso)>
            {{ $item->name }}
          </option>
        @endforeach
      </select>
    </div>
  </div>
  <div class="row mb-2">
    <label for="originInput" class="col-4 col-form-label">{{ __('Origin') }}</label>
    <div class="col-8">
      <div class="combobox">
        <input type="search"
               class="form-control form-control-sm combobox-input"
               placeholder="{{ __('Filter options...') }}"
               list="originOptions"
               data-combobox-target="#originSelect"
               id="originInput"
               value="{{ $selectedOriginName ?? '' }}"
               autocomplete="off">
        <datalist id="originOptions">
          @foreach ($countries as $item)
            <option value="{{ $item->name }}" data-code="{{ $item->iso }}"></option>
          @endforeach
        </datalist>
      </div>
      <select class="form-select d-none combobox-select"
              id="originSelect"
              name="origin">
        <option value="">{{ __('None') }}</option>
        @foreach ($countries as $item)
          <option value="{{ $item->iso }}"
            @selected($selectedOriginCode === $item->iso)>
            {{ $item->name }}
          </option>
        @endforeach
      </select>
    </div>
  </div>
  <div class="row mb-2">
    <label for="typeInput" class="col-4 col-form-label">{{ __('Type') }}</label>
    <div class="col-8">
      <div class="combobox">
        <input type="search"
               class="form-control form-control-sm combobox-input"
               placeholder="{{ __('Filter options...') }}"
               list="typeOptions"
               data-combobox-target="#typeSelect"
               id="typeInput"
               value="{{ $selectedTypeName ?? '' }}"
               autocomplete="off">
        <datalist id="typeOptions">
          @foreach ($matterTypes as $item)
            <option value="{{ $item->type }}" data-code="{{ $item->code }}"></option>
          @endforeach
        </datalist>
      </div>
      <select class="form-select d-none combobox-select"
              id="typeSelect"
              name="type_code"
              required>
        <option value="" disabled {{ empty($selectedTypeCode) ? 'selected' : '' }}>
          {{ __('Select an option') }}
        </option>
        @foreach ($matterTypes as $item)
          <option value="{{ $item->code }}"
            @selected($selectedTypeCode === $item->code)>
            {{ $item->type }}
          </option>
        @endforeach
      </select>
    </div>
  </div>
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
  <div class="row">
    <label for="responsibleInput" class="col-4 col-form-label fw-bold">{{ __('Responsible') }}</label>
    <div class="col-8">
      <div class="combobox">
        <input type="search"
               class="form-control form-control-sm combobox-input"
               placeholder="{{ __('Filter options...') }}"
               list="responsibleOptions"
               data-combobox-target="#responsibleSelect"
               id="responsibleInput"
               value="{{ $selectedResponsibleDisplay }}"
               autocomplete="off">
        <datalist id="responsibleOptions">
          @foreach ($responsibleUsers as $user)
            <option value="{{ trim($user->name . ' (' . $user->login . ')') }}" data-code="{{ $user->login }}"></option>
          @endforeach
        </datalist>
      </div>
      <select class="form-select d-none combobox-select"
              id="responsibleSelect"
              name="responsible"
              required>
        @foreach ($responsibleUsers as $user)
          <option value="{{ $user->login }}"
            @selected($selectedResponsibleLogin === $user->login)>
            {{ $user->name }} ({{ $user->login }})
          </option>
        @endforeach
      </select>
    </div>
  </div>

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
