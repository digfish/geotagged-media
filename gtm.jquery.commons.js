jQuery(document).ready(function ($) {

    //$('form#post').prop('disabled',true);

    function isFormDisabled(evt) {
        console.log('submission enabled?', !$('form#post').prop('disabled'));
    }

    $(document).on('click', '.btn_geotag_edit', function (evt) {

        evt.preventDefault();
        var name = $(this).attr('id').split('_')[2];

        $associated_hidden = $(this).parent().siblings('input[type=hidden]').eq(0);
        $associated_label = $(this).parent().siblings('strong').hide();
        //       $associated_hidden.data('initial',$associated_label.html());
        console.log("associated hidden", $associated_hidden);
        console.log("associated label", $associated_label);
        $associated_hidden.attr('type', 'text');
        // html("<input class='gtm_editable' type='text' name='"+ name +"' value='" + $associated_label.text() + "'>");
        // disable form submission
        console.log("=> Disable form submission!");
        $(this).parents('form').prop('disabled', true);
        isFormDisabled(evt);
    });


    $(document).on('keyup keypress', 'input.gtm_editable', function (evt) {
        console.log("Pressed " + "'" + evt.key + "'");
//        $(this).parents('form').prop('disabled',true);

        var typedVal = $(evt.target).val();
        if (typedVal.length < 10) {
            return;
        }
        var curr_val = $(this).val();
        if (evt.key == 'Enter') {
            evt.preventDefault();
            $('form#post').prop('disabled', true);
            isFormDisabled(evt);
            console.log("Pressed Enter!");
            //setTimeout( editReenableInput(evt,this), 100);
            return false;
        }
    });

    $(document).on('focusout blur', 'input.gtm_editable', function (evt) {
        console.log('focusout', evt.target);
        $('form#post').prop('disabled', false);
        editReenableInput(evt, this);
        //return false;
    });


    /*
        $('[type=submit]').click(function(evt) {
            $(this).parents('form').prop('disabled',false);
        });
    */

    function editReenableInput(evt, input) {
        // reenable form submission
        console.log('>editReenableInput!');
        var curr_val = $(input).val();
        //$(input).parents('form').prop('disabled',false);
        $associated_label = $(input).siblings('.gtm-strong').eq(0);
        isFormDisabled(evt);
        console.log('evt=', evt);
        console.log('input=', input);
        console.log("associated label = ", $associated_label);
        $(input).attr('type', 'hidden');
        $associated_label.show();
        //  $assoc_label = $(input).after($associated_label.data('initial'));
        $associated_label.text(curr_val);
        console.log('<editReenableInput!');

    }

});

