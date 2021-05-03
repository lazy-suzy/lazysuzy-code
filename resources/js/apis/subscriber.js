export default function init() {
  const EMAIL_REGEX = /(?:[a-z0-9!#$%&'*+/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+/=?^_`{|}~-]+)*|"(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21\x23-\x5b\x5d-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])*")@(?:(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?|\[(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?|[a-z0-9-]*[a-z0-9]:(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21-\x5a\x53-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])+)\])/g
  $(document).ready(function () {
    const SUBSCRIBER_API = `/api/subscribe?url=${window.location.href}`
    $('.js-submitEmail').click(e => {
      e.preventDefault()
      e.stopImmediatePropagation();
      const email = $('.js-subscriber').val()
      if (email.trim() && EMAIL_REGEX.test(email.trim())) {
        $.ajax({
          type: 'GET',
          url: `${SUBSCRIBER_API}&email=${email}`,
          dataType: 'json',
          success: function (data) {
            $('.js-form').hide()
            $('.js-success').removeClass('d-none')
          },
          error: function (jqXHR, exception) {
            console.log(jqXHR);
            console.log(exception);
          }
        });
      } else {
        $('.js-subscriber').addClass('error')
        $('.js-error').removeClass('d-none')
      }
    })
  });
  $('.js-subscriber').on('input', () => {
    $('.js-subscriber').removeClass('error')
    $('.js-error').addClass('d-none')
  })
}
