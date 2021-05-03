<div class="subscriber-container d-none">
   <div class="inner-content js-form">
      <div class="subscriber-text">
         <div>Be the first to see new styles & deals on 
            <span class="category-text-inline js-category-text"> {{Request::segment(2)}} {{Request::segment(3)}}</span>
         </div>
      </div>
      <div class="subscriber-form">
         <div class="input-group">
            <input type="text"class="form-control js-subscriber "placeholder="Email Address"aria-label="Email Address"aria-describedby="button-addon2">
            <div class="input-group-append"><button class="btn js-submitEmail"type="button"id="button-addon2">Submit</button></div>
         </div>
         <div class="error-message js-error d-none">Please enter a valid email</div>
      </div>
   </div>
   <div class="inner-content js-success d-none">
      <div class="success-message">Thanks for signing up. You'll be the first to receive updates about new collections, top brands and exclusive products.</div>
   </div>
</div>