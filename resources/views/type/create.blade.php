<form id="createTypeForm">
  <fieldset>
    @php
      $rows = [
        [
          [
            'label' => __('Code'),
            'name' => 'code',
            'title' => $tableComments['code'] ?? '',
            'labelClass' => 'fw-bold',
          ],
          [
            'label' => __('Type name'),
            'name' => 'type',
            'title' => $tableComments['type'] ?? '',
            'labelClass' => 'fw-bold',
          ],
        ],
      ];
    @endphp
    <x-form-generator :rows="$rows" />
  </fieldset>
  <button type="button" id="createTypeSubmit" class="btn btn-primary">{{ __('Create type') }}</button><br>
</form>
