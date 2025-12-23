<form id="createRoleForm">
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
            'label' => __('Role'),
            'name' => 'name',
            'title' => $tableComments['name'] ?? '',
            'labelClass' => 'fw-bold',
          ],
        ],
        [
          [
            'label' => __('Display order'),
            'name' => 'display_order',
            'title' => $tableComments['display_order'] ?? '',
          ],
          [
            'label' => __('Is shareable'),
            'name' => 'shareable',
            'title' => $tableComments['shareable'] ?? '',
            'type' => 'checkbox',
            'value' => 1,
          ],
        ],
        [
          [
            'label' => __('Show reference'),
            'name' => 'show_ref',
            'title' => $tableComments['show_ref'] ?? '',
            'type' => 'checkbox',
            'value' => 1,
          ],
          [
            'label' => __('Show company'),
            'name' => 'show_company',
            'title' => $tableComments['show_company'] ?? '',
            'type' => 'checkbox',
            'value' => 1,
          ],
        ],
        [
          [
            'label' => __('Show rate'),
            'name' => 'show_rate',
            'title' => $tableComments['show_rate'] ?? '',
            'type' => 'checkbox',
            'value' => 1,
          ],
          [
            'label' => __('Show date'),
            'name' => 'show_date',
            'title' => $tableComments['show_date'] ?? '',
            'type' => 'checkbox',
            'value' => 1,
          ],
        ],
        [
          [
            'label' => __('Notes'),
            'name' => 'notes',
            'title' => $tableComments['notes'] ?? '',
            'type' => 'textarea',
            'inputColspan' => 3,
          ],
        ],
      ];
    @endphp
    <x-form-generator :rows="$rows" />
  </fieldset>
  <button type="button" id="createRoleSubmit" class="btn btn-primary">{{ __('Create role') }}</button><br>
</form>
