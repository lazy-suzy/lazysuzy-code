/*
Reference: http://jsfiddle.net/BB3JK/47/
*/

export default function makeSelectBox() {
    $('select').each(function() {
        var $this = $(this),
            numberOfOptions = $(this).children('option').length

        //Remove previously made selectbox
        $('#selectbox-' + $this.attr('id')).remove()

        $this.addClass('select-hidden')
        $this.wrap('<div class="select"></div>')
        $this.after(
            '<div class="select-styled" id="selectbox-' +
                $this.attr('id') +
                '"></div>'
        )

        var $styledSelect = $this.next('div.select-styled')
        var strSelectedText = $(this).children('option:selected')
            ? $(this)
                  .children('option:selected')
                  .text()
            : $this
                  .children('option:selected')
                  .eq(0)
                  .text()
        var strSelectedValue = $(this).children('option:selected')
            ? $(this)
                  .children('option:selected')
                  .attr('value')
            : $this
                  .children('option:selected')
                  .eq(0)
                  .attr('value')
        $styledSelect.text(strSelectedText)
        $styledSelect.attr('active', strSelectedValue)

        var $list = $('<ul />', {
            class: 'select-options'
        }).insertAfter($styledSelect)

        for (var i = 0; i < numberOfOptions; i++) {
            $('<li />', {
                text: $this
                    .children('option')
                    .eq(i)
                    .text(),
                rel: $this
                    .children('option')
                    .eq(i)
                    .val()
            }).appendTo($list)
        }

        var $listItems = $list.children('li')

        $styledSelect.click(function(e) {
            e.stopPropagation()
            $('div.select-styled.active')
                .not(this)
                .each(function() {
                    $(this)
                        .removeClass('active')
                        .next('ul.select-options')
                        .hide()
                })
            $(this)
                .toggleClass('active')
                .next('ul.select-options')
                .toggle()
        })

        $listItems.click(function(e) {
            e.stopPropagation()
            $styledSelect.text($(this).text()).removeClass('active')
            var strSelectedValue = $(this).attr('rel')
            $styledSelect.attr('active', strSelectedValue)
            $(document).trigger('select-value-changed', $styledSelect)

            $this.val($(this).attr('rel'))
            $list.hide()
            //console.log($this.val());
        })

        $(document).click(function() {
            $styledSelect.removeClass('active')
            $list.hide()
        })
    })
}
