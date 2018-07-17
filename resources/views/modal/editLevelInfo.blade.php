<form id="updUserForm" class="ui mini form" action="{{ url('/chat/action/updLevelInfo') }}">
    <div class="field">
        <label>充值量</label>
        <div class="ui input icon">
            <input type="text" name="recharge_min"  placeholder="" value="{{ $recharge_min }}"/>
        </div>
    </div>
    <div class="field">
        <label>打码量</label>
        <div class="ui input icon">
            <input type="text" name="bet_min"  placeholder="" value="{{ $bet_min }}"/>
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
                role: {
                    validators: {
                        notEmpty: {
                            message: '参数错误'
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