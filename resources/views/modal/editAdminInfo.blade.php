<form id="updUserForm" class="ui mini form" action="{{ url('/chat/action/updAdminInfo') }}">
    <div class="field">
        <label>帐号</label>
        <div class="ui input icon">
            <input type="text" name="account"  placeholder="" value="{{ $account }}" @if($account!="") disabled="disabled" @endif />
        </div>
    </div>
    <div class="field">
        <label>名称</label>
        <div class="ui input icon">
            <input type="text" name="nickname"  placeholder="" value="{{ $nickname }}" />
        </div>
    </div>
    <div class="field">
        <label>密码</label>
        <div class="ui input icon">
            <input type="text" name="password"  placeholder="" value="" />
        </div>
    </div>

    <input type="hidden" value="{{ $id }}" name="id">
</form>

<script>
    $('#updUserForm').formValidation({
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
                            message: '帐号不能为空'
                        }
                    }
                },
                nickname: {
                    validators: {
                        notEmpty: {
                            message: '名称不能为空'
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
                    $('#dtTable').DataTable().ajax.reload(null,false);
                } else {
                    Calert(result.msg,'red');
                }
            }
        });
    });
</script>