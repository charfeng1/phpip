<form id="createCategoryForm">
  <fieldset>
    @php
      $displayWithInput = new Illuminate\Support\HtmlString(
          '<input type=\"hidden\" name=\"display_with\">'.
          '<input type=\"text\" class=\"form-control form-control-sm\" data-ac=\"/category/autocomplete\" data-actarget=\"display_with\" autocomplete=\"off\">'
      );
      $rows = [
        [
          [
            'label' => __('Code'),
            'name' => 'code',
            'title' => $tableComments['code'] ?? '',
            'labelClass' => 'fw-bold',
          ],
          [
            'label' => __('Category name'),
            'name' => 'category',
            'title' => $tableComments['category'] ?? '',
            'labelClass' => 'fw-bold',
          ],
        ],
        [
          [
            'label' => __('Reference prefix'),
            'name' => 'ref_prefix',
            'title' => $tableComments['ref_prefix'] ?? '',
          ],
          [
            'label' => __('Display with'),
            'name' => 'display_with',
            'title' => $tableComments['display_with'] ?? '',
            'labelClass' => 'fw-bold',
            'type' => 'custom',
            'content' => $displayWithInput,
          ],
        ],
      ];
    @endphp
    <x-form-generator :rows="$rows" />
  </fieldset>
  <button type="button" id="createCategorySubmit" class="btn btn-primary">{{ __('Create category') }}</button><br>
</form>
