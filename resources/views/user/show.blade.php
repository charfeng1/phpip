<div class="{{ isset($isProfileView) && $isProfileView ? 'row' : '' }}">
  <div class="{{ isset($isProfileView) && $isProfileView ? 'col-md-6' : '' }}">
    <div class="card mb-3">
      <div class="card-header">
        <h5 class="card-title mb-0">{{ __('User Info') }}</h5>
      </div>
      <div class="card-body">
        <table class="table table-striped" data-resource="{{ '/user/' . $userInfo->id }}">
          <tr>
            <th title="{{ $userComments['name'] }}">{{ __('Name') }}</th>
            <td><input class="noformat form-control" name="name" value="{{ $userInfo->name }}"></td>
          </tr>
          <tr>
            <th title="{{ $userComments['default_role'] }}">{{ __('Role') }}</th>
            <td><input type="text" class="noformat form-control" name="default_role" data-ac="/dbrole/autocomplete" data-aclength="0" value="{{ empty($userInfo->roleInfo) ? '' : $userInfo->roleInfo->name }}" autocomplete="off"></td>
          </tr>
          <tr>
            <th>{{ __('Email') }}</th>
            <td><input type='email' class="noformat form-control" name="email" value="{{ $userInfo->email }}" required></td>
          </tr>
          <tr>
            <th title="{{ $userComments['company_id'] }}">{{ __('Company') }}</th>
            <td><input type="text" class="noformat form-control" name="company_id" data-ac="/actor/autocomplete" value="{{ empty($userInfo->company) ? '' : $userInfo->company->name }}" autocomplete="off"></td>
          </tr>
          <tr>
            <th title="{{ __('Supervisor/Manager for team hierarchy') }}">{{ __('Supervisor') }}</th>
            <td><input type="text" class="noformat form-control" name="parent_id" data-ac="/user/autocomplete-by-id" value="{{ empty($userInfo->parent) ? '' : $userInfo->parent->name }}" autocomplete="off"></td>
          </tr>
          @if($userInfo->directReports->count() > 0)
          <tr>
            <th title="{{ __('Users reporting to this user') }}">{{ __('Direct Reports') }}</th>
            <td>
              <ul class="list-unstyled mb-0">
                @foreach($userInfo->directReports as $report)
                  <li><a href="/user/{{ $report->id }}">{{ $report->name }}</a></li>
                @endforeach
              </ul>
            </td>
          </tr>
          @endif
          <tr>
            <th>{{ __('Phone') }}</th>
            <td><input type='text' class="noformat form-control" name="phone" value="{{ $userInfo->phone }}"></td>
          </tr>
          <tr>
            <th>{{ __('Language') }}</th>
            <td>
              @php
                $currentLang = $userInfo->language ?? config('locales.default', 'en');
                // Normalize legacy locale codes
                if (in_array($currentLang, ['en_GB', 'en_US'])) {
                    $currentLang = 'en';
                }
              @endphp
              <select class="select select-bordered select-sm w-full noformat" name="language">
                @foreach(config('locales.supported', ['en' => 'English']) as $code => $name)
                  <option value="{{ $code }}" {{ $currentLang === $code ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
              </select>
            </td>
          </tr>
          @if(!isset($isProfileView))
          <tr>
            <th>{{ __('Notes') }}</th>
            <td><textarea class="noformat form-control" name="notes">{{ $userInfo->notes }}</textarea></td>
          </tr>
          @endif
        </table>
      </div>
    </div>
  </div>

  <div class="{{ isset($isProfileView) && $isProfileView ? 'col-md-6' : '' }}">
    <div class="card">
      <div class="card-header">
        <h5 class="card-title mb-0">{{ __('Credentials') }}</h5>
      </div>
      <div class="card-body">
        <form method="POST" action="{{ isset($isProfileView) ? route('user.updateProfile') : '/user/' . $userInfo->id }}">
          @csrf
          @method('PUT')
          <table class="table table-striped">
            <tr>
              <th>{{ __('User name') }}</th>
              <td>{{ $userInfo->login }}</td>
            </tr>
            <tr>
              <th>{{ __('Password') }}</th>
              <td>
                <input type="password" class="form-control" name="password" placeholder="{{ __('Leave empty to keep password') }}">
                @if(isset($isProfileView))
                <small class="text-muted">{{ __('Password must be at least 8 characters and include uppercase, lowercase, number, and special character.') }}</small>
                @endif
              </td>
            </tr>
            <tr>
              <th>{{ __('Confirm Password') }}</th>
              <td>
                <input type="password" class="form-control" name="password_confirmation" placeholder="{{ __('Confirm password') }}">
              </td>
            </tr>
          </table>
          <div class="text-end mt-3">
            <button type="submit" class="btn btn-primary">{{ __('Update Password') }}</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>