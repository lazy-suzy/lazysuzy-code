<div class="modal fade auth-modal" id="modalLoginForm" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header border-bottom-0">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="form-title text-center modal-header-text">
          <h1>Hey,Star Stuff !</h1>
        </div>

          <!-- <div class="d-flex justify-content-center social-buttons">
            <a href="{{ url('/redirect/google') }}" class="btn btn-danger btn-block"><i class="fa fa-google"></i> Continue with <b>Google</b></a>
          </div> -->
          <div class="d-flex justify-content-center social-buttons">
            <a href="{{ url('/redirect/google') }}" class="btn btn-primary btn-facebook btn-block"><i class="fa fa-facebook"></i> Continue with <b>Facebook</b></a>
          </div>
          <div class="d-flex justify-content-center social-buttons">
            <a href="{{ url('/redirect/google') }}" class="btn btn-default btn-block"><i class="fa fa-google"></i> Continue with <b>Google</b></a>
          </div>
          <div class="or-seperator"></div>
          <div class="text-center form title">Not Feeling Social ?</div>

        </div>
        <div class="d-flex flex-column text-center">
          <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="form-group row">
              <!-- <label for="email" class="col-md-4 col-form-label text-md-right">{{ __('E-Mail Address') }}</label> -->

              <div class="col-md-12">
                <input id="email" type="email" class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }}" name="email" value="{{ old('email') }}" placeholder="Email" required>

                @if ($errors->has('email'))
                <span class="invalid-feedback" role="alert">
                  <strong>{{ $errors->first('email') }}</strong>
                </span>
                @endif
              </div>
            </div>

            <div class="form-group row">
              <!-- <label for="password" class="col-md-4 col-form-label text-md-right">{{ __('Password') }}</label> -->

              <div class="col-md-12">
                <input id="password" type="password" class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}" name="password" placeholder="Password" required>

                @if ($errors->has('password'))
                <span class="invalid-feedback" role="alert">
                  <strong>{{ $errors->first('password') }}</strong>
                </span>
                @endif
              </div>
            </div>
            <button type="submit" class="btn -btn">Login</button>

            @if (Route::has('password.request'))
            <a class="btn btn-link" href="{{ route('password.request') }}">
              {{ __('Reset Password') }}
            </a>
            @endif
          </form>


      </div>

      <div class="modal-footer d-flex justify-content-center text-center">
        <div class="signup-section">Not a member yet? <a href="#" class="text-info user-login-modal1"> JOIN NOW</a>.</div>
      </div>

    </div>
  </div>
