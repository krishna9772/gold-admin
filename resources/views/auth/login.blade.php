<x-auth-layout>
  <x-slot name="title">
    @lang('Login')
  </x-slot>

  <x-auth-card>
    <x-slot name="logo">
      <a >
        <x-application-logo />
      </a>
    </x-slot>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <!-- Social Login -->
    <x-auth-social-login />

    <!-- Validation Errors -->
    <x-auth-validation-errors class="mb-4" :errors="$errors" />

    <form method="POST" action="{{ $url ?? route('admin-login') }}">
      @csrf

      <!-- Email Address -->
      <div>
        <x-label for="email" :value="__('frontend.email')" />

        <x-input id="email" type="email" name="email" placeholder="{{__('frontend.enter_email')}}" :value="old('email')" required autofocus />
      </div>

      <!-- Password -->
      <div class="mt-4">
        <x-label for="password" :value="__('frontend.password')" />

        <x-input id="password" type="password" name="password" placeholder="{{__('messages.enter_password')}}" required autocomplete="current-password" />
      </div>

      <!-- Remember Me -->
      {{-- <div class="mt-4">
        <label for="remember_me" class="d-inline-flex">
          <input id="remember_me" type="checkbox" class="form-check-input" name="remember">
          <span class="ms-2">{{ __('frontend.remember_me') }}</span>
        </label>
      </div> --}}

      <div class="d-flex align-items-center justify-content-between mt-4">
        {{-- @if (Route::has('password.request'))
        <a class="underline text-sm text-gray-600 hover:text-gray-900" href="{{ route('password.request') }}">
          {{ __('frontend.forgot_password') }}
        </a>
        @endif --}}

        <button type="submit" id="submit-btn" onclick="disableButton()" class="btn btn-primary">
            {{ __('frontend.login') }}
        </button>
      </div>

    </form>
    <div class="d-none">
      <h6 class="text-center border-top py-3 mt-3">Demo Accounts</h6>
      <div class="parent">

      <select name="select"  id="SelectUser"  id="select" class="form-control selectpiker" onchange="getSelectedOption()">
          <option value="">Select Role</option>
          <option value="12345678,admin@streamit.com">Admin</option>
          <option value="12345678,demo@streamit.com"  selected>Demo Admin</option>
          <option value="secret,john@streamit.com">User</option>
      </select>

</div>

    </div>
   
    <x-slot name="extra">
      @if (Route::has('register'))
      <p class="text-center text-gray-600 mt-4">
        Do not have an account? <a href="{{ route('register') }}" class="underline hover:text-gray-900">Register</a>.
      </p>
      @endif
    </x-slot>
  </x-auth-card>

  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <style>

    .select2-container--default .select2-selection--single .select2-selection__rendered{
      line-height: inherit;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow,
    .select2-container--default .select2-selection--single .select2-selection__clear,
    .select2-container--classic .select2-selection--single .select2-selection__arrow {
      height: 100%;
    }

    </style>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

  <script type="text/javascript">
     window.onload = function() {
        getSelectedOption();
    };

    $(document).ready(function() {
            $('#SelectUser').select2({
                placeholder: "Select Role",
                minimumResultsForSearch: Infinity

            });
        });

 function disableButton(){
document.getElementById('submit-btn').classList.add('disabled');
document.getElementById('submit-btn').innerText = 'Login...';

}



    function getSelectedOption() {
        var selectElement = document.getElementById("SelectUser");
        var selectedOption = selectElement.options[selectElement.selectedIndex];

        if (selectedOption  && selectedOption.value !== "") {
            var optionText = selectedOption.textContent || selectedOption.innerText; // Get the text of the selected option
            var optionValue = selectedOption.value; // Get the value of the selected option

            var values = optionValue.split(",");
            var password = values[0];
            var email = values[1];

            domId('email').value =email;
            domId('password').value = password;

        } else {
          domId('email').value = "";
          domId('password').value = "";
        }
    }
    function domId (name) {
      return document.getElementById(name)
    }
    function setLoginCredentials(type) {
      domId('email').value = domId(type+'_email').textContent
      domId('password').value = domId(type+'_password').textContent
    }
  </script>
</x-auth-layout>
