<form id="form" class="ui mini form" action="{{ url('/chat/action/addFriends') }}">
    <div class="field">
        <div class="ui input icon">
            <input type="text" name="account" placeholder="请输入您的账号..." value="" data-fv-field="account"><i class="fv-control-feedback" data-fv-icon-for="account" style="display: none;"></i>
        </div>
    </div>
    <div class="field">
        <div class="ui input icon">
            <input type="text" name="to_account" placeholder="请输入您要添加的账号..." value="" data-fv-field="account"><i class="fv-control-feedback" data-fv-icon-for="account" style="display: none;"></i>
        </div>
    </div>
</form>

<script>
    $('#form').formValidation({
            framework: 'semantic',
            icon: {
                valid: 'checkmark icon',
                invalid: 'remove icon',
                validating: 'refresh icon'
            },
            fields: {
                account: {
                    validators: {
                        notEmpty: {
                            message: '请输入您的账号...'
                        }
                    }
                },
                to_account: {
                    validators: {
                        notEmpty: {
                            message: '请输入您要添加的账号...'
                        }
                    }
                }
            }
        }).on('success.form.fv', function(e) {
        e.preventDefault();
        var $form = $(e.target),
            fv    = $form.data('formValidation');
        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: $form.serialize(),
            success: function(result) {
                if(result.status == true){
                    jc.close();
                } else {
                    Calert(result.msg,'red');
                }
            }
        });
    });
</script>