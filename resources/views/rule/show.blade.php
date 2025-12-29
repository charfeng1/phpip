<div class="card bg-base-100 shadow-sm reload-part relative" style="height: 480px;" x-data="{ activeTab: 'main' }">
  <div class="tabs tabs-bordered tabs-lg w-full">
    <button type="button" class="tab flex-1" :class="activeTab === 'main' && 'tab-active'" @click="activeTab = 'main'">{{ __('Main') }}</button>
    <button type="button" class="tab flex-1" :class="activeTab === 'conditions' && 'tab-active'" @click="activeTab = 'conditions'">{{ __('Conditions') }}</button>
    <button type="button" class="tab flex-1" :class="activeTab === 'cost' && 'tab-active'" @click="activeTab = 'cost'">{{ __('Cost') }}</button>
    <button type="button" class="tab flex-1" :class="activeTab === 'template' && 'tab-active'" @click="activeTab = 'template'">{{ __('Template') }}</button>
  </div>
  <div class="p-2 overflow-y-auto flex-1" data-resource="/rule/{{ $ruleInfo->id }}">
    <fieldset x-show="activeTab === 'main'" x-cloak>
      <table class="table">
        <tr>
          <th><label class="required-field" title="{{ $ruleComments['task'] }}">{{ __('Task') }}</label></th>
          <td><input type="text" class="form-control noformat" name="task" data-ac="/event-name/autocomplete/1?category={{ $ruleInfo->for_category }}" placeholder="{{ $ruleInfo->taskInfo->name }}"></td>
        </tr>
        <tr>
          <th><label title="{{ $ruleComments['trigger_event'] }}">{{ __('Triggered by') }}</label></th>
          <td><input type="text" class="form-control noformat" name="trigger_event" data-ac="/event-name/autocomplete/0?category={{ $ruleInfo->for_category }}" placeholder="{{ $ruleInfo->trigger->name }}"></td>
        </tr>
        <tr>
          <th><label title="{{ $ruleComments['detail'] }}">{{ __('Detail') }}</label></th>
          <td><input class="form-control noformat" name="detail" value="{{ $ruleInfo->detail }}"></td>
        </tr>
        <tr>
          <th><label title="{{ $ruleComments['for_category'] }}">{{ __('Category') }}</label></th>
          <td><input type="text" class="form-control noformat" name="for_category" data-ac="/category/autocomplete" value="{{ $ruleInfo->category->category }}"></td>
        </tr>
        <tr>
          <th><label title="{{ $ruleComments['for_country'] }}">{{ __('Country') }}</label></th>
          <td><input type="text" class="form-control noformat" name="for_country" data-ac="/country/autocomplete" value="{{ $ruleInfo->country->name ?? '' }}"></td>
        </tr>
        <tr>
          <th><label title="{{ $ruleComments['for_origin'] }}">{{ __('Origin') }}</label></th>
          <td><input type="text" class="form-control noformat" name="for_origin" data-ac="/country/autocomplete" value="{{ $ruleInfo->origin->name ?? '' }}"></td>
        </tr>
        <tr>
          <th><label title="{{ $ruleComments['for_type'] }}">{{ __('Type') }}</label></th>
          <td><input type="text" class="form-control noformat" name="for_type" data-ac="/type/autocomplete" value="{{ $ruleInfo->type->type ?? '' }}"></td>
        </tr>
        <tr>
          <th><label title="{{ $ruleComments['clear_task'] }}">{{ __('Clears task') }}</label></th>
          <td><input class="noformat" type="checkbox" name="clear_task" {{ $ruleInfo->clear_task ? 'checked' : '' }}></td>
        </tr>
        <tr>
          <th><label title="{{ $ruleComments['delete_task'] }}">{{ __('Deletes task') }}</label></th>
          <td><input class="noformat" type="checkbox" name="delete_task" {{ $ruleInfo->delete_task ? 'checked' : '' }}></td>
        </tr>
        <tr>
          <th><label title="{{ $ruleComments['active'] }}">{{ __('Enabled') }}</label></th>
          <td><input class="noformat" type="checkbox" name="active" {{ $ruleInfo->active ? 'checked' : '' }}></td>
        </tr>
        <tr>
          <td colspan="4">
            <label>{{ __('Notes') }}</label>
            <textarea class="form-control noformat" name="notes" rows="4">{{ $ruleInfo->notes }}</textarea>
          </td>
        </tr>
      </table>
    </fieldset>
    <fieldset x-show="activeTab === 'conditions'" x-cloak>
      <table class="table">
        <tr>
          <th colspan="2"><label title="{{ $ruleComments['trigger_event'] }}">{{ __('Triggered by') }}</label></th>
          <td colspan="2"><input type="text" class="form-control noformat" name="trigger_event" data-ac="/event-name/autocomplete/0?category={{ $ruleInfo->for_category }}" placeholder="{{ $ruleInfo->trigger->name }}"></td>
        </tr>
        <tr>
          <th><label title="{{ $ruleComments['days'] }}">{{ __('Days') }}</label></th>
          <td><input class="form-control noformat" name="days" value="{{ $ruleInfo->days }}"></td>
          <th><label title="{{ $ruleComments['months'] }}">{{ __('Months') }}</label></th>
          <td><input class="form-control noformat" name="months" value="{{ $ruleInfo->months }}"></td>
        </tr>
        <tr>
          <th><label title="{{ $ruleComments['years'] }}">{{ __('Years') }}</label></th>
          <td><input class="form-control noformat" name="years" value="{{ $ruleInfo->years }}"></td>
          <th><label title="{{ $ruleComments['end_of_month'] }}">{{ __('End of month') }}</label></th>
          <td><input class="noformat" type="checkbox" name="end_of_month" {{ $ruleInfo->end_of_month ? 'checked' : '' }}></td>
        </tr>
        <tr>
          <th><label title="{{ $ruleComments['use_priority'] }}">{{ __('Use priority') }}</label></th>
          <td><input class="noformat" type="checkbox" name="use_priority" {{ $ruleInfo->use_priority ? 'checked' : '' }}></td>
          <th><label title="{{ $ruleComments['recurring'] }}">{{ __('Recurring') }}</label></th>
          <td><input class="noformat" type="checkbox" name="recurring" {{ $ruleInfo->recurring ? 'checked' : '' }}></td>
        </tr>
        <tr>
          <th><label title="{{ $ruleComments['condition_event'] }}">{{ __('Apply if') }}</label></th>
          <td colspan="3"><input type="text" class="form-control noformat" name="condition_event" data-ac="/event-name/autocomplete/0?category={{ $ruleInfo->for_category }}" value="{{ $ruleInfo->condition_eventInfo->name ?? '' }}"></td>
        </tr>
        <tr>
          <th><label title="{{ $ruleComments['abort_on'] }}">{{ __('Abort if') }}</label></th>
          <td colspan="3"><input type="text" class="form-control noformat" name="abort_on" data-ac="/event-name/autocomplete/0?category={{ $ruleInfo->for_category }}" value="{{ $ruleInfo->abort_onInfo->name ?? '' }}"></td>
        </tr>
        <tr>
          <th><label title="{{ $ruleComments['responsible'] }}">{{ __('Responsible') }}</label></th>
          <td colspan="3"><input type="text" class="form-control noformat" name="responsible" data-ac="/user/autocomplete" value="{{ $ruleInfo->responsibleInfo->name ?? '' }}"></td>
        </tr>
        <tr>
          <th><label title="{{ $ruleComments['use_before'] }}">{{ __('Use before') }}</label></th>
          <td colspan="3"><input type="date" class="form-control noformat" name="use_before" value="{{ $ruleInfo->use_before }}"></td>
        </tr>
        <tr>
          <th><label title="{{ $ruleComments['use_after'] }}">{{ __('Use after') }}</label></th>
          <td colspan="3"><input type="date" class="form-control noformat" name="use_after" value="{{ $ruleInfo->use_after }}"></td>
        </tr>
      </table>
    </fieldset>
    <fieldset x-show="activeTab === 'cost'" x-cloak>
      <table class="table">
        <tr>
          <th><label title="{{ $ruleComments['cost'] }}">{{ __('Cost') }}</label></th>
          <td><input class="form-control noformat" name="cost" value="{{ $ruleInfo->cost }}"></td>
        </tr>
        <tr>
          <th><label title="{{ $ruleComments['fee'] }}">{{ __('Fee') }}</label></th>
          <td><input class="form-control noformat" name="fee" value="{{ $ruleInfo->fee }}"></td>
        </tr>
        <tr>
          <th><label title="{{ $ruleComments['currency'] }}">{{ __('Currency') }}</label></th>
          <td><input type="text" maxlength="3" class="form-control noformat" name="currency" value="{{ $ruleInfo->currency }}"></td>
        </tr>
      </table>
    </fieldset>
    <fieldset x-show="activeTab === 'template'" x-cloak>
      <table class="table">
        <tr>
          <td colspan="4">
            <form id="addTemplateForm" class="form-inline">
              <input type="hidden" name="task_rule_id" value="{{ $ruleInfo->id }}">
              <div class="input-group">
                <input type="hidden" name="template_class_id" value="">
                <input type="text" class="form-control form-control-sm" name="className" placeholder="{{ __('Class') }}" data-ac="/template-class/autocomplete" data-actarget="template_class_id">
                <button type="button" class="btn btn-primary btn-sm" id="addRuleTemplateSubmit">&check;</button>
                <button type="reset" class="btn btn-outline-primary btn-sm">&times;</button>
              </div>
            </form>
          </td>
        </tr>
      </table>
    </fieldset>
  </div>
  <button type="button" class="btn btn-outline btn-error btn-sm absolute bottom-2 right-2" id="deleteRule" title="{{ __('Delete rule') }}" data-url='/rule/{{ $ruleInfo->id }}' data-message="{{ __('the rule') }} {{ $ruleInfo->taskInfo->name }}">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
    </svg>
    {{ __('Delete') }}
  </button>
</div>
